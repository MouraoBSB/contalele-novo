# Plano de Implementação — Painel Administrativo do Site Conta Lelê

> **Para executores agênticos:** SUB-SKILL OBRIGATÓRIA: use `superpowers:subagent-driven-development` (recomendado) ou `superpowers:executing-plans` para implementar este plano tarefa a tarefa. Os passos usam caixas de seleção (`- [ ]`) para acompanhamento.

**Objetivo:** Construir o painel administrativo (`/admin/`) que permite à Lelê gerenciar o conteúdo do site — cordéis, e-books, notícias, depoimentos, festivais, prêmios — além de ler as mensagens de contato e configurar o site, sem depender de programador.

**Arquitetura:** Continuação dos Planos 1 e 2. O painel vive em `admin/`, protegido por login com sessão. O CRUD dos 6 tipos de conteúdo é **dirigido por metadados**: um único motor (`admin/conteudo.php`) lê as definições de campos em `admin/incluir/recursos.php` e serve listagem, formulário, gravação, exclusão, publicação e reordenação para todos os tipos. O envio de e-mail usa PHPMailer via SMTP (a hospedagem não tem `mail()`).

**Stack:** PHP 8.3, MySQL/PDO, HTML5, CSS próprio, PHPMailer 7 (sem Composer). Sem outras dependências.

**Documentos de referência:**
- Spec: `docs/superpowers/specs/2026-05-22-site-contalele-fundacao-vitrine-design.md` (seção 7 — painel administrativo)
- Diretrizes da marca: `CLAUDE.md` (seções 1–9)

---

## Contexto herdado (já existe e funciona)

- Banco `cemaneto_contalele`, 9 tabelas. `usuarios_admin` tem 1 registro (admin criado pelo instalador, com `precisa_trocar_senha = 1`). `configuracoes` tem as chaves: `whatsapp`, `email_contato`, `instagram`, `facebook`, `youtube`, `smtp_host`, `smtp_porta`, `smtp_usuario`, `smtp_senha`, `smtp_remetente`, `smtp_seguranca`.
- `usuarios_admin`: colunas `id, nome, email, senha_hash, precisa_trocar_senha, tentativas_login, bloqueado_ate, ultimo_acesso, ativo, criado_em`.
- Tabelas de conteúdo (`cordeis, ebooks, noticias, depoimentos, festivais, premios`): todas têm `id`, `ordem`, `publicado`, `criado_em`. Colunas específicas — ver `sql/schema.sql`.
- `includes/`: `conexao.php` (`bd()`), `erros.php` (`registrar_log`, `ativar_tratamento_erros`), `funcoes.php` (`e()`, `gerar_slug()`, `validar_email()`, `limpar_texto()`, `csrf_token()`, `csrf_validar()`), `repositorio.php` (`configuracao()`, `listar_*`), `seo.php`.
- `admin/diagnostico.php` já existe (protegido por token).
- `config.php`: bloco `instalador.token` e `instalador.admin_email`.
- PHP local: `C:\php83\php.exe`. Testes: `php tests/correr.php` (30 testes). Deploy: `bash deploy/enviar.sh [arquivos...]`.
- A hospedagem **não tem a função `mail()`** — e-mail só via SMTP/PHPMailer.

---

## Estrutura de arquivos (deste plano)

```
lib/PHPMailer/
├── PHPMailer.php, SMTP.php, Exception.php   NOVO — biblioteca (sem Composer)
includes/
└── email.php                NOVO — enviar_email() via PHPMailer/SMTP
paginas/contato.php          MODIFICADO — usa enviar_email()
admin/
├── incluir/
│   ├── .htaccess            NOVO — Require all denied
│   ├── sessao.php           NOVO — sessão + autenticação
│   ├── topo.php             NOVO — cabeçalho do painel
│   ├── rodape.php           NOVO — rodapé do painel
│   ├── upload.php           NOVO — processar uploads de imagem/PDF
│   └── recursos.php         NOVO — metadados dos 6 tipos de conteúdo
├── login.php                NOVO
├── sair.php                 NOVO
├── trocar-senha.php         NOVO
├── index.php                NOVO — dashboard
├── conteudo.php             NOVO — motor de CRUD (todos os tipos)
├── mensagens.php            NOVO — caixa de mensagens de contato
├── configuracoes.php        NOVO — edição de configurações + SMTP
└── diagnostico.php          (já existe)
assets/css/admin.css         NOVO — estilos do painel
tests/upload-teste.php       NOVO
```

**Convenções:** `declare(strict_types=1)`; indentação 4 espaços; aspas simples; texto pt-BR; cabeçalho de autoria `Thiago Mourão — https://github.com/MouraoBSB` em arquivos novos (exceto a biblioteca de terceiros); toda saída escapada com `e()`; todo formulário com token CSRF; toda query com prepared statement.

**Padrão de página do admin:** cada arquivo em `admin/` (exceto `incluir/`) começa com:
```php
<?php
declare(strict_types=1);
define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require __DIR__ . '/incluir/sessao.php';
ativar_tratamento_erros($config['site']['ambiente']);
```

---

## Task 1: Baixar o PHPMailer

**Files:**
- Create: `lib/PHPMailer/PHPMailer.php`, `lib/PHPMailer/SMTP.php`, `lib/PHPMailer/Exception.php`

- [ ] **Step 1: Baixar os 3 arquivos da release 7.1.1**

Run (bash):
```bash
mkdir -p lib/PHPMailer
base="https://raw.githubusercontent.com/PHPMailer/PHPMailer/v7.1.1/src"
for f in PHPMailer SMTP Exception; do
  curl -sSL "$base/$f.php" -o "lib/PHPMailer/$f.php"
done
ls -la lib/PHPMailer/
```
Expected: os 3 arquivos `.php` baixados, cada um com tamanho > 1 KB.

- [ ] **Step 2: Verificar a sintaxe**

Run:
```powershell
& "C:\php83\php.exe" -l lib\PHPMailer\PHPMailer.php
& "C:\php83\php.exe" -l lib\PHPMailer\SMTP.php
& "C:\php83\php.exe" -l lib\PHPMailer\Exception.php
```
Expected: `No syntax errors detected` nos três.

- [ ] **Step 3: Commit**

```bash
git add lib/PHPMailer
git commit -m "Adiciona a biblioteca PHPMailer"
```

---

## Task 2: Helper de e-mail e retrofit do formulário de contato

**Files:**
- Create: `includes/email.php`
- Modify: `paginas/contato.php`

- [ ] **Step 1: Criar `includes/email.php`**

