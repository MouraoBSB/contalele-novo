# Plano de Implementação — Vitrine Pública do Site Conta Lelê

> **Para executores agênticos:** SUB-SKILL OBRIGATÓRIA: use `superpowers:subagent-driven-development` (recomendado) ou `superpowers:executing-plans` para implementar este plano tarefa a tarefa. Os passos usam caixas de seleção (`- [ ]`) para acompanhamento.

**Objetivo:** Construir as 8 páginas públicas do site Conta Lelê — a vitrine institucional que substitui o site antigo e prepara o terreno comercial para os cursos.

**Arquitetura:** Continuação do Plano 1 (Fundação). As páginas em `paginas/` são montadas pelo front-controller `index.php`. Conteúdo que cresce (cordéis, e-books, notícias, depoimentos, festivais, prêmios) vem do banco MySQL via uma camada de acesso a dados (`includes/repositorio.php`); textos institucionais fixos ficam em `includes/conteudo.php`. O conteúdo inicial é semeado por um script web protegido por token. Verificação é feita no site em produção após o deploy de cada página.

**Stack:** PHP 8.3, MySQL/PDO, HTML5, CSS próprio com tokens da marca, JavaScript vanilla. Sem novas dependências.

**Documentos de referência:**
- Spec: `docs/superpowers/specs/2026-05-22-site-contalele-fundacao-vitrine-design.md`
- Diretrizes da marca: `CLAUDE.md` (seções 1–9)
- **Conteúdo (fonte da verdade dos textos):** `Instruções Site antigo/DOCUMENTACAO-CONTEUDO-CONTA-LELE.md` — daqui em diante citado como **DOC-CONTEUDO**. Use sempre as versões já corrigidas dos textos.

---

## Contexto herdado do Plano 1 (já existe e funciona)

- `index.php` — front-controller. Mapa `$rotas` com 8 rotas: `'' inicio`, `sobre`, `contacao`, `cordeis`, `livros`, `festivais`, `cursos`, `contato`. Hoje só `inicio` tem arquivo; as outras 7 caem em 404.
- `includes/`: `conexao.php` (`bd(): PDO`), `erros.php` (`registrar_log()`, `pagina_erro()`, `ativar_tratamento_erros()`), `funcoes.php` (`e()`, `gerar_slug()`, `validar_email()`, `limpar_texto()`, `csrf_token()`, `csrf_validar()`), `seo.php` (`seo_padrao()`, `seo_render()`), `cabecalho.php`, `rodape.php`.
- `index.php` define `$seo` (via `seo_padrao()`) e o passa ao `cabecalho.php`. A Task 1 ajusta o roteador para que cada página possa sobrescrever `$seo` antes de o `<head>` ser renderizado.
- Banco `cemaneto_contalele`: 9 tabelas criadas. `configuracoes` semeada (whatsapp, e-mails, redes). As tabelas de conteúdo estão **vazias**.
- CSS: `assets/css/style.css` tem o layout base (cabeçalho, rodapé, botões, `.cl-conteudo`, `.cl-btn`, etc.).
- Deploy: `bash deploy/enviar.sh [arquivos...]`. PHP local: `C:\php83\php.exe`. Testes: `php tests/correr.php`.
- O site está atrás do **Cloudflare**.

---

## Ajuste de arquitetura: SEO definido pela página

Hoje o `index.php` inclui `cabecalho.php` (que renderiza o `<head>`) **antes** de incluir a página. Para cada página ter `<title>`/descrição próprios, a página precisa definir `$seo` antes do `<head>` ser renderizado. A Task 1 resolve isso: o roteador passa a **avaliar a página em buffer**, capturar o `$seo` que ela definir, e só então renderizar cabeçalho + conteúdo + rodapé.

---

## Estrutura de arquivos (deste plano)

```
includes/
├── repositorio.php       NOVO  — acesso a dados (listagens do banco)
├── conteudo.php          NOVO  — textos institucionais fixos (arrays PHP)
├── seo.php               MODIFICADO — + json_ld()
paginas/
├── inicio.php            REESCRITO — home completa
├── sobre.php             NOVO
├── contacao.php          NOVO
├── cordeis.php           NOVO
├── livros.php            NOVO
├── festivais.php         NOVO
├── cursos.php            NOVO
├── contato.php           NOVO  — inclui o processamento do formulário
index.php                 MODIFICADO — renderização em 2 fases (SEO da página)
assets/css/style.css      MODIFICADO — + componentes da vitrine
assets/img/               NOVO  — fotos e capas otimizadas
downloads/                NOVO  — PDFs dos e-books
favicon.ico               NOVO
assets/img/og-padrao.jpg  NOVO
sitemap.xml               NOVO
seed-conteudo.php          NOVO (raiz) — semeia o conteúdo; removido após uso
tests/repositorio-teste.php NOVO
```

**Convenções:** `declare(strict_types=1)` nos PHP; indentação 4 espaços; aspas simples; texto pt-BR; cabeçalho de autoria `Thiago Mourão — https://github.com/MouraoBSB` em arquivos novos; toda saída dinâmica escapada com `e()`.

---

## Task 1: Renderização em duas fases (SEO por página)

**Files:**
- Modify: `index.php`

- [ ] **Step 1: Reescrever o trecho de renderização do `index.php`**

Substitua, em `index.php`, o bloco que hoje é:
```php
// Variáveis de SEO disponíveis para cada página (sobrescritas dentro dela).
$seo = seo_padrao($config['site']['url']);

// Renderiza com buffer: se algo falhar no meio, o handler de erro descarta
// o HTML parcial e exibe a página de erro limpa (ver pagina_erro()).
ob_start();
require CL_RAIZ . '/includes/cabecalho.php';
require CL_RAIZ . '/paginas/' . $pagina . '.php';
require CL_RAIZ . '/includes/rodape.php';
ob_end_flush();
```
por:
```php
// SEO padrão; a página pode sobrescrever $seo antes de seu HTML.
$seo = seo_padrao($config['site']['url']);

// Fase 1: avalia a página em buffer. A página define $seo (se quiser) e
// produz seu HTML de conteúdo, capturado em $conteudoPagina.
ob_start();
require CL_RAIZ . '/paginas/' . $pagina . '.php';
$conteudoPagina = ob_get_clean();

// Fase 2: monta a resposta — cabeçalho (com o $seo final) + conteúdo + rodapé.
ob_start();
require CL_RAIZ . '/includes/cabecalho.php';
echo $conteudoPagina;
require CL_RAIZ . '/includes/rodape.php';
ob_end_flush();
```

Observação: `pagina_erro()` (em `erros.php`) já descarta todos os buffers abertos antes de renderizar o erro, então uma exceção na Fase 1 ou 2 continua sendo tratada corretamente.

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l index.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Testar localmente que a home ainda renderiza**

Run (servidor embutido, em background):
```
& "C:\php83\php.exe" -S localhost:8000 index.php
```
`curl -s http://localhost:8000/` deve conter o slogan da home provisória e `</html>`. `curl -s -o NUL -w "%{http_code}" http://localhost:8000/rota-x` deve dar `404`. Encerre o servidor.

- [ ] **Step 4: Commit**

```bash
git add index.php
git commit -m "Permite que cada página defina seu próprio SEO"
```

---

## Task 2: Recuperar e otimizar os assets

**Files:**
- Create: arquivos em `assets/img/` e `downloads/`

As capas de cordéis, PDFs de e-books e fotos de festivais/equipe/depoimentos estão no repositório antigo `contalele-antigo`. As fotos da Lelê estão em `Identidade Visual/Foto Lele/` (70 arquivos).

- [ ] **Step 1: Clonar o repositório antigo num diretório temporário**

Run (bash):
```bash
git clone --depth 1 https://github.com/MouraoBSB/contalele-antigo.git /tmp/cl-antigo
ls /tmp/cl-antigo/img /tmp/cl-antigo/downloads
```
Expected: as pastas `img/` (capas, festivais, fotos) e `downloads/` (5 PDFs) listadas.

- [ ] **Step 2: Copiar os PDFs dos e-books**

