# Contas de Cursistas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar contas de cursistas (cadastro com verificação de e-mail, login, recuperação de senha para cursista e admin, login com Google e área "Minha conta") ao site Conta Lelê.

**Architecture:** PHP 8.3 puro, sem framework, sem Composer. Sessão do cursista isolada do admin sob o nome de cookie `cl_site`; o admin continua em `cl_admin`. Utilitários comuns (tokens de uso único, e-mail com template da marca, cliente OIDC do Google) ficam em `includes/`. As páginas públicas passam pelo front-controller (`index.php`) e usam o layout da marca; as páginas do admin são scripts diretos em `/admin/`.

**Tech Stack:** PHP 8.3, PDO/MySQL (utf8mb4), PHPMailer (vendorizado em `lib/`), cURL para OAuth2/OIDC do Google, CSS de marca existente (classes `cl-*` e `adm-*`).

---

## Decisões de arquitetura (ler antes de começar)

1. **Sessão pública unificada sob `cl_site`.** Vanilla PHP só mantém **uma** sessão nomeada ativa por requisição. Hoje `csrf_token()` (em `includes/funcoes.php`) faz `session_start()` com o nome padrão `PHPSESSID` quando nenhuma sessão está ativa. Se uma página pública (ex.: `/contato`) iniciar a sessão padrão e, em seguida, o cabeçalho tentar abrir a sessão `cl_site` para detectar login, ocorre conflito. **Solução:** toda página pública que precise de sessão chama `iniciar_sessao_site()` **antes** de `csrf_token()`. Assim `csrf_token()` reaproveita a sessão `cl_site` já ativa. A única página pública existente que usa CSRF é `paginas/contato.php` — ela recebe esse ajuste neste plano.

2. **Detecção de login sem quebrar cache.** `cursista_logado()` só inicia a sessão se o cookie `cl_site` já existir. Visitante anônimo nunca recebe cookie via cabeçalho → HTML público continua cacheável no Cloudflare.

3. **Dados em sessão, sem hit de banco no cabeçalho.** Como o admin já faz, guardamos os dados mínimos do cursista na sessão no login; o cabeçalho lê da sessão.

4. **Tokens guardam apenas o hash.** O token cru vai no link do e-mail; no banco fica só `sha256(token)`.

5. **Admin via Google: "só vincula, nunca cria".** Login Google no admin só funciona para e-mail que já é admin ativo.

6. **`INTERVAL` com valor interpolado, não bindado.** MySQL/PDO não aceita placeholder dentro de `INTERVAL ? SECOND` de forma confiável. Onde precisar, interpolamos um inteiro já convertido com `(int)` (o valor nunca vem do usuário).

---

## Mapa de arquivos

**Novos (includes):**
- `includes/tokens.php` — geração/validação de tokens de uso único (agnóstico de escopo).
- `includes/cursistas.php` — acesso a dados de `usuarios_cursistas` + regras puras (senha, bloqueio).
- `includes/autenticacao_cursista.php` — sessão `cl_site` e helpers de login do cursista.
- `includes/google_oauth.php` — cliente OIDC do Google com cURL (agnóstico de escopo).

**Novos (páginas públicas):**
- `paginas/criar-conta.php`, `paginas/verificar-email.php`, `paginas/entrar.php`, `paginas/sair.php`,
  `paginas/recuperar-senha.php`, `paginas/redefinir-senha.php`, `paginas/minha-conta.php`,
  `paginas/conta-senha.php`, `paginas/google-iniciar.php`, `paginas/google-callback.php`.

**Novos (admin):**
- `admin/recuperar-senha.php`, `admin/redefinir-senha.php`, `admin/google.php`, `admin/google-callback.php`.

**Novos (raiz / testes):**
- `migrar.php` — migração idempotente do banco já instalado.
- `includes/limites.php` — limite de ações por IP (anti-abuso, Task 16).
- `tests/tokens-teste.php`, `tests/cursistas-teste.php`, `tests/limites-teste.php`.

**Modificados:**
- `sql/schema.sql`, `index.php`, `includes/cabecalho.php`, `includes/email.php`, `paginas/contato.php`,
  `admin/login.php`, `admin/configuracoes.php`, `admin/diagnostico.php`, `instalar.php`, `.htaccess`.

---

## Task 1: Schema do banco e migração

**Files:**
- Modify: `sql/schema.sql` (acrescentar ao final)
- Create: `migrar.php`
- Modify: `.htaccess:17-19`
- Modify: `instalar.php:53-65` (seed de configurações)
- Modify: `admin/diagnostico.php:45-46`

- [ ] **Step 1: Acrescentar as tabelas e colunas novas ao schema**

Acrescente ao final de `sql/schema.sql`:

```sql

-- Cursistas (usuários comuns / alunos) — Fase 2
CREATE TABLE IF NOT EXISTS usuarios_cursistas (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome              VARCHAR(120) NOT NULL,
    email             VARCHAR(160) NOT NULL,
    senha_hash        VARCHAR(255) NULL,
    google_id         VARCHAR(40)  NULL,
    email_verificado  TINYINT(1)   NOT NULL DEFAULT 0,
    verificado_em     DATETIME     NULL,
    tentativas_login  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_ate     DATETIME     NULL,
    ultimo_acesso     DATETIME     NULL,
    ativo             TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_cursistas_email (email),
    UNIQUE KEY uq_usuarios_cursistas_google (google_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tokens de uso único: verificação de e-mail e recuperação de senha (cursista e admin)
CREATE TABLE IF NOT EXISTS tokens_autenticacao (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    escopo      ENUM('cursista','admin')           NOT NULL,
    usuario_id  INT UNSIGNED                        NOT NULL,
    finalidade  ENUM('verificacao','recuperacao')  NOT NULL,
    token_hash  CHAR(64)     NOT NULL,
    expira_em   DATETIME     NOT NULL,
    usado_em    DATETIME     NULL,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token_hash (token_hash),
    KEY ix_token_lookup (escopo, usuario_id, finalidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 1b: Adicionar `google_id` ao `CREATE TABLE usuarios_admin` no schema**

Para que **instalações novas** (que rodam só o `schema.sql` via `instalar.php`) já tenham a coluna, edite a
definição existente de `usuarios_admin` em `sql/schema.sql`. Troque a linha:

```sql
    senha_hash           VARCHAR(255) NOT NULL,
```

por:

```sql
    senha_hash           VARCHAR(255) NOT NULL,
    google_id            VARCHAR(40)  NULL,
```

E troque a linha da chave única do e-mail:

```sql
    UNIQUE KEY uq_usuarios_admin_email (email)
```

por:

```sql
    UNIQUE KEY uq_usuarios_admin_email (email),
    UNIQUE KEY uq_usuarios_admin_google (google_id)
```

> Assim, instalação nova recebe `google_id` nativamente pelo `schema.sql`; bancos **já instalados**
> recebem a mesma coluna pelo `ALTER TABLE` idempotente do `migrar.php` (a tabela já existe, então o
> `CREATE TABLE IF NOT EXISTS` não a recria — por isso o `ALTER` no `migrar.php` continua necessário).

- [ ] **Step 2: Criar `migrar.php` (migração idempotente)**

Crie `migrar.php`:

```php
<?php
/**
 * Migração idempotente do banco do site Conta Lelê — Fase 2 (contas de cursistas).
 * Cria tabelas/colunas novas e semeia configurações do Google sem afetar dados existentes.
 * Protegido pelo token do instalador. REMOVA este arquivo após o uso.
 *
 * Uso: https://contalele.com.br/migrar.php?token=SEU_TOKEN
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', __DIR__);
$config = require __DIR__ . '/config.php';
require __DIR__ . '/includes/erros.php';
require __DIR__ . '/includes/funcoes.php';
require __DIR__ . '/includes/conexao.php';

ativar_tratamento_erros($config['site']['ambiente']);
header('Content-Type: text/html; charset=utf-8');

$token = $_GET['token'] ?? '';
if (!hash_equals($config['instalador']['token'], (string) $token)) {
    http_response_code(403);
    exit('Acesso negado.');
}

echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8">';
echo '<title>Migração — Conta Lelê</title>';
echo '<body style="font-family:sans-serif;max-width:640px;margin:40px auto;padding:0 16px">';
echo '<h1>Migração do banco — contas de cursistas</h1>';

$pdo = bd($config['db']);

// 1. Tabelas novas (idempotente via IF NOT EXISTS).
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS usuarios_cursistas (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        nome VARCHAR(120) NOT NULL,
        email VARCHAR(160) NOT NULL,
        senha_hash VARCHAR(255) NULL,
        google_id VARCHAR(40) NULL,
        email_verificado TINYINT(1) NOT NULL DEFAULT 0,
        verificado_em DATETIME NULL,
        tentativas_login TINYINT UNSIGNED NOT NULL DEFAULT 0,
        bloqueado_ate DATETIME NULL,
        ultimo_acesso DATETIME NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_usuarios_cursistas_email (email),
        UNIQUE KEY uq_usuarios_cursistas_google (google_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS tokens_autenticacao (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        escopo ENUM('cursista','admin') NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        finalidade ENUM('verificacao','recuperacao') NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expira_em DATETIME NOT NULL,
        usado_em DATETIME NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_token_hash (token_hash),
        KEY ix_token_lookup (escopo, usuario_id, finalidade)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo '<p>Tabelas usuarios_cursistas e tokens_autenticacao garantidas.</p>';

// 2. Coluna google_id em usuarios_admin (idempotente: confere antes).
$temColuna = $pdo->query("SHOW COLUMNS FROM usuarios_admin LIKE 'google_id'")->fetch();
if (!$temColuna) {
    $pdo->exec(
        "ALTER TABLE usuarios_admin
         ADD COLUMN google_id VARCHAR(40) NULL,
         ADD UNIQUE KEY uq_usuarios_admin_google (google_id)"
    );
    echo '<p>Coluna google_id adicionada em usuarios_admin.</p>';
} else {
    echo '<p>Coluna google_id já existe em usuarios_admin.</p>';
}

// 3. Configurações do Google (idempotente via INSERT IGNORE).
$ins = $pdo->prepare(
    'INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES (?, ?, ?)'
);
foreach ([
    ['google_client_id',     '',  'Google OAuth — Client ID'],
    ['google_client_secret', '',  'Google OAuth — Client Secret'],
    ['google_oauth_ativo',   '0', 'Google OAuth — ativo (1/0)'],
] as $linha) {
    $ins->execute($linha);
}
echo '<p>Configurações do Google semeadas.</p>';

echo '<hr><p><strong>Importante:</strong> remova o arquivo <code>migrar.php</code> do servidor agora.</p>';
```

- [ ] **Step 3: Bloquear `migrar.php` no `.htaccess`**

Em `.htaccess`, troque a linha do `FilesMatch` (linha ~17):

```apache
<FilesMatch "^(config\.php|config\.exemplo\.php|.*\.local\.php|instalar\.php|migrar\.php|seed-conteudo\.php)$">
    Require all denied
</FilesMatch>
```

- [ ] **Step 4: Semear as chaves do Google em `instalar.php`**

Em `instalar.php`, dentro do array `$padroes` (após a linha `['smtp_seguranca', …]`), acrescente:

```php
    ['google_client_id',     '',  'Google OAuth — Client ID'],
    ['google_client_secret', '',  'Google OAuth — Client Secret'],
    ['google_oauth_ativo',   '0', 'Google OAuth — ativo (1/0)'],
```

- [ ] **Step 5: Atualizar a lista de tabelas esperadas no diagnóstico**

Em `admin/diagnostico.php`, na linha que define `$esperadas`, inclua as duas tabelas novas:

```php
    $esperadas = ['usuarios_admin', 'usuarios_cursistas', 'tokens_autenticacao', 'cordeis',
        'ebooks', 'noticias', 'depoimentos', 'festivais', 'premios', 'mensagens_contato',
        'configuracoes'];
```

- [ ] **Step 6: Aplicar a migração no banco de desenvolvimento e verificar**

Rode o instalador num banco novo OU `migrar.php` num banco existente. Para verificar via CLI (ajuste credenciais conforme `config.php`):

Run: `php -r "require 'config.php'; $c=(require 'config.php')['db']; $p=new PDO('mysql:host='.$c['host'].';dbname='.$c['nome'].';charset=utf8mb4',$c['usuario'],$c['senha']); var_dump($p->query('SHOW TABLES LIKE \"usuarios_cursistas\"')->fetch() !== false, $p->query('SHOW COLUMNS FROM usuarios_admin LIKE \"google_id\"')->fetch() !== false);"`
Expected: `bool(true)` duas vezes.

- [ ] **Step 7: Commit**

```bash
git add sql/schema.sql migrar.php .htaccess instalar.php admin/diagnostico.php
git commit -m "Adiciona schema e migração das contas de cursistas"
```

---

## Task 2: `includes/tokens.php` (tokens de uso único)

**Files:**
- Create: `includes/tokens.php`
- Test: `tests/tokens-teste.php`

- [ ] **Step 1: Escrever o teste das funções puras**

Crie `tests/tokens-teste.php`:

```php
<?php
declare(strict_types=1);

require __DIR__ . '/../includes/tokens.php';

// gerar_token_cru()
$t1 = gerar_token_cru();
$t2 = gerar_token_cru();
afirmar(strlen($t1) === 64, 'gerar_token_cru() devolve 64 caracteres hex');
afirmar(ctype_xdigit($t1), 'gerar_token_cru() devolve apenas dígitos hexadecimais');
afirmar($t1 !== $t2, 'gerar_token_cru() gera valores diferentes a cada chamada');

// hash_token()
afirmar(strlen(hash_token($t1)) === 64, 'hash_token() devolve sha256 de 64 caracteres');
afirmar(hash_token($t1) === hash_token($t1), 'hash_token() é determinístico');
afirmar(hash_token($t1) !== $t1, 'hash_token() não devolve o token cru');
afirmar(hash_token($t1) !== hash_token($t2), 'hash_token() difere para tokens diferentes');

// TTLs definidos
afirmar(TOKEN_TTL_VERIFICACAO === 86400, 'TTL de verificação é 24h');
afirmar(TOKEN_TTL_RECUPERACAO === 3600, 'TTL de recuperação é 1h');
```

- [ ] **Step 2: Rodar o teste e ver falhar**

Run: `php tests/correr.php`
Expected: FALHA em `[tokens-teste.php]` com erro de "require" / função/constante indefinida (arquivo ainda não existe).

- [ ] **Step 3: Implementar `includes/tokens.php`**

Crie `includes/tokens.php`:

```php
<?php
/**
 * Tokens de uso único do site Conta Lelê (verificação de e-mail e recuperação de senha).
 * Agnóstico de escopo: serve cursista e admin. Grava apenas o hash do token.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

const TOKEN_TTL_VERIFICACAO = 86400; // 24 horas
const TOKEN_TTL_RECUPERACAO = 3600;  // 1 hora

/**
 * Gera um token cru de 256 bits (64 caracteres hexadecimais).
 */
