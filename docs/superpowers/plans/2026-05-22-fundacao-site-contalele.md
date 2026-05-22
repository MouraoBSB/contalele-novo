# Plano de Implementação — Fundação do Site Conta Lelê

> **Para executores agênticos:** SUB-SKILL OBRIGATÓRIA: use `superpowers:subagent-driven-development` (recomendado) ou `superpowers:executing-plans` para implementar este plano tarefa a tarefa. Os passos usam caixas de seleção (`- [ ]`) para acompanhamento.

**Objetivo:** Entregar o esqueleto do site Conta Lelê no ar — estrutura de pastas, banco de dados, roteamento de URLs limpas, tratamento de erros, layout base com a identidade da marca e fluxo de deploy via FTPS.

**Arquitetura:** PHP 8.3 puro, sem framework. Um `index.php` faz de front-controller e roteia para arquivos em `paginas/`, montando `cabeçalho + página + rodapé`. MySQL via PDO. O schema é criado no servidor por um instalador web protegido por token. Desenvolvimento local com PHP portátil; deploy em produção via FTPS com `curl`.

**Stack:** PHP 8.3, MySQL/PDO, HTML5, CSS próprio com tokens da marca, JavaScript vanilla, Apache `.htaccess`, `curl` (FTPS).

**Spec de referência:** `docs/superpowers/specs/2026-05-22-site-contalele-fundacao-vitrine-design.md`

---

## Estrutura de arquivos (criada por este plano)

```
/
├── index.php                  front-controller + roteador
├── .htaccess                  URLs limpas, HTTPS, gzip, cache, segurança
├── instalar.php               instalador web do banco (token; removido após uso)
├── config.exemplo.php          modelo de configuração (versionado)
├── config.php                  configuração real (NÃO versionado)
├── robots.txt
├── includes/
│   ├── .htaccess               Require all denied
│   ├── conexao.php             fábrica de conexão PDO
│   ├── erros.php               handler global de erros + log
│   ├── funcoes.php             helpers (escape, slug, validação, CSRF)
│   ├── seo.php                 meta tags / Open Graph / JSON-LD
│   ├── cabecalho.php           <head> + header + navegação
│   └── rodape.php              rodapé + botão WhatsApp + scripts
├── paginas/
│   ├── .htaccess               Require all denied
│   ├── inicio.php              home provisória
│   └── erro-404.php            página de erro
├── admin/
│   └── diagnostico.php         autodiagnóstico do ambiente
├── assets/
│   ├── css/brand-tokens.css    tokens da marca (cópia de deploy)
│   ├── css/style.css           estilos base do site
│   └── js/main.js              JS base (menu mobile)
├── sql/
│   ├── .htaccess               Require all denied
│   └── schema.sql              schema do banco (9 tabelas)
├── lib/
│   └── .htaccess               Require all denied
├── downloads/                  PDFs dos e-books (vazio nesta fase)
├── uploads/
│   └── .gitkeep
├── logs/
│   ├── .htaccess               Require all denied
│   └── .gitkeep
├── tests/                      testes CLI (NÃO deployados)
│   ├── correr.php              runner de testes
│   └── funcoes-teste.php       testes de includes/funcoes.php
└── deploy/
    ├── enviar.sh               script de deploy FTPS
    └── credenciais.exemplo.env modelo de credenciais de FTP
```

**Convenções deste projeto:**
- Arquivos PHP novos relevantes levam cabeçalho de autoria: `Thiago Mourão — https://github.com/MouraoBSB`.
- Todo texto (comentários, mensagens, logs) em português brasileiro.
- Indentação de 4 espaços em PHP; aspas simples por padrão.
- Identificadores de domínio em pt-BR (`gerar_slug`, `bd`, `cabecalho`).

---

## Task 1: Instalar PHP 8.3 local e verificar o ambiente remoto

**Files:**
- Nenhum arquivo do projeto criado; instala ferramenta e produz um inventário.

- [ ] **Step 1: Baixar o PHP 8.3 portátil**

Run (PowerShell):
```powershell
$url = "https://windows.php.net/downloads/releases/latest/php-8.3-nts-Win32-vs16-x64-latest.zip"
Invoke-WebRequest -Uri $url -OutFile "$env:TEMP\php83.zip"
Expand-Archive -Path "$env:TEMP\php83.zip" -DestinationPath "C:\php83" -Force
```
Expected: pasta `C:\php83` com `php.exe` dentro.

- [ ] **Step 2: Configurar o php.ini com as extensões necessárias**

Copie `C:\php83\php.ini-development` para `C:\php83\php.ini` e habilite (remova o `;` inicial) as extensões: `extension=pdo_mysql`, `extension=mysqli`, `extension=mbstring`, `extension=openssl`, `extension=curl`, `extension=fileinfo`, `extension=gd`, `extension=exif`, `extension=intl`. Defina também `extension_dir = "ext"`.

- [ ] **Step 3: Verificar a instalação**

Run:
```powershell
& "C:\php83\php.exe" --version
& "C:\php83\php.exe" -m
```
Expected: versão `PHP 8.3.x` e a lista de módulos contendo `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`.

- [ ] **Step 4: Verificar conectividade FTPS com o servidor**

Run (bash) — usa as credenciais de FTP fornecidas pelo usuário:
```bash
curl --ssl-reqd --list-only "ftp://186.209.113.101/" --user "USUARIO_FTP:SENHA_FTP"
```
Expected: lista o conteúdo atual da raiz do servidor (deve estar vazia ou quase). Registre o que existir.

- [ ] **Step 5: Verificar acesso remoto ao MySQL (informativo)**

Run:
```powershell
& "C:\php83\php.exe" -r "try { new PDO('mysql:host=186.209.113.101;dbname=cemaneto_contalele', 'USUARIO_DB', 'SENHA_DB'); echo 'CONEXAO REMOTA OK'; } catch (Throwable \$e) { echo 'SEM ACESSO REMOTO: ' . \$e->getMessage(); }"
```
Expected: provavelmente "SEM ACESSO REMOTO" — hospedagem compartilhada costuma bloquear MySQL externo. **Isso é esperado e não bloqueia nada:** o schema será criado pelo instalador web (Task 6/11), que roda no servidor e conecta em `localhost`. Apenas registre o resultado.

- [ ] **Step 6: Registrar o inventário do ambiente**

Anote em uma nota de execução: versão do PHP local, extensões confirmadas, estado da raiz do FTP, e se o MySQL remoto respondeu. Sem commit (nenhum arquivo do projeto mudou).

---

## Task 2: Estrutura de pastas e arquivos de configuração

**Files:**
- Create: `config.exemplo.php`, `config.php`
- Create: `includes/.htaccess`, `paginas/.htaccess`, `sql/.htaccess`, `logs/.htaccess`, `lib/.htaccess`
- Create: `uploads/.gitkeep`, `logs/.gitkeep`, `downloads/.gitkeep`
- Modify: `.gitignore`

- [ ] **Step 1: Criar a árvore de pastas**

