# Design — Site Conta Lelê · Fase 0 (Fundação) + Fase 1 (Vitrine institucional)

> Autor: Thiago Mourão — https://github.com/MouraoBSB
> Data: 2026-05-22
> Status: aprovado para planejamento

---

## 1. Visão geral e objetivo

Reconstruir o site da **Conta Lelê** (contalele.com.br) — marca de contação de
histórias da Letícia Rocha Mourão Marques. O site antigo foi arquivado no git
(commit `63f12b7`) e a raiz do servidor já foi esvaziada.

O projeto completo tem duas missões: **divulgação** da Conta Lelê e **venda de
cursos online** (estilo Hotmart). Esse escopo grande foi decomposto em fases
independentes — cada uma com seu próprio ciclo de design → plano → implementação:

| Fase | Escopo |
|---|---|
| **0 — Fundação & Deploy** | Estrutura, conexão MySQL, deploy FTPS, layout base, segurança de credenciais |
| **1 — Vitrine institucional** | Páginas públicas de divulgação + painel admin de conteúdo |
| 2 — Plataforma de cursos | (futuro) catálogo, contas de aluno, checkout, área de membros, admin de cursos |

**Este documento cobre as Fases 0 e 1.** A Fase 2 terá design próprio.

---

## 2. Escopo

### Dentro do escopo (Fase 0 + 1)

- Estrutura de projeto PHP puro, deploy via FTPS, configuração de ambiente.
- Site institucional responsivo com 8 páginas públicas + 404.
- Painel administrativo para gerenciar o conteúdo que cresce com o tempo.
- Formulário de contato funcional (grava no banco + e-mail) e botão de WhatsApp.
- Banco de dados MySQL com schema versionado.
- SEO, performance, acessibilidade (WCAG AA) e responsividade mobile-first.
- Tratamento de erros estruturado e autodiagnóstico.

### Fora do escopo

- Toda a plataforma de cursos (catálogo, contas de aluno, checkout, pagamento,
  área de membros, player de aulas) — é a Fase 2.
- Página "Projeto Cordelzinho" — conteúdo pendente com a Letícia (ver §15).
- Newsletter — removida (o canal do YouTube já é o ponto de inscrição).

---

## 3. Decisões tomadas

| Tema | Decisão |
|---|---|
| Estratégia de deploy | Direto na raiz, à vista. Publicação **página por página**, cada uma só quando completa. |
| Gestão de conteúdo | **Híbrido** — conteúdo que cresce no banco + painel admin; textos institucionais fixos no código. |
| Contato | Formulário funcional (grava no banco + e-mail) **+** botão de WhatsApp fixo. Sem newsletter. |
| Arquitetura PHP | PHP puro organizado, sem framework. Front-controller leve + includes. |
| Versão PHP | 8.3 (já ativa no servidor). Código compatível com qualquer PHP 8.x. |
| CSS/JS | Próprios, com os tokens da marca. Libs externas só por CDN. Sem Bootstrap. |

---

## 4. Arquitetura

### 4.1 Stack

- **Backend:** PHP 8.3, MySQL via PDO (prepared statements).
- **Frontend:** HTML5 semântico, CSS próprio com os tokens da marca
  (`brand-tokens.css`), JavaScript vanilla.
- **CDN:** Google Fonts (Nunito + Fredoka One); uma biblioteca leve de carrossel
  para os depoimentos. Nada de framework JS.
- **Hospedagem:** compartilhada (cPanel — Napoleon host). Deploy por FTPS.

### 4.2 Estrutura de arquivos

A raiz do FTP (`/`) é o docroot — tudo nela é acessível pela web; arquivos
sensíveis são bloqueados por `.htaccess`.