function gerar_token_cru(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Devolve o hash sha256 (hex) do token cru. É o que se guarda no banco.
 */
function hash_token(string $tokenCru): string
{
    return hash('sha256', $tokenCru);
}

/**
 * Cria um token, grava o hash em tokens_autenticacao e devolve o token cru.
 */
function criar_token(string $escopo, int $usuarioId, string $finalidade, int $ttlSegundos): string
{
    // Housekeeping oportunístico: ~2% das criações limpam tokens expirados/usados antigos.
    if (random_int(1, 50) === 1) {
        limpar_tokens_expirados();
    }
    $tokenCru = gerar_token_cru();
    $expira = date('Y-m-d H:i:s', time() + $ttlSegundos);
    bd()->prepare(
        'INSERT INTO tokens_autenticacao (escopo, usuario_id, finalidade, token_hash, expira_em)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([$escopo, $usuarioId, $finalidade, hash_token($tokenCru), $expira]);
    return $tokenCru;
}

/**
 * Valida e consome um token: confere finalidade, não-uso e validade; marca como usado.
 * Devolve a linha (com escopo e usuario_id) ou null se inválido.
 */
function consumir_token(string $tokenCru, string $finalidade): ?array
{
    if ($tokenCru === '') {
        return null;
    }
    $st = bd()->prepare(
        'SELECT * FROM tokens_autenticacao
         WHERE token_hash = ? AND finalidade = ? AND usado_em IS NULL AND expira_em > NOW()'
    );
    $st->execute([hash_token($tokenCru), $finalidade]);
    $linha = $st->fetch();
    if (!$linha) {
        return null;
    }
    bd()->prepare('UPDATE tokens_autenticacao SET usado_em = NOW() WHERE id = ?')
        ->execute([$linha['id']]);
    return $linha;
}

/**
 * Marca como usados todos os tokens pendentes de uma finalidade para um usuário.
 */
function invalidar_tokens(string $escopo, int $usuarioId, string $finalidade): void
{
    bd()->prepare(
        'UPDATE tokens_autenticacao SET usado_em = NOW()
         WHERE escopo = ? AND usuario_id = ? AND finalidade = ? AND usado_em IS NULL'
    )->execute([$escopo, $usuarioId, $finalidade]);
}

/**
 * Diz se houve um token criado há menos de $segundos (throttle de envio).
 */
function token_recente(string $escopo, int $usuarioId, string $finalidade, int $segundos): bool
{
    $segundos = (int) $segundos;
    $st = bd()->prepare(
        "SELECT 1 FROM tokens_autenticacao
         WHERE escopo = ? AND usuario_id = ? AND finalidade = ?
           AND criado_em > DATE_SUB(NOW(), INTERVAL {$segundos} SECOND) LIMIT 1"
    );
    $st->execute([$escopo, $usuarioId, $finalidade]);
    return (bool) $st->fetchColumn();
}

/**
 * Housekeeping: remove tokens expirados e os já usados há mais de 7 dias.
 */
function limpar_tokens_expirados(): void
{
    bd()->query(
        'DELETE FROM tokens_autenticacao
         WHERE expira_em < NOW()
            OR (usado_em IS NOT NULL AND usado_em < DATE_SUB(NOW(), INTERVAL 7 DAY))'
    );
}
```

- [ ] **Step 4: Rodar o teste e ver passar**

Run: `php tests/correr.php`
Expected: `[tokens-teste.php]` todas `ok`, total sem falhas.

- [ ] **Step 5: Commit**

```bash
git add includes/tokens.php tests/tokens-teste.php
git commit -m "Adiciona camada de tokens de uso único"
```

---

## Task 3: Extensão do `includes/email.php` (template da marca + HTML)

**Files:**
- Modify: `includes/email.php`

- [ ] **Step 1: Tornar `enviar_email()` capaz de enviar HTML**

Em `includes/email.php`, substitua a assinatura e o trecho do corpo da função `enviar_email`.

Troque a assinatura:

```php
function enviar_email(string $para, string $assunto, string $corpo, ?string $responder = null): bool
```

por:

```php
function enviar_email(string $para, string $assunto, string $corpo, ?string $responder = null, ?string $html = null): bool
```

E troque o bloco:

```php
        $mail->Subject = $assunto;
        $mail->Body    = $corpo;

        $mail->send();
```

por:

```php
        $mail->Subject = $assunto;
        if ($html !== null) {
            $mail->isHTML(true);
            $mail->Body    = $html;
            $mail->AltBody = $corpo;
        } else {
            $mail->Body = $corpo;
        }

        $mail->send();
```

- [ ] **Step 2: Adicionar o template de e-mail da marca**

No final de `includes/email.php`, acrescente:

```php

/**
 * Monta um e-mail HTML simples na identidade da marca Conta Lelê.
 * Usa estilos inline (compatibilidade com clientes de e-mail) e a paleta da marca.
 *
 * @param array<int,string> $paragrafos Parágrafos do corpo (texto puro; serão escapados).
 */
function template_email(string $titulo, array $paragrafos, ?string $textoBotao = null, ?string $urlBotao = null): string
{
    $corpo = '';
    foreach ($paragrafos as $p) {
        $corpo .= '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#2b1300">'
            . e($p) . '</p>';
    }

    $botao = '';
    if ($textoBotao !== null && $urlBotao !== null) {
        $botao = '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 4px">'
            . '<tr><td style="border-radius:999px;background:#2b1300">'
            . '<a href="' . e($urlBotao) . '" '
            . 'style="display:inline-block;padding:14px 28px;border-radius:999px;'
            . 'background:#2b1300;color:#ffdc3a;font-weight:800;font-size:15px;'
            . 'text-decoration:none;font-family:Arial,Helvetica,sans-serif">'
            . e($textoBotao) . '</a></td></tr></table>';
    }

    return '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#fdf5d4;'
        . 'font-family:Arial,Helvetica,sans-serif">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
        . 'style="background:#fdf5d4;padding:24px 0"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
        . 'style="max-width:520px;background:#ffffff;border-radius:18px;overflow:hidden;'
        . 'box-shadow:0 8px 18px -14px rgba(43,19,0,0.3)">'
        . '<tr><td style="background:#ffdc3a;padding:18px 28px;font-weight:900;'
        . 'font-size:18px;color:#2b1300">Conta Lelê</td></tr>'
        . '<tr><td style="padding:28px">'
        . '<h1 style="margin:0 0 18px;font-size:22px;color:#2b1300">' . e($titulo) . '</h1>'
        . $corpo . $botao
        . '<p style="margin:24px 0 0;font-size:12px;color:rgba(43,19,0,0.58)">'
        . 'Se você não solicitou este e-mail, pode ignorá-lo com segurança.</p>'
        . '</td></tr></table></td></tr></table></body></html>';
}
```

- [ ] **Step 3: Verificar que o site continua carregando (smoke do parser)**

Run: `php -l includes/email.php`
Expected: `No syntax errors detected in includes/email.php`

- [ ] **Step 4: Commit**

```bash
git add includes/email.php
git commit -m "Adiciona envio de e-mail HTML com template da marca"
```

---

## Task 4: `includes/cursistas.php` (dados + regras puras)

**Files:**
- Create: `includes/cursistas.php`
- Test: `tests/cursistas-teste.php`

- [ ] **Step 1: Escrever os testes das funções puras**

Crie `tests/cursistas-teste.php`:

```php
<?php
declare(strict_types=1);

require __DIR__ . '/../includes/cursistas.php';

// senha_forte()
afirmar(senha_forte('12345678'), 'senha_forte() aceita 8 caracteres');
afirmar(!senha_forte('1234567'), 'senha_forte() rejeita 7 caracteres');
afirmar(!senha_forte(''), 'senha_forte() rejeita vazio');

// normalizar_email()
afirmar_igual('a@b.com', normalizar_email('  A@B.com '), 'normalizar_email() apara e baixa caixa');
afirmar_igual('joao@exemplo.com', normalizar_email('Joao@Exemplo.COM'), 'normalizar_email() baixa o domínio');

// decisao_bloqueio()
afirmar_igual(['tentativas' => 1, 'bloquear' => false], decisao_bloqueio(0), 'decisao_bloqueio() incrementa de 0 para 1');
afirmar_igual(['tentativas' => 4, 'bloquear' => false], decisao_bloqueio(3), 'decisao_bloqueio() incrementa sem bloquear no 4');
afirmar_igual(['tentativas' => 0, 'bloquear' => true], decisao_bloqueio(4), 'decisao_bloqueio() bloqueia na 5ª tentativa');
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php tests/correr.php`
Expected: FALHA em `[cursistas-teste.php]` (arquivo/funções inexistentes).

- [ ] **Step 3: Implementar `includes/cursistas.php`**

Crie `includes/cursistas.php`:

```php
<?php
/**
 * Acesso a dados dos cursistas (usuarios_cursistas) e regras de validação/bloqueio.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once __DIR__ . '/conexao.php';

const CURSISTA_MAX_TENTATIVAS = 5;
const CURSISTA_BLOQUEIO_MINUTOS = 15;

/** Senha mínima do projeto: 8 caracteres. */
function senha_forte(string $senha): bool
{
    return mb_strlen($senha) >= 8;
}

/** Normaliza um e-mail para comparação/armazenamento (apara e baixa a caixa). */
function normalizar_email(string $email): string
{
    return mb_strtolower(trim($email));
}

/**
 * Decide o próximo estado de tentativas a partir do total atual.
 * Pura (sem I/O) para ser testável.
 *
 * @return array{tentativas:int,bloquear:bool}
 */
function decisao_bloqueio(int $tentativasAtuais): array
{
    $t = $tentativasAtuais + 1;
    if ($t >= CURSISTA_MAX_TENTATIVAS) {
        return ['tentativas' => 0, 'bloquear' => true];
    }
    return ['tentativas' => $t, 'bloquear' => false];
}

function cursista_por_email(string $email): ?array
{
    $st = bd()->prepare('SELECT * FROM usuarios_cursistas WHERE email = ? LIMIT 1');
    $st->execute([normalizar_email($email)]);
    return $st->fetch() ?: null;
}

function cursista_por_id(int $id): ?array
{
    $st = bd()->prepare('SELECT * FROM usuarios_cursistas WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function cursista_por_google_id(string $googleId): ?array
{
    $st = bd()->prepare('SELECT * FROM usuarios_cursistas WHERE google_id = ? LIMIT 1');
    $st->execute([$googleId]);
    return $st->fetch() ?: null;
}

/**
 * Cria um cursista e devolve o id. Senha e google_id podem ser nulos.
 */
function criar_cursista(string $nome, string $email, ?string $senhaHash, ?string $googleId, bool $verificado): int
{
    bd()->prepare(
        'INSERT INTO usuarios_cursistas (nome, email, senha_hash, google_id, email_verificado, verificado_em)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $nome,
        normalizar_email($email),
        $senhaHash,
        $googleId,
        $verificado ? 1 : 0,
        $verificado ? date('Y-m-d H:i:s') : null,
    ]);
    return (int) bd()->lastInsertId();
}

function vincular_google_cursista(int $id, string $googleId): void
{
    bd()->prepare('UPDATE usuarios_cursistas SET google_id = ? WHERE id = ?')
        ->execute([$googleId, $id]);
}

function marcar_email_verificado(int $id): void
{
    bd()->prepare(
        'UPDATE usuarios_cursistas SET email_verificado = 1, verificado_em = NOW() WHERE id = ?'
    )->execute([$id]);
}

function atualizar_senha_cursista(int $id, string $senhaHash): void
{
    bd()->prepare('UPDATE usuarios_cursistas SET senha_hash = ? WHERE id = ?')
        ->execute([$senhaHash, $id]);
}

function atualizar_nome_cursista(int $id, string $nome): void
{
    bd()->prepare('UPDATE usuarios_cursistas SET nome = ? WHERE id = ?')
        ->execute([$nome, $id]);
}

function registrar_acesso_cursista(int $id): void
{
    bd()->prepare(
        'UPDATE usuarios_cursistas SET tentativas_login = 0, bloqueado_ate = NULL, ultimo_acesso = NOW() WHERE id = ?'
    )->execute([$id]);
}

function cursista_bloqueado(array $cursista): bool
{
    return $cursista['bloqueado_ate'] !== null
        && strtotime((string) $cursista['bloqueado_ate']) > time();
}

/**
 * Registra uma tentativa de login falha, aplicando o bloqueio quando atingir o limite.
 */
function registrar_tentativa_falha_cursista(array $cursista): void
{
    $d = decisao_bloqueio((int) $cursista['tentativas_login']);
    if ($d['bloquear']) {
        bd()->prepare(
            "UPDATE usuarios_cursistas SET tentativas_login = 0,
             bloqueado_ate = DATE_ADD(NOW(), INTERVAL " . CURSISTA_BLOQUEIO_MINUTOS . " MINUTE) WHERE id = ?"
        )->execute([(int) $cursista['id']]);
    } else {
        bd()->prepare('UPDATE usuarios_cursistas SET tentativas_login = ? WHERE id = ?')
            ->execute([$d['tentativas'], (int) $cursista['id']]);
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `php tests/correr.php`
Expected: `[cursistas-teste.php]` todas `ok`.

- [ ] **Step 5: Commit**

```bash
git add includes/cursistas.php tests/cursistas-teste.php
git commit -m "Adiciona camada de dados e regras dos cursistas"
```

---

## Task 5: `includes/autenticacao_cursista.php` (sessão `cl_site`)

**Files:**
- Create: `includes/autenticacao_cursista.php`

- [ ] **Step 1: Implementar a sessão e os helpers de login**

Crie `includes/autenticacao_cursista.php`:

```php
<?php
/**
 * Sessão e autenticação do cursista (área pública). Sessão isolada do admin
 * sob o cookie `cl_site`. É a sessão única do site público (também usada pelo CSRF).
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once __DIR__ . '/cursistas.php';

/**
 * Inicia a sessão pública (cl_site) com cookies endurecidos, uma única vez.
 */
function iniciar_sessao_site(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('cl_site');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => ($_SERVER['HTTPS'] ?? '') !== '',
    ]);
    session_start();
}

/**
 * Devolve os dados do cursista logado, ou null. NÃO inicia sessão para anônimo
 * (sem o cookie cl_site), preservando o cache de HTML para visitantes.
 */
function cursista_logado(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!isset($_COOKIE['cl_site'])) {
            return null;
        }
        iniciar_sessao_site();
    }
    return $_SESSION['cursista'] ?? null;
}

/**
 * Registra o cursista na sessão após login bem-sucedido.
 */
function logar_cursista(array $cursista): void
{
    iniciar_sessao_site();
    session_regenerate_id(true);
    $_SESSION['cursista'] = [
        'id'    => (int) $cursista['id'],
        'nome'  => (string) $cursista['nome'],
        'email' => (string) $cursista['email'],
    ];
}

/**
 * Encerra a sessão do cursista.
 */
function deslogar_cursista(): void
{
    iniciar_sessao_site();
    $_SESSION = [];
    session_destroy();
}

/**
 * Exige cursista logado. Redireciona para /entrar preservando o destino.
 */
function exigir_cursista(): void
{
    iniciar_sessao_site();
    if (($_SESSION['cursista'] ?? null) === null) {
        $destino = rawurlencode($_SERVER['REQUEST_URI'] ?? '/minha-conta');
        header('Location: /entrar?destino=' . $destino);
        exit;
    }
}

/**
 * Valida um destino de redirecionamento interno (evita open redirect).
 * Aceita só caminhos que começam com uma única barra.
 */
function destino_seguro(?string $destino, string $padrao = '/minha-conta'): string
{
    // Rejeita: vazio; não-absoluto; protocolo-relativo (//host); barra invertida
    // (o navegador normaliza \ para / e //host vira open redirect); e caracteres de controle.
    if (!is_string($destino) || $destino === '' || $destino[0] !== '/'
        || str_starts_with($destino, '//')
        || str_contains($destino, '\\')
        || preg_match('/[\x00-\x1f]/', $destino) === 1) {
        return $padrao;
    }
    return $destino;
}
```

- [ ] **Step 2: Smoke do parser**

Run: `php -l includes/autenticacao_cursista.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add includes/autenticacao_cursista.php
git commit -m "Adiciona sessão e autenticação do cursista"
```

---

## Task 6: Rotas, cabeçalho e unificação da sessão pública

**Files:**
- Modify: `index.php:29-38` (array `$rotas`)
- Modify: `includes/cabecalho.php`
- Modify: `includes/seo.php` (suporte a `robots`)
- Modify: `paginas/contato.php`
- Modify: `assets/css/style.css` (link "Entrar")

- [ ] **Step 1: Registrar as novas rotas públicas**

Em `index.php`, substitua o array `$rotas` por:

```php
$rotas = [
    ''          => 'inicio',
    'sobre'     => 'sobre',
    'contacao'  => 'contacao',
    'cordeis'   => 'cordeis',
    'livros'    => 'livros',
    'festivais' => 'festivais',
    'cursos'    => 'cursos',
    'contato'   => 'contato',
    // Contas de cursistas
    'criar-conta'            => 'criar-conta',
    'entrar'                 => 'entrar',
    'sair'                   => 'sair',
    'verificar-email'        => 'verificar-email',
    'recuperar-senha'        => 'recuperar-senha',
    'redefinir-senha'        => 'redefinir-senha',
    'minha-conta'            => 'minha-conta',
    'minha-conta/senha'      => 'conta-senha',
    'entrar/google'          => 'google-iniciar',
    'entrar/google/callback' => 'google-callback',
];
```

- [ ] **Step 2: Detecção de login no cabeçalho**

Em `includes/cabecalho.php`, após `require_once CL_RAIZ . '/includes/girassol.php';` acrescente:

```php
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
$cursistaCab = cursista_logado();
```

E substitua o bloco do CTA (atual `<div class="cl-cabecalho__cta"> … </div>`) por:

```php
        <div class="cl-cabecalho__cta">
            <?php if ($cursistaCab !== null): ?>
                <a class="cl-btn cl-btn-ghost" href="/minha-conta">Minha conta</a>
            <?php else: ?>
                <a class="cl-conta-link" href="/entrar">Entrar</a>
            <?php endif; ?>
            <a class="cl-btn cl-btn-primary" href="/cursos">Quero contar histórias</a>
            <button class="cl-menu-btn" type="button" aria-expanded="false" aria-label="Abrir menu" aria-controls="cl-nav-principal">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <line x1="4" y1="7" x2="20" y2="7"/>
                    <line x1="4" y1="12" x2="20" y2="12"/>
                    <line x1="4" y1="17" x2="20" y2="17"/>
                </svg>
            </button>
        </div>
```

- [ ] **Step 3: Estilo do link "Entrar"**

No final de `assets/css/style.css`, acrescente:

```css
.cl-conta-link {
  font-weight: 800;
  color: var(--cl-ink);
  text-decoration: none;
  padding: 8px 6px;
  white-space: nowrap;
}
.cl-conta-link:hover { color: var(--cl-accent); }
```

- [ ] **Step 3b: Suporte a `robots` no SEO (para as páginas de conta serem noindex)**

Em `includes/seo.php`, dentro de `seo_render()`, logo após a linha do `<meta name="description" …>`, acrescente:

```php
    if (!empty($seo['robots'])) {
        echo '<meta name="robots" content="' . e($seo['robots']) . '">' . "\n";
    }
```

(As páginas de conta definem `$seo['robots'] = 'noindex, nofollow'`; sem este suporte a meta não seria renderizada.)

- [ ] **Step 4: Unificar a sessão pública em `contato.php`**

Em `paginas/contato.php`, troque a linha:

```php
require_once CL_RAIZ . '/includes/girassol.php';
```

por:

```php
require_once CL_RAIZ . '/includes/girassol.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';

iniciar_sessao_site();
```

(O `iniciar_sessao_site()` deve vir antes de qualquer chamada a `csrf_token()`, que está mais abaixo no arquivo.)

- [ ] **Step 5: Verificar o site público no servidor embutido**

Run: `php -S localhost:8080 index.php` (em outro terminal) e então
Run: `curl -s -o NUL -w "%{http_code}\n" http://localhost:8080/entrar`
Expected: `200` (a rota existe; a página vem na Task 8 — até lá pode dar 404, então só rode este passo após a Task 8, ou aceite 404 agora). Para validar agora apenas o parser:
Run: `php -l index.php && php -l includes/cabecalho.php && php -l paginas/contato.php`
Expected: `No syntax errors detected` nos três.

- [ ] **Step 6: Commit**

```bash
git add index.php includes/cabecalho.php includes/seo.php paginas/contato.php assets/css/style.css
git commit -m "Adiciona rotas das contas e unifica a sessão pública (cl_site)"
```

---

## Task 7: Cadastro e verificação de e-mail

**Files:**
- Create: `paginas/criar-conta.php`
- Create: `paginas/verificar-email.php`

- [ ] **Step 1: Implementar `paginas/criar-conta.php`**

Crie `paginas/criar-conta.php`:

```php
<?php
/**
 * Cadastro de cursista (double opt-in). Cria a conta e envia o e-mail de verificação.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/tokens.php';
require_once CL_RAIZ . '/includes/email.php';

iniciar_sessao_site();
if (cursista_logado() !== null) {
    header('Location: /minha-conta');
    exit;
}

$aviso = null;
$enviado = false;
$valores = ['nome' => '', 'email' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $erros = [];
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente de novo.';
    }
    if (!empty($_POST['site'] ?? '')) {
        $erros[] = 'Envio bloqueado.';
    }

    $valores['nome']  = limpar_texto((string) ($_POST['nome'] ?? ''));
    $valores['email'] = limpar_texto((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $conf  = (string) ($_POST['confirmacao'] ?? '');

    if ($valores['nome'] === '')           { $erros[] = 'Diga o seu nome.'; }
    if (!validar_email($valores['email'])) { $erros[] = 'Informe um e-mail válido.'; }
    if (!senha_forte($senha))              { $erros[] = 'A senha precisa ter pelo menos 8 caracteres.'; }
    if ($senha !== $conf)                  { $erros[] = 'A confirmação não confere com a senha.'; }

    if (!$erros) {
        $base = rtrim($config['site']['url'], '/');
        $existente = cursista_por_email($valores['email']);
        if ($existente !== null) {
            // Anti-enumeração: não revela que a conta existe.
            enviar_email(
                $existente['email'],
                'Sobre a sua conta na Conta Lelê',
                "Recebemos um pedido de cadastro com este e-mail, mas você já tem uma conta.\n"
                    . "Para entrar: {$base}/entrar\nEsqueceu a senha? {$base}/recuperar-senha",
                null,
                template_email(
                    'Você já tem uma conta',
                    ['Recebemos um pedido de cadastro com este e-mail, mas você já tem uma conta na Conta Lelê.',
                     'Se foi você, é só entrar. Se esqueceu a senha, use o botão abaixo.'],
                    'Recuperar senha',
                    "{$base}/recuperar-senha"
                )
            );
        } else {
            try {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $id = criar_cursista($valores['nome'], $valores['email'], $hash, null, false);
                $tk = criar_token('cursista', $id, 'verificacao', TOKEN_TTL_VERIFICACAO);
                $url = "{$base}/verificar-email?token=" . $tk;
                enviar_email(
                    normalizar_email($valores['email']),
                    'Confirme o seu e-mail — Conta Lelê',
                    "Olá, {$valores['nome']}!\n\nConfirme o seu e-mail para ativar a conta:\n{$url}\n\n"
                        . 'O link vale por 24 horas.',
                    null,
                    template_email(
                        'Confirme o seu e-mail',
                        ["Olá, {$valores['nome']}! Falta só um passo para ativar a sua conta.",
                         'Clique no botão abaixo para confirmar o seu e-mail. O link vale por 24 horas.'],
                        'Confirmar e-mail',
                        $url
                    )
                );
            } catch (PDOException $e) {
                // Corrida de cadastro com o mesmo e-mail (violação de UNIQUE, SQLSTATE 23000):
                // trata como "conta já existe", mantendo a resposta neutra (anti-enumeração).
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }
        $enviado = true;
    } else {
        $aviso = ['tipo' => 'erro', 'texto' => implode(' ', $erros)];
    }
}

$token = csrf_token();
$seo['titulo']    = 'Criar conta | Conta Lelê';
$seo['descricao'] = 'Crie a sua conta na Conta Lelê para acessar os cursos da Lelê.';
$seo['robots']    = 'noindex, nofollow';
?>
<section class="cl-hero-simples">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Sua conta</p>
        <h1>Criar <span class="cl-em">conta</span></h1>
    </div>
</section>
<section class="cl-secao" style="padding-top:24px">
    <div class="cl-conteudo" style="max-width:520px">
        <?php if ($enviado): ?>
            <p class="cl-aviso cl-aviso--ok">Quase lá! Enviamos um e-mail de confirmação.
                Abra a sua caixa de entrada para ativar a conta.</p>
            <p>Não recebeu? Verifique o spam ou <a href="/criar-conta">tente de novo</a>.</p>
        <?php else: ?>
            <?php if ($aviso): ?>
                <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
            <?php endif; ?>
            <form class="cl-form" method="post" action="/criar-conta" novalidate>
                <input type="hidden" name="csrf" value="<?= e($token) ?>">
                <label class="cl-mel">Não preencha este campo
                    <input type="text" name="site" tabindex="-1" autocomplete="off"></label>
                <div class="cl-form__grid">
                    <label class="cl-campo cl-campo--full"><span>Nome</span>
                        <input type="text" name="nome" required value="<?= e($valores['nome']) ?>"
                               autocomplete="name"></label>
                    <label class="cl-campo cl-campo--full"><span>E-mail</span>
                        <input type="email" name="email" required value="<?= e($valores['email']) ?>"
                               autocomplete="email"></label>
                    <label class="cl-campo"><span>Senha (mínimo 8)</span>
                        <input type="password" name="senha" required minlength="8"
                               autocomplete="new-password"></label>
                    <label class="cl-campo"><span>Confirme a senha</span>
                        <input type="password" name="confirmacao" required minlength="8"
                               autocomplete="new-password"></label>
                </div>
                <div class="cl-form__rodape">
                    <button class="cl-btn cl-btn-primary cl-btn-lg" type="submit">Criar conta</button>
                </div>
            </form>
            <p style="margin-top:16px">Já tem conta? <a href="/entrar">Entrar</a></p>
        <?php endif; ?>
    </div>
</section>
```

> Nota: `$config['site']['url']` vem de `config.php` (já existe). Em desenvolvimento, ajuste essa
> chave para o host local se quiser que os links dos e-mails apontem para o ambiente de teste.

- [ ] **Step 2: Implementar `paginas/verificar-email.php`**

Crie `paginas/verificar-email.php`:

```php
<?php
/**
 * Verificação de e-mail do cursista via token. Ativa a conta e faz login.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/tokens.php';

iniciar_sessao_site();

$tokenCru = (string) ($_GET['token'] ?? '');
$linha = consumir_token($tokenCru, 'verificacao');

$ok = false;
if ($linha !== null && $linha['escopo'] === 'cursista') {
    $cursista = cursista_por_id((int) $linha['usuario_id']);
    if ($cursista !== null && (int) $cursista['ativo'] === 1) {
        marcar_email_verificado((int) $cursista['id']);
        logar_cursista($cursista);
        header('Location: /minha-conta');
        exit;
    }
}

$seo['titulo'] = 'Verificação de e-mail | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-secao" style="padding-top:48px">
    <div class="cl-conteudo" style="max-width:520px">
        <h1>Link inválido ou expirado</h1>
        <p class="cl-aviso cl-aviso--erro">Não foi possível confirmar o seu e-mail.
            O link pode ter expirado ou já ter sido usado.</p>
        <p>Crie a conta de novo para receber um novo link de confirmação:
            <a href="/criar-conta">Criar conta</a>.</p>
    </div>
</section>
```

- [ ] **Step 3: Smoke do parser**

Run: `php -l paginas/criar-conta.php && php -l paginas/verificar-email.php`
Expected: `No syntax errors detected` nos dois.

- [ ] **Step 4: Commit**

```bash
git add paginas/criar-conta.php paginas/verificar-email.php
git commit -m "Adiciona cadastro de cursista com verificação de e-mail"
```

---

## Task 8: Login e logout do cursista

**Files:**
- Create: `paginas/entrar.php`
- Create: `paginas/sair.php`

- [ ] **Step 1: Implementar `paginas/entrar.php`**

Crie `paginas/entrar.php`:

```php
<?php
/**
 * Login do cursista (e-mail/senha) com bloqueio por tentativas.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/tokens.php';
require_once CL_RAIZ . '/includes/email.php';

iniciar_sessao_site();

$destino = destino_seguro($_GET['destino'] ?? null);
if (cursista_logado() !== null) {
    header('Location: ' . $destino);
    exit;
}

$aviso = null;
$naoVerificado = null; // guarda o cursista quando o login falha por falta de verificação

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } elseif (($_POST['reenviar'] ?? '') === '1') {
        // Reenvio do e-mail de verificação (botão da própria tela) — branch isolado.
        $c = cursista_por_email((string) ($_POST['email'] ?? ''));
        if ($c && (int) $c['email_verificado'] !== 1
            && !token_recente('cursista', (int) $c['id'], 'verificacao', 60)) {
            $base = rtrim($config['site']['url'], '/');
            $tk = criar_token('cursista', (int) $c['id'], 'verificacao', TOKEN_TTL_VERIFICACAO);
            $url = "{$base}/verificar-email?token=" . $tk;
            enviar_email(
                (string) $c['email'],
                'Confirme o seu e-mail — Conta Lelê',
                "Confirme o seu e-mail:\n{$url}\nO link vale por 24 horas.",
                null,
                template_email('Confirme o seu e-mail',
                    ['Aqui está um novo link de confirmação. Ele vale por 24 horas.'],
                    'Confirmar e-mail', $url)
            );
        }
        $aviso = ['tipo' => 'ok', 'texto' => 'Se a conta existir e ainda não estiver confirmada, enviamos um novo link.'];
    } else {
        $destino = destino_seguro($_POST['destino'] ?? null);
        $email = limpar_texto((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        $c = cursista_por_email($email);

        if ($c && cursista_bloqueado($c)) {
            $aviso = ['tipo' => 'erro', 'texto' => 'Conta temporariamente bloqueada por tentativas. Aguarde 15 minutos.'];
        } elseif ($c && (int) $c['ativo'] === 1 && $c['senha_hash'] !== null
                  && password_verify($senha, $c['senha_hash'])) {
            if ((int) $c['email_verificado'] !== 1) {
                $naoVerificado = $c;
                $aviso = ['tipo' => 'erro', 'texto' => 'Confirme o seu e-mail antes de entrar.'];
            } else {
                registrar_acesso_cursista((int) $c['id']);
                logar_cursista($c);
                header('Location: ' . $destino);
                exit;
            }
        } elseif ($c && $c['senha_hash'] === null) {
            $aviso = ['tipo' => 'erro', 'texto' => 'Esta conta usa entrada pelo Google. Use o botão "Entrar com Google".'];
        } else {
            if ($c) {
                registrar_tentativa_falha_cursista($c);
            }
            $aviso = ['tipo' => 'erro', 'texto' => 'E-mail ou senha incorretos.'];
        }
    }
}

$googleAtivo = configuracao('google_oauth_ativo', '0') === '1' && configuracao('google_client_id') !== '';
$token = csrf_token();
$seo['titulo'] = 'Entrar | Conta Lelê';
$seo['descricao'] = 'Entre na sua conta da Conta Lelê.';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-hero-simples">
    <div class="cl-conteudo"><p class="cl-eyebrow">Sua conta</p><h1><span class="cl-em">Entrar</span></h1></div>
</section>
<section class="cl-secao" style="padding-top:24px">
    <div class="cl-conteudo" style="max-width:460px">
        <?php if ($aviso): ?>
            <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
        <?php endif; ?>

        <?php if ($naoVerificado !== null): ?>
            <form method="post" action="/entrar" style="margin-bottom:16px">
                <input type="hidden" name="csrf" value="<?= e($token) ?>">
                <input type="hidden" name="reenviar" value="1">
                <input type="hidden" name="email" value="<?= e((string) $naoVerificado['email']) ?>">
                <button class="cl-btn cl-btn-yellow" type="submit">Reenviar e-mail de confirmação</button>
            </form>
        <?php endif; ?>

        <form class="cl-form" method="post" action="/entrar" novalidate>
            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <input type="hidden" name="destino" value="<?= e($destino) ?>">
            <div class="cl-form__grid">
                <label class="cl-campo cl-campo--full"><span>E-mail</span>
                    <input type="email" name="email" required autocomplete="email" autofocus></label>
                <label class="cl-campo cl-campo--full"><span>Senha</span>
                    <input type="password" name="senha" required autocomplete="current-password"></label>
            </div>
            <div class="cl-form__rodape">
                <button class="cl-btn cl-btn-primary cl-btn-lg" type="submit">Entrar</button>
            </div>
        </form>

        <?php if ($googleAtivo): ?>
            <div style="margin:18px 0;text-align:center;color:var(--cl-ink-dim)">ou</div>
            <a class="cl-btn-google" href="/entrar/google">
                <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.71-1.57 2.68-3.89 2.68-6.62z"/><path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.81.54-1.84.86-3.04.86-2.34 0-4.32-1.58-5.03-3.7H.96v2.33A9 9 0 0 0 9 18z"/><path fill="#FBBC05" d="M3.97 10.72a5.4 5.4 0 0 1 0-3.44V4.95H.96a9 9 0 0 0 0 8.1l3.01-2.33z"/><path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0A9 9 0 0 0 .96 4.95l3.01 2.33C4.68 5.16 6.66 3.58 9 3.58z"/></svg>
                Entrar com Google
            </a>
        <?php endif; ?>

        <p style="margin-top:18px">
            <a href="/recuperar-senha">Esqueci minha senha</a> · Não tem conta?
            <a href="/criar-conta">Criar conta</a>
        </p>
    </div>
</section>
```

- [ ] **Step 2: Estilo do botão Google**

No final de `assets/css/style.css`, acrescente:

```css
.cl-btn-google {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  padding: 13px 20px;
  border-radius: 999px;
  background: #fff;
  color: #2b1300;
  font-weight: 800;
  font-size: 15px;
  text-decoration: none;
  border: 1.5px solid var(--cl-ink);
  transition: transform .15s ease, box-shadow .15s ease;
}
.cl-btn-google:hover { transform: translateY(-1px); box-shadow: 0 8px 18px -12px rgba(43,19,0,0.35); }
```

- [ ] **Step 3: Implementar `paginas/sair.php`**

Crie `paginas/sair.php`:

```php
<?php
/**
 * Logout do cursista.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/autenticacao_cursista.php';

deslogar_cursista();
header('Location: /');
exit;
```

- [ ] **Step 4: Smoke do parser**

Run: `php -l paginas/entrar.php && php -l paginas/sair.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Commit**

```bash
git add paginas/entrar.php paginas/sair.php assets/css/style.css
git commit -m "Adiciona login e logout do cursista"
```

---

## Task 9: Recuperação de senha do cursista

**Files:**
- Create: `paginas/recuperar-senha.php`
- Create: `paginas/redefinir-senha.php`

- [ ] **Step 1: Implementar `paginas/recuperar-senha.php`**

Crie `paginas/recuperar-senha.php`:

```php
<?php
/**
 * Solicitação de recuperação de senha do cursista (anti-enumeração + throttle).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/tokens.php';
require_once CL_RAIZ . '/includes/email.php';

iniciar_sessao_site();

$enviado = false;
$aviso = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } else {
        $email = limpar_texto((string) ($_POST['email'] ?? ''));
        $c = $email !== '' ? cursista_por_email($email) : null;
        if ($c && (int) $c['ativo'] === 1
            && !token_recente('cursista', (int) $c['id'], 'recuperacao', 60)) {
            $base = rtrim($config['site']['url'], '/');
            $tk = criar_token('cursista', (int) $c['id'], 'recuperacao', TOKEN_TTL_RECUPERACAO);
            $url = "{$base}/redefinir-senha?token=" . $tk;
            enviar_email(
                (string) $c['email'],
                'Redefinir a sua senha — Conta Lelê',
                "Para redefinir a sua senha, acesse:\n{$url}\nO link vale por 1 hora.",
                null,
                template_email('Redefinir a sua senha',
                    ['Recebemos um pedido para redefinir a sua senha.',
                     'Clique no botão abaixo. O link vale por 1 hora. Se não foi você, ignore este e-mail.'],
                    'Redefinir senha', $url)
            );
        }
        $enviado = true;
    }
}

$token = csrf_token();
$seo['titulo'] = 'Recuperar senha | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-hero-simples">
    <div class="cl-conteudo"><p class="cl-eyebrow">Sua conta</p><h1>Recuperar <span class="cl-em">senha</span></h1></div>
</section>
<section class="cl-secao" style="padding-top:24px">
    <div class="cl-conteudo" style="max-width:460px">
        <?php if ($enviado): ?>
            <p class="cl-aviso cl-aviso--ok">Se houver uma conta com esse e-mail, enviamos um link
                para redefinir a senha. Verifique a sua caixa de entrada.</p>
        <?php else: ?>
            <?php if ($aviso): ?>
                <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
            <?php endif; ?>
            <form class="cl-form" method="post" action="/recuperar-senha" novalidate>
                <input type="hidden" name="csrf" value="<?= e($token) ?>">
                <div class="cl-form__grid">
                    <label class="cl-campo cl-campo--full"><span>E-mail da conta</span>
                        <input type="email" name="email" required autocomplete="email" autofocus></label>
                </div>
                <div class="cl-form__rodape">
                    <button class="cl-btn cl-btn-primary cl-btn-lg" type="submit">Enviar link</button>
                </div>
            </form>
            <p style="margin-top:16px"><a href="/entrar">Voltar para entrar</a></p>
        <?php endif; ?>
    </div>
</section>
```

- [ ] **Step 2: Implementar `paginas/redefinir-senha.php`**

Crie `paginas/redefinir-senha.php`:

```php
<?php
/**
 * Redefinição de senha do cursista via token.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/tokens.php';

iniciar_sessao_site();

$aviso = null;
$concluido = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
        $tokenCru = (string) ($_POST['token'] ?? '');
    } else {
        $tokenCru = (string) ($_POST['token'] ?? '');
        $nova = (string) ($_POST['nova'] ?? '');
        $conf = (string) ($_POST['confirmacao'] ?? '');
        if (!senha_forte($nova)) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A senha precisa ter pelo menos 8 caracteres.'];
        } elseif ($nova !== $conf) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A confirmação não confere com a nova senha.'];
        } else {
            $linha = consumir_token($tokenCru, 'recuperacao');
            if ($linha === null || $linha['escopo'] !== 'cursista') {
                $aviso = ['tipo' => 'erro', 'texto' => 'Link inválido ou expirado. Peça um novo.'];
            } else {
                $id = (int) $linha['usuario_id'];
                atualizar_senha_cursista($id, password_hash($nova, PASSWORD_DEFAULT));
                marcar_email_verificado($id); // ter recebido o e-mail comprova o endereço
                invalidar_tokens('cursista', $id, 'recuperacao');
                $concluido = true;
            }
        }
    }
} else {
    $tokenCru = (string) ($_GET['token'] ?? '');
}

$valido = false;
if (!$concluido && $tokenCru !== '') {
    // Confere validade sem consumir (peek), para exibir o formulário.
    $st = bd()->prepare(
        'SELECT 1 FROM tokens_autenticacao
         WHERE token_hash = ? AND finalidade = ? AND escopo = ? AND usado_em IS NULL AND expira_em > NOW()'
    );
    $st->execute([hash_token($tokenCru), 'recuperacao', 'cursista']);
    $valido = (bool) $st->fetchColumn();
}

$token = csrf_token();
$seo['titulo'] = 'Redefinir senha | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-secao" style="padding-top:48px">
    <div class="cl-conteudo" style="max-width:460px">
        <h1>Redefinir senha</h1>
        <?php if ($concluido): ?>
            <p class="cl-aviso cl-aviso--ok">Senha redefinida! Agora é só <a href="/entrar">entrar</a>
                com a nova senha.</p>
        <?php elseif (!$valido): ?>
            <p class="cl-aviso cl-aviso--erro"><?= e($aviso['texto'] ?? 'Link inválido ou expirado.') ?></p>
            <p><a href="/recuperar-senha">Pedir um novo link</a></p>
        <?php else: ?>
            <?php if ($aviso): ?>
                <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
            <?php endif; ?>
            <form class="cl-form" method="post" action="/redefinir-senha" novalidate>
                <input type="hidden" name="csrf" value="<?= e($token) ?>">
                <input type="hidden" name="token" value="<?= e($tokenCru) ?>">
                <div class="cl-form__grid">
                    <label class="cl-campo cl-campo--full"><span>Nova senha (mínimo 8)</span>
                        <input type="password" name="nova" required minlength="8" autocomplete="new-password"></label>
                    <label class="cl-campo cl-campo--full"><span>Confirme a nova senha</span>
                        <input type="password" name="confirmacao" required minlength="8" autocomplete="new-password"></label>
                </div>
                <div class="cl-form__rodape">
                    <button class="cl-btn cl-btn-primary cl-btn-lg" type="submit">Salvar nova senha</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>
```

- [ ] **Step 3: Smoke do parser**

Run: `php -l paginas/recuperar-senha.php && php -l paginas/redefinir-senha.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add paginas/recuperar-senha.php paginas/redefinir-senha.php
git commit -m "Adiciona recuperação de senha do cursista"
```

---

## Task 10: Área "Minha conta"

**Files:**
- Create: `paginas/minha-conta.php`
- Create: `paginas/conta-senha.php`

- [ ] **Step 1: Implementar `paginas/minha-conta.php`**

Crie `paginas/minha-conta.php`:

```php
<?php
/**
 * Painel do cursista (área logada) — dados e edição de nome.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';

exigir_cursista();

$logado = cursista_logado();
$c = cursista_por_id((int) $logado['id']);
if ($c === null) {
    deslogar_cursista();
    header('Location: /entrar');
    exit;
}

$aviso = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } else {
        $nome = limpar_texto((string) ($_POST['nome'] ?? ''));
        if ($nome === '') {
            $aviso = ['tipo' => 'erro', 'texto' => 'O nome não pode ficar vazio.'];
        } else {
            atualizar_nome_cursista((int) $c['id'], $nome);
            $_SESSION['cursista']['nome'] = $nome;
            $c['nome'] = $nome;
            $aviso = ['tipo' => 'ok', 'texto' => 'Dados atualizados.'];
        }
    }
}

$usaGoogle = $c['google_id'] !== null;
$temSenha = $c['senha_hash'] !== null;
$token = csrf_token();
$seo['titulo'] = 'Minha conta | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-hero-simples">
    <div class="cl-conteudo"><p class="cl-eyebrow">Sua conta</p>
        <h1>Olá, <span class="cl-em"><?= e((string) $c['nome']) ?></span>!</h1></div>
</section>
<section class="cl-secao" style="padding-top:24px">
    <div class="cl-conteudo" style="max-width:560px">
        <?php if ($aviso): ?>
            <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
        <?php endif; ?>

        <form class="cl-form" method="post" action="/minha-conta" novalidate>
            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <div class="cl-form__grid">
                <label class="cl-campo cl-campo--full"><span>Nome</span>
                    <input type="text" name="nome" required value="<?= e((string) $c['nome']) ?>"></label>
                <label class="cl-campo cl-campo--full"><span>E-mail</span>
                    <input type="email" value="<?= e((string) $c['email']) ?>" disabled></label>
            </div>
            <div class="cl-form__rodape">
                <button class="cl-btn cl-btn-primary" type="submit">Salvar</button>
            </div>
        </form>

        <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap">
            <a class="cl-btn cl-btn-yellow" href="/minha-conta/senha">
                <?= $temSenha ? 'Trocar senha' : 'Definir uma senha' ?></a>
            <a class="cl-btn cl-btn-ghost" href="/sair">Sair</a>
        </div>

        <?php if ($usaGoogle): ?>
            <p style="margin-top:16px;color:var(--cl-ink-dim)">Esta conta também entra com o Google.</p>
        <?php endif; ?>
    </div>
</section>
```

- [ ] **Step 2: Implementar `paginas/conta-senha.php`**

Crie `paginas/conta-senha.php`:

```php
<?php
/**
 * Troca/definição de senha do cursista logado.
 * Conta só-Google define a primeira senha sem exigir a atual.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';

exigir_cursista();

$logado = cursista_logado();
$c = cursista_por_id((int) $logado['id']);
if ($c === null) {
    deslogar_cursista();
    header('Location: /entrar');
    exit;
}

$temSenha = $c['senha_hash'] !== null;
$aviso = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } else {
        $atual = (string) ($_POST['atual'] ?? '');
        $nova  = (string) ($_POST['nova'] ?? '');
        $conf  = (string) ($_POST['confirmacao'] ?? '');

        if ($temSenha && !password_verify($atual, (string) $c['senha_hash'])) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A senha atual está incorreta.'];
        } elseif (!senha_forte($nova)) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A nova senha precisa ter pelo menos 8 caracteres.'];
        } elseif ($nova !== $conf) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A confirmação não confere com a nova senha.'];
        } else {
            atualizar_senha_cursista((int) $c['id'], password_hash($nova, PASSWORD_DEFAULT));
            $aviso = ['tipo' => 'ok', 'texto' => 'Senha salva com sucesso.'];
            $temSenha = true;
        }
    }
}

$token = csrf_token();
$seo['titulo'] = 'Senha | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-secao" style="padding-top:48px">
    <div class="cl-conteudo" style="max-width:460px">
        <h1><?= $temSenha ? 'Trocar senha' : 'Definir senha' ?></h1>
        <?php if ($aviso): ?>
            <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
        <?php endif; ?>
        <form class="cl-form" method="post" action="/minha-conta/senha" novalidate>
            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <div class="cl-form__grid">
                <?php if ($temSenha): ?>
                    <label class="cl-campo cl-campo--full"><span>Senha atual</span>
                        <input type="password" name="atual" required autocomplete="current-password"></label>
                <?php endif; ?>
                <label class="cl-campo cl-campo--full"><span>Nova senha (mínimo 8)</span>
                    <input type="password" name="nova" required minlength="8" autocomplete="new-password"></label>
                <label class="cl-campo cl-campo--full"><span>Confirme a nova senha</span>
                    <input type="password" name="confirmacao" required minlength="8" autocomplete="new-password"></label>
            </div>
            <div class="cl-form__rodape">
                <button class="cl-btn cl-btn-primary cl-btn-lg" type="submit">Salvar</button>
                <a class="cl-btn cl-btn-ghost" href="/minha-conta">Voltar</a>
            </div>
        </form>
    </div>
</section>
```

- [ ] **Step 3: Smoke do parser**

Run: `php -l paginas/minha-conta.php && php -l paginas/conta-senha.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Verificação manual do fluxo de senha/login (servidor embutido + banco)**

Pré-condição: banco migrado e SMTP configurado (ou aceite que e-mails falhem silenciosamente em dev).
Run: `php -S localhost:8080 index.php`
Passos no navegador: `/criar-conta` → criar conta → confirmar pelo link do e-mail (em dev sem SMTP, pegue o token via `SELECT` na tabela e monte a URL `/verificar-email?token=...`) → cair em `/minha-conta` logado → `/minha-conta/senha` trocar senha → `/sair` → `/entrar` com a nova senha.
Expected: cada etapa conclui sem erro; o cabeçalho mostra "Minha conta" quando logado e "Entrar" quando não.

- [ ] **Step 5: Commit**

```bash
git add paginas/minha-conta.php paginas/conta-senha.php
git commit -m "Adiciona a área Minha conta do cursista"
```

---

## Task 11: `includes/google_oauth.php` (cliente OIDC)

**Files:**
- Create: `includes/google_oauth.php`

- [ ] **Step 1: Implementar o cliente**

Crie `includes/google_oauth.php`:

```php
<?php
/**
 * Cliente OAuth2/OpenID Connect do Google, com cURL. Agnóstico de escopo.
 * Credenciais vêm da tabela `configuracoes` (google_client_id/secret/ativo).
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once __DIR__ . '/repositorio.php';

/** True se o login com Google está configurado e ativo. */
function google_configurado(): bool
{
    return configuracao('google_oauth_ativo', '0') === '1'
        && configuracao('google_client_id') !== ''
        && configuracao('google_client_secret') !== '';
}

/** Monta a URL de autorização do Google. */
function google_url_autorizacao(string $redirectUri, string $state): string
{
    $params = [
        'client_id'     => configuracao('google_client_id'),
        'redirect_uri'  => $redirectUri,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/**
 * Troca o código de autorização por um access_token. Devolve o array de tokens ou null.
 */
function google_trocar_codigo(string $code, string $redirectUri): ?array
{
    $resp = google_post('https://oauth2.googleapis.com/token', [
        'code'          => $code,
        'client_id'     => configuracao('google_client_id'),
        'client_secret' => configuracao('google_client_secret'),
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code',
    ]);
    if ($resp === null || empty($resp['access_token'])) {
        return null;
    }
    return $resp;
}

/**
 * Busca o perfil do usuário (userinfo OIDC). Devolve sub/email/email_verified/name/picture ou null.
 */
function google_perfil(string $accessToken): ?array
{
    $ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $codigo !== 200) {
        registrar_log('Google userinfo falhou', ['http' => $codigo]);
        return null;
    }
    $dados = json_decode((string) $body, true);
    return is_array($dados) ? $dados : null;
}

/**
 * POST application/x-www-form-urlencoded que devolve JSON decodificado, ou null.
 */
function google_post(string $url, array $dados): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($dados),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $codigo !== 200) {
        registrar_log('Google token endpoint falhou', ['http' => $codigo]);
        return null;
    }
    $json = json_decode((string) $body, true);
    return is_array($json) ? $json : null;
}

/**
 * Gera um state anti-CSRF.
 */
function google_state(): string
{
    return bin2hex(random_bytes(16));
}
```

- [ ] **Step 2: Smoke do parser**

Run: `php -l includes/google_oauth.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add includes/google_oauth.php
git commit -m "Adiciona cliente OIDC do Google"
```

---

## Task 12: Login com Google do cursista

**Files:**
- Create: `paginas/google-iniciar.php`
- Create: `paginas/google-callback.php`

- [ ] **Step 1: Implementar `paginas/google-iniciar.php`**

Crie `paginas/google-iniciar.php`:

```php
<?php
/**
 * Inicia o login com Google do cursista (gera state e redireciona ao Google).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/google_oauth.php';

iniciar_sessao_site();

if (!google_configurado()) {
    header('Location: /entrar');
    exit;
}

$state = google_state();
$_SESSION['google_state'] = $state;
$base = rtrim($config['site']['url'], '/');
$redirectUri = $base . '/entrar/google/callback';

header('Location: ' . google_url_autorizacao($redirectUri, $state));
exit;
```

- [ ] **Step 2: Implementar `paginas/google-callback.php`**

Crie `paginas/google-callback.php`:

```php
<?php
/**
 * Callback do login com Google do cursista: criar-ou-vincular e logar.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/google_oauth.php';

iniciar_sessao_site();

$erro = null;
$code  = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');
$stateSessao = (string) ($_SESSION['google_state'] ?? '');
unset($_SESSION['google_state']);

if (!google_configurado()) {
    $erro = 'O login com Google não está disponível.';
} elseif ($code === '' || $state === '' || !hash_equals($stateSessao, $state)) {
    $erro = 'Falha na verificação de segurança. Tente novamente.';
} else {
    $base = rtrim($config['site']['url'], '/');
    $redirectUri = $base . '/entrar/google/callback';
    $tokens = google_trocar_codigo($code, $redirectUri);
    $perfil = $tokens !== null ? google_perfil($tokens['access_token']) : null;

    if ($perfil === null || empty($perfil['sub']) || empty($perfil['email'])
        || ($perfil['email_verified'] ?? false) !== true) {
        $erro = 'Não foi possível confirmar a sua conta Google.';
    } else {
        $sub   = (string) $perfil['sub'];
        $email = normalizar_email((string) $perfil['email']);
        $nome  = limpar_texto((string) ($perfil['name'] ?? 'Cursista'));

        $c = cursista_por_google_id($sub);
        if ($c === null) {
            $porEmail = cursista_por_email($email);
            if ($porEmail !== null) {
                vincular_google_cursista((int) $porEmail['id'], $sub);
                if ((int) $porEmail['email_verificado'] !== 1) {
                    marcar_email_verificado((int) $porEmail['id']);
                }
                $c = cursista_por_id((int) $porEmail['id']);
            } else {
                $id = criar_cursista($nome, $email, null, $sub, true);
                $c = cursista_por_id($id);
            }
        }

        if ($c !== null && (int) $c['ativo'] === 1) {
            registrar_acesso_cursista((int) $c['id']);
            logar_cursista($c);
            header('Location: /minha-conta');
            exit;
        }
        $erro = 'A sua conta está inativa. Fale com a gente.';
    }
}

$seo['titulo'] = 'Entrar com Google | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-secao" style="padding-top:48px">
    <div class="cl-conteudo" style="max-width:460px">
        <h1>Não deu certo</h1>
        <p class="cl-aviso cl-aviso--erro"><?= e($erro ?? 'Erro inesperado.') ?></p>
        <p><a href="/entrar">Voltar para entrar</a></p>
    </div>
</section>
```

- [ ] **Step 3: Smoke do parser**

Run: `php -l paginas/google-iniciar.php && php -l paginas/google-callback.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add paginas/google-iniciar.php paginas/google-callback.php
git commit -m "Adiciona login com Google do cursista"
```

---

## Task 13: Recuperação de senha do admin

**Files:**
- Create: `admin/recuperar-senha.php`
- Create: `admin/redefinir-senha.php`
- Modify: `admin/login.php` (link "Esqueci minha senha")

- [ ] **Step 1: Implementar `admin/recuperar-senha.php`**

Crie `admin/recuperar-senha.php` (layout standalone, no estilo de `admin/login.php`):

```php
<?php
/**
 * Solicitação de recuperação de senha do admin (anti-enumeração + throttle).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require CL_RAIZ . '/includes/repositorio.php';
require CL_RAIZ . '/includes/tokens.php';
require CL_RAIZ . '/includes/email.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
iniciar_sessao();

$enviado = false;
$erro = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } else {
        $email = limpar_texto((string) ($_POST['email'] ?? ''));
        $st = bd()->prepare('SELECT * FROM usuarios_admin WHERE email = ? AND ativo = 1 LIMIT 1');
        $st->execute([$email]);
        $a = $st->fetch();
        if ($a && !token_recente('admin', (int) $a['id'], 'recuperacao', 60)) {
            $base = rtrim($config['site']['url'], '/');
            $tk = criar_token('admin', (int) $a['id'], 'recuperacao', TOKEN_TTL_RECUPERACAO);
            $url = "{$base}/admin/redefinir-senha.php?token=" . $tk;
            enviar_email(
                (string) $a['email'],
                'Redefinir senha do painel — Conta Lelê',
                "Para redefinir a senha do painel, acesse:\n{$url}\nO link vale por 1 hora.",
                null,
                template_email('Redefinir senha do painel',
                    ['Recebemos um pedido para redefinir a senha do painel administrativo.',
                     'Clique no botão abaixo. O link vale por 1 hora.'],
                    'Redefinir senha', $url)
            );
        }
        $enviado = true;
    }
}

$token = csrf_token();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Recuperar senha — Painel Conta Lelê</title>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<main class="adm-conteudo" style="max-width:380px">
    <h1>Recuperar senha</h1>
    <?php if ($enviado): ?>
        <p class="adm-aviso adm-aviso--ok">Se houver uma conta com esse e-mail, enviamos um link.</p>
        <p><a href="/admin/login.php">Voltar ao login</a></p>
    <?php else: ?>
        <?php if ($erro !== null): ?>
            <p class="adm-aviso adm-aviso--erro"><?= e($erro) ?></p>
        <?php endif; ?>
        <form method="post" action="/admin/recuperar-senha.php" class="adm-cartao">
            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <label class="adm-campo"><span>E-mail do painel</span>
                <input type="email" name="email" required autofocus></label>
            <button class="adm-btn" type="submit">Enviar link</button>
        </form>
        <p style="margin-top:12px"><a href="/admin/login.php">Voltar ao login</a></p>
    <?php endif; ?>
</main>
</body>
</html>
```

- [ ] **Step 2: Implementar `admin/redefinir-senha.php`**

Crie `admin/redefinir-senha.php`:

```php
<?php
/**
 * Redefinição de senha do admin via token.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require CL_RAIZ . '/includes/tokens.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
iniciar_sessao();

$erro = null;
$concluido = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $tokenCru = (string) ($_POST['token'] ?? '');
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } else {
        $nova = (string) ($_POST['nova'] ?? '');
        $conf = (string) ($_POST['confirmacao'] ?? '');
        if (mb_strlen($nova) < 8) {
            $erro = 'A senha precisa ter pelo menos 8 caracteres.';
        } elseif ($nova !== $conf) {
            $erro = 'A confirmação não confere com a nova senha.';
        } else {
            $linha = consumir_token($tokenCru, 'recuperacao');
            if ($linha === null || $linha['escopo'] !== 'admin') {
                $erro = 'Link inválido ou expirado. Peça um novo.';
            } else {
                $id = (int) $linha['usuario_id'];
                bd()->prepare(
                    'UPDATE usuarios_admin SET senha_hash = ?, precisa_trocar_senha = 0,
                     tentativas_login = 0, bloqueado_ate = NULL WHERE id = ?'
                )->execute([password_hash($nova, PASSWORD_DEFAULT), $id]);
                invalidar_tokens('admin', $id, 'recuperacao');
                $concluido = true;
            }
        }
    }
} else {
    $tokenCru = (string) ($_GET['token'] ?? '');
}

$valido = false;
if (!$concluido && $tokenCru !== '') {
    $st = bd()->prepare(
        'SELECT 1 FROM tokens_autenticacao
         WHERE token_hash = ? AND finalidade = ? AND escopo = ? AND usado_em IS NULL AND expira_em > NOW()'
    );
    $st->execute([hash_token($tokenCru), 'recuperacao', 'admin']);
    $valido = (bool) $st->fetchColumn();
}

$token = csrf_token();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Redefinir senha — Painel Conta Lelê</title>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<main class="adm-conteudo" style="max-width:380px">
    <h1>Redefinir senha</h1>
    <?php if ($concluido): ?>
        <p class="adm-aviso adm-aviso--ok">Senha redefinida! <a href="/admin/login.php">Entrar</a>.</p>
    <?php elseif (!$valido): ?>
        <p class="adm-aviso adm-aviso--erro"><?= e($erro ?? 'Link inválido ou expirado.') ?></p>
        <p><a href="/admin/recuperar-senha.php">Pedir um novo link</a></p>
    <?php else: ?>
        <?php if ($erro !== null): ?>
            <p class="adm-aviso adm-aviso--erro"><?= e($erro) ?></p>
        <?php endif; ?>
        <form method="post" action="/admin/redefinir-senha.php" class="adm-cartao">
            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <input type="hidden" name="token" value="<?= e($tokenCru) ?>">
            <label class="adm-campo"><span>Nova senha (mínimo 8)</span>
                <input type="password" name="nova" required minlength="8"></label>
            <label class="adm-campo"><span>Confirme a nova senha</span>
                <input type="password" name="confirmacao" required minlength="8"></label>
            <button class="adm-btn" type="submit">Salvar nova senha</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
```

- [ ] **Step 3: Link "Esqueci minha senha" no login do admin**

Em `admin/login.php`, logo após o `</form>` (antes do `</main>`), acrescente:

```php
    <p style="margin-top:12px"><a href="/admin/recuperar-senha.php">Esqueci minha senha</a></p>
```

- [ ] **Step 4: Smoke do parser**

Run: `php -l admin/recuperar-senha.php && php -l admin/redefinir-senha.php && php -l admin/login.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Commit**

```bash
git add admin/recuperar-senha.php admin/redefinir-senha.php admin/login.php
git commit -m "Adiciona recuperação de senha do admin"
```

---

## Task 14: Login com Google do admin + configurações

**Files:**
- Create: `admin/google.php`
- Create: `admin/google-callback.php`
- Modify: `admin/login.php` (botão "Entrar com Google")
- Modify: `admin/configuracoes.php` (campos do Google)

- [ ] **Step 1: Implementar `admin/google.php`**

Crie `admin/google.php`:

```php
<?php
/**
 * Inicia o login com Google do admin (gera state e redireciona).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require CL_RAIZ . '/includes/google_oauth.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
iniciar_sessao();

if (!google_configurado()) {
    header('Location: /admin/login.php');
    exit;
}

$state = google_state();
$_SESSION['google_state'] = $state;
$base = rtrim($config['site']['url'], '/');
$redirectUri = $base . '/admin/google-callback.php';

header('Location: ' . google_url_autorizacao($redirectUri, $state));
exit;
```

- [ ] **Step 2: Implementar `admin/google-callback.php` ("só vincula, nunca cria")**

Crie `admin/google-callback.php`:

```php
<?php
/**
 * Callback do login com Google do admin. Regra: só vincula a admin já existente, nunca cria.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require CL_RAIZ . '/includes/repositorio.php';
require CL_RAIZ . '/includes/google_oauth.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
iniciar_sessao();

$erro = null;
$code  = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');
$stateSessao = (string) ($_SESSION['google_state'] ?? '');
unset($_SESSION['google_state']);

if (!google_configurado()) {
    $erro = 'O login com Google não está disponível.';
} elseif ($code === '' || $state === '' || !hash_equals($stateSessao, $state)) {
    $erro = 'Falha na verificação de segurança. Tente novamente.';
} else {
    $base = rtrim($config['site']['url'], '/');
    $redirectUri = $base . '/admin/google-callback.php';
    $tokens = google_trocar_codigo($code, $redirectUri);
    $perfil = $tokens !== null ? google_perfil($tokens['access_token']) : null;

    if ($perfil === null || empty($perfil['sub']) || empty($perfil['email'])
        || ($perfil['email_verified'] ?? false) !== true) {
        $erro = 'Não foi possível confirmar a sua conta Google.';
    } else {
        $sub   = (string) $perfil['sub'];
        $email = mb_strtolower(trim((string) $perfil['email']));

        // 1) admin já vinculado a esse google_id?
        $st = bd()->prepare('SELECT * FROM usuarios_admin WHERE google_id = ? AND ativo = 1 LIMIT 1');
        $st->execute([$sub]);
        $a = $st->fetch();

        // 2) senão, admin com esse e-mail? (vincula)
        if (!$a) {
            $st = bd()->prepare('SELECT * FROM usuarios_admin WHERE email = ? AND ativo = 1 LIMIT 1');
            $st->execute([$email]);
            $a = $st->fetch();
            if ($a) {
                bd()->prepare('UPDATE usuarios_admin SET google_id = ? WHERE id = ?')
                    ->execute([$sub, (int) $a['id']]);
            }
        }

        if ($a) {
            bd()->prepare(
                'UPDATE usuarios_admin SET tentativas_login = 0, bloqueado_ate = NULL, ultimo_acesso = NOW() WHERE id = ?'
            )->execute([(int) $a['id']]);
            logar($a);
            header('Location: /admin/');
            exit;
        }
        $erro = 'Esta conta Google não tem acesso ao painel.';
    }
}

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Entrar com Google — Painel Conta Lelê</title>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<main class="adm-conteudo" style="max-width:380px">
    <h1>Não deu certo</h1>
    <p class="adm-aviso adm-aviso--erro"><?= e($erro ?? 'Erro inesperado.') ?></p>
    <p><a href="/admin/login.php">Voltar ao login</a></p>
</main>
</body>
</html>
```

- [ ] **Step 3: Botão "Entrar com Google" no login do admin**

Em `admin/login.php`, adicione no topo (após `iniciar_sessao();`) a checagem de disponibilidade:

```php
require CL_RAIZ . '/includes/repositorio.php';
$googleAtivo = configuracao('google_oauth_ativo', '0') === '1' && configuracao('google_client_id') !== '';
```

E, dentro do `<main>`, logo após o `</form>` (antes do link "Esqueci minha senha"), acrescente:

```php
    <?php if ($googleAtivo): ?>
        <p style="margin:12px 0;text-align:center;color:var(--cl-ink-dim)">ou</p>
        <a class="adm-btn" style="display:block;text-align:center;text-decoration:none"
           href="/admin/google.php">Entrar com Google</a>
    <?php endif; ?>
```

> `admin/login.php` já tem `require CL_RAIZ . '/includes/conexao.php';`. O `require` de `repositorio.php` é novo e necessário para `configuracao()`.

- [ ] **Step 4: Campos do Google em `admin/configuracoes.php`**

Em `admin/configuracoes.php`, acrescente ao array `$campos` (após `smtp_seguranca`):

```php
    'google_client_id'     => ['Google — Client ID', 'text'],
    'google_client_secret' => ['Google — Client Secret', 'password'],
    'google_oauth_ativo'   => ['Google — ativo (1 = ligado, 0 = desligado)', 'text'],
```

E generalize a regra de "não sobrescrever segredo em branco" para incluir o secret do Google. Troque:

```php
            if ($chave === 'smtp_senha' && ($_POST[$chave] ?? '') === '') {
                continue;
            }
```

por:

```php
            if (in_array($chave, ['smtp_senha', 'google_client_secret'], true)
                && ($_POST[$chave] ?? '') === '') {
                continue;
            }
```

E no formulário, troque a condição que renderiza o campo de senha. Troque:

```php
            <?php if ($chave === 'smtp_senha'): ?>
```

por:

```php
            <?php if ($tipo === 'password'): ?>
```

- [ ] **Step 5: Smoke do parser**

Run: `php -l admin/google.php && php -l admin/google-callback.php && php -l admin/login.php && php -l admin/configuracoes.php`
Expected: `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add admin/google.php admin/google-callback.php admin/login.php admin/configuracoes.php
git commit -m "Adiciona login com Google do admin e configurações"
```

---

## Task 15: Verificação final (testes + UAT)

**Files:** nenhum novo (verificação).

- [ ] **Step 1: Rodar toda a bateria de testes CLI**

Run: `php tests/correr.php`
Expected: `Total: N ok, 0 falha(s)` (inclui `funcoes-teste`, `repositorio-teste`, `erros-teste`, `upload-teste`, `tokens-teste`, `cursistas-teste`).

- [ ] **Step 2: Lint de todos os arquivos novos/alterados**

Run (PowerShell):
```powershell
Get-ChildItem -Recurse -Filter *.php -Path includes,paginas,admin,. -Depth 1 |
  ForEach-Object { php -l $_.FullName } | Select-String -Pattern "Errors" -Context 0,0
```
Expected: nenhuma linha com "Errors" (todos "No syntax errors detected").

- [ ] **Step 3: UAT manual — checklist completo**

Pré-condições: banco migrado (`migrar.php`), SMTP configurado, credenciais Google preenchidas e `google_oauth_ativo=1`, dois redirect URIs registrados no Google Cloud.

Marque cada item:
- [ ] Cadastro `/criar-conta` → recebe e-mail → `/verificar-email?token=` ativa e cai logado em `/minha-conta`.
- [ ] Cadastro com e-mail já existente → mesma tela de "enviamos e-mail" (sem revelar) + e-mail "você já tem conta".
- [ ] Login `/entrar` com senha correta → `/minha-conta`. Com senha errada 5×→ bloqueio 15 min.
- [ ] Login com conta não verificada → barra e oferece reenviar; reenvio respeita throttle de 60 s.
- [ ] Recuperação `/recuperar-senha` → e-mail → `/redefinir-senha?token=` → nova senha → login OK; link velho não funciona mais.
- [ ] Recuperação admin `/admin/recuperar-senha.php` → e-mail → `/admin/redefinir-senha.php?token=` → login admin OK.
- [ ] Google cursista: conta nova (cria), e depois mesmo e-mail por senha (vincula).
- [ ] Google admin: e-mail que é admin (vincula e entra); e-mail que não é admin (acesso negado).
- [ ] Cabeçalho: "Entrar" para anônimo, "Minha conta" para logado; visitante anônimo não recebe cookie `cl_site` na home.
- [ ] `/minha-conta` e `/minha-conta/senha` redirecionam para `/entrar` quando deslogado.

- [ ] **Step 3b: Purgar o cache do Cloudflare após o deploy**

O cabeçalho público mudou (novo CTA "Entrar"/"Minha conta") e há CSS novo. Como o HTML anônimo é
cacheável por design, sem o purge o edge pode servir a versão antiga (sem o link "Entrar").
Após publicar via `deploy/enviar.sh`, **purgue o cache do Cloudflare** (painel → Caching → Purge
Everything, ou via API). Os assets já têm cache-busting por `filemtime`, mas o HTML não.

- [ ] **Step 4: Commit (se algum ajuste foi necessário na UAT)**

```bash
git add -A
git commit -m "Ajustes finais das contas de cursistas após UAT"
```

---

## Task 16: Endurecimento anti-abuso (limite por IP + honeypot no login)

Endurece os endpoints que enviam e-mail e os logins contra abuso (email-bombing e account-lockout DoS),
com limite por IP em janela deslizante e honeypot nos formulários de login. Roda **depois** do núcleo.

**Files:**
- Create: `includes/limites.php`
- Test: `tests/limites-teste.php`
- Modify: `sql/schema.sql` (tabela `limites_acao`)
- Modify: `migrar.php` (criar `limites_acao`)
- Modify: `admin/diagnostico.php` (incluir `limites_acao` em `$esperadas`)
- Modify: `paginas/criar-conta.php`, `paginas/recuperar-senha.php`, `paginas/entrar.php`
- Modify: `admin/login.php`, `admin/recuperar-senha.php`

- [ ] **Step 1: Tabela `limites_acao` no schema e na migração**

Acrescente ao final de `sql/schema.sql`:

```sql

-- Limites de ação por IP (anti-abuso) — Fase 2
CREATE TABLE IF NOT EXISTS limites_acao (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    acao      VARCHAR(40)  NOT NULL,
    ip        VARCHAR(45)  NOT NULL,
    criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_limites_lookup (acao, ip, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Em `migrar.php`, antes da seção de configurações (Step 2 da Task 1), acrescente:

```php
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS limites_acao (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        acao VARCHAR(40) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY ix_limites_lookup (acao, ip, criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo '<p>Tabela limites_acao garantida.</p>';
```

Em `admin/diagnostico.php`, inclua `'limites_acao'` no array `$esperadas`.

- [ ] **Step 2: Escrever o teste de `ip_requisicao()`**

Crie `tests/limites-teste.php`:

```php
<?php
declare(strict_types=1);

require __DIR__ . '/../includes/limites.php';

$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
afirmar_igual('203.0.113.7', ip_requisicao(), 'ip_requisicao() devolve o REMOTE_ADDR');

unset($_SERVER['REMOTE_ADDR']);
afirmar_igual('0.0.0.0', ip_requisicao(), 'ip_requisicao() usa o padrão sem REMOTE_ADDR');
```

- [ ] **Step 3: Rodar e ver falhar**

Run: `php tests/correr.php`
Expected: FALHA em `[limites-teste.php]` (arquivo/função inexistente).

- [ ] **Step 4: Implementar `includes/limites.php`**

Crie `includes/limites.php`:

```php
<?php
/**
 * Limites de ação por IP (anti-abuso) — contagem em janela deslizante.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once __DIR__ . '/conexao.php';

/** IP da requisição atual (com padrão seguro). */
function ip_requisicao(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/** Registra uma ocorrência da ação para o IP. */
function registrar_acao(string $acao, ?string $ip = null): void
{
    bd()->prepare('INSERT INTO limites_acao (acao, ip) VALUES (?, ?)')
        ->execute([$acao, $ip ?? ip_requisicao()]);
}

/** True se o IP já atingiu $max ocorrências de $acao na janela de $janelaSegundos. */
function acao_excedida(string $acao, int $max, int $janelaSegundos, ?string $ip = null): bool
{
    $janelaSegundos = (int) $janelaSegundos;
    $st = bd()->prepare(
        "SELECT COUNT(*) FROM limites_acao
         WHERE acao = ? AND ip = ? AND criado_em > DATE_SUB(NOW(), INTERVAL {$janelaSegundos} SECOND)"
    );
    $st->execute([$acao, $ip ?? ip_requisicao()]);
    return (int) $st->fetchColumn() >= $max;
}

/** Housekeeping: remove registros com mais de 1 dia. */
function limpar_limites_antigos(): void
{
    bd()->query('DELETE FROM limites_acao WHERE criado_em < DATE_SUB(NOW(), INTERVAL 1 DAY)');
}
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php tests/correr.php`
Expected: `[limites-teste.php]` todas `ok`.

- [ ] **Step 6: Aplicar limite por IP no cadastro (`paginas/criar-conta.php`)**

Acrescente o require (junto aos demais requires do topo):

```php
require_once CL_RAIZ . '/includes/limites.php';
```

Troque:

```php
    if (!$erros) {
        $base = rtrim($config['site']['url'], '/');
        $existente = cursista_por_email($valores['email']);
```

por:

```php
    if (!$erros && acao_excedida('cadastro', 10, 3600)) {
        $enviado = true; // limite de envios por IP atingido — resposta neutra (anti-enumeração)
    } elseif (!$erros) {
        registrar_acao('cadastro');
        $base = rtrim($config['site']['url'], '/');
        $existente = cursista_por_email($valores['email']);
```

- [ ] **Step 7: Aplicar limite por IP nas recuperações de senha**

Em `paginas/recuperar-senha.php`, acrescente o require `includes/limites.php` no topo e troque:

```php
        if ($c && (int) $c['ativo'] === 1
            && !token_recente('cursista', (int) $c['id'], 'recuperacao', 60)) {
            $base = rtrim($config['site']['url'], '/');
```

por:

```php
        if ($c && (int) $c['ativo'] === 1
            && !acao_excedida('recuperacao', 5, 3600)
            && !token_recente('cursista', (int) $c['id'], 'recuperacao', 60)) {
            registrar_acao('recuperacao');
            $base = rtrim($config['site']['url'], '/');
```

Em `admin/recuperar-senha.php`, acrescente o require `includes/limites.php` no topo e troque:

```php
        if ($a && !token_recente('admin', (int) $a['id'], 'recuperacao', 60)) {
            $base = rtrim($config['site']['url'], '/');
```

por:

```php
        if ($a && !acao_excedida('recuperacao_admin', 5, 3600)
            && !token_recente('admin', (int) $a['id'], 'recuperacao', 60)) {
            registrar_acao('recuperacao_admin');
            $base = rtrim($config['site']['url'], '/');
```

- [ ] **Step 8: Honeypot + limite por IP no login do cursista (`paginas/entrar.php`)**

Acrescente o require `includes/limites.php` no topo. Troque o início do bloco POST:

```php
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } elseif (($_POST['reenviar'] ?? '') === '1') {
```

por:

```php
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } elseif (!empty($_POST['site'])) {
        $aviso = ['tipo' => 'erro', 'texto' => 'E-mail ou senha incorretos.'];
    } elseif (acao_excedida('login', 20, 900)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Muitas tentativas. Aguarde alguns minutos.'];
    } elseif (($_POST['reenviar'] ?? '') === '1') {
```

E, dentro do ramo de autenticação (o `} else {` final que trata e-mail/senha), logo após a linha
`$destino = destino_seguro($_POST['destino'] ?? null);`, acrescente:

```php
        registrar_acao('login');
```

No formulário de login (a `<form class="cl-form" ...>` principal), logo após o input hidden `csrf`,
acrescente o honeypot:

```php
            <label class="cl-mel">Não preencha este campo
                <input type="text" name="site" tabindex="-1" autocomplete="off"></label>
```

- [ ] **Step 9: Honeypot + limite por IP no login do admin (`admin/login.php`)**

Acrescente o require `includes/limites.php` (junto aos demais requires do topo). Troque:

```php
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } else {
        $email = limpar_texto((string) ($_POST['email'] ?? ''));
```

por:

```php
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } elseif (!empty($_POST['site'])) {
        $erro = 'E-mail ou senha incorretos.';
    } elseif (acao_excedida('login_admin', 20, 900)) {
        $erro = 'Muitas tentativas. Aguarde alguns minutos.';
    } else {
        registrar_acao('login_admin');
        $email = limpar_texto((string) ($_POST['email'] ?? ''));
```

No formulário de login do admin, logo após o input hidden `csrf`, acrescente o honeypot:

```php
        <label style="position:absolute;left:-9999px" aria-hidden="true">Não preencha
            <input type="text" name="site" tabindex="-1" autocomplete="off"></label>
```

- [ ] **Step 10: Rodar testes, lint e commit**

Run: `php tests/correr.php`
Expected: `Total: N ok, 0 falha(s)`.

Run: `php -l includes/limites.php && php -l paginas/criar-conta.php && php -l paginas/recuperar-senha.php && php -l paginas/entrar.php && php -l admin/login.php && php -l admin/recuperar-senha.php && php -l migrar.php`
Expected: `No syntax errors detected` em todos.

```bash
git add includes/limites.php tests/limites-teste.php sql/schema.sql migrar.php admin/diagnostico.php paginas/criar-conta.php paginas/recuperar-senha.php paginas/entrar.php admin/login.php admin/recuperar-senha.php
git commit -m "Adiciona endurecimento anti-abuso (limite por IP e honeypot no login)"
```

> **Nota de UAT adicional:** confirme que muitas tentativas de login do mesmo IP passam a ser barradas
> com "Muitas tentativas"; que preencher o campo oculto `site` bloqueia o login; e que o limite de
> envios por IP no cadastro/recuperação não impede o uso legítimo normal.

> **Turnstile (opcional, fora desta task):** o Cloudflare já está na frente; se quiser proteção extra
> contra automação, integrar o Cloudflare Turnstile aos formulários de cadastro/login é o próximo passo
> natural, mas exige chaves e configuração próprias.

---

## Auto-revisão (preenchida pelo autor do plano)

**Cobertura do spec:** §1 modelo de dados → Task 1; §3 camadas → Tasks 2-5, 11; §5 rotas/páginas → Tasks 6-10, 12-14; §6 fluxos → Tasks 7-10, 12-14; §7 segurança (tokens só-hash, anti-enumeração, throttle 60s, honeypot, sessões, OAuth state, admin "só vincula") → Tasks 2, 7, 8, 9, 11-14; §8 cache → Task 6 (detecção sem cookie p/ anônimo) + Task 15 Step 3b (purge do Cloudflare); §9 identidade visual → Tasks 6-10 (classes `cl-*`, botão Google); §10 testes → Tasks 2, 4, 15, 16; §11 pré-requisitos Google → Task 15 (UAT); endurecimento anti-abuso (limite por IP + honeypot) → Task 16. Sem lacunas.

**Limitação conhecida (do spec):** sessões já abertas em outros dispositivos não são encerradas à força na redefinição de senha. Mantida intencionalmente.

---

## Verificação multi-agente (run wf_47d6fd1e-52d)

Revisão adversarial do plano (cobertura, bugs PHP, segurança, consistência): 26 achados, 8 confirmados.
Corrigidos no plano: coluna `google_id` no `schema.sql` (Task 1 Step 1b); `destino_seguro()` contra
barra invertida/controle (Task 5); try/catch de corrida no cadastro (Task 7); chamada oportunística de
`limpar_tokens_expirados()` (Task 2); purge do Cloudflare (Task 15 Step 3b).

## Endurecimento anti-abuso (aprovado — implementado na Task 16)

Dois achados de segurança reais (também presentes na página `/contato` atual). O usuário aprovou
implementá-los nesta entrega — ver **Task 16**. Resumo do que foi endereçado:

1. **Limite por IP no envio de e-mail (anti email-bombing).** `/criar-conta`, `/recuperar-senha` e o
   reenvio disparam e-mail para o endereço informado; o throttle de 60 s é por conta, não por IP/destino.
   Um script pode usar o domínio como relay e prejudicar a reputação do SMTP.
   *Mitigação:* limite por IP e por destino (ex.: tabela `limites_envio` ou contador com janela), e/ou
   Cloudflare Turnstile nos formulários (o Cloudflare já está na frente). Registrar IP para auditoria.

2. **Account-lockout DoS no login.** O bloqueio por 5 tentativas é por conta; sem honeypot/captcha no
   login, um atacante que conheça o e-mail (cursista ou admin) pode bloquear a conta repetidamente.
   *Mitigação:* limite/backoff por IP além do bloqueio por conta, honeypot/captcha no login, log de IP.

> Implementado na **Task 16**: tabela `limites_acao`, limite por IP em cadastro/recuperação/login e
> honeypot nos formulários de login (cursista e admin). Turnstile fica como passo opcional futuro.