```php
<?php
/**
 * Envio de e-mail do site Conta Lelê via SMTP (PHPMailer).
 * As credenciais de SMTP vêm da tabela `configuracoes`.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once CL_RAIZ . '/lib/PHPMailer/Exception.php';
require_once CL_RAIZ . '/lib/PHPMailer/PHPMailer.php';
require_once CL_RAIZ . '/lib/PHPMailer/SMTP.php';

/**
 * Envia um e-mail de texto simples. Devolve true se enviou, false se não.
 * Não lança exceção: registra no log e devolve false em caso de falha.
 *
 * @param string|null $responder Endereço de Reply-To (opcional).
 */
function enviar_email(string $para, string $assunto, string $corpo, ?string $responder = null): bool
{
    $host = configuracao('smtp_host');
    if ($host === '') {
        registrar_log('E-mail não enviado: SMTP não configurado', ['assunto' => $assunto]);
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->Port       = (int) (configuracao('smtp_porta', '587'));
        $mail->SMTPAuth   = true;
        $mail->Username   = configuracao('smtp_usuario');
        $mail->Password   = configuracao('smtp_senha');
        $mail->CharSet    = 'UTF-8';
        $seguranca = configuracao('smtp_seguranca', 'tls');
        if ($seguranca === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($seguranca === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $remetente = configuracao('smtp_remetente', configuracao('smtp_usuario'));
        $mail->setFrom($remetente, 'Site Conta Lelê');
        $mail->addAddress($para);
        if ($responder !== null && validar_email($responder)) {
            $mail->addReplyTo($responder);
        }
        $mail->Subject = $assunto;
        $mail->Body    = $corpo;

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        registrar_log('Falha no envio de e-mail', ['erro' => $mail->ErrorInfo]);
        return false;
    }
}
```

- [ ] **Step 2: Modificar `paginas/contato.php` para usar `enviar_email()`**

Em `paginas/contato.php`, no topo, após a linha `require_once CL_RAIZ . '/includes/repositorio.php';`, adicione:
```php
require_once CL_RAIZ . '/includes/email.php';
```
Depois, localize o bloco que hoje começa com o comentário `// Notificação por e-mail — best-effort:` e termina no fechamento do `if (function_exists('mail')) { ... }`. Substitua TODO esse bloco por:
```php
            // Notificação por e-mail — best-effort: uma falha aqui NÃO derruba
            // o envio, pois a mensagem já está salva no banco.
            $corpoEmail = "Nome: {$valores['nome']}\nE-mail: {$valores['email']}\n"
                . "Telefone: {$valores['telefone']}\nAssunto: {$valores['assunto']}\n\n"
                . $valores['mensagem'];
            enviar_email(
                $emailContato,
                'Contato pelo site: ' . ($valores['assunto'] ?: 'sem assunto'),
                $corpoEmail,
                $valores['email']
            );
```

- [ ] **Step 3: Verificar a sintaxe**

Run:
```powershell
& "C:\php83\php.exe" -l includes\email.php
& "C:\php83\php.exe" -l paginas\contato.php
```
Expected: `No syntax errors detected` nos dois.

- [ ] **Step 4: Commit**

```bash
git add includes/email.php paginas/contato.php
git commit -m "Adiciona envio de e-mail por SMTP e integra ao formulário de contato"
```

---

## Task 3: Sessão e autenticação

**Files:**
- Create: `admin/incluir/sessao.php`, `admin/incluir/.htaccess`
- Test: `tests/upload-teste.php` (criado vazio aqui? não — ver Task 8)

- [ ] **Step 1: Criar `admin/incluir/.htaccess`**

```apache
# Arquivos internos do painel — sem acesso direto pela web.
Require all denied
```

- [ ] **Step 2: Criar `admin/incluir/sessao.php`**

```php
<?php
/**
 * Sessão e autenticação do painel administrativo.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Inicia a sessão do painel com cookies endurecidos (uma única vez).
 */
function iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('cl_admin');
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
 * Devolve os dados do administrador logado, ou null.
 */
function usuario_logado(): ?array
{
    return $_SESSION['admin'] ?? null;
}

/**
 * Registra o administrador na sessão após login bem-sucedido.
 */
function logar(array $usuario): void
{
    iniciar_sessao();
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id'                   => (int) $usuario['id'],
        'nome'                 => (string) $usuario['nome'],
        'email'                => (string) $usuario['email'],
        'precisa_trocar_senha' => (int) $usuario['precisa_trocar_senha'],
    ];
}

/**
 * Encerra a sessão do administrador.
 */
function deslogar(): void
{
    iniciar_sessao();
    $_SESSION = [];
    session_destroy();
}

/**
 * Exige login. Redireciona para o login se não houver; força a troca
 * de senha no primeiro acesso. Chame no topo de toda página protegida.
 */
function exigir_login(): void
{
    iniciar_sessao();
    if (usuario_logado() === null) {
        header('Location: /admin/login.php');
        exit;
    }
    $u = usuario_logado();
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!empty($u['precisa_trocar_senha']) && $script !== 'trocar-senha.php' && $script !== 'sair.php') {
        header('Location: /admin/trocar-senha.php');
        exit;
    }
}
```

- [ ] **Step 3: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l admin\incluir\sessao.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add admin/incluir/.htaccess admin/incluir/sessao.php
git commit -m "Adiciona sessão e autenticação do painel"
```

---

## Task 4: Layout e CSS do painel

**Files:**
- Create: `admin/incluir/topo.php`, `admin/incluir/rodape.php`, `assets/css/admin.css`

- [ ] **Step 1: Criar `assets/css/admin.css`**

```css
/* Site Conta Lelê — estilos do painel administrativo
   Thiago Mourão — https://github.com/MouraoBSB */

*, *::before, *::after { box-sizing: border-box; }
body {
  margin: 0; font-family: var(--cl-font-ui);
  background: var(--cl-bg); color: var(--cl-ink);
}
a { color: var(--cl-accent); }
img { max-width: 100%; height: auto; }

.adm-topo {
  background: var(--cl-ink); color: var(--cl-bg);
  padding: 12px 20px; display: flex; flex-wrap: wrap;
  align-items: center; gap: 16px;
}
.adm-topo a { color: var(--cl-bg-3); text-decoration: none; font-weight: 700; font-size: 14px; }
.adm-topo strong { font-family: var(--cl-font-display); font-weight: 400; }
.adm-topo nav { display: flex; flex-wrap: wrap; gap: 14px; }
.adm-topo .adm-sair { margin-left: auto; }

.adm-conteudo { max-width: 900px; margin: 0 auto; padding: 24px 20px 64px; }
.adm-conteudo h1 { font-size: 24px; font-weight: 900; }
.adm-conteudo h2 { font-size: 18px; font-weight: 800; }

.adm-cartao {
  background: #fff; border: 1px solid var(--cl-border);
  border-radius: 14px; padding: 18px; margin-bottom: 16px;
}
.adm-grade { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }

table.adm-tabela { width: 100%; border-collapse: collapse; background: #fff; }
.adm-tabela th, .adm-tabela td {
  text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--cl-border);
  font-size: 14px;
}
.adm-tabela th { background: var(--cl-bg-2); }

.adm-campo { display: block; margin-bottom: 14px; }
.adm-campo span { display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px; }
.adm-campo input, .adm-campo textarea, .adm-campo select {
  width: 100%; padding: 9px 11px; font: inherit;
  border: 1.5px solid var(--cl-border); border-radius: 10px; background: #fff;
}
.adm-campo textarea { min-height: 120px; resize: vertical; }