Run (PowerShell):
```powershell
$pastas = "includes","paginas","admin","assets\css","assets\js","assets\img","sql","lib","downloads","uploads","logs","tests","deploy"
foreach ($p in $pastas) { New-Item -ItemType Directory -Force -Path $p | Out-Null }
```
Expected: todas as pastas criadas.

- [ ] **Step 2: Criar `config.exemplo.php`**

```php
<?php
/**
 * Configuração do site Conta Lelê — MODELO.
 * Copie este arquivo para config.php e preencha com os valores reais.
 * config.php NÃO é versionado.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

return [
    'db' => [
        'host'    => 'localhost',
        'nome'    => 'NOME_DO_BANCO',
        'usuario' => 'USUARIO_DO_BANCO',
        'senha'   => 'SENHA_DO_BANCO',
        'charset' => 'utf8mb4',
    ],
    'site' => [
        'url'      => 'https://contalele.com.br',
        'ambiente' => 'producao', // 'producao' ou 'desenvolvimento'
    ],
    'instalador' => [
        // Token aleatório longo que protege instalar.php e admin/diagnostico.php.
        'token'       => 'TOKEN_ALEATORIO_LONGO',
        // E-mail do primeiro usuário do painel administrativo.
        'admin_email' => 'contato@contalele.com.br',
    ],
];
```

- [ ] **Step 3: Criar `config.php` real**

Copie `config.exemplo.php` para `config.php` e preencha com os valores reais fornecidos pelo usuário: credenciais do banco `cemaneto_contalele`, e um token de instalador gerado aleatoriamente.

Gere o token:
```powershell
& "C:\php83\php.exe" -r "echo bin2hex(random_bytes(32));"
```
`config.php` NÃO é versionado (já coberto pelo `.gitignore`).

- [ ] **Step 4: Criar os `.htaccess` de bloqueio**

Crie `includes/.htaccess`, `paginas/.htaccess`, `sql/.htaccess`, `logs/.htaccess` e `lib/.htaccess`, todos com o mesmo conteúdo:
```apache
# Pasta de uso interno — sem acesso direto pela web.
Require all denied
```

- [ ] **Step 5: Criar os marcadores de pasta**

Crie `uploads/.gitkeep`, `logs/.gitkeep` e `downloads/.gitkeep` como arquivos vazios (para versionar as pastas vazias).

- [ ] **Step 6: Atualizar o `.gitignore`**

Adicione ao final de `.gitignore`:
```
# Credenciais de deploy
deploy/credenciais.env

# PHP portátil (caso extraído dentro do projeto)
/php83/
```

- [ ] **Step 7: Commit**

```bash
git add config.exemplo.php includes/.htaccess paginas/.htaccess sql/.htaccess logs/.htaccess lib/.htaccess uploads/.gitkeep logs/.gitkeep downloads/.gitkeep .gitignore
git commit -m "Cria estrutura de pastas e configuração base"
```

---

## Task 3: Funções utilitárias com testes (TDD)

**Files:**
- Create: `includes/funcoes.php`
- Test: `tests/correr.php`, `tests/funcoes-teste.php`

- [ ] **Step 1: Criar o runner de testes**

`tests/correr.php`:
```php
<?php
/**
 * Runner de testes CLI do site Conta Lelê.
 * Uso: php tests/correr.php
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

$GLOBALS['placar'] = ['ok' => 0, 'falhas' => 0];

function afirmar(bool $condicao, string $descricao): void
{
    if ($condicao) {
        $GLOBALS['placar']['ok']++;
        echo "  ok    — {$descricao}\n";
    } else {
        $GLOBALS['placar']['falhas']++;
        echo "  FALHOU — {$descricao}\n";
    }
}

function afirmar_igual($esperado, $obtido, string $descricao): void
{
    $passou = $esperado === $obtido;
    $detalhe = $passou ? '' : ' (esperado ' . var_export($esperado, true)
        . ', obtido ' . var_export($obtido, true) . ')';
    afirmar($passou, $descricao . $detalhe);
}

foreach (glob(__DIR__ . '/*-teste.php') as $arquivo) {
    echo "\n[" . basename($arquivo) . "]\n";
    require $arquivo;
}

echo "\n----------\n";
echo "Total: {$GLOBALS['placar']['ok']} ok, {$GLOBALS['placar']['falhas']} falha(s)\n";
exit($GLOBALS['placar']['falhas'] > 0 ? 1 : 0);
```

- [ ] **Step 2: Escrever os testes que falham**

`tests/funcoes-teste.php`:
```php
<?php
declare(strict_types=1);

require __DIR__ . '/../includes/funcoes.php';

// e() — escape de HTML
afirmar_igual('&lt;b&gt;', e('<b>'), 'e() escapa tags HTML');
afirmar_igual('João &amp; Cia', e('João & Cia'), 'e() escapa & preservando acentos UTF-8');

// gerar_slug()
afirmar_igual('cordel-monstruoso', gerar_slug('Cordel Monstruoso'), 'gerar_slug() simples');
afirmar_igual('os-dois-cabritos', gerar_slug('Os Dois Cabritos'), 'gerar_slug() com artigos');
afirmar_igual('maria-nao-vai', gerar_slug('Maria não vai!'), 'gerar_slug() remove acento e pontuação');
afirmar_igual('a-lele', gerar_slug('  A   Lelê  '), 'gerar_slug() colapsa espaços');

// validar_email()
afirmar(validar_email('contato@contalele.com.br'), 'validar_email() aceita e-mail válido');
afirmar(!validar_email('invalido'), 'validar_email() rejeita texto sem @');
afirmar(!validar_email(''), 'validar_email() rejeita vazio');

// limpar_texto()
afirmar_igual('Oi Lelê', limpar_texto("  Oi   Lelê  "), 'limpar_texto() apara e colapsa espaços');
afirmar_igual('texto', limpar_texto('<script>texto</script>'), 'limpar_texto() remove tags');
```

- [ ] **Step 3: Rodar os testes e ver falhar**

Run:
```powershell
& "C:\php83\php.exe" tests\correr.php
```
Expected: erro fatal — `includes/funcoes.php` ainda não existe / funções indefinidas.

- [ ] **Step 4: Implementar `includes/funcoes.php`**

```php
<?php
/**
 * Funções utilitárias do site Conta Lelê.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Escapa um valor para saída segura em HTML (anti-XSS).
 */
function e(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Gera um slug amigável a partir de um texto (minúsculo, sem acento, com hífens).
 * Usa a extensão intl (Normalizer) para remover acentos de forma confiável,
 * com iconv como alternativa caso intl não esteja disponível.
 */
function gerar_slug(string $texto): string
{
    $texto = trim($texto);
    if (class_exists('Normalizer')) {
        $texto = Normalizer::normalize($texto, Normalizer::FORM_D) ?: $texto;
        $texto = preg_replace('/\p{Mn}/u', '', $texto); // remove marcas de acento
    } else {
        $transliterado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($transliterado !== false) {
            $texto = $transliterado;
        }
    }
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim($texto, '-');
}

/**
 * Valida um endereço de e-mail.
 */
function validar_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Limpa um texto de entrada: remove tags, apara e colapsa espaços.
 */
function limpar_texto(string $valor): string
{
    $valor = strip_tags($valor);
    $valor = preg_replace('/\s+/u', ' ', $valor);
    return trim($valor);
}

/**
 * Devolve o token CSRF da sessão, criando-o se necessário.
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Valida um token CSRF recebido contra o da sessão (comparação segura).
 */
function csrf_validar(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return !empty($_SESSION['csrf'])
        && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}
```