```
/  (raiz FTP = docroot)
├── index.php              front-controller + roteador
├── .htaccess              URLs limpas, gzip, cache, headers de segurança
├── config.php             credenciais DB/SMTP — fora do git, bloqueado por .htaccess
├── config.exemplo.php     modelo versionado, sem segredos
├── robots.txt
├── sitemap.xml
├── favicon.ico
├── includes/              (.htaccess: deny all)
│   ├── conexao.php        PDO + tratamento de falha
│   ├── cabecalho.php      <head>, header, navegação
│   ├── rodape.php         footer + scripts
│   ├── funcoes.php        helpers (slug, escape, sanitização, CSRF)
│   ├── seo.php            meta tags, Open Graph, JSON-LD
│   ├── erros.php          handler global de erros/exceções
│   └── conteudo.php       arrays PHP dos textos institucionais fixos
├── paginas/               (.htaccess: deny all — incluídas pelo roteador)
│   ├── inicio.php  sobre.php  contacao.php  cordeis.php
│   ├── livros.php  festivais.php  cursos.php  contato.php
│   └── erro-404.php
├── admin/
│   ├── index.php          dashboard
│   ├── login.php  logout.php
│   ├── cordeis.php  ebooks.php  noticias.php
│   ├── depoimentos.php  festivais.php  premios.php
│   ├── mensagens.php  configuracoes.php
│   ├── diagnostico.php    autodiagnóstico do ambiente
│   └── includes/          layout + autenticação do painel
├── assets/
│   ├── css/style.css      (+ versão minificada)
│   ├── js/main.js
│   └── img/               logos, girassol, fotos da Lelê, capas, equipe
├── downloads/             PDFs dos e-books
├── uploads/               imagens/PDFs enviados pelo admin (.htaccess: sem PHP)
├── logs/                  logs de erro estruturados (fora do git)
├── lib/PHPMailer/          PHPMailer incluído sem Composer
├── sql/
│   ├── schema.sql         schema versionado
│   └── seed.php           cria 1º usuário admin + configurações iniciais
└── tests/                 testes leves (CLI) das funções puras
```

### 4.3 Roteamento

`.htaccess` reescreve toda requisição que não seja arquivo/diretório real para
`index.php`. O `index.php`:

1. Carrega `config.php`, `includes/erros.php`, `includes/conexao.php`,
   `includes/funcoes.php`.
2. Lê a rota de `$_SERVER['REQUEST_URI']` e consulta um mapa fixo:

   ```
   ''           → inicio        'cordeis'   → cordeis
   'sobre'      → sobre         'livros'    → livros
   'contacao'   → contacao      'festivais' → festivais
   'cursos'     → cursos        'contato'   → contato
   ```

3. Rota desconhecida → HTTP 404 + `paginas/erro-404.php`.
4. Monta a página: `includes/cabecalho.php` + `paginas/<rota>.php` +
   `includes/rodape.php`.

URLs finais limpas: `/`, `/sobre`, `/contacao`, `/cordeis`, `/livros`,
`/festivais`, `/cursos`, `/contato`.

---

## 5. Sitemap e páginas

Navegação principal (7 itens): **Início · A Lelê · Contação de Histórias ·
Cordéis · Livros · Contato**, com **Cursos** como botão destacado.
"Festivais & Imprensa" e o link do canal no YouTube ficam no rodapé.

| Página | Rota | Conteúdo |
|---|---|---|
| **Início** | `/` | Hero (foto + slogan "Eu sou a Lelê! Conto e escrevo histórias pra você!"); o que é a Conta Lelê; destaques de serviços; cordéis em destaque; depoimentos (carrossel); faixa "novo vídeo toda sexta às 16h"; CTA de contato. |
| **A Lelê** | `/sobre` | Bio completa ("Eu sou a Lelê, e vou contar uma história pra você!"); "Por que contar histórias?" (texto pessoal); prêmios; equipe (4 membros). |
| **Contação de Histórias** | `/contacao` | Os 6 serviços; "Para quem contar histórias" (públicos: bebês, gestantes, crianças, adolescentes, adultos, idosos, pacientes, escritores, escolas, hotéis, empresas, condomínios); os 6 diferenciais; CTA de contratação. |
| **Cordéis** | `/cordeis` | Galeria dinâmica: capa + sinopse + vídeo do YouTube. |
| **Livros** | `/livros` | E-books dinâmicos: capa + sinopse + download do PDF. |
| **Festivais & Imprensa** | `/festivais` | Festivais e participações; prêmios; publicações na imprensa. |
| **Cursos** | `/cursos` | Teaser "em breve" + captura de interesse (placeholder até a Fase 2). |
| **Contato** | `/contato` | Formulário + WhatsApp + redes sociais + localização (Planaltina-DF). |
| **404** | — | Página de erro personalizada com a identidade da marca. |

