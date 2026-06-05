# Spec — Contas de cursistas: cadastro, login, recuperação de senha e login com Google

**Projeto:** Site Conta Lelê
**Data:** 2026-06-05
**Autor:** Thiago Mourão — https://github.com/MouraoBSB
**Status:** Design aprovado — aguardando plano de implementação

---

## 1. Objetivo e escopo

Adicionar ao site um **segundo tipo de usuário** — o **cursista** (usuário comum, público) — ao lado
do **admin** já existente. Esta etapa entrega:

- Cadastro de cursista (criação de conta) com **verificação de e-mail (double opt-in)**.
- Login de cursista (e-mail/senha) e logout.
- **Recuperação de senha por e-mail** — para **cursista e admin**.
- **Login com Google (OpenID Connect)** — para **cursista e admin**.
- **Área logada do cursista** ("Minha conta") mínima: dados, editar nome, trocar senha, sair.

### Fora de escopo (próxima etapa)
Pagamento, assinatura, catálogo e conteúdo de cursos, e a seção "Meus cursos" da área logada.

### Princípios herdados do projeto
- PHP 8.3 puro, sem framework e **sem Composer** (dependências de terceiros são vendorizadas à mão em `lib/`).
- Reaproveitar o que já existe: conexão PDO (`bd()`), CSRF (`csrf_token`/`csrf_validar`), SMTP
  (`enviar_email()`), padrões de bloqueio por tentativas e a identidade visual da marca (classes `cl-*`).
- Não refatorar o login do admin que já está em produção (abordagem híbrida — ver §3).

---

## 2. Decisões de design (travadas)

| # | Decisão | Escolha |
|---|---|---|
| 1 | Modelo de dados | Tabela separada `usuarios_cursistas` (admin permanece em `usuarios_admin`) |
| 2 | Verificação de e-mail no cadastro por senha | Sim — double opt-in |
| 3 | Localização das páginas do cursista | Rotas do front-controller, com o layout da marca |
| 4 | Escopo da área "Minha conta" | Mínima (perfil + segurança) |
| 5 | Estrutura do código de auth | Híbrida — sessão do cursista isolada (`cl_site`), utilitários comuns compartilhados |
| 6 | Recuperação de senha | Cursista **e** admin |
| 7 | Login com Google | Cursista **e** admin (admin: "só vincula, nunca cria") |
| 8 | Credenciais do Google OAuth | Tabela `configuracoes` (editáveis no painel, como o SMTP) |
| 9 | Cliente OAuth/OIDC | Cliente enxuto próprio com cURL (sem `google/apiclient`) |

---

## 3. Arquitetura de código (abordagem híbrida)

### Novos includes compartilhados

- **`includes/tokens.php`** — tokens de uso único, **agnósticos de escopo** (servem cursista e admin):
  - `criar_token(string $escopo, int $usuarioId, string $finalidade, int $ttlSegundos): string`
    gera um token de 256 bits (`bin2hex(random_bytes(32))`), grava **apenas o `hash('sha256', $token)`**
    em `tokens_autenticacao` e devolve o token cru (para compor o link do e-mail).
  - `consumir_token(string $tokenCru, string $finalidade): ?array` — localiza pelo hash; valida
    não-usado e não-expirado; marca `usado_em = NOW()`; devolve a linha (`escopo`, `usuario_id`) ou `null`.
  - `invalidar_tokens(string $escopo, int $usuarioId, string $finalidade): void` — marca como usados
    os tokens pendentes (usado ao concluir uma redefinição de senha).
  - `limpar_tokens_expirados(): void` — housekeeping (chamado de forma oportunística).

- **`includes/cursistas.php`** — acesso a dados de `usuarios_cursistas`:
  `cursista_por_email()`, `cursista_por_id()`, `cursista_por_google_id()`, `criar_cursista()`,
  `vincular_google()`, `marcar_email_verificado()`, `atualizar_senha_cursista()`,
  `atualizar_nome_cursista()`, `registrar_acesso_cursista()`, e o **bloqueio por tentativas**
  (mesma regra do admin: 5 tentativas → bloqueio de 15 min).