- [ ] **Step 5: Rodar os testes e ver passar**

Run:
```powershell
& "C:\php83\php.exe" tests\correr.php
```
Expected: `Total: 11 ok, 0 falha(s)` e código de saída 0.

- [ ] **Step 6: Adicionar teste de CSRF**

Acrescente ao final de `tests/funcoes-teste.php`:
```php
// CSRF — geração e validação
$t = csrf_token();
afirmar(strlen($t) === 64, 'csrf_token() gera token de 64 caracteres');
afirmar(csrf_token() === $t, 'csrf_token() é estável na mesma sessão');
afirmar(csrf_validar($t), 'csrf_validar() aceita o token correto');
afirmar(!csrf_validar('errado'), 'csrf_validar() rejeita token errado');
afirmar(!csrf_validar(null), 'csrf_validar() rejeita null');
```

- [ ] **Step 7: Rodar e ver passar**

Run:
```powershell
& "C:\php83\php.exe" tests\correr.php
```
Expected: `Total: 16 ok, 0 falha(s)`.

- [ ] **Step 8: Commit**

```bash
git add includes/funcoes.php tests/correr.php tests/funcoes-teste.php
git commit -m "Adiciona funções utilitárias com testes"
```

---

## Task 4: Tratamento de erros e logging

**Files:**
- Create: `includes/erros.php`
- Test: `tests/erros-teste.php`

- [ ] **Step 1: Escrever o teste que falha**

`tests/erros-teste.php`:
```php
<?php
declare(strict_types=1);

require __DIR__ . '/../includes/erros.php';

$dir = sys_get_temp_dir() . '/cl-logs-' . uniqid();
mkdir($dir);
registrar_log('Mensagem de teste', ['chave' => 'valor'], $dir);

$arquivos = glob($dir . '/erro-*.log');
afirmar(count($arquivos) === 1, 'registrar_log() cria um arquivo de log do dia');
$conteudo = file_get_contents($arquivos[0]);
afirmar(strpos($conteudo, 'Mensagem de teste') !== false, 'registrar_log() grava a mensagem');
afirmar(strpos($conteudo, 'valor') !== false, 'registrar_log() grava o contexto');
```

- [ ] **Step 2: Rodar e ver falhar**

Run:
```powershell
& "C:\php83\php.exe" tests\correr.php
```
Expected: falha — `includes/erros.php` / `registrar_log()` indefinida.

- [ ] **Step 3: Implementar `includes/erros.php`**

```php
<?php
/**
 * Tratamento global de erros e logging estruturado do site Conta Lelê.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

if (!defined('CL_RAIZ')) {
    define('CL_RAIZ', dirname(__DIR__));
}

/**
 * Grava uma entrada estruturada no log do dia.
 */
function registrar_log(string $mensagem, array $contexto = [], ?string $dir = null): void
{
    $dir = $dir ?? CL_RAIZ . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $arquivo = $dir . '/erro-' . date('Y-m-d') . '.log';
    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $mensagem;
    if ($contexto !== []) {
        $linha .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    @file_put_contents($arquivo, $linha . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * Exibe uma página de erro amigável e encerra a execução.
 */
function pagina_erro(int $codigo = 500): void
{
    if (!headers_sent()) {
        http_response_code($codigo);
    }
    $titulo = $codigo === 404 ? 'Página não encontrada' : 'Algo deu errado';
    $arquivo = CL_RAIZ . '/paginas/erro-' . ($codigo === 404 ? '404' : '500') . '.php';
    if (is_file($arquivo)) {
        require $arquivo;
    } else {
        echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8">';
        echo '<title>' . $titulo . '</title><p>' . $titulo . '.</p>';
    }
    exit;
}

/**
 * Ativa os handlers globais de erro/exceção conforme o ambiente.
 */
function ativar_tratamento_erros(string $ambiente = 'producao'): void
{
    $producao = $ambiente !== 'desenvolvimento';

    ini_set('display_errors', $producao ? '0' : '1');
    error_reporting(E_ALL);

    set_exception_handler(static function (Throwable $e) use ($producao): void {
        registrar_log('Exceção não tratada: ' . $e->getMessage(), [
            'arquivo' => $e->getFile() . ':' . $e->getLine(),
            'uri'     => $_SERVER['REQUEST_URI'] ?? 'cli',
        ]);
        if ($producao) {
            pagina_erro(500);
        } else {
            echo '<pre>' . $e . '</pre>';
        }
    });

    set_error_handler(static function (int $nivel, string $msg, string $arq, int $linha): bool {
        if (!(error_reporting() & $nivel)) {
            return false;
        }
        throw new ErrorException($msg, 0, $nivel, $arq, $linha);
    });

    register_shutdown_function(static function () use ($producao): void {
        $erro = error_get_last();
        if ($erro !== null && in_array($erro['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            registrar_log('Erro fatal: ' . $erro['message'], [
                'arquivo' => $erro['file'] . ':' . $erro['line'],
            ]);
            if ($producao && !headers_sent()) {
                pagina_erro(500);
            }
        }
    });
}
```

- [ ] **Step 4: Rodar e ver passar**

Run:
```powershell
& "C:\php83\php.exe" tests\correr.php
```
Expected: os 3 testes novos de erro passam (`Total: 19 ok, 0 falha(s)`).

- [ ] **Step 5: Commit**

```bash
git add includes/erros.php tests/erros-teste.php
git commit -m "Adiciona tratamento global de erros e logging"
```

---

## Task 5: Conexão com o banco de dados

**Files:**
- Create: `includes/conexao.php`

- [ ] **Step 1: Implementar `includes/conexao.php`**

```php
<?php
/**
 * Fábrica de conexão PDO com o banco de dados do site Conta Lelê.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Devolve a conexão PDO compartilhada, criando-a no primeiro uso.
 *
 * @param array|null $config Configuração de 'db' (opcional; usado em testes).
 */
function bd(?array $config = null): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if ($config === null) {
        $raiz = defined('CL_RAIZ') ? CL_RAIZ : dirname(__DIR__);
        $todo = require $raiz . '/config.php';
        $config = $todo['db'];
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['nome'],
        $config['charset']
    );

    try {
        $pdo = new PDO($dsn, $config['usuario'], $config['senha'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        if (function_exists('registrar_log')) {
            registrar_log('Falha ao conectar ao banco', ['erro' => $e->getMessage()]);
        }
        throw new RuntimeException('Não foi possível conectar ao banco de dados.', 0, $e);
    }

    return $pdo;
}
```

- [ ] **Step 2: Verificar a sintaxe**

Run:
```powershell
& "C:\php83\php.exe" -l includes\conexao.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add includes/conexao.php
git commit -m "Adiciona fábrica de conexão PDO"
```

---