O conteúdo textual vem da documentação do site antigo
(`Instruções Site antigo/DOCUMENTACAO-CONTEUDO-CONTA-LELE.md`), nas versões já
corrigidas. `lang="pt-BR"` em todas as páginas (o site antigo tinha `lang="en"`).

---

## 6. Modelo de dados

Banco `cemaneto_contalele`, InnoDB, `utf8mb4`. **A primeira tarefa da Fase 0
verifica o estado atual do banco** (`SHOW TABLES`) antes de criar qualquer
estrutura — nada é assumido nem sobrescrito.

| Tabela | Colunas principais |
|---|---|
| `usuarios_admin` | `id`, `nome`, `email` (único), `senha_hash`, `precisa_trocar_senha`, `tentativas_login`, `bloqueado_ate`, `ultimo_acesso`, `ativo`, `criado_em` |
| `cordeis` | `id`, `titulo`, `slug` (único), `sinopse`, `video_youtube`, `imagem_capa`, `ordem`, `publicado`, `criado_em`, `atualizado_em` |
| `ebooks` | `id`, `titulo`, `slug` (único), `sinopse`, `arquivo_pdf`, `imagem_capa`, `ordem`, `publicado`, `criado_em`, `atualizado_em` |
| `noticias` | `id`, `titulo`, `veiculo`, `url`, `imagem`, `data_publicacao`, `ordem`, `publicado`, `criado_em` |
| `depoimentos` | `id`, `autor`, `texto`, `foto`, `ordem`, `publicado`, `criado_em` |
| `festivais` | `id`, `titulo`, `descricao`, `ano`, `videos`, `imagem`, `ordem`, `publicado`, `criado_em` |
| `premios` | `id`, `titulo`, `ano`, `ordem`, `publicado` |
| `mensagens_contato` | `id`, `nome`, `email`, `telefone`, `assunto`, `mensagem`, `lida`, `ip`, `criado_em` |
| `configuracoes` | `chave` (PK), `valor`, `descricao` |

Convenções: `ordem` INT para ordenação manual; `publicado` TINYINT(1); datas em
`DATETIME` com `CURRENT_TIMESTAMP`. O schema fica em `sql/schema.sql`.

Tabela `configuracoes` guarda dados editáveis pelo painel, sem mexer no código:
número de WhatsApp, e-mail de contato, **parâmetros de SMTP** (servidor, porta,
usuário, senha, e-mail remetente, criptografia) e URLs de Instagram, Facebook e
YouTube. Nenhuma credencial de SMTP fica embutida no código.

**Fluxo de dados:** as páginas públicas leem apenas registros `publicado = 1`,
ordenados por `ordem`. O admin escreve no banco. Os textos institucionais fixos
(bio, serviços, públicos, diferenciais) ficam em `includes/conteudo.php`.

---

## 7. Painel administrativo (`/admin/`)

- **Login:** sessão PHP, senha verificada com `password_verify` (bcrypt), token
  CSRF, bloqueio temporário após várias tentativas erradas
  (`tentativas_login` / `bloqueado_ate`).
- **Dashboard:** contadores — mensagens não lidas, total de cordéis, e-books etc.
- **CRUDs:** cordéis, e-books, notícias, depoimentos, festivais, prêmios — criar,
  editar, excluir, reordenar, publicar/despublicar.
- **Mensagens:** caixa de entrada do formulário de contato — listar, ver, marcar
  como lida, excluir.
- **Configurações:** WhatsApp, e-mails, redes sociais e **SMTP** (servidor,
  porta, usuário, senha, remetente, criptografia) — o envio de e-mail é
  configurado por aqui, nunca no código.