Copie os 5 PDFs de `/tmp/cl-antigo/downloads/` para `downloads/` do projeto:
`A_Galinha_Ruiva.pdf`, `A_Historia_De_Um_Coracao.pdf`, `Cordel_Monstruoso.pdf`, `Fui_Dormir_Em_Uma_Oquinha.pdf`, `Os_Dois_Cabritos.pdf`.

- [ ] **Step 3: Copiar as capas de cordéis e imagens de festivais/equipe/depoimentos**

Copie de `/tmp/cl-antigo/img/` para `assets/img/`:
- Capas de cordéis: `monstruoso.png`, `cabritos.png`, `cazoutras.png`, `sapa.png`, `amarelo.png`, `feminino.png`, `ziquizira.png`, `poderosa.png`, `chico.png`.
- Festivais: `festival1.png` a `festival11.png`.
- Depoentes: `Adairis.jpg`, `Adeilson.jpg`, `Gisely.jpg`, `Iraneide.jpg`, `Marlene.jpg`.
- Equipe: `Thiago.jpg`, `Leticia.jpg`, `Marlete.jpg`, `Kleyton.jpg`.

- [ ] **Step 4: Selecionar 3 fotos da Lelê**

Abra (leia como imagem) as fotos em `Identidade Visual/Foto Lele/` e escolha 3 que sigam o estilo da marca (CLAUDE.md §5: figurino preto, expressão risonha, fundo de estúdio). Copie para `assets/img/` renomeando:
- `lele-hero.jpg` — foto vertical, rosto no terço superior, para o hero da home.
- `lele-sobre.jpg` — foto para a página "A Lelê".
- `lele-contacao.jpg` — foto em ação/contação, para a página de Contação.

- [ ] **Step 5: Otimizar as imagens**

Crie um script temporário `otimizar-imagens.php` na raiz do projeto:
```php
<?php
// Ferramenta de uso único — redimensiona as imagens de assets/img/
// para no máximo 1600px no maior lado, reduzindo o peso para a web.
foreach (glob(__DIR__ . '/assets/img/*') as $f) {
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        continue;
    }
    $img = $ext === 'png' ? @imagecreatefrompng($f) : @imagecreatefromjpeg($f);
    if (!$img) {
        echo 'pulou ' . basename($f) . PHP_EOL;
        continue;
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $maior = max($w, $h);
    if ($maior > 1600) {
        $img = imagescale($img, (int) ($w * 1600 / $maior), (int) ($h * 1600 / $maior));
    }
    if ($ext === 'png') {
        imagesavealpha($img, true);
        imagepng($img, $f, 6);
    } else {
        imagejpeg($img, $f, 82);
    }
    echo 'ok ' . basename($f) . PHP_EOL;
}
echo 'Concluído.' . PHP_EOL;
```
Run: `& "C:\php83\php.exe" otimizar-imagens.php`
Depois remova o script (uso único): `Remove-Item otimizar-imagens.php`.
Confirme com `ls -la assets/img/` que as imagens grandes encolheram (alvo: abaixo de ~400 KB cada).

- [ ] **Step 6: Commit**

```bash
git add assets/img downloads
git commit -m "Recupera e otimiza os assets do site antigo"
```

---

## Task 3: Camada de acesso a dados

**Files:**
- Create: `includes/repositorio.php`
- Test: `tests/repositorio-teste.php`

- [ ] **Step 1: Escrever o teste que falha**

`tests/repositorio-teste.php`:
```php
<?php
declare(strict_types=1);

require __DIR__ . '/../includes/funcoes.php';
require __DIR__ . '/../includes/repositorio.php';

// A validação de nome de tabela é pura (não toca o banco) e pode ser testada.
$erro = null;
try {
    listar_publicados('tabela_invalida; DROP TABLE x');
} catch (InvalidArgumentException $e) {
    $erro = $e;
}
afirmar($erro instanceof InvalidArgumentException, 'listar_publicados() rejeita nome de tabela fora da lista branca');

afirmar(function_exists('listar_cordeis'), 'listar_cordeis() existe');
afirmar(function_exists('listar_ebooks'), 'listar_ebooks() existe');
afirmar(function_exists('listar_noticias'), 'listar_noticias() existe');
afirmar(function_exists('listar_depoimentos'), 'listar_depoimentos() existe');
afirmar(function_exists('listar_festivais'), 'listar_festivais() existe');
afirmar(function_exists('listar_premios'), 'listar_premios() existe');
afirmar(function_exists('youtube_id'), 'youtube_id() existe');

// youtube_id() é pura — extrai o ID de várias formas de URL do YouTube.
afirmar_igual('2s-SudIPNXs', youtube_id('https://www.youtube.com/watch?v=2s-SudIPNXs'), 'youtube_id() de URL watch');
afirmar_igual('abc12345678', youtube_id('https://youtu.be/abc12345678'), 'youtube_id() de URL curta');
afirmar_igual('', youtube_id('texto sem url'), 'youtube_id() devolve vazio quando não há ID');
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `& "C:\php83\php.exe" tests\correr.php`
Expected: falha — `includes/repositorio.php` / funções indefinidas.

- [ ] **Step 3: Implementar `includes/repositorio.php`**

```php
<?php
/**
 * Camada de acesso a dados do site Conta Lelê.
 * Listagens de conteúdo publicado, lidas do banco.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Lê todos os registros publicados de uma tabela de conteúdo,
 * ordenados por `ordem` e `id`.
 *
 * @return array<int,array<string,mixed>>
 */
function listar_publicados(string $tabela): array
{
    $permitidas = ['cordeis', 'ebooks', 'noticias', 'depoimentos', 'festivais', 'premios'];
    if (!in_array($tabela, $permitidas, true)) {
        throw new InvalidArgumentException("Tabela não permitida: {$tabela}");
    }
    return bd()->query(
        "SELECT * FROM {$tabela} WHERE publicado = 1 ORDER BY ordem, id"
    )->fetchAll();
}

function listar_cordeis(): array      { return listar_publicados('cordeis'); }
function listar_ebooks(): array       { return listar_publicados('ebooks'); }
function listar_noticias(): array     { return listar_publicados('noticias'); }
function listar_depoimentos(): array  { return listar_publicados('depoimentos'); }
function listar_festivais(): array    { return listar_publicados('festivais'); }
function listar_premios(): array      { return listar_publicados('premios'); }

/**
 * Lê uma configuração da tabela `configuracoes` (com valor padrão).
 */
function configuracao(string $chave, string $padrao = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (bd()->query('SELECT chave, valor FROM configuracoes') as $linha) {
            $cache[$linha['chave']] = (string) $linha['valor'];
        }
    }
    return $cache[$chave] ?? $padrao;
}

/**
 * Extrai o ID de um vídeo a partir de uma URL do YouTube.
 * Aceita as formas watch?v=, youtu.be/ e embed/. Devolve '' se não achar.
 */
function youtube_id(string $url): string
{
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        return $m[1];
    }
    return '';
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `& "C:\php83\php.exe" tests\correr.php`
Expected: os testes novos passam (`Total: 29 ok, 0 falha(s)`).

- [ ] **Step 5: Commit**

```bash
git add includes/repositorio.php tests/repositorio-teste.php
git commit -m "Adiciona camada de acesso a dados da vitrine"
```

---

## Task 4: Conteúdo institucional fixo

**Files:**
- Create: `includes/conteudo.php`

Este arquivo guarda, em arrays PHP, os textos institucionais que não mudam com frequência. Os textos vêm de **DOC-CONTEUDO** (use as versões corrigidas). Leia esse documento para transcrever os textos exatos.

- [ ] **Step 1: Criar `includes/conteudo.php`**