- **`includes/autenticacao_cursista.php`** — sessão isolada do cursista (depende de `cursistas.php`):
  - `iniciar_sessao_cursista()` — `session_name('cl_site')`; cookie endurecido idêntico ao admin
    (`httponly`, `samesite=Lax`, `secure` sob HTTPS).
  - `cursista_logado(): ?array` — **só inicia a sessão se o cookie `cl_site` já existir**; visitante
    anônimo não recebe cookie (preserva o cache de HTML no Cloudflare — ver §8).
  - `logar_cursista(array $u): void` — `session_regenerate_id(true)` + dados mínimos na sessão.
  - `deslogar_cursista(): void`.
  - `exigir_cursista(): void` — redireciona a `/entrar?destino=<rota>` se não houver sessão.

- **`includes/google_oauth.php`** — cliente OIDC enxuto com cURL, **agnóstico de escopo**:
  - `google_configurado(): bool` — true se `google_client_id`/`google_client_secret`/`google_oauth_ativo` estão preenchidos.
  - `google_url_autorizacao(string $redirectUri, string $state): string` — monta a URL de
    `accounts.google.com/o/oauth2/v2/auth` com `scope=openid email profile`, `state`, `access_type=online`.
  - `google_trocar_codigo(string $code, string $redirectUri): ?array` — POST a `oauth2.googleapis.com/token`
    (com `client_id`/`client_secret`), devolve `access_token`.
  - `google_perfil(string $accessToken): ?array` — GET a `openidconnect.googleapis.com/v1/userinfo`,
    devolve `['sub','email','email_verified','name','picture']`.

### Extensão de `includes/email.php` (retrocompatível)
- `enviar_email()` ganha um parâmetro opcional `?string $html = null`. Quando informado, envia
  multipart: `Body` = HTML, `AltBody` = `$corpo` (texto). Sem o parâmetro, comportamento idêntico ao atual.
- Novo `template_email(string $titulo, array $paragrafos, ?string $textoBotao = null, ?string $urlBotao = null): string`
  — monta o HTML na identidade da marca (fundo creme `--bg`, texto café `--ink`, botão pílula `--ink`/`--bg-3`,
  girassol como ornamento). Tabelas inline-styled para compatibilidade com clientes de e-mail.

### Arquivos modificados
- `index.php` — novas rotas (ver §5).
- `includes/cabecalho.php` — ponto de entrada da conta no header ("Entrar" / "Minha conta").
- `includes/email.php` — extensão acima.
- `sql/schema.sql` — novas tabelas e colunas.
- `admin/login.php` — link "Esqueci minha senha" + botão "Entrar com Google".
- `admin/configuracoes.php` — novos campos: `google_client_id`, `google_client_secret` (mascarado),
  `google_oauth_ativo`.
- `admin/diagnostico.php` — incluir as tabelas novas em `$esperadas`.
- `instalar.php` — semear as novas chaves de `configuracoes` (Google).

### Arquivos novos do admin (recuperação + Google)
- `admin/recuperar-senha.php`, `admin/redefinir-senha.php` (escopo `admin`).
- `admin/google.php` (inicia o fluxo OAuth do admin), `admin/google-callback.php` (callback do admin).

---

## 4. Modelo de dados

### 4.1 Nova tabela `usuarios_cursistas`
```sql
CREATE TABLE IF NOT EXISTS usuarios_cursistas (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome              VARCHAR(120) NOT NULL,
    email             VARCHAR(160) NOT NULL,
    senha_hash        VARCHAR(255) NULL,              -- NULL para contas só-Google
    google_id         VARCHAR(40)  NULL,              -- claim `sub` do Google
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
```

### 4.2 Nova coluna em `usuarios_admin`
```sql
ALTER TABLE usuarios_admin
    ADD COLUMN google_id VARCHAR(40) NULL,
    ADD UNIQUE KEY uq_usuarios_admin_google (google_id);
```
`senha_hash` do admin permanece **NOT NULL** — Google é método adicional, nunca substituto.