.adm-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 16px; border-radius: 999px; border: 1.5px solid var(--cl-ink);
  font: inherit; font-weight: 800; font-size: 14px; cursor: pointer; text-decoration: none;
  background: var(--cl-ink); color: var(--cl-bg-3);
}
.adm-btn--claro { background: #fff; color: var(--cl-ink); }
.adm-btn--perigo { background: #fff; color: var(--cl-accent); border-color: var(--cl-accent); }
.adm-btn--mini { padding: 4px 10px; font-size: 12px; }

.adm-aviso { padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; }
.adm-aviso--ok { background: #e7f6e7; color: #1f6b1f; }
.adm-aviso--erro { background: #fae3dd; color: var(--cl-accent); }

.adm-tag { font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 999px; }
.adm-tag--sim { background: #e7f6e7; color: #1f6b1f; }
.adm-tag--nao { background: var(--cl-border); color: var(--cl-ink-dim); }
.adm-acoes { display: flex; flex-wrap: wrap; gap: 6px; }
```

- [ ] **Step 2: Criar `admin/incluir/topo.php`**

```php
<?php
/**
 * Cabeçalho do painel administrativo.
 * Thiago Mourão — https://github.com/MouraoBSB
 *
 * @var string $tituloPagina  Título da página (definido antes do require).
 */

declare(strict_types=1);

$u = usuario_logado();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($tituloPagina ?? 'Painel') ?> — Conta Lelê</title>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<header class="adm-topo">
    <strong>Conta Lelê</strong>
    <nav>
        <a href="/admin/">Painel</a>
        <a href="/admin/conteudo.php?recurso=cordeis">Cordéis</a>
        <a href="/admin/conteudo.php?recurso=ebooks">Livros</a>
        <a href="/admin/conteudo.php?recurso=noticias">Notícias</a>
        <a href="/admin/conteudo.php?recurso=depoimentos">Depoimentos</a>
        <a href="/admin/conteudo.php?recurso=festivais">Festivais</a>
        <a href="/admin/conteudo.php?recurso=premios">Prêmios</a>
        <a href="/admin/mensagens.php">Mensagens</a>
        <a href="/admin/configuracoes.php">Configurações</a>
    </nav>
    <a class="adm-sair" href="/admin/sair.php">Sair (<?= e($u['nome'] ?? '') ?>)</a>
</header>
<main class="adm-conteudo">
```

- [ ] **Step 3: Criar `admin/incluir/rodape.php`**

```php
<?php
/**
 * Rodapé do painel administrativo.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);
?>
</main>
</body>
</html>
```

- [ ] **Step 4: Verificar a sintaxe**

Run:
```powershell
& "C:\php83\php.exe" -l admin\incluir\topo.php
& "C:\php83\php.exe" -l admin\incluir\rodape.php
```
Expected: `No syntax errors detected` nos dois.

- [ ] **Step 5: Commit**

```bash
git add admin/incluir/topo.php admin/incluir/rodape.php assets/css/admin.css
git commit -m "Adiciona layout e estilos do painel"
```

---

## Task 5: Login e logout

**Files:**
- Create: `admin/login.php`, `admin/sair.php`

- [ ] **Step 1: Criar `admin/login.php`**

```php
<?php
/**
 * Login do painel administrativo, com bloqueio por tentativas.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
iniciar_sessao();

// Já logado? Vai para o painel.
if (usuario_logado() !== null) {
    header('Location: /admin/');
    exit;
}

$erro = null;
const MAX_TENTATIVAS = 5;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } else {
        $email = limpar_texto((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');

        $st = bd()->prepare('SELECT * FROM usuarios_admin WHERE email = ? AND ativo = 1');
        $st->execute([$email]);
        $u = $st->fetch();

        $bloqueado = $u && $u['bloqueado_ate'] !== null
            && strtotime((string) $u['bloqueado_ate']) > time();

        if ($bloqueado) {
            $erro = 'Conta temporariamente bloqueada por tentativas. Aguarde 15 minutos.';
        } elseif ($u && password_verify($senha, $u['senha_hash'])) {
            bd()->prepare(
                'UPDATE usuarios_admin SET tentativas_login = 0, bloqueado_ate = NULL,
                 ultimo_acesso = NOW() WHERE id = ?'
            )->execute([$u['id']]);
            logar($u);
            header('Location: /admin/');
            exit;
        } else {
            if ($u) {
                $tentativas = (int) $u['tentativas_login'] + 1;
                if ($tentativas >= MAX_TENTATIVAS) {
                    bd()->prepare(
                        'UPDATE usuarios_admin SET tentativas_login = 0,
                         bloqueado_ate = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?'
                    )->execute([$u['id']]);
                } else {
                    bd()->prepare('UPDATE usuarios_admin SET tentativas_login = ? WHERE id = ?')
                        ->execute([$tentativas, $u['id']]);
                }
            }
            $erro = 'E-mail ou senha incorretos.';
        }
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
    <title>Entrar — Painel Conta Lelê</title>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<main class="adm-conteudo" style="max-width:380px">
    <h1>Painel da Conta Lelê</h1>
    <?php if ($erro !== null): ?>
        <p class="adm-aviso adm-aviso--erro"><?= e($erro) ?></p>
    <?php endif; ?>
    <form method="post" action="/admin/login.php" class="adm-cartao">
        <input type="hidden" name="csrf" value="<?= e($token) ?>">
        <label class="adm-campo"><span>E-mail</span>
            <input type="email" name="email" required autofocus></label>
        <label class="adm-campo"><span>Senha</span>
            <input type="password" name="senha" required></label>
        <button class="adm-btn" type="submit">Entrar</button>
    </form>
</main>
</body>
</html>
```

- [ ] **Step 2: Criar `admin/sair.php`**

```php
<?php
/**
 * Logout do painel administrativo.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
require __DIR__ . '/incluir/sessao.php';

deslogar();
header('Location: /admin/login.php');
exit;
```

- [ ] **Step 3: Verificar a sintaxe**

Run:
```powershell
& "C:\php83\php.exe" -l admin\login.php
& "C:\php83\php.exe" -l admin\sair.php
```
Expected: `No syntax errors detected` nos dois.

- [ ] **Step 4: Commit**

```bash
git add admin/login.php admin/sair.php
git commit -m "Adiciona login e logout do painel"
```

---

## Task 6: Troca de senha no primeiro acesso

**Files:**
- Create: `admin/trocar-senha.php`

- [ ] **Step 1: Criar `admin/trocar-senha.php`**

```php
<?php
/**
 * Troca de senha do administrador (obrigatória no primeiro acesso).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
exigir_login();

$u = usuario_logado();
$aviso = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } else {
        $nova = (string) ($_POST['nova'] ?? '');
        $conf = (string) ($_POST['confirmacao'] ?? '');
        if (mb_strlen($nova) < 8) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A senha precisa ter pelo menos 8 caracteres.'];
        } elseif ($nova !== $conf) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A confirmação não confere com a nova senha.'];
        } else {
            $hash = password_hash($nova, PASSWORD_DEFAULT);
            bd()->prepare(
                'UPDATE usuarios_admin SET senha_hash = ?, precisa_trocar_senha = 0 WHERE id = ?'
            )->execute([$hash, $u['id']]);
            $_SESSION['admin']['precisa_trocar_senha'] = 0;
            header('Location: /admin/');
            exit;
        }
    }
}

$token = csrf_token();
$tituloPagina = 'Trocar senha';
require __DIR__ . '/incluir/topo.php';
?>
<h1>Trocar senha</h1>
<?php if (!empty($u['precisa_trocar_senha'])): ?>
    <p>Este é o seu primeiro acesso. Defina uma senha pessoal para continuar.</p>
<?php endif; ?>
<?php if ($aviso !== null): ?>
    <p class="adm-aviso adm-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
<?php endif; ?>
<form method="post" action="/admin/trocar-senha.php" class="adm-cartao">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    <label class="adm-campo"><span>Nova senha (mínimo 8 caracteres)</span>
        <input type="password" name="nova" required minlength="8"></label>
    <label class="adm-campo"><span>Confirme a nova senha</span>
        <input type="password" name="confirmacao" required minlength="8"></label>
    <button class="adm-btn" type="submit">Salvar nova senha</button>
</form>
<?php require __DIR__ . '/incluir/rodape.php'; ?>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l admin\trocar-senha.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add admin/trocar-senha.php
git commit -m "Adiciona troca de senha no primeiro acesso"
```

---

## Task 7: Dashboard

**Files:**
- Create: `admin/index.php`

- [ ] **Step 1: Criar `admin/index.php`**

```php
<?php
/**
 * Dashboard do painel administrativo.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
exigir_login();

$pdo = bd();
$contar = static fn (string $sql): int => (int) $pdo->query($sql)->fetchColumn();

$cartoes = [
    ['Mensagens não lidas', $contar('SELECT COUNT(*) FROM mensagens_contato WHERE lida = 0'), '/admin/mensagens.php'],
    ['Cordéis',     $contar('SELECT COUNT(*) FROM cordeis'),     '/admin/conteudo.php?recurso=cordeis'],
    ['Livros',      $contar('SELECT COUNT(*) FROM ebooks'),      '/admin/conteudo.php?recurso=ebooks'],
    ['Notícias',    $contar('SELECT COUNT(*) FROM noticias'),    '/admin/conteudo.php?recurso=noticias'],
    ['Depoimentos', $contar('SELECT COUNT(*) FROM depoimentos'), '/admin/conteudo.php?recurso=depoimentos'],
    ['Festivais',   $contar('SELECT COUNT(*) FROM festivais'),   '/admin/conteudo.php?recurso=festivais'],
    ['Prêmios',     $contar('SELECT COUNT(*) FROM premios'),     '/admin/conteudo.php?recurso=premios'],
];

$tituloPagina = 'Painel';
require __DIR__ . '/incluir/topo.php';
?>
<h1>Olá, <?= e(usuario_logado()['nome']) ?>!</h1>
<p>Use o menu acima para gerenciar o conteúdo do site.</p>
<div class="adm-grade">
    <?php foreach ($cartoes as [$rotulo, $qtd, $url]): ?>
        <a class="adm-cartao" href="<?= e($url) ?>" style="text-decoration:none;color:inherit">
            <div style="font-size:32px;font-weight:900"><?= e((string) $qtd) ?></div>
            <div><?= e($rotulo) ?></div>
        </a>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/incluir/rodape.php'; ?>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l admin\index.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add admin/index.php
git commit -m "Adiciona o dashboard do painel"
```

---

## Task 8: Helper de upload de arquivos

**Files:**
- Create: `admin/incluir/upload.php`
- Test: `tests/upload-teste.php`

- [ ] **Step 1: Escrever o teste que falha** — `tests/upload-teste.php`:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../admin/incluir/upload.php';

// nome_upload_seguro() é pura — gera um nome de arquivo seguro e aleatório.
$n = nome_upload_seguro('Foto Da Lelê!.JPG');
afirmar((bool) preg_match('/^[a-z0-9]+\.jpg$/', $n), 'nome_upload_seguro() gera nome aleatório com extensão minúscula');
afirmar(nome_upload_seguro('a.png') !== nome_upload_seguro('a.png'), 'nome_upload_seguro() gera nomes diferentes a cada chamada');
afirmar_igual('pdf', pathinfo(nome_upload_seguro('doc.PDF'), PATHINFO_EXTENSION), 'nome_upload_seguro() preserva a extensão pdf');

// extensao_permitida()
afirmar(extensao_permitida('foto.jpg', ['jpg', 'png']), 'extensao_permitida() aceita jpg na lista');
afirmar(!extensao_permitida('script.php', ['jpg', 'png']), 'extensao_permitida() rejeita php fora da lista');
afirmar(!extensao_permitida('arquivo', ['jpg']), 'extensao_permitida() rejeita arquivo sem extensão');
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `& "C:\php83\php.exe" tests\correr.php`
Expected: falha — `admin/incluir/upload.php` / funções indefinidas.

- [ ] **Step 3: Criar `admin/incluir/upload.php`**

```php
<?php
/**
 * Processamento de uploads do painel (imagens e PDFs).
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Verifica se o arquivo tem uma das extensões permitidas (minúsculas).
 */
function extensao_permitida(string $nomeArquivo, array $permitidas): bool
{
    $ext = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
    return $ext !== '' && in_array($ext, $permitidas, true);
}

/**
 * Gera um nome de arquivo seguro e aleatório, preservando a extensão.
 */
function nome_upload_seguro(string $nomeOriginal): string
{
    $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
    return bin2hex(random_bytes(8)) . ($ext !== '' ? '.' . $ext : '');
}

/**
 * Processa um arquivo enviado em $_FILES[$campo].
 * Valida tamanho, extensão e tipo MIME real; move para uploads/.
 * Devolve o nome do arquivo salvo, ou null se não houve envio,
 * ou lança RuntimeException se o arquivo for inválido.
 *
 * @param string[] $extensoes  Extensões permitidas (ex.: ['jpg','png','webp']).
 * @param string[] $mimes      Tipos MIME permitidos.
 */
function processar_upload(string $campo, array $extensoes, array $mimes, int $maxBytes = 5242880): ?string
{
    if (empty($_FILES[$campo]) || ($_FILES[$campo]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $arquivo = $_FILES[$campo];
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio do arquivo.');
    }
    if ($arquivo['size'] > $maxBytes) {
        throw new RuntimeException('Arquivo grande demais (máximo ' . (int) ($maxBytes / 1048576) . ' MB).');
    }
    if (!extensao_permitida($arquivo['name'], $extensoes)) {
        throw new RuntimeException('Tipo de arquivo não permitido.');
    }
    $mimeReal = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    if (!in_array($mimeReal, $mimes, true)) {
        throw new RuntimeException('O conteúdo do arquivo não confere com o tipo esperado.');
    }
    $nome = nome_upload_seguro($arquivo['name']);
    $destino = CL_RAIZ . '/uploads/' . $nome;
    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Não foi possível salvar o arquivo.');
    }
    return $nome;
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `& "C:\php83\php.exe" tests\correr.php`
Expected: os 6 testes novos passam (`Total: 36 ok, 0 falha(s)`).

- [ ] **Step 5: Commit**

```bash
git add admin/incluir/upload.php tests/upload-teste.php
git commit -m "Adiciona helper de upload de arquivos com testes"
```

---

## Task 9: Metadados dos recursos de conteúdo

**Files:**
- Create: `admin/incluir/recursos.php`

- [ ] **Step 1: Criar `admin/incluir/recursos.php`**

```php
<?php
/**
 * Metadados dos recursos de conteúdo do painel.
 * Cada recurso define a tabela, os rótulos e os campos do formulário.
 * O motor de CRUD (admin/conteudo.php) é dirigido por este arquivo.
 *
 * Tipos de campo: 'texto', 'area', 'numero', 'data', 'imagem', 'pdf'.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

return [
    'cordeis' => [
        'singular' => 'Cordel',
        'plural'   => 'Cordéis',
        'tabela'   => 'cordeis',
        'rotulo'   => 'titulo',
        'slug'     => true,
        'campos'   => [
            'titulo'        => ['rotulo' => 'Título',        'tipo' => 'texto',  'obrigatorio' => true],
            'sinopse'       => ['rotulo' => 'Sinopse',       'tipo' => 'area',   'obrigatorio' => true],
            'video_youtube' => ['rotulo' => 'Vídeo (URL do YouTube)', 'tipo' => 'texto'],
            'imagem_capa'   => ['rotulo' => 'Capa',          'tipo' => 'imagem'],
        ],
    ],
    'ebooks' => [
        'singular' => 'Livro',
        'plural'   => 'Livros',
        'tabela'   => 'ebooks',
        'rotulo'   => 'titulo',
        'slug'     => true,
        'campos'   => [
            'titulo'      => ['rotulo' => 'Título',  'tipo' => 'texto', 'obrigatorio' => true],
            'sinopse'     => ['rotulo' => 'Sinopse', 'tipo' => 'area',  'obrigatorio' => true],
            'arquivo_pdf' => ['rotulo' => 'Arquivo PDF', 'tipo' => 'pdf'],
            'imagem_capa' => ['rotulo' => 'Capa',    'tipo' => 'imagem'],
        ],
    ],
    'noticias' => [
        'singular' => 'Notícia',
        'plural'   => 'Notícias',
        'tabela'   => 'noticias',
        'rotulo'   => 'titulo',
        'slug'     => false,
        'campos'   => [
            'titulo'          => ['rotulo' => 'Título',  'tipo' => 'texto', 'obrigatorio' => true],
            'veiculo'         => ['rotulo' => 'Veículo', 'tipo' => 'texto'],
            'url'             => ['rotulo' => 'Link',    'tipo' => 'texto', 'obrigatorio' => true],
            'data_publicacao' => ['rotulo' => 'Data',    'tipo' => 'data'],
            'imagem'          => ['rotulo' => 'Imagem',  'tipo' => 'imagem'],
        ],
    ],
    'depoimentos' => [
        'singular' => 'Depoimento',
        'plural'   => 'Depoimentos',
        'tabela'   => 'depoimentos',
        'rotulo'   => 'autor',
        'slug'     => false,
        'campos'   => [
            'autor' => ['rotulo' => 'Autor', 'tipo' => 'texto', 'obrigatorio' => true],
            'texto' => ['rotulo' => 'Depoimento', 'tipo' => 'area', 'obrigatorio' => true],
            'foto'  => ['rotulo' => 'Foto', 'tipo' => 'imagem'],
        ],
    ],
    'festivais' => [
        'singular' => 'Festival',
        'plural'   => 'Festivais',
        'tabela'   => 'festivais',
        'rotulo'   => 'titulo',
        'slug'     => false,
        'campos'   => [
            'titulo'    => ['rotulo' => 'Título',    'tipo' => 'texto', 'obrigatorio' => true],
            'descricao' => ['rotulo' => 'Descrição', 'tipo' => 'area',  'obrigatorio' => true],
            'ano'       => ['rotulo' => 'Ano',       'tipo' => 'numero'],
            'videos'    => ['rotulo' => 'Vídeos (uma URL por linha)', 'tipo' => 'area'],
            'imagem'    => ['rotulo' => 'Imagem',    'tipo' => 'imagem'],
        ],
    ],
    'premios' => [
        'singular' => 'Prêmio',
        'plural'   => 'Prêmios',
        'tabela'   => 'premios',
        'rotulo'   => 'titulo',
        'slug'     => false,
        'campos'   => [
            'titulo' => ['rotulo' => 'Título', 'tipo' => 'texto', 'obrigatorio' => true],
            'ano'    => ['rotulo' => 'Ano',    'tipo' => 'numero'],
        ],
    ],
];
```

- [ ] **Step 2: Verificar a sintaxe e a estrutura**

Run:
```powershell
& "C:\php83\php.exe" -l admin\incluir\recursos.php
& "C:\php83\php.exe" -r "$r = require 'admin/incluir/recursos.php'; echo count($r),' recursos: ',implode(', ', array_keys($r));"
```
Expected: `No syntax errors detected` e `6 recursos: cordeis, ebooks, noticias, depoimentos, festivais, premios`.

- [ ] **Step 3: Commit**

```bash
git add admin/incluir/recursos.php
git commit -m "Adiciona metadados dos recursos de conteúdo"
```

---

## Task 10: Motor de CRUD de conteúdo

**Files:**
- Create: `admin/conteudo.php`

Este é o motor único que serve listagem, formulário, gravação, exclusão, publicação e reordenação para os 6 tipos de conteúdo, lendo `recursos.php`.

- [ ] **Step 1: Criar `admin/conteudo.php`**

```php
<?php
/**
 * Motor de CRUD de conteúdo do painel — dirigido por admin/incluir/recursos.php.
 * Ações (via ?acao=): listar (padrão), novo, editar, salvar, excluir, publicar, mover.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require __DIR__ . '/incluir/sessao.php';
require __DIR__ . '/incluir/upload.php';

ativar_tratamento_erros($config['site']['ambiente']);
exigir_login();

$recursos = require __DIR__ . '/incluir/recursos.php';
$chave = (string) ($_GET['recurso'] ?? '');
if (!isset($recursos[$chave])) {
    http_response_code(404);
    exit('Recurso não encontrado.');
}
$rec    = $recursos[$chave];
$tabela = $rec['tabela'];
$pdo    = bd();
$acao   = (string) ($_GET['acao'] ?? 'listar');
$base   = '/admin/conteudo.php?recurso=' . rawurlencode($chave);

$IMG_EXT = ['jpg', 'jpeg', 'png', 'webp'];
$IMG_MIME = ['image/jpeg', 'image/png', 'image/webp'];
$PDF_EXT = ['pdf'];
$PDF_MIME = ['application/pdf'];

// ── Ações POST ───────────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Sessão expirada. Volte e recarregue a página.');
    }

    if ($acao === 'salvar') {
        $id = (int) ($_POST['id'] ?? 0);
        $dados = [];
        $erros = [];
        foreach ($rec['campos'] as $nome => $def) {
            if ($def['tipo'] === 'imagem' || $def['tipo'] === 'pdf') {
                try {
                    $ehImg = $def['tipo'] === 'imagem';
                    $arquivo = processar_upload(
                        $nome,
                        $ehImg ? $IMG_EXT : $PDF_EXT,
                        $ehImg ? $IMG_MIME : $PDF_MIME
                    );
                } catch (RuntimeException $e) {
                    $erros[] = $def['rotulo'] . ': ' . $e->getMessage();
                    $arquivo = null;
                }
                if ($arquivo !== null) {
                    $dados[$nome] = $arquivo;
                }
                continue;
            }
            $valor = trim((string) ($_POST[$nome] ?? ''));
            if ($def['tipo'] !== 'area') {
                $valor = limpar_texto($valor);
            }
            if (!empty($def['obrigatorio']) && $valor === '') {
                $erros[] = $def['rotulo'] . ' é obrigatório.';
            }
            if ($def['tipo'] === 'numero') {
                $dados[$nome] = $valor === '' ? null : (int) $valor;
            } elseif ($def['tipo'] === 'data') {
                $dados[$nome] = $valor === '' ? null : $valor;
            } else {
                $dados[$nome] = $valor;
            }
        }
        $dados['ordem']     = (int) ($_POST['ordem'] ?? 0);
        $dados['publicado'] = isset($_POST['publicado']) ? 1 : 0;
        if (!empty($rec['slug'])) {
            $dados['slug'] = gerar_slug((string) ($dados[$rec['rotulo']] ?? '')) ?: ('item-' . time());
        }

        if ($erros) {
            $_SESSION['cl_flash'] = ['tipo' => 'erro', 'texto' => implode(' ', $erros)];
            header('Location: ' . $base . '&acao=' . ($id ? 'editar&id=' . $id : 'novo'));
            exit;
        }

        if ($id > 0) {
            $sets = implode(', ', array_map(static fn ($c) => "{$c} = ?", array_keys($dados)));
            $st = $pdo->prepare("UPDATE {$tabela} SET {$sets} WHERE id = ?");
            $st->execute([...array_values($dados), $id]);
        } else {
            $cols = implode(', ', array_keys($dados));
            $marc = implode(', ', array_fill(0, count($dados), '?'));
            $pdo->prepare("INSERT INTO {$tabela} ({$cols}) VALUES ({$marc})")
                ->execute(array_values($dados));
        }
        $_SESSION['cl_flash'] = ['tipo' => 'ok', 'texto' => 'Salvo com sucesso.'];
        header('Location: ' . $base);
        exit;
    }

    if ($acao === 'excluir') {
        $pdo->prepare("DELETE FROM {$tabela} WHERE id = ?")->execute([(int) ($_POST['id'] ?? 0)]);
        $_SESSION['cl_flash'] = ['tipo' => 'ok', 'texto' => 'Item excluído.'];
        header('Location: ' . $base);
        exit;
    }

    if ($acao === 'publicar') {
        $pdo->prepare("UPDATE {$tabela} SET publicado = 1 - publicado WHERE id = ?")
            ->execute([(int) ($_POST['id'] ?? 0)]);
        header('Location: ' . $base);
        exit;
    }

    if ($acao === 'mover') {
        $id = (int) ($_POST['id'] ?? 0);
        $dir = ($_POST['direcao'] ?? '') === 'subir' ? 'subir' : 'descer';
        $st = $pdo->prepare("SELECT id, ordem FROM {$tabela} WHERE id = ?");
        $st->execute([$id]);
        $atual = $st->fetch();
        if ($atual) {
            $comp = $dir === 'subir' ? '<' : '>';
            $ord  = $dir === 'subir' ? 'DESC' : 'ASC';
            $st2 = $pdo->prepare(
                "SELECT id, ordem FROM {$tabela} WHERE ordem {$comp} ? ORDER BY ordem {$ord} LIMIT 1"
            );
            $st2->execute([(int) $atual['ordem']]);
            $vizinho = $st2->fetch();
            if ($vizinho) {
                $up = $pdo->prepare("UPDATE {$tabela} SET ordem = ? WHERE id = ?");
                $up->execute([(int) $vizinho['ordem'], (int) $atual['id']]);
                $up->execute([(int) $atual['ordem'], (int) $vizinho['id']]);
            }
        }
        header('Location: ' . $base);
        exit;
    }

    http_response_code(400);
    exit('Ação inválida.');
}

// ── Formulário (novo / editar) ───────────────────────────────────────
$token = csrf_token();
$flash = $_SESSION['cl_flash'] ?? null;
unset($_SESSION['cl_flash']);

if ($acao === 'novo' || $acao === 'editar') {
    $registro = array_fill_keys(array_keys($rec['campos']), '');
    $registro['id'] = 0;
    $registro['ordem'] = 0;
    $registro['publicado'] = 1;
    if ($acao === 'editar') {
        $st = $pdo->prepare("SELECT * FROM {$tabela} WHERE id = ?");
        $st->execute([(int) ($_GET['id'] ?? 0)]);
        $registro = $st->fetch();
        if (!$registro) {
            http_response_code(404);
            exit('Item não encontrado.');
        }
    }
    $tituloPagina = $rec['singular'];
    require __DIR__ . '/incluir/topo.php';
    ?>
    <p><a href="<?= e($base) ?>">&larr; Voltar para <?= e($rec['plural']) ?></a></p>
    <h1><?= $registro['id'] ? 'Editar' : 'Novo' ?> <?= e($rec['singular']) ?></h1>
    <?php if ($flash): ?>
        <p class="adm-aviso adm-aviso--<?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></p>
    <?php endif; ?>
    <form method="post" action="<?= e($base) ?>&acao=salvar" enctype="multipart/form-data" class="adm-cartao">
        <input type="hidden" name="csrf" value="<?= e($token) ?>">
        <input type="hidden" name="id" value="<?= e((string) $registro['id']) ?>">
        <?php foreach ($rec['campos'] as $nome => $def): ?>
            <label class="adm-campo"><span><?= e($def['rotulo']) ?><?= !empty($def['obrigatorio']) ? ' *' : '' ?></span>
                <?php $valor = (string) ($registro[$nome] ?? ''); ?>
                <?php if ($def['tipo'] === 'area'): ?>
                    <textarea name="<?= e($nome) ?>"><?= e($valor) ?></textarea>
                <?php elseif ($def['tipo'] === 'imagem' || $def['tipo'] === 'pdf'): ?>
                    <?php if ($valor !== ''): ?>
                        <small>Atual: <?= e($valor) ?> — envie um arquivo só para substituir.</small>
                    <?php endif; ?>
                    <input type="file" name="<?= e($nome) ?>"
                           accept="<?= $def['tipo'] === 'imagem' ? 'image/*' : 'application/pdf' ?>">
                <?php elseif ($def['tipo'] === 'numero'): ?>
                    <input type="number" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
                <?php elseif ($def['tipo'] === 'data'): ?>
                    <input type="date" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
                <?php else: ?>
                    <input type="text" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
        <label class="adm-campo"><span>Ordem de exibição</span>
            <input type="number" name="ordem" value="<?= e((string) ($registro['ordem'] ?? 0)) ?>"></label>
        <label style="display:flex;gap:8px;align-items:center;margin-bottom:16px">
            <input type="checkbox" name="publicado" value="1" <?= !empty($registro['publicado']) ? 'checked' : '' ?>>
            <span>Publicado (visível no site)</span></label>
        <button class="adm-btn" type="submit">Salvar</button>
        <a class="adm-btn adm-btn--claro" href="<?= e($base) ?>">Cancelar</a>
    </form>
    <?php
    require __DIR__ . '/incluir/rodape.php';
    exit;
}

// ── Listagem (ação padrão) ───────────────────────────────────────────
$itens = $pdo->query("SELECT * FROM {$tabela} ORDER BY ordem, id")->fetchAll();
$tituloPagina = $rec['plural'];
require __DIR__ . '/incluir/topo.php';
?>
<h1><?= e($rec['plural']) ?></h1>
<?php if ($flash): ?>
    <p class="adm-aviso adm-aviso--<?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></p>
<?php endif; ?>
<p><a class="adm-btn" href="<?= e($base) ?>&acao=novo">+ Novo <?= e($rec['singular']) ?></a></p>
<?php if (!$itens): ?>
    <p>Nenhum item ainda.</p>
<?php else: ?>
<table class="adm-tabela">
    <thead><tr><th><?= e($rec['campos'][$rec['rotulo']]['rotulo']) ?></th>
        <th>Publicado</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($itens as $item): ?>
        <tr>
            <td><?= e((string) $item[$rec['rotulo']]) ?></td>
            <td><span class="adm-tag adm-tag--<?= $item['publicado'] ? 'sim' : 'nao' ?>">
                <?= $item['publicado'] ? 'Sim' : 'Não' ?></span></td>
            <td><div class="adm-acoes">
                <a class="adm-btn adm-btn--mini adm-btn--claro" href="<?= e($base) ?>&acao=editar&id=<?= (int) $item['id'] ?>">Editar</a>
                <form method="post" action="<?= e($base) ?>&acao=mover" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <button class="adm-btn adm-btn--mini adm-btn--claro" name="direcao" value="subir">&uarr;</button>
                    <button class="adm-btn adm-btn--mini adm-btn--claro" name="direcao" value="descer">&darr;</button>
                </form>
                <form method="post" action="<?= e($base) ?>&acao=publicar" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <button class="adm-btn adm-btn--mini adm-btn--claro" type="submit"><?= $item['publicado'] ? 'Despublicar' : 'Publicar' ?></button>
                </form>
                <form method="post" action="<?= e($base) ?>&acao=excluir" style="display:inline"
                      onsubmit="return confirm('Excluir este item? Esta ação não pode ser desfeita.');">
                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <button class="adm-btn adm-btn--mini adm-btn--perigo" type="submit">Excluir</button>
                </form>
            </div></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php require __DIR__ . '/incluir/rodape.php'; ?>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l admin\conteudo.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add admin/conteudo.php
git commit -m "Adiciona o motor de CRUD de conteúdo"
```

---

## Task 11: Caixa de mensagens de contato

**Files:**
- Create: `admin/mensagens.php`

- [ ] **Step 1: Criar `admin/mensagens.php`**

```php
<?php
/**
 * Caixa de entrada das mensagens do formulário de contato.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
exigir_login();

$pdo = bd();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Sessão expirada.');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $acao = (string) ($_POST['acao'] ?? '');
    if ($acao === 'excluir') {
        $pdo->prepare('DELETE FROM mensagens_contato WHERE id = ?')->execute([$id]);
    } elseif ($acao === 'alternar_lida') {
        $pdo->prepare('UPDATE mensagens_contato SET lida = 1 - lida WHERE id = ?')->execute([$id]);
    }
    header('Location: /admin/mensagens.php');
    exit;
}

$mensagens = $pdo->query(
    'SELECT * FROM mensagens_contato ORDER BY criado_em DESC'
)->fetchAll();
$token = csrf_token();

$tituloPagina = 'Mensagens';
require __DIR__ . '/incluir/topo.php';
?>
<h1>Mensagens de contato</h1>
<?php if (!$mensagens): ?>
    <p>Nenhuma mensagem recebida ainda.</p>
<?php else: ?>
    <?php foreach ($mensagens as $m): ?>
        <article class="adm-cartao">
            <p style="margin:0 0 6px">
                <strong><?= e($m['nome']) ?></strong>
                &lt;<a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>&gt;
                <?php if (!$m['lida']): ?>
                    <span class="adm-tag adm-tag--sim">nova</span>
                <?php endif; ?>
            </p>
            <p style="margin:0 0 6px;color:var(--cl-ink-dim);font-size:13px">
                <?= e($m['criado_em']) ?>
                <?php if ($m['telefone']): ?> · Tel: <?= e($m['telefone']) ?><?php endif; ?>
                <?php if ($m['assunto']): ?> · Assunto: <?= e($m['assunto']) ?><?php endif; ?>
            </p>
            <p style="white-space:pre-wrap"><?= e($m['mensagem']) ?></p>
            <div class="adm-acoes">
                <form method="post" action="/admin/mensagens.php" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <button class="adm-btn adm-btn--mini adm-btn--claro" name="acao" value="alternar_lida">
                        <?= $m['lida'] ? 'Marcar como não lida' : 'Marcar como lida' ?></button>
                </form>
                <form method="post" action="/admin/mensagens.php" style="display:inline"
                      onsubmit="return confirm('Excluir esta mensagem?');">
                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <button class="adm-btn adm-btn--mini adm-btn--perigo" name="acao" value="excluir">Excluir</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/incluir/rodape.php'; ?>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l admin\mensagens.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add admin/mensagens.php
git commit -m "Adiciona a caixa de mensagens de contato"
```

---

## Task 12: Configurações

**Files:**
- Create: `admin/configuracoes.php`

- [ ] **Step 1: Criar `admin/configuracoes.php`**

```php
<?php
/**
 * Edição das configurações do site (contatos, redes e SMTP).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
exigir_login();

$pdo = bd();

// Campos editáveis: chave => [rótulo, tipo input].
$campos = [
    'whatsapp'       => ['WhatsApp (só dígitos, com DDI — ex.: 5561999999999)', 'text'],
    'email_contato'  => ['E-mail de contato exibido no site', 'email'],
    'instagram'      => ['URL do Instagram', 'text'],
    'facebook'       => ['URL do Facebook', 'text'],
    'youtube'        => ['URL do canal no YouTube', 'text'],
    'smtp_host'      => ['SMTP — servidor', 'text'],
    'smtp_porta'     => ['SMTP — porta (ex.: 587)', 'text'],
    'smtp_usuario'   => ['SMTP — usuário', 'text'],
    'smtp_senha'     => ['SMTP — senha', 'password'],
    'smtp_remetente' => ['SMTP — e-mail remetente', 'email'],
    'smtp_seguranca' => ['SMTP — criptografia (tls, ssl ou nenhuma)', 'text'],
];

$aviso = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } else {
        $st = $pdo->prepare(
            'INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        foreach (array_keys($campos) as $chave) {
            // Campo de senha em branco: mantém o valor atual.
            if ($chave === 'smtp_senha' && ($_POST[$chave] ?? '') === '') {
                continue;
            }
            $st->execute([$chave, trim((string) ($_POST[$chave] ?? ''))]);
        }
        $aviso = ['tipo' => 'ok', 'texto' => 'Configurações salvas.'];
    }
}

// Lê os valores atuais.
$valores = [];
foreach ($pdo->query('SELECT chave, valor FROM configuracoes') as $linha) {
    $valores[$linha['chave']] = (string) $linha['valor'];
}

$token = csrf_token();
$tituloPagina = 'Configurações';
require __DIR__ . '/incluir/topo.php';
?>
<h1>Configurações</h1>
<?php if ($aviso !== null): ?>
    <p class="adm-aviso adm-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
<?php endif; ?>
<form method="post" action="/admin/configuracoes.php" class="adm-cartao">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    <?php foreach ($campos as $chave => [$rotulo, $tipo]): ?>
        <label class="adm-campo"><span><?= e($rotulo) ?></span>
            <?php if ($chave === 'smtp_senha'): ?>
                <input type="password" name="<?= e($chave) ?>"
                       placeholder="<?= $valores[$chave] !== '' ? 'Senha salva — preencha só para trocar' : '' ?>">
            <?php else: ?>
                <input type="<?= e($tipo) ?>" name="<?= e($chave) ?>"
                       value="<?= e($valores[$chave] ?? '') ?>">
            <?php endif; ?>
        </label>
    <?php endforeach; ?>
    <button class="adm-btn" type="submit">Salvar configurações</button>
</form>
<?php require __DIR__ . '/incluir/rodape.php'; ?>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l admin\configuracoes.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add admin/configuracoes.php
git commit -m "Adiciona a tela de configurações do site"
```

---

## Task 13: Deploy e verificação final

**Files:** nenhum arquivo novo — deploy e verificação.

- [ ] **Step 1: Rodar a suíte de testes**

Run: `& "C:\php83\php.exe" tests\correr.php`
Expected: `Total: 36 ok, 0 falha(s)`.

- [ ] **Step 2: Enviar tudo para o servidor**

Run: `bash deploy/enviar.sh`
Expected: `Deploy concluído.` — incluindo `lib/PHPMailer/*`, `includes/email.php`, `assets/css/admin.css` e os arquivos de `admin/`.

- [ ] **Step 3: Verificar a proteção e o acesso do painel**

```bash
echo "login:        $(curl -s -o /dev/null -w '%{http_code}' https://contalele.com.br/admin/login.php)"
echo "painel s/login: $(curl -s -o /dev/null -w '%{http_code}' -L https://contalele.com.br/admin/)"
echo "sessao.php:    $(curl -s -o /dev/null -w '%{http_code}' https://contalele.com.br/admin/incluir/sessao.php)"
```
Expected: `login.php` → 200; `admin/` sem login → 200 após redirecionar para o login (a página de login renderiza); `admin/incluir/sessao.php` → 403 (bloqueado pelo `.htaccess`).

- [ ] **Step 4: Testar o login e o fluxo do painel (manual, no navegador)**

Acesse `https://contalele.com.br/admin/login.php` e entre com `contato@contalele.com.br` e a senha provisória definida na instalação. Confirme:
- O primeiro acesso redireciona para a troca de senha; defina uma senha nova (8+ caracteres).
- O dashboard mostra os contadores (cordéis = 9, livros = 5, etc.).
- Em "Cordéis", a listagem mostra os 9 cordéis; editar um e salvar funciona; publicar/despublicar alterna o selo; reordenar move o item.
- "Mensagens" lista as mensagens de contato (se houver).
- "Configurações" salva e relê os valores.

- [ ] **Step 5: Verificar o site público após o uso do painel**

Confirme que o site público continua íntegro: `curl -s -o /dev/null -w "%{http_code}" https://contalele.com.br/cordeis` → 200.

- [ ] **Step 6: Limpar o cache do Cloudflare se necessário**

Se uma alteração feita no painel não aparecer no site público, o cache do Cloudflare pode estar servindo assets antigos. O HTML é `DYNAMIC` (não cacheado), mas imagens enviadas pelo painel são novas — só haverá cache após o primeiro acesso. Não é necessária ação de purge para conteúdo novo.

- [ ] **Step 7: Commit final e push**

```bash
git push origin main
```
Expected: push aceito para `contalele-novo`.

---

## Verificação de cobertura (auto-revisão)

- **Login com sessão, bcrypt, CSRF, bloqueio por tentativas** (spec §7) → Tasks 3, 5 ✓
- **Troca de senha forçada no primeiro acesso** (spec §7) → Task 6 ✓
- **Dashboard com contadores** (spec §7) → Task 7 ✓
- **CRUD de cordéis, e-books, notícias, depoimentos, festivais, prêmios** (spec §7) → Tasks 9, 10 ✓
- **Reordenar, publicar/despublicar** (spec §7) → Task 10 ✓
- **Caixa de mensagens de contato** (spec §7) → Task 11 ✓
- **Configurações incl. SMTP** (spec §7, §8) → Task 12 ✓
- **Upload de imagens e PDFs com validação** (spec §7) → Tasks 8, 10 ✓
- **Envio de e-mail por SMTP/PHPMailer** (spec §8) → Tasks 1, 2 ✓
- **Visual com tokens da marca** (spec §7) → Task 4 ✓
- **`incluir/` protegido** → Task 3 ✓

**Fora do escopo deste plano:** gestão de múltiplos usuários admin (há um só); recuperação de senha por e-mail (se a senha for perdida, redefinir via banco); a plataforma de cursos (Fase 2). Com este plano, a **Fase 1 fica completa**: site institucional + painel de gestão.

**Observações:**
- A senha de SMTP é gravada em texto na tabela `configuracoes` (a tela de configuração trata o campo como password e mantém o valor se enviado em branco). Criptografar a coluna é uma melhoria futura de segurança, registrada no design.
- O formulário de contato passa a enviar e-mail de verdade assim que o SMTP for preenchido em Configurações.