Estrutura exata do arquivo (preencha os textos lendo DOC-CONTEUDO):
```php
<?php
/**
 * Conteúdo institucional fixo do site Conta Lelê.
 * Textos de DOC-CONTEUDO (versões corrigidas).
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

return [
    // DOC-CONTEUDO §3.1 e §1
    'slogan'      => 'Eu sou a Lelê! Conto e escrevo histórias pra você.',
    'hero_sub'    => 'Contadora de histórias, atriz, cordelista, escritora, '
        . 'mediadora de leitura e agente cultural do DF.',

    // DOC-CONTEUDO §3.2 — texto biográfico (transcreva o parágrafo completo)
    'bio'         => '...',
    // DOC-CONTEUDO §3.3 — "Por que contar histórias?" (transcreva)
    'proposito'   => '...',

    // DOC-CONTEUDO §4 — 6 serviços: cada um [titulo, texto]
    'servicos'    => [
        ['titulo' => 'Contadora de Histórias', 'texto' => '...'],
        ['titulo' => 'Cordelista',             'texto' => '...'],
        ['titulo' => 'Escritora',              'texto' => '...'],
        ['titulo' => 'Youtuber',               'texto' => '...'],
        ['titulo' => 'Atriz',                  'texto' => '...'],
        ['titulo' => 'Agente Cultural do DF',  'texto' => '...'],
    ],

    // DOC-CONTEUDO §6 — públicos: cada um [titulo, texto]
    'publicos'    => [
        ['titulo' => 'Para bebês com seus cuidadores', 'texto' => '...'],
        ['titulo' => 'Para gestantes',                 'texto' => '...'],
        ['titulo' => 'Para crianças',                  'texto' => '...'],
        ['titulo' => 'Para grupo de adolescentes',     'texto' => '...'],
        ['titulo' => 'Para adultos',                   'texto' => '...'],
        ['titulo' => 'Para idosos',                    'texto' => '...'],
        ['titulo' => 'Para pacientes',                 'texto' => '...'],
        ['titulo' => 'Para escritores / editoras',     'texto' => '...'],
        ['titulo' => 'Para escola',                    'texto' => '...'],
        ['titulo' => 'Para hotéis e resorts',          'texto' => '...'],
        ['titulo' => 'Para empresas / lojas',          'texto' => '...'],
        ['titulo' => 'Para condomínios particulares',  'texto' => '...'],
    ],

    // DOC-CONTEUDO §5 — 6 diferenciais: cada um [titulo, texto]
    'diferenciais' => [
        ['titulo' => 'Uma das Melhores do Mercado',  'texto' => '...'],
        ['titulo' => '100% Trabalhos Entregues',     'texto' => '...'],
        ['titulo' => 'Artista Premiada',             'texto' => '...'],
        ['titulo' => '100% dos Clientes Satisfeitos','texto' => '...'],
        ['titulo' => 'Artista Profissional',         'texto' => '...'],
        ['titulo' => 'Suporte ao Cliente',           'texto' => '...'],
    ],

    // DOC-CONTEUDO §12 — equipe: [nome, funcao, foto]
    'equipe'      => [
        ['nome' => 'Letícia Rocha Mourão Marques', 'funcao' => 'Roteirista, Artista, Atriz e Contadora de Histórias', 'foto' => 'Leticia.jpg'],
        ['nome' => 'Thiago Rocha Mourão',          'funcao' => 'Diretor Gráfico, Filmaker e Editor',                  'foto' => 'Thiago.jpg'],
        ['nome' => 'Marlete Alves de Oliveira',    'funcao' => 'Figurinista e Costureira',                            'foto' => 'Marlete.jpg'],
        ['nome' => 'Kleyton dos Santos Lima',      'funcao' => 'Músico e Compositor',                                 'foto' => 'Kleyton.jpg'],
    ],
];
```
Transcreva os textos de `bio`, `proposito`, `servicos`, `publicos` e `diferenciais` **fielmente** de DOC-CONTEUDO (seções 3, 4, 5, 6), nas versões corrigidas. Não invente nem resuma — copie o texto que está no documento.

- [ ] **Step 2: Verificar a sintaxe e a estrutura**

Run:
```powershell
& "C:\php83\php.exe" -l includes\conteudo.php
& "C:\php83\php.exe" -r "$c = require 'includes/conteudo.php'; echo count($c['servicos']),' servicos, ',count($c['publicos']),' publicos, ',count($c['diferenciais']),' diferenciais, ',count($c['equipe']),' equipe';"
```
Expected: `No syntax errors detected` e `6 servicos, 12 publicos, 6 diferenciais, 4 equipe`. Confirme que `bio` e `proposito` não estão com `'...'`.

- [ ] **Step 3: Commit**

```bash
git add includes/conteudo.php
git commit -m "Adiciona conteúdo institucional fixo"
```

---

## Task 5: Seed do conteúdo dinâmico

**Files:**
- Create: `seed-conteudo.php` (raiz)

Cria um script web, protegido pelo token do instalador, que insere no banco o conteúdo dinâmico (cordéis, e-books, notícias, depoimentos, festivais, prêmios). Os dados vêm de **DOC-CONTEUDO** seções 7–13.

- [ ] **Step 1: Criar `seed-conteudo.php`**

```php
<?php
/**
 * Semeia o conteúdo dinâmico inicial do site Conta Lelê.
 * Protegido por token. REMOVA este arquivo após o uso.
 * Uso: https://contalele.com.br/seed-conteudo.php?token=SEU_TOKEN
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
header('Content-Type: text/plain; charset=utf-8');

if (!hash_equals($config['instalador']['token'], (string) ($_GET['token'] ?? ''))) {
    http_response_code(403);
    exit('Acesso negado.');
}

$pdo = bd($config['db']);

// Só semeia se a tabela estiver vazia (idempotente).
function semear(PDO $pdo, string $tabela, array $colunas, array $linhas): void
{
    $qtd = (int) $pdo->query("SELECT COUNT(*) FROM {$tabela}")->fetchColumn();
    if ($qtd > 0) {
        echo "{$tabela}: já tem {$qtd} registro(s), pulando.\n";
        return;
    }
    $marc = '(' . implode(',', array_fill(0, count($colunas), '?')) . ')';
    $ins = $pdo->prepare(
        'INSERT INTO ' . $tabela . ' (' . implode(',', $colunas) . ') VALUES ' . $marc
    );
    foreach ($linhas as $linha) {
        $ins->execute($linha);
    }
    echo "{$tabela}: " . count($linhas) . " registro(s) inserido(s).\n";
}

// ── CORDÉIS (DOC-CONTEUDO §7) ────────────────────────────────────────
// Colunas: titulo, slug, sinopse, video_youtube, imagem_capa, ordem
$cordeis = [
    // [titulo, slug, sinopse, video_youtube, imagem_capa, ordem]
    // Transcreva os 9 cordéis de DOC-CONTEUDO §7. Exemplo do 1º:
    ['Cordel Monstruoso', 'cordel-monstruoso', 'TRANSCREVER SINOPSE §7',
        'https://www.youtube.com/watch?v=2s-SudIPNXs', 'monstruoso.png', 1],
    // ... os outros 8 (Os Dois Cabritos→cabritos.png, Maria não vai Cazoutras→cazoutras.png,
    //     Sapa Cristina→sapa.png, Cordel Amarelo→amarelo.png, Cordel Feminino→feminino.png,
    //     Nada de Ziquizira→ziquizira.png, Cordel da Poderosa→poderosa.png,
    //     Cordel do Chico→chico.png), com os links de vídeo de §7.
];
semear($pdo, 'cordeis', ['titulo','slug','sinopse','video_youtube','imagem_capa','ordem'], $cordeis);

// ── E-BOOKS (DOC-CONTEUDO §8) ────────────────────────────────────────
$ebooks = [
    // [titulo, slug, sinopse, arquivo_pdf, imagem_capa, ordem]
    // 5 e-books de §8. arquivo_pdf é o nome do PDF em downloads/.
    // Capas: reaproveite as capas de cordéis quando o título coincidir
    // (Os Dois Cabritos→cabritos.png, Cordel Monstruoso→monstruoso.png);
    // para os sem capa própria, deixe imagem_capa = null.
];
semear($pdo, 'ebooks', ['titulo','slug','sinopse','arquivo_pdf','imagem_capa','ordem'], $ebooks);

// ── NOTÍCIAS (DOC-CONTEUDO §11) ──────────────────────────────────────
$noticias = [
    // [titulo, veiculo, url, imagem, data_publicacao, ordem]
    // 4 publicações de §11. imagem e data_publicacao podem ser null.
];
semear($pdo, 'noticias', ['titulo','veiculo','url','imagem','data_publicacao','ordem'], $noticias);

// ── DEPOIMENTOS (DOC-CONTEUDO §13) ───────────────────────────────────
$depoimentos = [
    // [autor, texto, foto, ordem] — 5 depoimentos de §13, tom mantido.
    // fotos: Adeilson.jpg, Gisely.jpg, Marlene.jpg, Iraneide.jpg, Adairis.jpg
];
semear($pdo, 'depoimentos', ['autor','texto','foto','ordem'], $depoimentos);

// ── FESTIVAIS (DOC-CONTEUDO §10) ─────────────────────────────────────
$festivais = [
    // [titulo, descricao, ano, videos, imagem, ordem] — 4 festivais de §10.
    // videos: URLs separadas por quebra de linha. imagem: festivalN.png.
];
semear($pdo, 'festivais', ['titulo','descricao','ano','videos','imagem','ordem'], $festivais);

// ── PRÊMIOS (DOC-CONTEUDO §10) ───────────────────────────────────────
$premios = [
    // [titulo, ano, ordem] — 5 prêmios de §10. ano pode ser null.
];
semear($pdo, 'premios', ['titulo','ano','ordem'], $premios);

echo "\nSeed concluído. Remova seed-conteudo.php do servidor.\n";
```
Preencha os 6 arrays transcrevendo o conteúdo de DOC-CONTEUDO §§7,8,10,11,13. Slugs: gere com a regra do `gerar_slug()` (minúsculo, sem acento, hifenizado). `ordem`: sequencial conforme aparece no documento.

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l seed-conteudo.php`
Expected: `No syntax errors detected`. (A execução ocorre na Task 16, no servidor.)

- [ ] **Step 3: Adicionar `seed-conteudo.php` ao `robots.txt` e à exclusão de deploy**

Em `robots.txt`, adicione após a linha `Disallow: /instalar.php`:
```
Disallow: /seed-conteudo.php
```
Em `deploy/enviar.sh`, no `EXCLUIR_REGEX`, troque `instalar\.php` por `(instalar|seed-conteudo)\.php`.

- [ ] **Step 4: Commit**

```bash
git add seed-conteudo.php robots.txt deploy/enviar.sh
git commit -m "Adiciona script de seed do conteúdo dinâmico"
```

---

## Task 6: SEO por página e JSON-LD

**Files:**
- Modify: `includes/seo.php`

- [ ] **Step 1: Adicionar a função `json_ld()` ao final de `includes/seo.php`**

Acrescente, antes do fim do arquivo:
```php
/**
 * Renderiza um bloco JSON-LD de dados estruturados.
 */