- **Upload:** imagens (capas) e PDFs — validação de MIME real, extensão e
  tamanho; nomes sanitizados/aleatórios; gravados em `uploads/`.
- **`diagnostico.php`:** autodiagnóstico — conexão com o banco, versão do PHP,
  extensões (PDO, mbstring, gd), permissões de escrita em `uploads/` e `logs/`,
  teste de SMTP.
- **Primeiro acesso:** `sql/seed.php` cria um usuário admin com senha provisória
  e `precisa_trocar_senha = 1`; a troca é forçada no primeiro login.
- Visual com os tokens da marca, em variante UI mais sóbria.

---

## 8. Formulário de contato e e-mail

- Campos: nome, e-mail, telefone, assunto, mensagem.
- Proteção: token CSRF, honeypot anti-bot, rate limit por IP, validação
  server-side com mensagens claras em pt-BR.
- Ao enviar: grava em `mensagens_contato` **e** dispara e-mail de notificação
  para `contato@contalele.com.br`.
- Envio via **PHPMailer** (incluído em `lib/PHPMailer/`, sem Composer) usando os
  parâmetros de SMTP definidos na tela de Configurações do painel admin (tabela
  `configuracoes`) — nenhuma credencial embutida no código.
- Enquanto o SMTP não estiver configurado, a mensagem continua sendo gravada em
  `mensagens_contato` (nada se perde) e o envio recai em `mail()` do PHP.
- Botão de WhatsApp fixo (`wa.me/5561991938603`) presente em todas as páginas.

---

## 9. Segurança

- PDO com prepared statements (anti SQL injection).
- Escape de toda saída com `htmlspecialchars` (anti XSS).
- Token CSRF em todos os formulários (público e admin).
- Senhas com `password_hash` / `password_verify` (bcrypt).
- Sessões endurecidas: cookies `HttpOnly`, `SameSite=Lax`, `Secure`; ID
  regenerado no login.
- `.htaccess` bloqueia acesso direto a `config.php`, `includes/`, `paginas/`,
  `logs/`, `sql/`, `lib/`; `uploads/` não executa PHP.
- Headers de segurança no `.htaccess`: `X-Content-Type-Options`,
  `X-Frame-Options`, `Referrer-Policy`, CSP básica.
- HTTPS forçado (redirect http→https) — depende do SSL ativo (pendência §15).
- Anti-spam no formulário público (honeypot + rate limit); bloqueio de força
  bruta no login.
- Upload: validação de MIME real + extensão + tamanho; nomes sanitizados.
- Credenciais (DB, SMTP, FTP) nunca versionadas — `.gitignore` cobre
  `config.php`, `logs/`, `uploads/` e arquivos de credencial de deploy.

---

## 10. Tratamento de erros e diagnóstico

- Handler global de erros e exceções (`includes/erros.php`) grava log
  estruturado em `logs/erro-AAAA-MM-DD.log`, em pt-BR, com contexto (rota,
  entrada, stack).
- `display_errors` desligado em produção — o visitante nunca vê stack trace.
- Páginas amigáveis de **404** e **500** com a identidade da marca.
- Conexão com o banco em `try/catch`: falha registra log e mostra mensagem
  amigável, sem expor credenciais.
- Validação de formulário server-side com mensagens claras em pt-BR.
- `admin/diagnostico.php` para autodiagnóstico do ambiente (ver §7).

---

## 11. SEO, performance, acessibilidade e responsividade

**SEO** — HTML5 semântico, 1 `<h1>` por página, `lang="pt-BR"`,
`<title>`/meta description únicos, Open Graph + Twitter Cards, JSON-LD
(`Person` da Lelê), URLs limpas, `robots.txt`, `sitemap.xml`, canonical, `alt`
em todas as imagens.

**Performance** — CSS/JS externos e minificados, gzip + cache headers no
`.htaccess`, imagens otimizadas com `loading="lazy"` e dimensões definidas,
WebP onde possível, fontes via CDN com `display=swap`, índices SQL adequados.
Assets versionados (`?v=hash`) para furar o cache do navegador após cada deploy.