## Task 6: Schema do banco e instalador web

**Files:**
- Create: `sql/schema.sql`
- Create: `instalar.php`

- [ ] **Step 1: Criar `sql/schema.sql`**

```sql
-- Schema do site Conta Lelê — Fase 1
-- Thiago Mourão — https://github.com/MouraoBSB
-- Codificação: utf8mb4 / InnoDB

CREATE TABLE IF NOT EXISTS usuarios_admin (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome                 VARCHAR(120) NOT NULL,
    email                VARCHAR(160) NOT NULL,
    senha_hash           VARCHAR(255) NOT NULL,
    precisa_trocar_senha TINYINT(1)   NOT NULL DEFAULT 1,
    tentativas_login     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_ate        DATETIME     NULL,
    ultimo_acesso        DATETIME     NULL,
    ativo                TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cordeis (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo        VARCHAR(160) NOT NULL,
    slug          VARCHAR(180) NOT NULL,
    sinopse       TEXT         NOT NULL,
    video_youtube VARCHAR(255) NULL,
    imagem_capa   VARCHAR(255) NULL,
    ordem         INT          NOT NULL DEFAULT 0,
    publicado     TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cordeis_slug (slug),
    KEY ix_cordeis_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ebooks (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo        VARCHAR(160) NOT NULL,
    slug          VARCHAR(180) NOT NULL,
    sinopse       TEXT         NOT NULL,
    arquivo_pdf   VARCHAR(255) NULL,
    imagem_capa   VARCHAR(255) NULL,
    ordem         INT          NOT NULL DEFAULT 0,
    publicado     TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ebooks_slug (slug),
    KEY ix_ebooks_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS noticias (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo          VARCHAR(200) NOT NULL,
    veiculo         VARCHAR(120) NULL,
    url             VARCHAR(255) NOT NULL,
    imagem          VARCHAR(255) NULL,
    data_publicacao DATE         NULL,
    ordem           INT          NOT NULL DEFAULT 0,
    publicado       TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_noticias_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS depoimentos (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    autor     VARCHAR(120) NOT NULL,
    texto     TEXT         NOT NULL,
    foto      VARCHAR(255) NULL,
    ordem     INT          NOT NULL DEFAULT 0,
    publicado TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_depoimentos_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS festivais (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo    VARCHAR(200) NOT NULL,
    descricao TEXT         NOT NULL,
    ano       SMALLINT     NULL,
    videos    TEXT         NULL,
    imagem    VARCHAR(255) NULL,
    ordem     INT          NOT NULL DEFAULT 0,
    publicado TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_festivais_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS premios (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo    VARCHAR(200) NOT NULL,
    ano       SMALLINT     NULL,
    ordem     INT          NOT NULL DEFAULT 0,
    publicado TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY ix_premios_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mensagens_contato (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome      VARCHAR(120) NOT NULL,
    email     VARCHAR(160) NOT NULL,
    telefone  VARCHAR(40)  NULL,
    assunto   VARCHAR(160) NULL,
    mensagem  TEXT         NOT NULL,
    lida      TINYINT(1)   NOT NULL DEFAULT 0,
    ip        VARCHAR(45)  NULL,
    criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_mensagens_lida (lida, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes (
    chave     VARCHAR(60)  NOT NULL,
    valor     TEXT         NULL,
    descricao VARCHAR(200) NULL,
    PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Criar `instalar.php`**

```php
<?php
/**
 * Instalador web do banco de dados do site Conta Lelê.
 * Cria o schema, semeia configurações e o primeiro usuário admin.
 * Protegido por token. REMOVA este arquivo após o uso.
 *
 * Uso: https://contalele.com.br/instalar.php?token=SEU_TOKEN
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
echo '<title>Instalador — Conta Lelê</title>';
echo '<body style="font-family:sans-serif;max-width:640px;margin:40px auto;padding:0 16px">';
echo '<h1>Instalador do banco — Conta Lelê</h1>';

$pdo = bd($config['db']);

// 1. Não reinstala por cima de um banco já populado.
$jaInstalado = $pdo->query("SHOW TABLES LIKE 'usuarios_admin'")->fetch();
if ($jaInstalado) {
    $temUsuario = (int) $pdo->query('SELECT COUNT(*) FROM usuarios_admin')->fetchColumn();
    if ($temUsuario > 0) {
        echo '<p><strong>O banco já está instalado.</strong> Nada foi alterado.</p>';
        echo '<p>Remova este arquivo (<code>instalar.php</code>) do servidor.</p>';
        exit;
    }
}

// 2. Cria o schema.
$sql = file_get_contents(__DIR__ . '/sql/schema.sql');
$pdo->exec($sql);
echo '<p>Tabelas criadas.</p>';

// 3. Semeia as configurações padrão.
$padroes = [
    ['whatsapp',        '5561991938603',                'Número de WhatsApp (só dígitos, com DDI)'],
    ['email_contato',   'contato@contalele.com.br',     'E-mail exibido no site'],
    ['instagram',       'https://www.instagram.com/contalele/', 'URL do Instagram'],
    ['facebook',        'https://www.facebook.com/leticia.mourao.585', 'URL do Facebook'],
    ['youtube',         'https://www.youtube.com/c/ContaLel%C3%AA', 'URL do canal no YouTube'],
    ['smtp_host',       '',                             'Servidor SMTP'],
    ['smtp_porta',      '587',                          'Porta SMTP'],
    ['smtp_usuario',    '',                             'Usuário SMTP'],
    ['smtp_senha',      '',                             'Senha SMTP'],
    ['smtp_remetente',  'contato@contalele.com.br',     'E-mail remetente'],
    ['smtp_seguranca',  'tls',                          'Criptografia SMTP (tls/ssl/nenhuma)'],
];
$ins = $pdo->prepare(
    'INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES (?, ?, ?)'
);
foreach ($padroes as $linha) {
    $ins->execute($linha);
}
echo '<p>Configurações padrão semeadas.</p>';

// 4. Cria o primeiro usuário admin com senha provisória aleatória.
$senhaProvisoria = bin2hex(random_bytes(6)); // 12 caracteres
$hash = password_hash($senhaProvisoria, PASSWORD_DEFAULT);
$pdo->prepare(
    'INSERT INTO usuarios_admin (nome, email, senha_hash, precisa_trocar_senha)
     VALUES (?, ?, ?, 1)'
)->execute(['Administrador', $config['instalador']['admin_email'], $hash]);

echo '<h2>Usuário administrador criado</h2>';
echo '<p>E-mail: <code>' . e($config['instalador']['admin_email']) . '</code></p>';
echo '<p>Senha provisória: <code style="font-size:1.2em">' . e($senhaProvisoria) . '</code></p>';
echo '<p><strong>Anote esta senha agora.</strong> Ela só aparece uma vez '
   . 'e deverá ser trocada no primeiro acesso ao painel.</p>';
echo '<hr><p><strong>Importante:</strong> remova o arquivo '
   . '<code>instalar.php</code> do servidor agora.</p>';
```

- [ ] **Step 3: Verificar a sintaxe**

Run:
```powershell
& "C:\php83\php.exe" -l instalar.php
```
Expected: `No syntax errors detected`. (A execução real ocorre na Task 11, no servidor.)

- [ ] **Step 4: Commit**

```bash
git add sql/schema.sql instalar.php
git commit -m "Adiciona schema do banco e instalador web"
```

---

## Task 7: Roteador e `.htaccess` principal

**Files:**
- Create: `index.php`, `.htaccess`, `robots.txt`

- [ ] **Step 1: Criar `index.php`**

```php
<?php
/**
 * Front-controller do site Conta Lelê.
 * Resolve a rota e monta cabeçalho + página + rodapé.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', __DIR__);

// Servidor embutido do PHP (php -S): serve arquivos estáticos reais direto.
if (PHP_SAPI === 'cli-server') {
    $arquivoEstatico = CL_RAIZ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($arquivoEstatico)) {
        return false;
    }
}

$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/seo.php';

ativar_tratamento_erros($config['site']['ambiente']);

// Mapa de rotas: caminho da URL => arquivo em paginas/.
$rotas = [
    ''          => 'inicio',
    'sobre'     => 'sobre',
    'contacao'  => 'contacao',
    'cordeis'   => 'cordeis',
    'livros'    => 'livros',
    'festivais' => 'festivais',
    'cursos'    => 'cursos',
    'contato'   => 'contato',
];

$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$rota = trim(rawurldecode($caminho), '/');

$pagina = $rotas[$rota] ?? null;
if ($pagina === null || !is_file(CL_RAIZ . '/paginas/' . $pagina . '.php')) {
    http_response_code(404);
    $pagina = 'erro-404';
}

// Variáveis de SEO disponíveis para cada página (sobrescritas dentro dela).
$seo = seo_padrao($config['site']['url']);

require CL_RAIZ . '/includes/cabecalho.php';
require CL_RAIZ . '/paginas/' . $pagina . '.php';
require CL_RAIZ . '/includes/rodape.php';
```

- [ ] **Step 2: Criar o `.htaccess` principal**

```apache
# Site Conta Lelê — regras de servidor
AddDefaultCharset UTF-8
Options -Indexes

# Força HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# URLs limpas → front-controller (arquivos e pastas reais passam direto)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]

# Bloqueia arquivos sensíveis
<FilesMatch "^(config\.php|config\.exemplo\.php|.*\.local\.php)$">
    Require all denied
</FilesMatch>

# Cabeçalhos de segurança
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Compressão
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript application/json image/svg+xml
</IfModule>

# Cache de assets estáticos
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>

ErrorDocument 404 /index.php
```

- [ ] **Step 3: Criar `robots.txt`**

```
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /instalar.php

Sitemap: https://contalele.com.br/sitemap.xml
```

- [ ] **Step 4: Commit**

```bash
git add index.php .htaccess robots.txt
git commit -m "Adiciona roteador front-controller e regras do .htaccess"
```

---

## Task 8: Layout base — SEO, cabeçalho, rodapé, CSS e JS

**Files:**
- Create: `includes/seo.php`, `includes/cabecalho.php`, `includes/rodape.php`
- Create: `assets/css/brand-tokens.css`, `assets/css/style.css`, `assets/js/main.js`

- [ ] **Step 1: Criar `includes/seo.php`**

```php
<?php
/**
 * Helpers de SEO do site Conta Lelê — meta tags, Open Graph e JSON-LD.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Devolve o conjunto padrão de dados de SEO.
 */