function json_ld(array $dados): void
{
    echo '<script type="application/ld+json">'
        . json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . '</script>' . "\n";
}

/**
 * Dados estruturados schema.org Person da Lelê (usados na home e no Sobre).
 */
function json_ld_pessoa(string $urlBase): array
{
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'Person',
        'name'     => 'Letícia Rocha Mourão Marques',
        'alternateName' => 'Conta Lelê',
        'jobTitle' => 'Contadora de histórias, cordelista e escritora',
        'url'      => rtrim($urlBase, '/'),
        'address'  => ['@type' => 'PostalAddress', 'addressLocality' => 'Planaltina', 'addressRegion' => 'DF', 'addressCountry' => 'BR'],
        'sameAs'   => [
            'https://www.instagram.com/contalele/',
            'https://www.youtube.com/c/ContaLel%C3%AA',
        ],
    ];
}
```

- [ ] **Step 2: Modificar `cabecalho.php` para renderizar JSON-LD opcional**

Em `includes/cabecalho.php`, logo após a chamada `<?php seo_render($seo); ?>`, adicione:
```php
    <?php if (!empty($seo['json_ld'])) { json_ld($seo['json_ld']); } ?>
```
Assim, qualquer página que defina `$seo['json_ld']` tem o bloco renderizado.

- [ ] **Step 3: Verificar a sintaxe**

Run:
```powershell
& "C:\php83\php.exe" -l includes\seo.php
& "C:\php83\php.exe" -l includes\cabecalho.php
```
Expected: `No syntax errors detected` nos dois.

- [ ] **Step 4: Commit**

```bash
git add includes/seo.php includes/cabecalho.php
git commit -m "Adiciona suporte a JSON-LD por página"
```

---

## Task 7: CSS dos componentes da vitrine

**Files:**
- Modify: `assets/css/style.css`

- [ ] **Step 1: Acrescentar ao final de `assets/css/style.css`**

```css
/* ─── Componentes da vitrine ─────────────────────────────────── */

/* Seções */
.cl-secao { padding: 56px 0; }
.cl-secao--alt { background: #fff; }
.cl-secao__titulo { font-size: clamp(24px, 4vw, 42px); font-weight: 900; margin: 0 0 8px; }
.cl-eyebrow {
  font-size: 11px; font-weight: 800; letter-spacing: 0.16em;
  text-transform: uppercase; color: var(--cl-accent); margin: 0 0 8px;
}
.cl-em {
  font-family: var(--cl-font-display); font-style: normal;
  font-weight: 400; color: var(--cl-accent);
}

/* Hero */
.cl-hero { background: var(--cl-bg-2); }
.cl-hero__interno {
  display: grid; gap: 24px; padding: 40px 0; align-items: center;
}
.cl-hero__foto {
  border-radius: 24px; width: 100%; object-fit: cover; aspect-ratio: 4/5;
  object-position: 50% 30%;
}
@media (min-width: 900px) {
  .cl-hero__interno { grid-template-columns: 1.1fr 0.9fr; padding: 64px 0; }
  .cl-hero__foto { border-radius: 280px 280px 24px 24px; }
}

/* Grade de cards */
.cl-grade { display: grid; gap: 18px; grid-template-columns: 1fr; }
@media (min-width: 640px) { .cl-grade { grid-template-columns: 1fr 1fr; } }
@media (min-width: 900px) { .cl-grade--3 { grid-template-columns: 1fr 1fr 1fr; } }

.cl-card {
  background: #fff; border: 1px solid var(--cl-border);
  border-radius: 18px; padding: 18px; box-shadow: var(--cl-shadow-md);
}
.cl-card h3 { margin: 0 0 6px; font-size: 18px; font-weight: 800; }
.cl-card p { margin: 0; color: var(--cl-ink-dim); font-size: 14px; }

/* Card de mídia (capa + texto) */
.cl-card-midia { padding: 0; overflow: hidden; }
.cl-card-midia img { width: 100%; aspect-ratio: 3/4; object-fit: cover; }
.cl-card-midia__corpo { padding: 16px; }

/* Vídeo do YouTube responsivo */
.cl-video {
  position: relative; aspect-ratio: 16/9; border-radius: 14px;
  overflow: hidden; background: var(--cl-ink);
}
.cl-video iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }

/* Depoimentos */
.cl-depo { background: #fff; border-radius: 18px; padding: 20px; box-shadow: var(--cl-shadow-md); }
.cl-depo__autor { display: flex; align-items: center; gap: 12px; margin-top: 12px; }
.cl-depo__autor img { width: 44px; height: 44px; border-radius: 999px; object-fit: cover; }

/* Banner café (faixa "sexta 16h" / CTA) */
.cl-faixa {
  background: var(--cl-ink); color: var(--cl-bg);
  border-radius: 24px; padding: 32px; text-align: center;
}
.cl-faixa h2 { color: var(--cl-bg); }
.cl-faixa .cl-btn-yellow { margin-top: 12px; }

/* Formulário */
.cl-campo { display: block; margin-bottom: 16px; }
.cl-campo span { display: block; font-weight: 700; font-size: 14px; margin-bottom: 6px; }
.cl-campo input, .cl-campo textarea {
  width: 100%; padding: 12px 14px; font: inherit;
  border: 1.5px solid var(--cl-border); border-radius: 12px; background: #fff;
}
.cl-campo textarea { min-height: 130px; resize: vertical; }
.cl-mel { position: absolute; left: -9999px; }  /* honeypot */
.cl-aviso { padding: 14px 16px; border-radius: 12px; margin-bottom: 16px; }
.cl-aviso--ok  { background: #e7f6e7; color: #1f6b1f; }
.cl-aviso--erro { background: #fae3dd; color: var(--cl-accent); }

/* Lista simples */
.cl-lista { list-style: none; padding: 0; display: grid; gap: 12px; }
.cl-lista a { font-weight: 700; }
```

- [ ] **Step 2: Verificar localmente que o CSS carrega**

Run o servidor embutido e `curl -s -o NUL -w "%{http_code}" http://localhost:8000/assets/css/style.css` → `200`. Confirme que não há erro de sintaxe CSS óbvio (chaves balanceadas).

- [ ] **Step 3: Commit**

```bash
git add assets/css/style.css
git commit -m "Adiciona estilos dos componentes da vitrine"
```

---

## Task 8: Página Início (home completa)

**Files:**
- Modify: `paginas/inicio.php` (reescrita completa)

- [ ] **Step 1: Reescrever `paginas/inicio.php`**

```php
<?php
/**
 * Página inicial do site Conta Lelê.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

$conteudo = require CL_RAIZ . '/includes/conteudo.php';
require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$cordeisDestaque = array_slice(listar_cordeis(), 0, 3);
$depoimentos     = listar_depoimentos();
$whats           = configuracao('whatsapp', '5561991938603');

$seo['titulo']    = 'Conta Lelê — Contação de histórias, cordéis e cursos';
$seo['descricao'] = 'A Lelê conta e escreve histórias de verdade para escolas, '
    . 'eventos e famílias. Conheça o trabalho, os cordéis e os livros.';
$seo['json_ld']   = json_ld_pessoa($seo['url_base']);
?>
<section class="cl-hero">
  <div class="cl-conteudo cl-hero__interno">
    <div>
      <p class="cl-eyebrow">Antes do livro vem o som</p>
      <h1 class="cl-secao__titulo">Eu sou a <span class="cl-em">Lelê</span>!
        Conto e escrevo histórias pra você.</h1>
      <p style="font-size:18px;color:var(--cl-ink-dim)"><?= e($conteudo['hero_sub']) ?></p>
      <p>
        <a class="cl-btn cl-btn-primary" href="/contacao">Conhecer o trabalho</a>
        <a class="cl-btn cl-btn-yellow" href="https://wa.me/<?= e($whats) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
      </p>
    </div>
    <img class="cl-hero__foto" src="/assets/img/lele-hero.jpg"
         alt="A Lelê, contadora de histórias" width="640" height="800">
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Quem é a Lelê</p>
    <h2 class="cl-secao__titulo">Histórias que <span class="cl-em">transformam</span></h2>
    <p style="max-width:680px;font-size:16px"><?= e($conteudo['proposito']) ?></p>
    <p><a class="cl-btn cl-btn-ghost" href="/sobre">Conhecer a Lelê</a></p>
  </div>
</section>

<section class="cl-secao cl-secao--alt">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">O que a Lelê faz</p>
    <h2 class="cl-secao__titulo">Conheça os serviços</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach (array_slice($conteudo['servicos'], 0, 6) as $s): ?>
        <article class="cl-card">
          <h3><?= e($s['titulo']) ?></h3>
          <p><?= e($s['texto']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:24px"><a class="cl-btn cl-btn-primary" href="/contacao">Ver tudo sobre contação</a></p>
  </div>
</section>

<?php if ($cordeisDestaque): ?>
<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Literatura de cordel</p>
    <h2 class="cl-secao__titulo">Cordéis da Lelê</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach ($cordeisDestaque as $c): ?>
        <article class="cl-card cl-card-midia">
          <?php if ($c['imagem_capa']): ?>
            <img src="/assets/img/<?= e($c['imagem_capa']) ?>" alt="Capa de <?= e($c['titulo']) ?>" loading="lazy">
          <?php endif; ?>
          <div class="cl-card-midia__corpo">
            <h3><?= e($c['titulo']) ?></h3>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:24px"><a class="cl-btn cl-btn-ghost" href="/cordeis">Ver todos os cordéis</a></p>
  </div>
</section>
<?php endif; ?>

<?php if ($depoimentos): ?>
<section class="cl-secao cl-secao--alt">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">O que dizem</p>
    <h2 class="cl-secao__titulo">Quem já viveu uma história</h2>
    <div class="cl-grade">
      <?php foreach ($depoimentos as $d): ?>
        <figure class="cl-depo" style="margin:0">
          <blockquote style="margin:0;font-size:15px">“<?= e($d['texto']) ?>”</blockquote>
          <figcaption class="cl-depo__autor">
            <?php if ($d['foto']): ?>
              <img src="/assets/img/<?= e($d['foto']) ?>" alt="<?= e($d['autor']) ?>" loading="lazy">
            <?php endif; ?>
            <strong><?= e($d['autor']) ?></strong>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="cl-secao">
  <div class="cl-conteudo">
    <div class="cl-faixa">
      <p class="cl-eyebrow" style="color:var(--cl-bg-3)">Canal no YouTube</p>
      <h2 class="cl-secao__titulo">Vídeo novo toda <span class="cl-em">sexta às 16h</span></h2>
      <p>Histórias para crianças e famílias, sempre com a Lelê.</p>
      <a class="cl-btn cl-btn-yellow" href="https://www.youtube.com/c/ContaLel%C3%AA" target="_blank" rel="noopener">Inscrever-se no canal</a>
    </div>
  </div>
</section>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l paginas\inicio.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add paginas/inicio.php
git commit -m "Reescreve a página inicial com conteúdo completo"
```

(A verificação visual em produção acontece na Task 16, após o seed.)

---

## Task 9: Página A Lelê (sobre)

**Files:**
- Create: `paginas/sobre.php`

- [ ] **Step 1: Criar `paginas/sobre.php`**

```php
<?php
/**
 * Página "A Lelê" — biografia, propósito, prêmios e equipe.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

$conteudo = require CL_RAIZ . '/includes/conteudo.php';
require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$premios = listar_premios();

$seo['titulo']    = 'A Lelê — quem conta as histórias | Conta Lelê';
$seo['descricao'] = 'Conheça Letícia Rocha Mourão, a Lelê: contadora de histórias, '
    . 'cordelista e escritora de Planaltina-DF, com mais de 20 anos de palco.';
$seo['json_ld']   = json_ld_pessoa($seo['url_base']);
?>
<section class="cl-hero">
  <div class="cl-conteudo cl-hero__interno">
    <div>
      <p class="cl-eyebrow">A Lelê</p>
      <h1 class="cl-secao__titulo">Eu sou a <span class="cl-em">Lelê</span>,
        e vou contar uma história pra você!</h1>
      <p style="font-size:16px"><?= e($conteudo['bio']) ?></p>
    </div>
    <img class="cl-hero__foto" src="/assets/img/lele-sobre.jpg"
         alt="A Lelê" width="640" height="800" loading="lazy">
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Por que contar histórias?</p>
    <h2 class="cl-secao__titulo">O <span class="cl-em">propósito</span></h2>
    <p style="max-width:680px;font-size:16px"><?= e($conteudo['proposito']) ?></p>
  </div>
</section>

<?php if ($premios): ?>
<section class="cl-secao cl-secao--alt">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Reconhecimento</p>
    <h2 class="cl-secao__titulo">Prêmios</h2>
    <ul class="cl-lista">
      <?php foreach ($premios as $p): ?>
        <li class="cl-card"><strong><?= e($p['titulo']) ?></strong><?php
          if (!empty($p['ano'])) { echo ' — ' . e((string) $p['ano']); } ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Quem faz acontecer</p>
    <h2 class="cl-secao__titulo">A equipe</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach ($conteudo['equipe'] as $m): ?>
        <article class="cl-card cl-card-midia">
          <img src="/assets/img/<?= e($m['foto']) ?>" alt="<?= e($m['nome']) ?>" loading="lazy">
          <div class="cl-card-midia__corpo">
            <h3><?= e($m['nome']) ?></h3>
            <p><?= e($m['funcao']) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l paginas\sobre.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add paginas/sobre.php
git commit -m "Adiciona a página A Lelê"
```

---

## Task 10: Página Contação de Histórias

**Files:**
- Create: `paginas/contacao.php`

- [ ] **Step 1: Criar `paginas/contacao.php`**

```php
<?php
/**
 * Página "Contação de Histórias" — serviços, públicos e diferenciais.
 * É a página comercial de contratação.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

$conteudo = require CL_RAIZ . '/includes/conteudo.php';
require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$whats = configuracao('whatsapp', '5561991938603');

$seo['titulo']    = 'Contação de Histórias — contrate a Lelê | Conta Lelê';
$seo['descricao'] = 'Contação de histórias para escolas, eventos, empresas e famílias. '
    . 'Conheça os serviços da Lelê e para quem a contação de histórias encanta.';
?>
<section class="cl-hero">
  <div class="cl-conteudo" style="padding:48px 16px">
    <p class="cl-eyebrow">Contação de Histórias</p>
    <h1 class="cl-secao__titulo">Torne o seu evento <span class="cl-em">encantador</span></h1>
    <p style="max-width:680px;font-size:16px">A Lelê apresenta-se em escolas,
      festivais, livrarias, eventos e casas — de forma presencial ou online.</p>
    <p><a class="cl-btn cl-btn-primary" href="https://wa.me/<?= e($whats) ?>" target="_blank" rel="noopener">Pedir um orçamento</a></p>
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">O que a Lelê faz</p>
    <h2 class="cl-secao__titulo">Serviços</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach ($conteudo['servicos'] as $s): ?>
        <article class="cl-card"><h3><?= e($s['titulo']) ?></h3><p><?= e($s['texto']) ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="cl-secao cl-secao--alt">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Para quem</p>
    <h2 class="cl-secao__titulo">Para quem contar histórias</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach ($conteudo['publicos'] as $p): ?>
        <article class="cl-card"><h3><?= e($p['titulo']) ?></h3><p><?= e($p['texto']) ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Por que a Lelê</p>
    <h2 class="cl-secao__titulo">Diferenciais</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach ($conteudo['diferenciais'] as $d): ?>
        <article class="cl-card"><h3><?= e($d['titulo']) ?></h3><p><?= e($d['texto']) ?></p></article>
      <?php endforeach; ?>
    </div>
    <div class="cl-faixa" style="margin-top:32px">
      <h2 class="cl-secao__titulo">Vamos encantar o seu evento?</h2>
      <a class="cl-btn cl-btn-yellow" href="/contato">Falar com a Lelê</a>
    </div>
  </div>
</section>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l paginas\contacao.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add paginas/contacao.php
git commit -m "Adiciona a página Contação de Histórias"
```

---

## Task 11: Página Cordéis

**Files:**
- Create: `paginas/cordeis.php`

- [ ] **Step 1: Criar `paginas/cordeis.php`**

```php
<?php
/**
 * Página "Cordéis" — galeria dos cordéis com vídeo do YouTube.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$cordeis = listar_cordeis();

$seo['titulo']    = 'Cordéis — literatura de cordel da Lelê | Conta Lelê';
$seo['descricao'] = 'Conheça os cordéis escritos e contados pela Lelê: histórias '
    . 'rimadas para crianças, com vídeos no YouTube.';
?>
<section class="cl-hero">
  <div class="cl-conteudo" style="padding:48px 16px">
    <p class="cl-eyebrow">Literatura de cordel</p>
    <h1 class="cl-secao__titulo">Os <span class="cl-em">cordéis</span> da Lelê</h1>
    <p style="max-width:680px;font-size:16px">Histórias rimadas, com aquele tempero
      especial — escritas e contadas pela Lelê.</p>
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo">
    <?php if (!$cordeis): ?>
      <p>Em breve, novos cordéis por aqui.</p>
    <?php else: ?>
      <div class="cl-grade">
        <?php foreach ($cordeis as $c): $vid = youtube_id((string) $c['video_youtube']); ?>
          <article class="cl-card">
            <h3><?= e($c['titulo']) ?></h3>
            <?php if ($vid): ?>
              <div class="cl-video" style="margin:12px 0">
                <iframe src="https://www.youtube-nocookie.com/embed/<?= e($vid) ?>"
                        title="<?= e($c['titulo']) ?>" loading="lazy"
                        allowfullscreen></iframe>
              </div>
            <?php elseif ($c['imagem_capa']): ?>
              <img src="/assets/img/<?= e($c['imagem_capa']) ?>" alt="Capa de <?= e($c['titulo']) ?>"
                   style="border-radius:14px;margin:12px 0" loading="lazy">
            <?php endif; ?>
            <p><?= e($c['sinopse']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l paginas\cordeis.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add paginas/cordeis.php
git commit -m "Adiciona a página Cordéis"
```

---

## Task 12: Página Livros

**Files:**
- Create: `paginas/livros.php`

- [ ] **Step 1: Criar `paginas/livros.php`**

```php
<?php
/**
 * Página "Livros" — e-books da Lelê com download em PDF.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$ebooks = listar_ebooks();

$seo['titulo']    = 'Livros — e-books da Lelê | Conta Lelê';
$seo['descricao'] = 'Baixe os livros digitais escritos pela Lelê: histórias '
    . 'para crianças, em PDF gratuito.';
?>
<section class="cl-hero">
  <div class="cl-conteudo" style="padding:48px 16px">
    <p class="cl-eyebrow">Livros digitais</p>
    <h1 class="cl-secao__titulo">Os <span class="cl-em">livros</span> da Lelê</h1>
    <p style="max-width:680px;font-size:16px">Histórias escritas pela Lelê,
      para ler e reler — disponíveis para download.</p>
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo">
    <?php if (!$ebooks): ?>
      <p>Em breve, novos livros por aqui.</p>
    <?php else: ?>
      <div class="cl-grade cl-grade--3">
        <?php foreach ($ebooks as $b): ?>
          <article class="cl-card cl-card-midia">
            <?php if ($b['imagem_capa']): ?>
              <img src="/assets/img/<?= e($b['imagem_capa']) ?>" alt="Capa de <?= e($b['titulo']) ?>" loading="lazy">
            <?php endif; ?>
            <div class="cl-card-midia__corpo">
              <h3><?= e($b['titulo']) ?></h3>
              <p><?= e($b['sinopse']) ?></p>
              <?php if ($b['arquivo_pdf']): ?>
                <p style="margin-top:12px">
                  <a class="cl-btn cl-btn-primary" href="/downloads/<?= e($b['arquivo_pdf']) ?>" download>Baixar PDF</a>
                </p>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l paginas\livros.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add paginas/livros.php
git commit -m "Adiciona a página Livros"
```

---

## Task 13: Página Festivais & Imprensa

**Files:**
- Create: `paginas/festivais.php`

- [ ] **Step 1: Criar `paginas/festivais.php`**

```php
<?php
/**
 * Página "Festivais & Imprensa" — festivais, prêmios e publicações.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$festivais = listar_festivais();
$premios   = listar_premios();
$noticias  = listar_noticias();

$seo['titulo']    = 'Festivais & Imprensa — a trajetória da Lelê | Conta Lelê';
$seo['descricao'] = 'Festivais literários, prêmios e publicações na imprensa: '
    . 'a trajetória da Conta Lelê.';
?>
<section class="cl-hero">
  <div class="cl-conteudo" style="padding:48px 16px">
    <p class="cl-eyebrow">Trajetória</p>
    <h1 class="cl-secao__titulo">Festivais &amp; <span class="cl-em">Imprensa</span></h1>
    <p style="max-width:680px;font-size:16px">Onde a Lelê já contou histórias e
      o que dizem sobre o trabalho dela.</p>
  </div>
</section>

<?php if ($festivais): ?>
<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Participações</p>
    <h2 class="cl-secao__titulo">Festivais</h2>
    <div class="cl-grade">
      <?php foreach ($festivais as $f): ?>
        <article class="cl-card">
          <h3><?= e($f['titulo']) ?><?php if (!empty($f['ano'])): ?>
            <small style="color:var(--cl-ink-dim)"> · <?= e((string) $f['ano']) ?></small><?php endif; ?></h3>
          <p><?= e($f['descricao']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($premios): ?>
<section class="cl-secao cl-secao--alt">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Reconhecimento</p>
    <h2 class="cl-secao__titulo">Prêmios</h2>
    <ul class="cl-lista">
      <?php foreach ($premios as $p): ?>
        <li class="cl-card"><strong><?= e($p['titulo']) ?></strong><?php
          if (!empty($p['ano'])) { echo ' — ' . e((string) $p['ano']); } ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php if ($noticias): ?>
<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Na mídia</p>
    <h2 class="cl-secao__titulo">Imprensa</h2>
    <ul class="cl-lista">
      <?php foreach ($noticias as $n): ?>
        <li class="cl-card">
          <a href="<?= e($n['url']) ?>" target="_blank" rel="noopener"><?= e($n['titulo']) ?></a>
          <?php if (!empty($n['veiculo'])): ?>
            <span style="color:var(--cl-ink-dim)"> — <?= e($n['veiculo']) ?></span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l paginas\festivais.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add paginas/festivais.php
git commit -m "Adiciona a página Festivais & Imprensa"
```

---

## Task 14: Página Cursos (teaser)

**Files:**
- Create: `paginas/cursos.php`

- [ ] **Step 1: Criar `paginas/cursos.php`**

```php
<?php
/**
 * Página "Cursos" — teaser. A plataforma de cursos vem na Fase 2.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$whats = configuracao('whatsapp', '5561991938603');

$seo['titulo']    = 'Cursos — em breve | Conta Lelê';
$seo['descricao'] = 'Cursos online da Lelê para professores e mediadores de '
    . 'leitura. Em breve. Avise-me quando abrir.';
?>
<section class="cl-secao">
  <div class="cl-conteudo">
    <div class="cl-faixa">
      <p class="cl-eyebrow" style="color:var(--cl-bg-3)">Novidade chegando</p>
      <h1 class="cl-secao__titulo">Cursos da Lelê para
        <span class="cl-em">professores</span></h1>
      <p style="max-width:560px;margin:12px auto">Estamos preparando cursos online
        para quem ensina, media leitura e quer encantar com histórias.
        Quer ser avisado quando abrir?</p>
      <a class="cl-btn cl-btn-yellow" href="https://wa.me/<?= e($whats) ?>?text=<?= rawurlencode('Oi! Quero saber dos cursos da Lelê.') ?>"
         target="_blank" rel="noopener">Quero ser avisado</a>
    </div>
  </div>
</section>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l paginas\cursos.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add paginas/cursos.php
git commit -m "Adiciona a página Cursos (teaser)"
```

---

## Task 15: Página Contato e processamento do formulário

**Files:**
- Create: `paginas/contato.php`

- [ ] **Step 1: Criar `paginas/contato.php`**

```php
<?php
/**
 * Página "Contato" — formulário, WhatsApp e redes.
 * Processa o POST do próprio formulário.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$whats = configuracao('whatsapp', '5561991938603');
$emailContato = configuracao('email_contato', 'contato@contalele.com.br');

$aviso = null;        // ['tipo' => 'ok'|'erro', 'texto' => ...]
$valores = ['nome' => '', 'email' => '', 'telefone' => '', 'assunto' => '', 'mensagem' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $erros = [];

    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente de novo.';
    }
    // Honeypot: campo invisível que só bots preenchem.
    if (!empty($_POST['site'] ?? '')) {
        $erros[] = 'Envio bloqueado.';
    }

    $valores['nome']     = limpar_texto((string) ($_POST['nome'] ?? ''));
    $valores['email']    = limpar_texto((string) ($_POST['email'] ?? ''));
    $valores['telefone'] = limpar_texto((string) ($_POST['telefone'] ?? ''));
    $valores['assunto']  = limpar_texto((string) ($_POST['assunto'] ?? ''));
    $valores['mensagem'] = trim((string) ($_POST['mensagem'] ?? ''));

    if ($valores['nome'] === '')                 { $erros[] = 'Diga o seu nome.'; }
    if (!validar_email($valores['email']))       { $erros[] = 'Informe um e-mail válido.'; }
    if (mb_strlen($valores['mensagem']) < 10)    { $erros[] = 'Escreva uma mensagem um pouco maior.'; }

    if (!$erros) {
        try {
            bd()->prepare(
                'INSERT INTO mensagens_contato (nome, email, telefone, assunto, mensagem, ip)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $valores['nome'], $valores['email'], $valores['telefone'],
                $valores['assunto'], $valores['mensagem'],
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            // Notificação por e-mail (mail() — o envio por SMTP entra com o painel).
            $corpo = "Nome: {$valores['nome']}\nE-mail: {$valores['email']}\n"
                . "Telefone: {$valores['telefone']}\nAssunto: {$valores['assunto']}\n\n"
                . $valores['mensagem'];
            $cabecalhos = 'Content-Type: text/plain; charset=utf-8' . "\r\n"
                . 'Reply-To: ' . $valores['email'];
            @mail($emailContato, 'Contato pelo site: ' . ($valores['assunto'] ?: 'sem assunto'),
                $corpo, $cabecalhos);

            $aviso = ['tipo' => 'ok', 'texto' => 'Mensagem enviada! A Lelê responde em breve.'];
            $valores = ['nome' => '', 'email' => '', 'telefone' => '', 'assunto' => '', 'mensagem' => ''];
        } catch (Throwable $e) {
            registrar_log('Falha ao gravar mensagem de contato', ['erro' => $e->getMessage()]);
            $aviso = ['tipo' => 'erro', 'texto' => 'Não foi possível enviar agora. Tente pelo WhatsApp.'];
        }
    } else {
        $aviso = ['tipo' => 'erro', 'texto' => implode(' ', $erros)];
    }
}

$token = csrf_token();

$seo['titulo']    = 'Contato — fale com a Lelê | Conta Lelê';
$seo['descricao'] = 'Entre em contato com a Conta Lelê para apresentações, '
    . 'cordéis personalizados e oficinas. WhatsApp e formulário.';
?>
<section class="cl-hero">
  <div class="cl-conteudo" style="padding:48px 16px">
    <p class="cl-eyebrow">Contato</p>
    <h1 class="cl-secao__titulo">Vamos <span class="cl-em">conversar</span>?</h1>
    <p style="max-width:680px;font-size:16px">Conte o que você imagina — a Lelê e a
      equipe vão ouvir, pode acreditar.</p>
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo" style="max-width:680px">
    <?php if ($aviso): ?>
      <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
    <?php endif; ?>

    <form method="post" action="/contato">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">
      <label class="cl-mel">Não preencha este campo
        <input type="text" name="site" tabindex="-1" autocomplete="off"></label>

      <label class="cl-campo"><span>Nome</span>
        <input type="text" name="nome" required value="<?= e($valores['nome']) ?>"></label>
      <label class="cl-campo"><span>E-mail</span>
        <input type="email" name="email" required value="<?= e($valores['email']) ?>"></label>
      <label class="cl-campo"><span>Telefone (opcional)</span>
        <input type="text" name="telefone" value="<?= e($valores['telefone']) ?>"></label>
      <label class="cl-campo"><span>Assunto</span>
        <input type="text" name="assunto" value="<?= e($valores['assunto']) ?>"></label>
      <label class="cl-campo"><span>Mensagem</span>
        <textarea name="mensagem" required><?= e($valores['mensagem']) ?></textarea></label>

      <button class="cl-btn cl-btn-primary" type="submit">Enviar mensagem</button>
      <a class="cl-btn cl-btn-yellow" href="https://wa.me/<?= e($whats) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
    </form>

    <p style="margin-top:32px;color:var(--cl-ink-dim)">
      Planaltina-DF · <a href="mailto:<?= e($emailContato) ?>"><?= e($emailContato) ?></a><br>
      <a href="<?= e(configuracao('instagram', '#')) ?>" target="_blank" rel="noopener">Instagram</a> ·
      <a href="<?= e(configuracao('youtube', '#')) ?>" target="_blank" rel="noopener">YouTube</a>
    </p>
  </div>
</section>
```

- [ ] **Step 2: Verificar a sintaxe**

Run: `& "C:\php83\php.exe" -l paginas\contato.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add paginas/contato.php
git commit -m "Adiciona a página Contato com formulário"
```

---

## Task 16: Favicon, imagem OG e sitemap

**Files:**
- Create: `favicon.ico`, `assets/img/og-padrao.jpg`, `sitemap.xml`

- [ ] **Step 1: Gerar o favicon a partir do girassol**

Use a imagem `Identidade Visual/Ícones/Girassol.png`. Gere um `favicon.ico` 32×32 (ou um PNG renomeado, aceito pelos navegadores) na raiz do projeto:
```powershell
& "C:\php83\php.exe" -r "$o=imagecreatefrompng('Identidade Visual/Ícones/Girassol.png'); $f=imagecreatetruecolor(32,32); imagealphablending($f,false); imagesavealpha($f,true); imagecopyresampled($f,$o,0,0,0,0,32,32,imagesx($o),imagesy($o)); imagepng($f,'favicon.ico'); echo 'favicon ok';"
```

- [ ] **Step 2: Gerar a imagem OG padrão**

Crie `assets/img/og-padrao.jpg` (1200×630) a partir de `assets/img/lele-hero.jpg`, recortando para a proporção 1200×630:
```powershell
& "C:\php83\php.exe" -r "$o=imagecreatefromjpeg('assets/img/lele-hero.jpg'); $w=imagesx($o); $h=imagesy($o); $alvo=1200/630; $atual=$w/$h; if($atual>$alvo){$nw=(int)($h*$alvo);$nx=(int)(($w-$nw)/2);$nh=$h;$ny=0;}else{$nh=(int)($w/$alvo);$ny=(int)(($h-$nh)/2);$nw=$w;$nx=0;} $d=imagecreatetruecolor(1200,630); imagecopyresampled($d,$o,0,0,$nx,$ny,1200,630,$nw,$nh); imagejpeg($d,'assets/img/og-padrao.jpg',85); echo 'og ok';"
```

- [ ] **Step 3: Criar `sitemap.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>https://contalele.com.br/</loc><priority>1.0</priority></url>
  <url><loc>https://contalele.com.br/sobre</loc><priority>0.8</priority></url>
  <url><loc>https://contalele.com.br/contacao</loc><priority>0.9</priority></url>
  <url><loc>https://contalele.com.br/cordeis</loc><priority>0.7</priority></url>
  <url><loc>https://contalele.com.br/livros</loc><priority>0.7</priority></url>
  <url><loc>https://contalele.com.br/festivais</loc><priority>0.6</priority></url>
  <url><loc>https://contalele.com.br/cursos</loc><priority>0.5</priority></url>
  <url><loc>https://contalele.com.br/contato</loc><priority>0.7</priority></url>
</urlset>
```

- [ ] **Step 4: Commit**

```bash
git add favicon.ico assets/img/og-padrao.jpg sitemap.xml
git commit -m "Adiciona favicon, imagem OG e sitemap"
```

---

## Task 17: Deploy, seed e verificação final

**Files:** nenhum arquivo novo — deploy e verificação.

- [ ] **Step 1: Enviar tudo para o servidor**

Run (bash):
```bash
bash deploy/enviar.sh
bash deploy/enviar.sh seed-conteudo.php
```
(O `seed-conteudo.php` está no `EXCLUIR_REGEX`, então é enviado explicitamente, como o `instalar.php` foi.)
Expected: `Deploy concluído.` nas duas chamadas.

- [ ] **Step 2: Rodar o seed do conteúdo**

Run (use o token de `config.php`):
```bash
curl -s "https://contalele.com.br/seed-conteudo.php?token=SEU_TOKEN"
```
Expected: linhas confirmando a inserção em `cordeis`, `ebooks`, `noticias`, `depoimentos`, `festivais`, `premios`.

- [ ] **Step 3: Remover o `seed-conteudo.php` do servidor**

```bash
source deploy/credenciais.env
curl --ssl-reqd -Q "DELE ${FTP_RAIZ}seed-conteudo.php" "ftp://${FTP_HOST}/" --user "${FTP_USUARIO}:${FTP_SENHA}"
```
Confirme: `curl -s -o /dev/null -w "%{http_code}" "https://contalele.com.br/seed-conteudo.php"` → `404`.

- [ ] **Step 4: Verificar cada página em produção**

Para cada rota, confirme HTTP 200 e um trecho de conteúdo esperado:
```bash
for rota in "" sobre contacao cordeis livros festivais cursos contato; do
  echo "/$rota -> $(curl -s -o /dev/null -w '%{http_code}' https://contalele.com.br/$rota)"
done
```
Expected: todas `200`. Depois, confirme conteúdo dinâmico:
- `curl -s https://contalele.com.br/cordeis` contém um título de cordel (ex.: `Cordel Monstruoso`) e um `<iframe` de vídeo.
- `curl -s https://contalele.com.br/livros` contém `Baixar PDF`.
- `curl -s https://contalele.com.br/` contém um depoimento e a faixa do YouTube.

- [ ] **Step 5: Testar o formulário de contato**

Envie um POST de teste e confirme que grava no banco:
```bash
TOKEN=$(curl -s -c /tmp/cl-cookie https://contalele.com.br/contato | grep -oP 'name="csrf" value="\K[^"]+')
curl -s -b /tmp/cl-cookie -d "csrf=$TOKEN" -d "nome=Teste Deploy" -d "email=teste@exemplo.com" \
  -d "mensagem=Mensagem de verificacao do formulario." "https://contalele.com.br/contato" \
  | grep -o 'Mensagem enviada'
```
Expected: `Mensagem enviada`. (A mensagem fica em `mensagens_contato`; aparecerá no painel admin da Fase 3.)

- [ ] **Step 6: Rodar a suíte de testes e o diagnóstico**

```bash
"C:/php83/php.exe" tests/correr.php
curl -s "https://contalele.com.br/admin/diagnostico.php?token=SEU_TOKEN" | grep -o "Ambiente saudável"
```
Expected: `Total: 29 ok, 0 falha(s)` e `Ambiente saudável`.

- [ ] **Step 7: Verificar a limpeza de cache do Cloudflare**

O site está atrás do Cloudflare. Após o deploy, os assets versionados mudam de nome de arquivo? Não — então confirme que o HTML não está cacheado de forma agressiva: `curl -sI https://contalele.com.br/ | grep -i cf-cache-status`. Se o HTML estiver vindo `HIT` desatualizado, oriente o usuário a usar "Purge Everything" no painel do Cloudflare (não há API key configurada neste projeto).

- [ ] **Step 8: Commit final e push**

```bash
git push origin main
```
Expected: push aceito para `contalele-novo`.

---

## Verificação de cobertura (auto-revisão)

- **8 páginas públicas** (spec §5) → Tasks 8–15 ✓
- **SEO por página, JSON-LD, sitemap** (spec §11) → Tasks 1, 6, 16 ✓
- **Conteúdo dinâmico do banco** (spec §6) → Tasks 3, 5 ✓
- **Conteúdo institucional fixo** (spec §6) → Task 4 ✓
- **Formulário de contato grava no banco + e-mail** (spec §8) → Task 15 ✓
- **Anti-spam (honeypot + CSRF)** (spec §9) → Task 15 ✓
- **Botão de WhatsApp** (spec §8) → presente no layout (Plano 1) + CTAs nas páginas ✓
- **Responsividade / acessibilidade** (spec §11) → Task 7 (CSS mobile-first, grades responsivas) ✓
- **favicon e imagem OG** (pendência do Plano 1) → Task 16 ✓
- **Assets reais (fotos, capas, PDFs)** (spec §13) → Task 2 ✓

**Fora do escopo deste plano:** painel administrativo e login (Plano 3 — Fase 1); envio de e-mail por SMTP via PHPMailer (entra com o painel, quando o SMTP for configurável); plataforma de cursos (Fase 2); página do Projeto Cordelzinho (pendência de conteúdo com a Letícia).

**Observações para o Plano 3 (painel administrativo):**
- O formulário de contato hoje usa `mail()`. Quando o painel permitir configurar SMTP, trocar por PHPMailer lendo `configuracoes`.
- As mensagens de contato já são gravadas em `mensagens_contato` — o painel só precisa listá-las.
- O conteúdo dinâmico já está no banco — o painel fará o CRUD sobre as mesmas tabelas.