**Acessibilidade (WCAG AA)** — contraste verificado nos tokens da marca,
navegação por teclado, foco visível, landmarks semânticos, `label` em todo
campo, skip link, respeito a `prefers-reduced-motion`.

**Responsividade** — mobile-first; breakpoints da marca (referência 402px
mobile, 1180px desktop); funciona em mobile, tablet e desktop.

---

## 12. Deploy e ambiente

- **Servidor:** FTPS explícito — `186.209.113.101:21`. Raiz do FTP = docroot.
- Deploy por script (FTPS), página por página, cada uma só quando completa.
- `config.php` existe apenas no servidor e localmente (fora do git);
  `config.exemplo.php` é o modelo versionado.
- **Primeira tarefa da Fase 0:** conectar ao MySQL e ao FTP e levantar o estado
  do ambiente — versão do PHP, extensões disponíveis, tabelas já existentes no
  banco, conteúdo atual da raiz. Nada é criado antes dessa verificação.
- Após cada atualização: limpeza de cache (versionamento de assets;
  cache do `.htaccess` ajustado).

---

## 13. Conteúdo e assets

- **Textos:** da documentação do site antigo (versões já corrigidas), mantendo a
  voz da marca (1ª pessoa, calorosa; regionalismo nordestino preservado).
- **Depoimentos:** tom espontâneo de WhatsApp mantido (autenticidade).
- **Fotos da Lelê / logos / girassol:** pasta `Identidade Visual` (70 fotos,
  3 logos, ícone do girassol).
- **Assets do site antigo** (capas de cordéis, PDFs dos e-books, fotos de
  festivais/equipe/depoimentos): extraídos do histórico do git (commit
  `63f12b7`).
- Imagens grandes serão otimizadas/redimensionadas antes de subir.

**Ajuste no `CLAUDE.md` do projeto:** a seção 10 descreve a estrutura `.jsx` de
um protótipo de design. Será reescrita **apenas a seção 10** para refletir esta
arquitetura HTML/PHP — as seções 1–9 (identidade da marca) permanecem intactas.

---

## 14. Verificação

- Testes leves em CLI (`tests/`) das funções puras críticas: geração de slug,
  validação de e-mail, sanitização de entrada.
- `admin/diagnostico.php` valida o ambiente.
- Checklist manual: cada página renderiza; formulário grava no banco e envia
  e-mail; CRUDs do admin funcionam; login/logout e bloqueio por tentativas;
  layout responsivo nos 3 tamanhos.
- Lighthouse (SEO, Performance, Acessibilidade) como meta de qualidade.
- Validação de HTML.

---

## 15. Pendências externas

Itens que dependem de ação fora do código. Cada um tem um padrão definido para
não bloquear o desenvolvimento:

1. **E-mail SMTP** — após o deploy, criar uma caixa de e-mail no cPanel (ex.:
   `contato@contalele.com.br`) e cadastrar servidor/porta/usuário/senha na tela
   de Configurações do painel admin. Não bloqueia o desenvolvimento: enquanto
   não configurado, as mensagens são gravadas no banco e o envio recai em
   `mail()`.
2. **SSL/HTTPS** — confirmar certificado SSL ativo no domínio. Padrão: assumir
   ativo e deixar o redirect http→https pronto para ligar.
3. **Favicon** — a marca pede um logo roxo para selo/favicon; há apenas
   preto/branco/amarelo. Padrão: gerar o favicon a partir do ícone do girassol.
4. **Cordelzinho** — texto do projeto pendente com a Letícia; fica fora da
   Fase 1 até ser fornecido.

---

## 16. Fases futuras (fora deste documento)

A **Fase 2 — Plataforma de cursos** terá design próprio e cobrirá: catálogo e
páginas de venda dos cursos, cadastro/login de alunos, checkout e pagamento,
área de membros com player de aulas e progresso, e painel administrativo de
cursos. O sistema de login e o painel admin desta Fase 1 servem de base para
ela.