function seo_padrao(string $urlBase): array
{
    return [
        'titulo'    => 'Conta Lelê — Contação de histórias, cordéis e cursos',
        'descricao' => 'A Lelê conta e escreve histórias de verdade. '
            . 'Contação de histórias, cordéis, livros e cursos para professores e mediadores.',
        'url_base'  => rtrim($urlBase, '/'),
        'imagem'    => rtrim($urlBase, '/') . '/assets/img/og-padrao.jpg',
    ];
}

/**
 * Renderiza as meta tags de SEO no <head>.
 */
function seo_render(array $seo): void
{
    $url = $seo['url_base'] . ($_SERVER['REQUEST_URI'] ?? '/');
    echo '<title>' . e($seo['titulo']) . '</title>' . "\n";
    echo '<meta name="description" content="' . e($seo['descricao']) . '">' . "\n";
    echo '<link rel="canonical" href="' . e($url) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . e($seo['titulo']) . '">' . "\n";
    echo '<meta property="og:description" content="' . e($seo['descricao']) . '">' . "\n";
    echo '<meta property="og:url" content="' . e($url) . '">' . "\n";
    echo '<meta property="og:image" content="' . e($seo['imagem']) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}
```

- [ ] **Step 2: Copiar os assets da marca (tokens CSS e logo)**

Copie o `brand-tokens.css` da raiz para `assets/css/` (cópia de deploy — o arquivo da raiz permanece como fonte de design) e o logo preto da marca para `assets/img/logo.png` (o fundo do cabeçalho é creme claro, então usa-se a versão preta).

Run (PowerShell):
```powershell
Copy-Item brand-tokens.css assets\css\brand-tokens.css -Force
Copy-Item "Identidade Visual\Logo\Logo Preta.png" assets\img\logo.png -Force
```

- [ ] **Step 3: Criar `assets/css/style.css`**

```css
/* Site Conta Lelê — estilos base
   Thiago Mourão — https://github.com/MouraoBSB
   Usa os tokens de brand-tokens.css */

*, *::before, *::after { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }
body { margin: 0; min-height: 100vh; }
img { max-width: 100%; height: auto; display: block; }
a { color: var(--cl-accent); }
h1, h2, h3 { text-wrap: balance; letter-spacing: -0.02em; }
p { text-wrap: pretty; }

/* Acessibilidade */
.cl-skip {
  position: absolute; left: -999px;
  background: var(--cl-ink); color: var(--cl-bg-3);
  padding: 8px 16px; border-radius: var(--cl-pill); z-index: 1000;
}
.cl-skip:focus { left: 16px; top: 16px; }
:focus-visible { outline: 3px solid var(--cl-accent); outline-offset: 2px; }
@media (prefers-reduced-motion: reduce) {
  * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}

/* Layout */
.cl-conteudo { max-width: 1180px; margin: 0 auto; padding: 0 16px; }
@media (min-width: 900px) { .cl-conteudo { padding: 0 32px; } }

/* Cabeçalho */
.cl-cabecalho {
  background: var(--cl-bg);
  border-bottom: 1px solid var(--cl-border);
  position: sticky; top: 0; z-index: 100;
}
.cl-cabecalho__interno {
  display: flex; align-items: center; justify-content: space-between;
  gap: 16px; padding-top: 12px; padding-bottom: 12px;
}
.cl-logo img { height: 40px; width: auto; }
.cl-nav { display: none; }
.cl-nav a {
  color: var(--cl-ink); text-decoration: none;
  font-weight: 700; font-size: 15px;
}
.cl-nav a:hover { color: var(--cl-accent); }
.cl-menu-btn {
  background: none; border: 0; cursor: pointer;
  padding: 8px; color: var(--cl-ink);
}
@media (min-width: 900px) {
  .cl-nav { display: flex; gap: 24px; align-items: center; }
  .cl-menu-btn { display: none; }
}
.cl-nav--aberto {
  display: flex; flex-direction: column; gap: 16px;
  padding: 16px; border-top: 1px solid var(--cl-border);
}