### 4.3 Nova tabela `tokens_autenticacao`
```sql
CREATE TABLE IF NOT EXISTS tokens_autenticacao (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    escopo      ENUM('cursista','admin')           NOT NULL,
    usuario_id  INT UNSIGNED                        NOT NULL,
    finalidade  ENUM('verificacao','recuperacao')  NOT NULL,
    token_hash  CHAR(64)     NOT NULL,              -- sha256 (hex) do token cru
    expira_em   DATETIME     NOT NULL,
    usado_em    DATETIME     NULL,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token_hash (token_hash),
    KEY ix_token_lookup (escopo, usuario_id, finalidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.4 Novas chaves em `configuracoes`
`google_client_id`, `google_client_secret`, `google_oauth_ativo` (`'1'`/`'0'`).

### 4.5 Migração do banco já instalado
`sql/schema.sql` recebe `usuarios_cursistas` e `tokens_autenticacao` com `CREATE TABLE IF NOT EXISTS`
(instalações novas já as criam). Para o banco de produção existente, um **`migrar.php`** token-protegido
— espelhando `instalar.php`, **bloqueado no `.htaccess`**, idempotente e removível após uso — executa:
1. `CREATE TABLE IF NOT EXISTS` das duas tabelas novas;
2. `ALTER TABLE usuarios_admin ADD COLUMN google_id …` de forma idempotente (verifica antes via
   `SHOW COLUMNS LIKE 'google_id'`);
3. `INSERT IGNORE` das novas chaves de `configuracoes`.

TTL dos tokens: **verificação = 24h**, **recuperação = 1h**.

---

## 5. Rotas e páginas

### 5.1 Rotas públicas (front-controller `index.php`, com layout da marca)

| Rota (URL) | Chave em `$rotas` → arquivo | Função |
|---|---|---|
| `/criar-conta` | `criar-conta` → `paginas/criar-conta.php` | Cadastro por e-mail/senha |
| `/entrar` | `entrar` → `paginas/entrar.php` | Login por e-mail/senha |
| `/sair` | `sair` → `paginas/sair.php` | Logout do cursista |
| `/verificar-email` | `verificar-email` → `paginas/verificar-email.php` | Confirma a conta via `?token=` |
| `/recuperar-senha` | `recuperar-senha` → `paginas/recuperar-senha.php` | Solicita link de redefinição |
| `/redefinir-senha` | `redefinir-senha` → `paginas/redefinir-senha.php` | Define nova senha via `?token=` |
| `/minha-conta` | `minha-conta` → `paginas/minha-conta.php` | Painel do cursista (exige login) |
| `/minha-conta/senha` | `minha-conta/senha` → `paginas/conta-senha.php` | Trocar senha logado (exige login) |
| `/entrar/google` | `entrar/google` → `paginas/google-iniciar.php` | Inicia OAuth do cursista |
| `/entrar/google/callback` | `entrar/google/callback` → `paginas/google-callback.php` | Callback OAuth do cursista |

> O front-controller já trata caminhos com barra como chave exata em `$rotas` e bufferiza a página
> antes de emitir HTML, então `header('Location: …')` + `exit` e `session_start()` dentro das páginas
> funcionam (nada é enviado antes do buffer).

### 5.2 Páginas do admin (scripts diretos em `/admin/`)
- `/admin/recuperar-senha.php`, `/admin/redefinir-senha.php`.
- `/admin/google.php`, `/admin/google-callback.php`.
- `/admin/login.php`: adicionar link "Esqueci minha senha" e botão "Entrar com Google".

### 5.3 Cabeçalho público
Em `includes/cabecalho.php`, na área de CTA: "Entrar" (anônimo) ou "Minha conta" (logado),
usando `cursista_logado()` (que não inicia sessão para anônimos).

---

## 6. Fluxos detalhados

### 6.1 Cadastro por e-mail/senha (double opt-in)
1. `/criar-conta`: formulário (`nome`, `email`, `senha`, `confirmacao`) + honeypot (`cl-mel`/campo `site`,
   padrão de `paginas/contato.php`) + CSRF. Validações: nome não vazio, e-mail válido, senha ≥ 8,
   `senha === confirmacao`.
2. **Anti-enumeração:** se o e-mail já existe, **não revela**; mostra a mesma tela de "verifique seu e-mail"
   e dispara um e-mail "você já tem uma conta" (com links de login/recuperação).
3. Cria o cursista com `email_verificado = 0`, gera token `verificacao` (TTL 24h) e envia e-mail
   (`template_email`) com link `…/verificar-email?token=<token>`.
4. `/verificar-email`: `consumir_token(token, 'verificacao')` → `marcar_email_verificado()` →
   `logar_cursista()` → redireciona a `/minha-conta`. Token inválido/expirado: tela com opção de reenviar.
5. Login barra conta com `email_verificado = 0`, oferecendo reenvio do e-mail de verificação
   (com throttle — ver §7).

### 6.2 Login por e-mail/senha (`/entrar`)
Espelha a lógica robusta de `admin/login.php`: `password_verify`; bloqueio por tentativas (5 → 15 min);
ao acertar, zera tentativas, grava `ultimo_acesso`, `session_regenerate_id` e loga. Mensagem genérica
("E-mail ou senha incorretos."). Respeita `?destino=` (rota interna validada) ou vai a `/minha-conta`.
Conta só-Google (sem `senha_hash`) que tente senha: mensagem orientando a entrar com o Google.

### 6.3 Recuperação de senha (cursista e admin)
1. `/recuperar-senha` (e `/admin/recuperar-senha.php`): pede o e-mail. **Resposta sempre idêntica**
   ("Se houver uma conta com esse e-mail, enviamos um link.") — anti-enumeração.
2. Se a conta existe: gera token `recuperacao` (TTL 1h, escopo conforme a origem) e envia o link
   (`…/redefinir-senha?token=` ou `/admin/redefinir-senha.php?token=`). **Throttle por e-mail** (ver §7).
3. `/redefinir-senha`: valida o token; pede nova senha (≥ 8 + confirmação); grava o hash;
   `consumir_token` marca o token usado; `invalidar_tokens(...,'recuperacao')` derruba os demais
   tokens de recuperação pendentes. Idêntico no escopo admin.

> **Limitação conhecida:** sessões já abertas em outros dispositivos **não** são encerradas à força
> nesta iteração (isso exigiria versionamento de sessão por usuário). A senha antiga deixa de funcionar
> imediatamente; o force-logout global fica registrado como melhoria futura.

### 6.4 Login com Google (OIDC) — cursista
1. `/entrar/google`: inicia `cl_site`, gera `state` aleatório salvo na sessão (`$_SESSION['oauth_state']`),
   redireciona à URL de autorização (`redirect_uri = …/entrar/google/callback`).
2. `/entrar/google/callback`: valida `state`; `google_trocar_codigo()` → `google_perfil()`.
   Exige `email_verified = true`. Lógica **criar-ou-vincular**:
   - `cursista_por_google_id(sub)` existe → loga.
   - senão `cursista_por_email(email)` existe → `vincular_google(sub)` (seguro: Google confirma o e-mail),
     garante `email_verificado = 1`, loga.
   - senão **cria** cursista (`google_id = sub`, `email_verificado = 1`, `senha_hash = NULL`), loga.
3. Redireciona a `/minha-conta`.

### 6.5 Login com Google (OIDC) — admin ("só vincula, nunca cria")
1. `/admin/google.php`: inicia `cl_admin`, gera `state`, redireciona (`redirect_uri = …/admin/google-callback.php`).
2. `/admin/google-callback.php`: valida `state`; obtém o perfil; exige `email_verified = true`.
   - admin com `google_id = sub` existe e `ativo` → loga.
   - senão admin com esse `email` existe e `ativo` → vincula `google_id` e loga.
   - **senão: acesso negado** ("Esta conta Google não tem acesso ao painel."). Nunca cria admin.
3. Loga e vai a `/admin/`. Respeita o `precisa_trocar_senha` existente.

### 6.6 Área "Minha conta" (mínima)
- `/minha-conta`: saudação, dados (nome, e-mail, status de verificação, se usa Google), editar nome (POST + CSRF),
  links "Trocar senha" e "Sair". Estrutura preparada para receber "Meus cursos" depois.
- `/minha-conta/senha`: troca de senha do cursista logado (senha atual — se houver — + nova ≥ 8 + confirmação).
  Conta só-Google define a primeira senha sem exigir a atual.

---

## 7. Segurança

- **Hashing:** `password_hash`/`password_verify` (padrão atual do projeto).
- **Tokens:** 256 bits de entropia; **apenas o `sha256` é gravado** (vazamento de banco não expõe tokens
  utilizáveis); uso único (`usado_em`); expiração curta (24h/1h); comparação por igualdade de hash.
- **CSRF:** `csrf_token`/`csrf_validar` em todos os POST.
- **Bloqueio por tentativas:** login do cursista espelha o admin (5 → 15 min).
- **Anti-enumeração de e-mail:** respostas idênticas no cadastro e na recuperação.
- **Honeypot anti-bot** no cadastro (campo `site` oculto, padrão do `/contato`).
- **Sessões isoladas:** `cl_site` (cursista) × `cl_admin` (admin); cookies `HttpOnly` + `SameSite=Lax`
  + `Secure` sob HTTPS; `session_regenerate_id` no login.
- **OAuth:** `state` anti-CSRF por sessão; `client_secret` nunca exposto ao browser; troca de código
  servidor-a-servidor sob TLS; só confia em `email_verified = true`; `redirect_uri` fixo por escopo.
- **Admin via Google:** "só vincula, nunca cria" — impede que qualquer conta Google vire admin.
- **Throttle de envio de e-mail** (verificação/recuperação): mínimo de **60 s** entre envios para o
  mesmo e-mail, verificado pelo `criado_em` do último token daquela finalidade — evita flood.
- **`exigir_cursista()`** protege as páginas da área logada; `limpar_tokens_expirados()` faz housekeeping.

---

## 8. Cache / CDN (Cloudflare)

- Páginas com sessão ativa (login, área logada, formulários autenticados) não são cacheadas — efeito
  natural do cookie de sessão.
- O cabeçalho detecta login **apenas quando o cookie `cl_site` já existe**; visitante anônimo não recebe
  cookie, então o HTML público continua cacheável no Cloudflare.
- Após o deploy, limpar o cache do Cloudflare. Assets já têm cache-busting por `filemtime` em `cabecalho.php`.

---

## 9. Identidade visual

- Páginas públicas reaproveitam classes existentes: `.cl-form`, `.cl-form__grid`, `.cl-campo`,
  `.cl-aviso`/`.cl-aviso--ok`/`.cl-aviso--erro`, `.cl-btn`/`.cl-btn-primary`/`.cl-btn-yellow`/`.cl-btn-ghost`,
  `.cl-hero-simples`, `.cl-secao`, `.cl-eyebrow`, `.cl-em`, e o girassol via `girassol()`.
- **Botão "Entrar com Google":** segue as diretrizes de marca do Google (botão neutro com o "G" oficial
  multicolor). O "G" do Google é **ativo exigido pela plataforma** — exceção legítima à regra de
  "não criar SVG decorativo" do `CLAUDE.md` (não é ornamento da marca Conta Lelê).
- E-mails transacionais usam `template_email()` na paleta da marca (creme/café, botão pílula, girassol).
- Tudo responsivo (mobile-first), seguindo o layout de 402px de referência e o grid do projeto.

---

## 10. Testes

Seguindo o runner CLI existente (`php tests/correr.php`, auto-descoberta de `*-teste.php`, asserções
`afirmar`/`afirmar_igual`):

- `tests/tokens-teste.php` — partes puras e testáveis sem banco: o token cru tem o comprimento/entropia
  esperados; o `sha256` é determinístico; a montagem do link é correta. As operações que dependem de
  banco (consumir/expirar/uso único) são cobertas por verificação manual em ambiente com banco, ou por
  teste integrado se houver banco de testes disponível.
- `tests/cursistas-teste.php` — validação de senha (mínimo 8, confirmação), normalização de e-mail,
  e a regra de bloqueio por tentativas (lógica pura, sem I/O).
- Verificação manual (UAT) ao final: cadastro → e-mail → verificação → login; recuperação cursista e admin;
  Google cursista (criar e vincular) e admin (vincular e negar).

---

## 11. Pré-requisitos do usuário (fora do código)

1. Criar um projeto no Google Cloud Console e configurar a **tela de consentimento OAuth**.
2. Gerar credenciais **OAuth 2.0 Client ID** (tipo "Web application").
3. Registrar os **dois redirect URIs**:
   - `https://contalele.com.br/entrar/google/callback` (cursista)
   - `https://contalele.com.br/admin/google-callback.php` (admin)
4. Lançar `client_id` e `client_secret` em `/admin/configuracoes.php` e ativar o toggle.
5. Configurar o SMTP (já existente) caso ainda não esteja — necessário para os e-mails transacionais.

---

## 12. Resumo de arquivos

**Novos:**
`includes/tokens.php`, `includes/cursistas.php`, `includes/autenticacao_cursista.php`,
`includes/google_oauth.php`, `paginas/criar-conta.php`, `paginas/entrar.php`, `paginas/sair.php`,
`paginas/verificar-email.php`, `paginas/recuperar-senha.php`, `paginas/redefinir-senha.php`,
`paginas/minha-conta.php`, `paginas/conta-senha.php`, `paginas/google-iniciar.php`,
`paginas/google-callback.php`, `admin/recuperar-senha.php`, `admin/redefinir-senha.php`,
`admin/google.php`, `admin/google-callback.php`, `migrar.php`,
`tests/tokens-teste.php`, `tests/cursistas-teste.php`.

**Modificados:**
`index.php`, `includes/cabecalho.php`, `includes/email.php`, `sql/schema.sql`,
`admin/login.php`, `admin/configuracoes.php`, `admin/diagnostico.php`, `instalar.php`, `.htaccess`
(bloquear `migrar.php`).