/* Rodapé */
.cl-rodape {
  background: var(--cl-ink); color: var(--cl-bg);
  padding: 48px 0; margin-top: 72px;
}
.cl-rodape a { color: var(--cl-bg-3); }

/* Botão flutuante de WhatsApp */
.cl-whats {
  position: fixed; right: 16px; bottom: 16px; z-index: 200;
  display: inline-flex; align-items: center; justify-content: center;
  width: 56px; height: 56px; border-radius: var(--cl-pill);
  background: #25d366; color: #fff;
  box-shadow: var(--cl-shadow-lg); text-decoration: none;
}

/* Botões */
.cl-btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 12px 24px; border-radius: var(--cl-pill);
  font-family: var(--cl-font-ui); font-weight: 800; font-size: 15px;
  border: 1.5px solid transparent; cursor: pointer; text-decoration: none;
  transition: transform .12s, filter .12s;
}
.cl-btn:hover { transform: translateY(-1px); filter: brightness(1.05); }
.cl-btn-primary { background: var(--cl-ink); color: var(--cl-bg-3); }
.cl-btn-yellow  { background: var(--cl-bg-3); color: var(--cl-ink); border-color: var(--cl-ink); }
```

- [ ] **Step 4: Criar `assets/js/main.js`**

```javascript
/* Site Conta Lelê — JavaScript base
   Thiago Mourão — https://github.com/MouraoBSB */

(function () {
  'use strict';

  // Alterna o menu de navegação no mobile.
  var botao = document.querySelector('.cl-menu-btn');
  var nav = document.querySelector('.cl-nav');

  if (botao && nav) {
    botao.addEventListener('click', function () {
      var aberto = nav.classList.toggle('cl-nav--aberto');
      botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });
  }
})();
```

- [ ] **Step 5: Criar `includes/cabecalho.php`**

```php
<?php
/**
 * Cabeçalho compartilhado do site Conta Lelê.
 * Thiago Mourão — https://github.com/MouraoBSB
 *
 * @var array $seo  Dados de SEO (definidos em index.php, ajustáveis na página).
 */

declare(strict_types=1);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php seo_render($seo); ?>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Fredoka+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<a class="cl-skip" href="#conteudo">Pular para o conteúdo</a>
<header class="cl-cabecalho">
    <div class="cl-conteudo cl-cabecalho__interno">
        <a class="cl-logo" href="/" aria-label="Conta Lelê — página inicial">
            <img src="/assets/img/logo.png" alt="Conta Lelê">
        </a>
        <button class="cl-menu-btn" type="button" aria-expanded="false" aria-label="Abrir menu">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <nav class="cl-nav" aria-label="Navegação principal">
            <a href="/">Início</a>
            <a href="/sobre">A Lelê</a>
            <a href="/contacao">Contação de Histórias</a>
            <a href="/cordeis">Cordéis</a>
            <a href="/livros">Livros</a>
            <a href="/contato">Contato</a>
            <a class="cl-btn cl-btn-primary" href="/cursos">Cursos</a>
        </nav>
    </div>
</header>
<main id="conteudo">
```

- [ ] **Step 6: Criar `includes/rodape.php`**

```php
<?php
/**
 * Rodapé compartilhado do site Conta Lelê.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);
?>
</main>
<footer class="cl-rodape">
    <div class="cl-conteudo">
        <p><strong>Conta Lelê</strong> — Contação de histórias, cordéis e cursos.</p>
        <p>Planaltina-DF · <a href="https://wa.me/5561991938603">WhatsApp (61) 99193-8603</a></p>
        <nav aria-label="Links do rodapé">
            <a href="/festivais">Festivais &amp; Imprensa</a> ·
            <a href="https://www.youtube.com/c/ContaLel%C3%AA">Canal no YouTube</a> ·
            <a href="/contato">Contato</a>
        </nav>
        <p><small>&copy; <?= date('Y') ?> Conta Lelê. Todos os direitos reservados.</small></p>
    </div>
</footer>
<a class="cl-whats" href="https://wa.me/5561991938603" target="_blank" rel="noopener"
   aria-label="Falar no WhatsApp">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2a10 10 0 0 0-8.6 15l-1.4 5 5.2-1.4A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20zm4.4-6c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6.5 6.5 0 0 1-3.2-2.8c-.1-.2 0-.4.1-.5l.4-.5c.1-.1.1-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.5-.4h-.5a1 1 0 0 0-.7.3c-.7.7-1 1.6-.9 2.6.3 2.5 2.6 5.2 5.7 6.3 2 .7 2.8.5 3.4.4.7-.1 1.4-.7 1.6-1.3.2-.6.2-1.1.1-1.2l-.4-.1z"/>
    </svg>
</a>
<script src="/assets/js/main.js" defer></script>
</body>
</html>
```

- [ ] **Step 7: Verificar a sintaxe**

Run:
```powershell
& "C:\php83\php.exe" -l includes\seo.php
& "C:\php83\php.exe" -l includes\cabecalho.php
& "C:\php83\php.exe" -l includes\rodape.php
```
Expected: `No syntax errors detected` nos três.

- [ ] **Step 8: Commit**

```bash
git add includes/seo.php includes/cabecalho.php includes/rodape.php assets/
git commit -m "Adiciona layout base, SEO, CSS e JS do site"
```

---

## Task 9: Página inicial provisória e página 404

**Files:**
- Create: `paginas/inicio.php`, `paginas/erro-404.php`, `paginas/erro-500.php`

- [ ] **Step 1: Criar `paginas/inicio.php`**

```php
<?php
/**
 * Página inicial — versão provisória da fundação.
 * Será substituída pelo conteúdo completo no Plano 2 (Vitrine).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);
?>
<section class="cl-conteudo" style="padding:72px 16px;text-align:center">
    <p class="cl-eyebrow">Antes do livro vem o som</p>
    <h1 style="font-size:clamp(32px,6vw,64px);font-weight:900">
        Eu sou a <em style="font-family:var(--cl-font-display);font-style:normal;color:var(--cl-accent)">Lelê</em>!
        Conto e escrevo histórias pra você.
    </h1>
    <p style="max-width:520px;margin:16px auto;font-size:18px;color:var(--cl-ink-dim)">
        O novo site da Conta Lelê está sendo preparado com muito carinho.
        Em breve, todas as histórias por aqui.
    </p>
    <p>
        <a class="cl-btn cl-btn-primary" href="/contato">Falar com a Lelê</a>
    </p>
</section>
```

- [ ] **Step 2: Criar `paginas/erro-404.php`**

```php
<?php
/**
 * Página de erro 404.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);
?>
<section class="cl-conteudo" style="padding:72px 16px;text-align:center">
    <p class="cl-eyebrow">Erro 404</p>
    <h1 style="font-size:clamp(28px,5vw,48px);font-weight:900">
        Essa página foi <em style="font-family:var(--cl-font-display);font-style:normal;color:var(--cl-accent)">contar histórias</em> em outro lugar.
    </h1>
    <p style="color:var(--cl-ink-dim)">Não encontramos o que você procura.</p>
    <p><a class="cl-btn cl-btn-primary" href="/">Voltar ao início</a></p>
</section>
```

- [ ] **Step 3: Criar `paginas/erro-500.php`**

```php
<?php
/**
 * Página de erro 500.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Algo deu errado — Conta Lelê</title>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<section class="cl-conteudo" style="padding:72px 16px;text-align:center">
    <h1 style="font-weight:900">Algo deu errado por aqui.</h1>
    <p style="color:var(--cl-ink-dim)">
        Já registramos o problema. Tente novamente em instantes.
    </p>
    <p><a class="cl-btn cl-btn-primary" href="/">Voltar ao início</a></p>
</section>
</body>
</html>
```

- [ ] **Step 4: Testar localmente com o servidor embutido do PHP**

Run (PowerShell, em um terminal separado):
```powershell
& "C:\php83\php.exe" -S localhost:8000 index.php
```
Abra `http://localhost:8000/` e `http://localhost:8000/rota-inexistente`.
Expected: a home provisória renderiza com a marca aplicada; a rota inexistente mostra a página 404. (As partes que usam o banco ainda não são exercidas nesta fase.)

- [ ] **Step 5: Commit**

```bash
git add paginas/inicio.php paginas/erro-404.php paginas/erro-500.php
git commit -m "Adiciona home provisória e páginas de erro"
```

---

## Task 10: Autodiagnóstico do ambiente

**Files:**
- Create: `admin/diagnostico.php`

- [ ] **Step 1: Criar `admin/diagnostico.php`**

```php
<?php
/**
 * Autodiagnóstico do ambiente do site Conta Lelê.
 * Protegido pelo token do instalador.
 * Uso: https://contalele.com.br/admin/diagnostico.php?token=SEU_TOKEN
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';

ativar_tratamento_erros($config['site']['ambiente']);
header('Content-Type: text/html; charset=utf-8');

$token = $_GET['token'] ?? '';
if (!hash_equals($config['instalador']['token'], (string) $token)) {
    http_response_code(403);
    exit('Acesso negado.');
}

/** Lista de verificações: [rótulo, ok(bool), detalhe]. */
$checagens = [];

$checagens[] = ['Versão do PHP', version_compare(PHP_VERSION, '8.1', '>='), PHP_VERSION];

foreach (['pdo_mysql', 'mbstring', 'openssl', 'curl', 'fileinfo', 'gd'] as $ext) {
    $checagens[] = ['Extensão ' . $ext, extension_loaded($ext), extension_loaded($ext) ? 'carregada' : 'AUSENTE'];
}

foreach (['uploads', 'logs'] as $pasta) {
    $caminho = CL_RAIZ . '/' . $pasta;
    $checagens[] = ['Pasta ' . $pasta . ' gravável', is_writable($caminho), $caminho];
}

try {
    $pdo = bd($config['db']);
    $checagens[] = ['Conexão com o banco', true, 'conectado'];
    $tabelas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $esperadas = ['usuarios_admin', 'cordeis', 'ebooks', 'noticias', 'depoimentos',
        'festivais', 'premios', 'mensagens_contato', 'configuracoes'];
    $faltando = array_diff($esperadas, $tabelas);
    $checagens[] = ['Tabelas do banco', $faltando === [],
        $faltando === [] ? count($esperadas) . ' tabelas presentes' : 'faltando: ' . implode(', ', $faltando)];
} catch (Throwable $e) {
    $checagens[] = ['Conexão com o banco', false, $e->getMessage()];
}

echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8">';
echo '<title>Diagnóstico — Conta Lelê</title>';
echo '<body style="font-family:sans-serif;max-width:680px;margin:40px auto;padding:0 16px">';
echo '<h1>Diagnóstico do ambiente</h1><ul style="line-height:1.8;list-style:none;padding:0">';
$tudoOk = true;
foreach ($checagens as [$rotulo, $ok, $detalhe]) {
    $tudoOk = $tudoOk && $ok;
    $icone = $ok ? '&#9989;' : '&#10060;';
    echo '<li>' . $icone . ' <strong>' . e($rotulo) . '</strong> — ' . e($detalhe) . '</li>';
}
echo '</ul><p><strong>' . ($tudoOk ? 'Ambiente saudável.' : 'Há itens a corrigir.') . '</strong></p>';
```

- [ ] **Step 2: Verificar a sintaxe**

Run:
```powershell
& "C:\php83\php.exe" -l admin\diagnostico.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add admin/diagnostico.php
git commit -m "Adiciona autodiagnóstico do ambiente"
```

---

## Task 11: Deploy — script FTPS e primeiro envio ao servidor

**Files:**
- Create: `deploy/credenciais.exemplo.env`, `deploy/enviar.sh`
- Create: `deploy/credenciais.env` (local, NÃO versionado)

- [ ] **Step 1: Criar `deploy/credenciais.exemplo.env`**

```bash
# Credenciais de deploy FTPS — MODELO.
# Copie para deploy/credenciais.env e preencha. credenciais.env NÃO é versionado.
# FTP_HOST usa o hostname do servidor (o certificado TLS é emitido para ele).
FTP_HOST=pro115.dnspro.com.br
FTP_USUARIO=USUARIO_FTP
FTP_SENHA=SENHA_FTP
FTP_RAIZ=/
```

- [ ] **Step 2: Criar `deploy/credenciais.env` real**

Copie `deploy/credenciais.exemplo.env` para `deploy/credenciais.env` e preencha com as credenciais de FTP fornecidas pelo usuário. Esse arquivo NÃO é versionado (já no `.gitignore`).

- [ ] **Step 3: Criar `deploy/enviar.sh`**

```bash
#!/usr/bin/env bash
# Deploy FTPS do site Conta Lelê.
# Thiago Mourão — https://github.com/MouraoBSB
# Uso: bash deploy/enviar.sh [caminho/relativo/arquivo ...]
#   Sem argumentos: envia todos os arquivos versionados deployáveis.
#   Com argumentos: envia apenas os arquivos indicados.

set -euo pipefail

DIR_PLANO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DIR_PLANO"
source deploy/credenciais.env

# Arquivos/pastas que NÃO vão para o servidor.
EXCLUIR_REGEX='^(\.git|docs|tests|deploy|Identidade Visual|Instruções Site antigo|config\.exemplo\.php|brand-tokens\.css|tokens\.json|CLAUDE\.md|\.gitignore)'

enviar_arquivo() {
    local arquivo="$1"
    local destino="ftp://${FTP_HOST}${FTP_RAIZ}${arquivo}"
    echo "  -> ${arquivo}"
    curl --silent --show-error --ssl-reqd --ftp-create-dirs \
        -T "${arquivo}" "${destino}" \
        --user "${FTP_USUARIO}:${FTP_SENHA}"
}

if [ "$#" -gt 0 ]; then
    LISTA=("$@")
else
    # core.quotepath=false mantém os acentos legíveis para o filtro de exclusão.
    mapfile -t LISTA < <(git -c core.quotepath=false ls-files | grep -Ev "$EXCLUIR_REGEX")
fi

echo "Enviando ${#LISTA[@]} arquivo(s) para ${FTP_HOST}..."
for arquivo in "${LISTA[@]}"; do
    [ -f "$arquivo" ] && enviar_arquivo "$arquivo"
done
echo "Deploy concluído."
```

- [ ] **Step 4: Tornar o script executável e enviar `config.php` ao servidor**

`config.php` não é versionado, então o `git ls-files` do script não o inclui — envie-o explicitamente, junto da primeira leva:
```bash
chmod +x deploy/enviar.sh
bash deploy/enviar.sh
bash deploy/enviar.sh config.php
```
Expected: lista de arquivos enviados, terminando com `Deploy concluído.`

- [ ] **Step 5: Rodar o instalador do banco no servidor**

Run (use o token definido em `config.php`):
```bash
curl --silent "https://contalele.com.br/instalar.php?token=SEU_TOKEN"
```
Expected: HTML confirmando "Tabelas criadas", "Configurações padrão semeadas" e a senha provisória do admin. **Anote a senha provisória.**

- [ ] **Step 6: Verificar o diagnóstico no servidor**

Run:
```bash
curl --silent "https://contalele.com.br/admin/diagnostico.php?token=SEU_TOKEN"
```
Expected: todas as checagens com ✅, incluindo "9 tabelas presentes" e "Ambiente saudável."

- [ ] **Step 7: Remover o instalador do servidor**

Após o sucesso, o `instalar.php` não deve permanecer no ar. Remova-o:
```bash
source deploy/credenciais.env
curl --ssl-reqd -Q "DELE ${FTP_RAIZ}instalar.php" "ftp://${FTP_HOST}/" --user "${FTP_USUARIO}:${FTP_SENHA}"
```
Expected: comando aceito. Confirme com `curl --silent -o /dev/null -w "%{http_code}" "https://contalele.com.br/instalar.php"` → deve responder `404`.

- [ ] **Step 8: Verificar o site no ar**

Abra `https://contalele.com.br/` no navegador.
Expected: a home provisória renderiza com a marca; HTTPS ativo; `https://contalele.com.br/rota-qualquer` mostra a 404.

- [ ] **Step 9: Commit**

```bash
git add deploy/credenciais.exemplo.env deploy/enviar.sh
git commit -m "Adiciona script de deploy FTPS"
```

---

## Task 12: Atualizar o CLAUDE.md e verificação final

**Files:**
- Modify: `CLAUDE.md` (apenas a seção 10)

- [ ] **Step 1: Reescrever a seção 10 do `CLAUDE.md`**

Substitua todo o conteúdo da seção `## 10. Estrutura de arquivos deste projeto` (da linha do título até antes de `## 11.`) por:

````markdown
## 10. Estrutura de arquivos deste projeto

O site é HTML + PHP puro (PHP 8.3), sem framework. Deploy via FTPS.

```
contalele/
├── CLAUDE.md             ← este arquivo (diretrizes da marca)
├── brand-tokens.css      ← tokens CSS — fonte de design
├── tokens.json           ← mesmos tokens em JSON
├── config.php            ← credenciais (NÃO versionado)
├── config.exemplo.php    ← modelo de configuração
├── index.php             ← front-controller + roteador
├── .htaccess             ← URLs limpas, HTTPS, cache, segurança
├── includes/             ← conexao, erros, funcoes, seo, cabecalho, rodape
├── paginas/              ← uma página por rota (incluídas pelo roteador)
├── admin/                ← painel administrativo + diagnostico
├── assets/               ← css/ (brand-tokens + style), js/, img/
├── sql/schema.sql        ← schema do banco
├── downloads/            ← PDFs dos e-books
├── uploads/              ← imagens enviadas pelo admin
├── logs/                 ← logs de erro
├── lib/                  ← bibliotecas de terceiros (ex.: PHPMailer)
├── tests/                ← testes CLI (rodar com: php tests/correr.php)
└── deploy/enviar.sh      ← script de deploy FTPS
```

### Adicionando uma nova página
1. Crie `paginas/<nome>.php` com o conteúdo da página (sem `<head>`/`<header>`).
2. Registre a rota no array `$rotas` em `index.php`.
3. Ajuste `$seo` dentro da página se o título/descrição forem específicos.

### Adicionando um curso/cordel/livro
O conteúdo dinâmico (cordéis, e-books, notícias, depoimentos, festivais) é
gerenciado pelo painel administrativo (`/admin/`) — não por edição de código.
````

- [ ] **Step 2: Rodar a suíte de testes completa**

Run:
```powershell
& "C:\php83\php.exe" tests\correr.php
```
Expected: `Total: 19 ok, 0 falha(s)` e código de saída 0.

- [ ] **Step 3: Verificação final da fundação**

Confirme cada item:
- `https://contalele.com.br/` renderiza a home provisória com a marca aplicada.
- HTTPS é forçado (acesso via `http://` redireciona para `https://`).
- Uma rota inexistente devolve a página 404 com status HTTP 404.
- `admin/diagnostico.php` (com token) reporta "Ambiente saudável" e 9 tabelas.
- `instalar.php` foi removido do servidor (responde 404).
- `git status` está limpo (tudo commitado).

- [ ] **Step 4: Commit**

```bash
git add CLAUDE.md
git commit -m "Atualiza CLAUDE.md com a estrutura real HTML/PHP"
```

- [ ] **Step 5: Enviar o repositório ao GitHub**

```bash
git push origin main
```
Expected: push aceito para `contalele-novo`.

---

## Verificação de cobertura (auto-revisão)

- **Estrutura de pastas / config** → Task 2 ✓
- **Conexão MySQL via PDO** → Task 5 ✓
- **Schema do banco (9 tabelas)** → Task 6 ✓
- **Roteamento de URLs limpas** → Task 7 ✓
- **Tratamento de erros + logging estruturado** → Task 4 ✓
- **Layout base + tokens da marca + responsividade** → Task 8 ✓
- **SEO base (meta, OG, canonical, robots)** → Tasks 7, 8 ✓
- **Segurança (.htaccess, HTTPS, bloqueios, CSRF helper)** → Tasks 2, 3, 7 ✓
- **Autodiagnóstico** → Task 10 ✓
- **Deploy FTPS** → Task 11 ✓
- **Funções utilitárias testadas** → Task 3 ✓
- **Atualização do CLAUDE.md (seção 10)** → Task 12 ✓

**Fora do escopo deste plano (Plano 2/3):** páginas de conteúdo da vitrine, leitura
de dados do banco nas páginas públicas, formulário de contato, painel
administrativo (login/CRUDs), `sitemap.xml`, integração com PHPMailer/SMTP,
favicon definitivo, imagem OG padrão, JSON-LD por página.

**Pendências externas (do spec §15):** confirmar SSL ativo antes do deploy
(Task 11 assume HTTPS); SMTP e favicon são tratados nos planos seguintes.
