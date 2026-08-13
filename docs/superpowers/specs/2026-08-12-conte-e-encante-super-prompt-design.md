# Super prompt — página de vendas Conte&Encante

**Como usar:** copie tudo a partir da linha `===== INÍCIO DO PROMPT =====` até o fim do
arquivo e cole numa conversa nova do Claude.ai. Não é preciso anexar nada — a copy, os
tokens da marca e as regras de layout já estão dentro do prompt. O resultado é um artifact
HTML navegável e responsivo. As fotos entram como placeholders nomeados, porque o artifact
não consegue acessar arquivos locais; a troca pelos arquivos reais acontece na portagem
para PHP, aqui no projeto.

**Decisões já fechadas:** landing isolada em `/conte-e-encante`, sem menu de navegação ·
tom editorial encantado, modo amarelo da marca · CTAs direto ao checkout da Hotmart ·
lacunas da oferta marcadas como placeholders visuais · **uma única sigla aberta na
página, o VIPE; o VISAR é nomeado como método autoral mas não destrinchado** (decisão da
Lelê por telefone, 12/08/2026, para a página não carregar duas siglas concorrentes).

---

===== INÍCIO DO PROMPT =====

Você vai construir uma página de vendas completa, em HTML e CSS, para um curso online de
contação de histórias. Entregue como um único artifact HTML autocontido. Leia o briefing
inteiro antes de escrever a primeira linha.

---

# 1. O que é

**Conte&Encante** é um curso de contação de histórias da Letícia Mourão — a **Lelê** —,
contadora de histórias, atriz, cordelista, escritora e mediadora de leitura com mais de 20
anos de palco. O curso é vendido pela Hotmart. Esta página é a única peça de venda: quem
chega aqui decide comprar ou sair.

**Quem lê a página:** professoras da Educação Infantil e dos anos iniciais, mediadoras de
leitura, bibliotecárias, e também mães e pais. Predominantemente mulheres, entre 25 e 55
anos, muitas lendo no celular. São profissionais da educação — levam o trabalho a sério e
desconfiam de promessa fácil.

**A tese da página:** contar histórias não é dom, é caminho. A pessoa não precisa virar
outra pessoa, precisa descobrir o próprio jeito.

**Preço:** R$ 297 à vista, ou parcelado no cartão conforme o checkout.

---

# 2. Marca — isto não é negociável

A Conta Lelê tem identidade fechada. Não invente paleta, não troque fontes, não introduza
cor nova.

## Paleta

```css
:root{
  --bg:      #fdf5d4;              /* creme — fundo principal */
  --bg-2:    #fbe87f;              /* amarelo claro — superfícies secundárias */
  --bg-3:    #ffdc3a;              /* amarelo girassol — CTAs e destaques */
  --ink:     #2b1300;              /* café profundo — texto e botões primários */
  --ink-dim: rgba(43,19,0,.58);    /* texto secundário */
  --accent:  #c4390e;              /* laranja queimado — eyebrows, links, ênfases */
  --surface: #ffffff;              /* cards e seções alternadas */
  --borda:   rgba(43,19,0,.08);    /* hairline */
  --sombra:  0 8px 18px -14px rgba(43,19,0,.18);
  --sombra-g:0 20px 40px -20px rgba(43,19,0,.30);
}
```

**Proibido:** rosa (choque ou pastel), gradiente neon ou holográfico, preto puro (`#000` —
use sempre `--ink`), branco puro em texto sobre fundo escuro (use `--bg` creme),
glassmorphism, sombra roxa sobre amarelo, roxo misturado com amarelo na mesma página.

## Tipografia

- **Nunito** (400/600/700/800/900) — títulos, corpo, botões, tudo.
- **Fredoka One** (400) — só para os momentos lúdicos: eyebrow de abertura, palavras
  destacadas dentro de títulos, o número grande do preço, os selos.

Carregue por `<link>` do Google Fonts. Se o ambiente bloquear a requisição, o fallback tem
de manter o layout de pé:

```css
--display: 'Fredoka One', 'Trebuchet MS', system-ui, sans-serif;
--ui:      'Nunito', 'Segoe UI', system-ui, -apple-system, sans-serif;
```

Regras tipográficas:

- Títulos em Nunito **900**, `line-height: 1.05`, `letter-spacing: -0.025em`.
- Palavras destacadas dentro de títulos usam `<em>` com `font-family: var(--display);
  font-style: normal; font-weight: 400; font-size: 1.15em; color: var(--accent);`.
  Exemplo: `histórias <em>de verdade</em>`.
- `text-wrap: balance` em títulos, `text-wrap: pretty` em parágrafos.
- Eyebrow: 11px, peso 800, `letter-spacing: .16em`, MAIÚSCULAS, cor `--accent`.
- Escala mobile: h1 32px · h2 22px · h3 18px · corpo 15px · caption 12px.
- Escala desktop: h1 64px · h2 42px · h3 22px · corpo 17px. Suba a partir de 900px.

## Formas

- Botões: **sempre pílula** (`border-radius: 999px`), texto peso 800. Botão retangular é
  erro.
- Raios: 8px chips e badges · 12–14px inputs · 18px cards · 22–24px cards de destaque e
  banners · 999px pílulas e avatares.
- Espaçamento na grade de 4px: 4 · 8 · 12 · 16 · 24 · 32 · 48 · 72.
- Conteúdo com `max-width: 1180px`, centralizado. Padding lateral 18px no mobile, 32px a
  partir de 900px.

## Botões

```css
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;
     padding:16px 30px;border-radius:999px;font-weight:800;font-size:16px;
     border:1.5px solid transparent;cursor:pointer;text-decoration:none;
     transition:transform .12s ease, box-shadow .12s ease}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 18px -10px rgba(43,19,0,.35)}
.btn-primary{background:var(--ink);color:var(--bg-3)}                          /* café com texto amarelo */
.btn-yellow {background:var(--bg-3);color:var(--ink);border-color:var(--ink)}
.btn-ghost  {background:transparent;color:var(--ink);border-color:var(--ink)}
```

Em seções de fundo café, o botão principal inverte: fundo `--bg-3`, texto `--ink`.

## O girassol

É o **único ornamento decorativo da marca**. Nada de ícones ilustrativos, nada de SVG
desenhado à mão, nada de emoji na interface. Use o girassol generosamente: cantos de
cards, atrás de títulos, no rodapé, sangrando para fora da caixa. Cores fixas — pétalas
`#ffcc1f`, contorno `#c4390e`, miolo `#5a2a0a`. Reproduza exatamente este SVG:

```html
<svg class="girassol" viewBox="0 0 200 200" width="120" height="120" aria-hidden="true" focusable="false">
  <defs>
    <ellipse id="gp-out" cx="100" cy="38" rx="13" ry="32" fill="#ffcc1f" stroke="#c4390e" stroke-width="2.4"/>
    <ellipse id="gp-in"  cx="100" cy="62" rx="9"  ry="20" fill="#f7a91a" stroke="#5a2a0a" stroke-width="1.2"/>
  </defs>
  <use href="#gp-out" transform="rotate(0 100 100)"/><use href="#gp-out" transform="rotate(30 100 100)"/>
  <use href="#gp-out" transform="rotate(60 100 100)"/><use href="#gp-out" transform="rotate(90 100 100)"/>
  <use href="#gp-out" transform="rotate(120 100 100)"/><use href="#gp-out" transform="rotate(150 100 100)"/>
  <use href="#gp-out" transform="rotate(180 100 100)"/><use href="#gp-out" transform="rotate(210 100 100)"/>
  <use href="#gp-out" transform="rotate(240 100 100)"/><use href="#gp-out" transform="rotate(270 100 100)"/>
  <use href="#gp-out" transform="rotate(300 100 100)"/><use href="#gp-out" transform="rotate(330 100 100)"/>
  <use href="#gp-in" transform="rotate(15 100 100)"/><use href="#gp-in" transform="rotate(45 100 100)"/>
  <use href="#gp-in" transform="rotate(75 100 100)"/><use href="#gp-in" transform="rotate(105 100 100)"/>
  <use href="#gp-in" transform="rotate(135 100 100)"/><use href="#gp-in" transform="rotate(165 100 100)"/>
  <use href="#gp-in" transform="rotate(195 100 100)"/><use href="#gp-in" transform="rotate(225 100 100)"/>
  <use href="#gp-in" transform="rotate(255 100 100)"/><use href="#gp-in" transform="rotate(285 100 100)"/>
  <use href="#gp-in" transform="rotate(315 100 100)"/><use href="#gp-in" transform="rotate(345 100 100)"/>
  <circle cx="100" cy="100" r="32" fill="#5a2a0a"/>
  <g fill="#3d1c06" opacity=".65">
    <circle cx="92" cy="92" r="2"/><circle cx="104" cy="90" r="2"/><circle cx="98" cy="100" r="2"/>
    <circle cx="108" cy="104" r="2"/><circle cx="90" cy="106" r="2"/><circle cx="100" cy="112" r="2"/>
    <circle cx="112" cy="98" r="2"/><circle cx="86" cy="100" r="2"/>
  </g>
</svg>
```

Alguns girassóis podem girar lentamente (`animation: gira 28s linear infinite`), desde que
a animação seja desligada em `prefers-reduced-motion`.

## Voz

Segunda pessoa direta: "Você vai aprender…", "A Lelê te conta…". Verbos sensoriais —
contar, encantar, escutar, brincar, mediar. Calorosa, lúdica e profissional ao mesmo
tempo: nunca infantilizada, porque quem lê é professora e precisa levar a sério.

**Nunca escreva** "incrível", "imperdível", "exclusivo", "transforme sua vida",
"oportunidade única", "últimas vagas". Isso é fala de coach e queima a autoridade que a
página inteira constrói. Não invente número de alunas, nota de avaliação, selo de
garantia falso nem contador regressivo.

Toda a copy da página já está escrita na seção 6. **Use o texto exato.** Você pode ajustar
pontuação e quebra de linha para caber no layout, mas não reescreva, não resuma e não
acrescente frases de venda.

---

# 3. Direção de arte

**Editorial encantado.** Tipografia grande, muito respiro, fotos em blocos generosos,
girassol pontuando. A página deve parecer material de uma artista com vinte anos de palco
— não uma página de lançamento de infoproduto. Sem selo de desconto, sem seta vermelha,
sem caixa piscando, sem contador regressivo.

**Ritmo de fundos.** As seções alternam para criar respiração:

- `--bg` creme — fundo padrão
- `--surface` branco — seções alternadas
- `--ink` café — reservado para **dois** momentos de virada emocional (blocos 3 e 11). São
  as apagadas de luz entre atos. Texto em creme, ênfases em `--bg-3`, botão amarelo.
- `--bg-2` / `--bg-3` amarelo — hero, encontros ao vivo e oferta

Nunca use café em três seções seguidas nem alterne creme e branco a cada bloco de forma
mecânica. O ritmo tem de acompanhar a emoção do texto.

**Densidade.** Padding vertical de seção: 64px no mobile, 88px a partir de 900px. Nas duas
seções café, suba para 88px / 120px — elas precisam de mais silêncio ao redor.

**Movimento.** Discreto. Elementos entram com `opacity 0 → 1` e `translateY(14px) → 0` ao
aparecerem na viewport, com atraso escalonado dentro de uma mesma grade. Tudo desligado em
`prefers-reduced-motion: reduce`.

---

# 4. Regras técnicas

- **Um único arquivo HTML**, com CSS em `<style>` e JavaScript em `<script>` inline.
  Nenhuma biblioteca, nenhum CDN, nenhuma requisição externa exceto o `<link>` das fontes.
- **Mobile-first.** Escreva o CSS para 402px de largura (iPhone 14 Pro) e suba com
  `min-width`. Breakpoints: 620px, 820px, 900px, 940px.
- **Nada de scroll horizontal** em nenhuma largura, do 320px ao 1920px.
- **Modo claro fixo.** Esta página é sempre creme. Não escreva media query de
  `prefers-color-scheme`.
- **Acessibilidade:** um único `<h1>`; hierarquia de headings sem pular nível; contraste
  mínimo 4.5:1 no corpo; foco visível em todo elemento interativo
  (`outline: 3px solid var(--accent); outline-offset: 3px`); acordeão do FAQ com
  `aria-expanded` e `aria-controls` e navegável por teclado; alvos de toque de no mínimo
  44×44px; `lang="pt-BR"` no `<html>`.
- **JavaScript mínimo:** só o acordeão do FAQ, a barra fixa que aparece ao rolar, e o
  reveal de entrada com `IntersectionObserver`. Nada além disso.
- **Ortografia:** português brasileiro com todos os acentos corretos. Nunca escreva
  "voce", "historia", "nao".
- **Título da página:** `<title>Conte&amp;Encante — curso de contação de histórias com
  Lelê</title>`.

## Placeholders de foto

O ambiente não tem acesso aos arquivos de imagem. Toda foto vira um bloco marcado, com a
proporção correta e o nome do arquivo visível:

```html
<div class="foto-ph foto-ph--3x4" role="img" aria-label="Lelê contando 1 no dedo, fundo azul">
  <span>[foto: Lelê-37.jpg]<br>Lelê contando 1 no dedo, fundo azul</span>
</div>
```

```css
.foto-ph{background:var(--bg-2);border:1.5px dashed rgba(43,19,0,.28);border-radius:18px;
  display:grid;place-items:center;text-align:center;padding:16px;
  font-family:ui-monospace,"SF Mono",Menlo,Consolas,monospace;font-size:12px;
  line-height:1.5;color:rgba(43,19,0,.62)}
.foto-ph--2x3{aspect-ratio:2/3}
.foto-ph--3x4{aspect-ratio:3/4}
.foto-ph--4x5{aspect-ratio:4/5}
.foto-ph--3x2{aspect-ratio:3/2}
.foto-ph--1x1{aspect-ratio:1/1}
```

O placeholder tem de ocupar **exatamente** o espaço que a foto real vai ocupar, com o
mesmo `border-radius`, a mesma moldura e o mesmo posicionamento. Ele existe para validar o
layout, não para ser um buraco.

**Atenção ao nome dos arquivos do curso.** Quatro fotos se chamam
`contalele-cante-e-encante*.jpeg`, com "c**a**nte". É um erro de digitação no nome do
arquivo, feito quando a pasta foi criada. O curso chama-se **Conte&Encante**, com
"c**o**nte", e é assim que ele aparece em todo texto visível. Copie os nomes de arquivo
exatamente como estão, com o erro — "corrigi-los" quebra o caminho da imagem.

As fotos de fundo branco vão receber, na versão final, `mix-blend-mode: multiply` para se
fundirem ao creme. Já deixe a classe escrita e comentada no CSS:

```css
/* Aplicar nas fotos de fundo branco quando os arquivos reais entrarem:
   funde o branco do estúdio no creme sem editar o arquivo. */
.foto--recorte{mix-blend-mode:multiply}
```

## Placeholders de conteúdo

Seis informações da oferta ainda não foram definidas pela Lelê. Elas **não podem sumir do
layout** — o espaço precisa estar desenhado e visivelmente marcado como pendente:

```html
<span class="pendente">a definir</span>
```

```css
.pendente{display:inline-block;padding:3px 10px;border-radius:8px;
  background:var(--accent);color:var(--bg);font-size:11px;font-weight:800;
  letter-spacing:.08em;text-transform:uppercase;vertical-align:middle}
```

São elas: o número de dias de garantia · se há certificado · o tempo de acesso · os bônus
· a periodicidade dos encontros ao vivo · os depoimentos. Onde o texto entre colchetes
aparecer na copy da seção 6, mantenha os colchetes e acrescente o chip. **Não invente o
conteúdo dessas lacunas.**

O método VISAR **não** é uma pendência e **não** leva chip: ele é nomeado no bloco 7 como
o método autoral da Lelê, e por decisão dela não é destrinchado nesta página. Ausência
deliberada não é lacuna.

## Links

Todos os botões de compra apontam para o mesmo destino, que ainda não existe:

```html
<a class="btn btn-primary" href="#CHECKOUT" target="_blank" rel="noopener">…</a>
<!-- Trocar #CHECKOUT pela URL do checkout da Hotmart quando ela existir. -->
```

---

# 5. Estrutura — 18 blocos, nesta ordem

Antes de cada bloco: fundo, foto e layout. A copy exata está na seção 6.

**0 · Topo.** Logo "Conta Lelê" à esquerda (use um placeholder `[logo: logo-black.png]`
com 44px de altura) e um botão pílula `btn-yellow` pequeno à direita: "Quero entrar".
**Sem menu de navegação** — esta é uma landing isolada e não pode ter saída. A barra some
ao rolar e reaparece como barra fixa no topo depois de 600px de scroll, com sombra sutil.
No mobile, a barra fixa vira uma faixa inferior com o botão ocupando a largura toda.

**1 · Hero.** Fundo `--bg-3` amarelo girassol, com um girassol grande sangrando pelo canto
inferior direito em opacidade baixa. Duas colunas a partir de 900px: copy à esquerda, foto
à direita; empilhado no mobile, foto **abaixo** da copy. Foto quadrada
`[foto: contalele-cante-e-encante (4).jpeg]` — Lelê sorrindo de colete listrado, segurando
uma lâmpada azul, fundo amarelo mostarda — em moldura `border-radius: 280px 280px 24px
24px` no desktop (arco no topo) e 24px no mobile. Eyebrow, h1, subtítulo, os quatro chips
"Mesmo que…" em linha que quebra, e o CTA. Os chips são pílulas de fundo `--bg` creme com
contorno fino café.

**2 · As dúvidas.** Fundo `--surface` branco. Título, parágrafo de abertura e as sete
falas como balões de citação — cards de fundo creme, cantos 18px, texto em itálico Nunito
700, com aspas grandes em Fredoka laranja. Distribua os balões em duas ou três colunas com
alturas ligeiramente irregulares, como um mural. Ao lado ou entre eles, uma tira
horizontal com três placeholders `[foto: Lele-1.jpg]`, `[foto: Lelê-3.jpg]`,
`[foto: Lelê-4.jpg]` em proporção 3:2 — as expressões de dúvida. O primeiro arquivo é o
único do acervo sem acento no nome; mantenha exatamente `Lele-1.jpg`.

**3 · A virada.** Fundo `--ink` café, texto creme. Sem foto. Centralizado, largura de
texto máxima de 720px, muito espaço em volta. As duas primeiras frases em corpo grande, e
"a sua verdade." isolada, em Fredoka One amarelo `--bg-3`, tamanho 44px no mobile e 72px
no desktop. Um girassol pequeno logo abaixo, centralizado.

**4 · Imagine.** Fundo `--bg` creme. Título à esquerda, e a lista de capacidades em duas
colunas a partir de 820px. Cada item começa com um traço em laranja, não com bullet
redondo. A frase final ("olhar para as crianças e perceber que elas estão com você") ganha
destaque: fundo branco, cantos 22px, borda esquerda de 4px em `--accent`. Foto vertical
`[foto: Lelê-70.jpg]` — Lelê de olhos fechados, expressão de encanto — na coluna lateral
do desktop. CTA ao final, centralizado.

**5 · O que é o Conte&Encante.** Fundo `--surface` branco. Duas colunas: texto à esquerda,
**o wordmark do curso** à direita. Abaixo, os onze elementos do curso como nuvem de chips:
Voz · Corpo · Olhar · Ritmo · Pausa · Imaginação · Objetos · Livros · Música ·
Participação · Repertório. Chips pílula, fundo `--bg-2`, texto café, peso 800, 14px. A
frase de fecho isolada, centralizada, em corpo maior.

O curso tem identidade própria, construída só com o que a marca já tem — sem imagem, sem
arquivo externo. Cartão amarelo girassol com borda café, a assinatura em eyebrow no topo,
o nome em Fredoka One em duas linhas com o `&` em laranja queimado, e o girassol sangrando
pelo canto inferior direito, como nos cards de tom:

```html
<div class="wordmark">
  <svg class="girassol g-deco" aria-hidden="true" focusable="false"><use href="#girassol-sym"/></svg>
  <p class="wordmark__assinatura">Mais que contar. Encantar.</p>
  <p class="wordmark__nome"><span class="wordmark__l1">Conte<span class="wordmark__e">&amp;</span></span><span class="wordmark__l2">Encante</span></p>
</div>
```

```css
.wordmark{position:relative;overflow:hidden;background:var(--bg-3);border:2px solid var(--ink);
  border-radius:24px;padding:32px 30px 44px;text-align:center;box-shadow:var(--sombra-g)}
.wordmark__nome{position:relative;z-index:1;margin:0;font-family:var(--display);font-weight:400;
  color:var(--ink);line-height:.94;letter-spacing:-.015em}
.wordmark__l1,.wordmark__l2{display:block;font-size:clamp(42px,10vw,72px)}
.wordmark__l2{transform:translateX(.12em)}
.wordmark__e{color:var(--accent);font-size:1.15em}
.wordmark__assinatura{position:relative;z-index:1;margin:0 0 18px;font-family:var(--ui);
  font-size:11px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;
  color:var(--ink);opacity:.68;line-height:1.6}
.wordmark .g-deco{right:-46px;bottom:-46px;opacity:.26;width:150px;height:150px}
@media (min-width:900px){
  .wordmark{padding:40px 34px 52px}
  .wordmark .g-deco{right:-38px;bottom:-38px;width:170px;height:170px}
}
```

A assinatura vai **acima** do nome, não abaixo: embaixo ela colide com o girassol em
telas de 320 a 402px. Verificado em render.

A linha de apoio é **"Mais que contar. Encantar."**, e não uma descrição de categoria. A
autora recusou "curso de contação de histórias" porque rotula o produto em vez de entregar
a promessa — e a frase escolhida ecoa o título do bloco 9, "Você não vai aprender apenas a
contar. Vai aprender a encantar." Não troque por um descritivo.

**6 · Para quem é.** Fundo `--bg` creme. Sete cards em grade — 1 coluna no mobile, 2 a
partir de 620px, 3 a partir de 940px. Cada card usa um dos quatro tons da marca, rotativos
na ordem amarelo → laranja → café → creme:

```css
.tom-amarelo{background:var(--bg-3);color:var(--ink)}
.tom-laranja{background:var(--accent);color:var(--bg)}
.tom-cafe   {background:var(--ink);color:var(--bg)}
.tom-creme  {background:var(--bg-2);color:var(--ink)}
```

Cada card tem cantos 22px, padding 24px, e um girassol posicionado em
`right:-22px; bottom:-22px` com `overflow:hidden` no card, aparecendo cortado. O sétimo
card ("ou simplesmente ama histórias") ocupa a largura toda no desktop, como fecho. Abaixo
da grade, a frase "Você não precisa chegar pronta" centralizada e grande.

**7 · O método por trás.** Fundo `--surface` branco. Bloco curto, de respiro e autoridade.
Eyebrow "O CAMINHO", título, parágrafo de origem, e então o nome **MÉTODO VISAR** em
Fredoka One, grande, centralizado, com um girassol atrás em opacidade baixa. Abaixo, o
parágrafo que explica o que ele é.

**O VISAR aparece nomeado, mas não é destrinchado nesta página.** Não liste as cinco
letras, não crie cards por pilar, não invente as palavras que elas representam. Ele existe
aqui como prova de que há um método autoral por trás do curso — o detalhamento acontece
dentro do Conte&Encante. Essa foi decisão da autora, para a página não carregar duas
siglas concorrentes.

Layout: duas colunas a partir de 900px. À esquerda o texto; à direita o placeholder
`[foto: Lelê-58.jpg]` (3:2) — a Lelê apresentando com a mão aberta, fundo azul. Fecho do
bloco: a frase sobre "não basta ter recursos", destacada num painel de fundo `--bg-2`
ocupando a largura das duas colunas.

**Sem números aqui.** Não diga quantos pilares o método tem. A única enumeração da página
é a do VIPE, no bloco seguinte — anunciar "cinco" aqui e "quatro" logo depois recria
exatamente a confusão que a decisão da autora quis eliminar.

**8 · VIPE.** Fundo `--bg` creme. **Este é o bloco conceitual mais importante da página** —
trate como capítulo, com padding vertical maior que o das seções vizinhas (88px mobile,
120px desktop). É aqui que a promessa do hero se fecha.

Estrutura: eyebrow, título, a frase de ligação com o VISAR, e quatro cards em grade 2×2 a
partir de 620px. Cada card tem a letra em Fredoka One gigante no canto superior, a
palavra, a descrição e uma foto:

- **V — Verdade** · `[foto: Lelê-12.jpg]` — mão no peito, olhos fechados (3:2)
- **I — Intenção** · `[foto: Lelê-60.jpg]` — expressão pensativa, dedo no queixo (3:2)
- **P — Presença** · `[foto: Lelê-69.jpg]` — mão junto ao olho, olhando (3:2)
- **E — Encantamento** · `[foto: Lelê-66.jpg]` — tecidos coloridos voando no ar (2:3)

O card do Encantamento é o mais forte visualmente: dê a ele o tom café, com a foto
vertical sangrando até a borda.

Abaixo da grade, o remate — e ele precisa do maior peso tipográfico da página depois do
h1, porque responde diretamente ao título do hero. Em painel de fundo `--ink` café,
centralizado, largura de texto máxima de 720px:

> **Você não precisa ter dom para contar histórias. Mas precisa ter VIPE.**

A frase em Nunito 900 creme, 32px no mobile e 52px no desktop, com "VIPE" em Fredoka One
amarelo `--bg-3`. Logo abaixo, as quatro palavras em linha, separadas por ponto médio, em
Fredoka One amarelo, tamanho menor: **Verdade · Intenção · Presença · Encantamento**.

Este painel café é o **terceiro** e último uso do fundo escuro na página — os outros dois
são os blocos 3 e 11. Como aqui ele é um painel dentro de uma seção creme, e não uma
seção inteira, o ritmo continua de pé.

**9 · Você vai aprender a.** Fundo `--surface` branco. Título, parágrafo, e os quinze
itens em duas colunas a partir de 820px. Cada item com um check desenhado em SVG (traço 1.7px,
viewBox 24, sem preenchimento) em círculo amarelo `--bg-3`. Sem foto — este bloco é denso
de texto e precisa respirar.

**10 · Recursos simples.** Fundo `--bg` creme. Título, dois parágrafos, e então o momento
visual mais lúdico da página, em duas camadas que não se misturam.

Primeiro as quatro transformações, como escada tipográfica centralizada: uma frase por
linha, em Fredoka One laranja, com respiro generoso entre elas. **Sem foto** — o acervo
não tem mola nem bola, e casar uma frase com uma imagem que não a mostra quebra a
confiança de quem lê.

Abaixo, uma tira de quatro fotos de recursos que a Lelê realmente usa em cena. Grade de 2
colunas no mobile e 4 no desktop, placeholders quadrados com cantos 18px. Sem legenda de
venda — só o texto descritivo do próprio placeholder.

- `[foto: contalele-cante-e-encante (1).jpeg]` — garrafinha verde com olhos e taça com
  chapéu de bruxa
- `[foto: Lelê-17.jpg]` — rosto atrás de um tecido colorido
- `[foto: Lelê-47.jpg]` — barco de papel sobre tecido azul
- `[foto: Lelê-22.jpg]` — Lelê rindo com pandeiro de fitas

Fecho: os parágrafos sobre a caixa de ferramentas.

**11 · E se eu for tímida?** Fundo `--ink` café, texto creme. A segunda apagada de luz.
Quatro perguntas, cada uma seguida do mesmo refrão. Componha como litania: a pergunta em
Nunito 900 creme, e "Você pode contar histórias." repetido logo abaixo em Fredoka One
amarelo. A repetição **é** o design — não substitua por aspas, ícones ou variação de
texto. A quinta pergunta quebra o padrão e recebe outra resposta. Fecha com a escada
"Existe técnica. Existe repertório…" e o CTA em botão amarelo.

**12 · Quem caminha com você.** Fundo `--surface` branco. Duas colunas a partir de 900px:
foto vertical grande `[foto: Lelê-49.jpg]` (Lelê segurando um girassol, fundo azul, 2:3) à
esquerda, texto à direita. Sobre o canto da foto, um selo circular em Fredoka One, fundo
`--bg-3`, com "+20 anos de palco". Eyebrow "QUEM VAI CAMINHAR COM VOCÊ" e o título "Oi, eu
sou a Lelê." em Fredoka One — é a assinatura da marca e aparece assim em todas as peças.

**13 · Encontros ao vivo.** Fundo `--bg-2` amarelo claro. É o diferencial da oferta e
precisa parecer um convite. Título, texto, e a periodicidade como painel pendente bem
visível. Foto 3:2 `[foto: Lelê-65.jpg]` — Lelê rindo de braços abertos. Um girassol
girando no canto.

**14 · Depoimentos.** Fundo `--surface` branco. Estrutura montada, conteúdo pendente. Três
cards de vídeo (proporção 9:16, com um botão de play desenhado em SVG sobre o placeholder)
e três cards de depoimento em texto, cada um com aspas em Fredoka laranja, espaço para
citação, avatar circular de 46px com borda amarela e nome. Todos os textos marcados como
pendentes. Acima da grade, a nota que define o critério: não é para mostrar que gostaram,
é para mostrar o que passaram a fazer diferente.

**15 · Tudo o que você recebe.** Fundo `--bg` creme. Oito linhas, cada uma com o check em
círculo amarelo, o nome do item em Nunito 800 e a descrição em `--ink-dim` abaixo. As duas últimas linhas
concentram três pendências — bônus, certificado e tempo de acesso. Grade de 1 coluna no
mobile e 2 no desktop.

**16 · Investimento.** Fundo `--bg-3` amarelo girassol, com dois girassóis sangrando pelas
laterais. O bloco de preço é um cartão branco central, cantos 24px, sombra `--sombra-g`,
largura máxima 560px: rótulo "Entre para o Conte&Encante por", o número **R$ 297** em
Fredoka One gigante (56px mobile, 88px desktop) em `--ink`, a linha sobre parcelamento em
`--ink-dim`, e o CTA principal em pílula café ocupando a largura toda do cartão. Nenhum
preço riscado, nenhum "de/por", nenhuma âncora falsa.

Logo abaixo, dentro da mesma seção, a garantia: painel de fundo `--bg` creme, com um selo
circular em Fredoka e o texto, com o número de dias pendente. Fecha com o parágrafo "E tem
uma diferença importante", em largura estreita e centralizado.

**17 · FAQ.** Fundo `--surface` branco. Oito perguntas em acordeão nativo com
`<button aria-expanded>`, uma linha divisória fina `--borda` entre elas, e um chevron em
SVG que gira 180° quando abre. Só uma aberta por vez. Largura máxima 820px, centralizado.
A primeira vem aberta.

**18 · Fechamento.** Fundo `--bg` creme, com girassóis nos dois cantos superiores. A
sequência "Talvez você tenha chegado até aqui…" em corpo grande e centralizado, com muito
espaço entre as linhas — o texto tem ritmo de poema e o layout tem de respeitar isso. Foto
3:2 `[foto: contalele-cante-e-encante (3).jpeg]` — Lelê fazendo um coração com as mãos —
centralizada, largura máxima 520px, cantos 24px. A dupla final "Você não precisa começar
sem medo. / Só precisa começar." em Nunito 900 grande. "Vem comigo?" em Fredoka One
laranja. Assinatura "Conte&Encante — com Lelê". CTA final grande.

**Rodapé mínimo.** Fundo `--ink` café. Logo em versão branca
`[logo: logo-white.png]`, a linha "© 2026 Conta Lelê · Planaltina-DF", e três links
apenas: Instagram, YouTube e um e-mail de contato. Nenhum link para o restante do site.

---

# 6. Copy — texto exato

Use exatamente estes textos. Não reescreva, não resuma, não acrescente.

## Bloco 1 — Hero

> **Eyebrow:** CONTE&ENCANTE · CURSO DE CONTAÇÃO DE HISTÓRIAS COM LELÊ
>
> **H1:** Você não precisa ter dom para contar histórias. Precisa descobrir como contar
> *do seu jeito*.
>
> **Subtítulo:** Aprenda a transformar histórias em experiências que despertam a
> imaginação, prendem a atenção das crianças e criam memórias — usando sua voz, seu corpo,
> sua presença e até os objetos mais simples que você já tem por perto.
>
> **Chips:** Mesmo que você seja tímida. · Mesmo que ache que não leva jeito. · Mesmo que
> sua turma seja agitada. · Mesmo que você tenha poucos recursos.
>
> **CTA:** QUERO APRENDER A CONTAR HISTÓRIAS

## Bloco 2 — As dúvidas

> **H2:** Talvez você já tenha uma história nas mãos. O que falta é saber o que fazer com
> ela.
>
> Você escolhe um livro lindo. Começa a contar. Mas, em poucos minutos, uma criança se
> distrai. Outra conversa. Outra levanta. E então aparecem aquelas dúvidas:
>
> **Balões:**
> - "Será que eu não tenho jeito para isso?"
> - "Minha voz não é bonita."
> - "Eu sou tímida demais."
> - "Não sei interpretar."
> - "Não consigo decorar histórias."
> - "Não tenho aqueles materiais maravilhosos que vejo na internet."
> - "Como algumas pessoas conseguem prender a atenção das crianças tão facilmente?"

## Bloco 3 — A virada

> A resposta não está em ter um dom especial.
>
> E muito menos em imitar o jeito de outra pessoa contar.
>
> **Contar histórias é algo que pode ser aprendido.**
>
> E, principalmente, pode ser aprendido sem apagar aquilo que você tem de mais importante:
>
> **a sua verdade.**

## Bloco 4 — Imagine

> **H2:** Imagine entrar diante das crianças sabendo exatamente como começar…
>
> - Saber criar expectativa antes mesmo de dizer "Era uma vez".
> - Perceber quando é hora de acelerar. Quando diminuir. Quando fazer silêncio. Quando
>   chamar as crianças para participar.
> - Saber usar sua voz sem precisar fazer vinte vozes diferentes.
> - Usar seu corpo com intenção, sem transformar a história numa sequência exagerada de
>   gestos.
> - Olhar para um objeto simples e enxergar nele uma possibilidade de história.
> - Escolher um livro e conseguir ir além da leitura das palavras.
>
> **Destaque:** E, principalmente: olhar para as crianças e perceber que elas estão com
> você.
>
> O Conte&Encante foi criado para ajudar você a construir esse caminho.
>
> **CTA:** QUERO APRENDER COM A LELÊ

## Bloco 5 — O que é o Conte&Encante

> **H2:** O que é o Conte&Encante?
>
> O Conte&Encante é um curso de contação de histórias criado por Letícia Mourão — a Lelê —
> para quem deseja contar histórias de maneira mais envolvente, criativa e intencional.
>
> Aqui você não vai aprender uma fórmula para copiar o jeito da Lelê contar. É justamente
> o contrário.
>
> Você vai aprender princípios, técnicas e possibilidades para descobrir *o seu jeito de
> contar histórias*.
>
> Ao longo do curso, vamos trabalhar elementos como verdade, intenção, presença e
> encantamento.
>
> **Chips:** Voz · Corpo · Olhar · Ritmo · Pausa · Imaginação · Objetos · Livros · Música
> · Participação · Repertório
>
> **Fecho:** E, principalmente, daquilo que nenhuma técnica substitui: a conexão entre
> quem conta e quem escuta.

## Bloco 6 — Para quem é

> **H2:** Este curso é para você que…
>
> 1. É professora da Educação Infantil ou dos anos iniciais e quer tornar seus momentos de
>    história mais envolventes.
> 2. Trabalha em biblioteca, sala de leitura ou mediação e quer ampliar seu repertório.
> 3. Está começando na contação de histórias e não sabe por onde começar.
> 4. Já conta histórias, mas sente que precisa compreender melhor voz, corpo, ritmo,
>    presença e participação.
> 5. Trabalha com crianças e procura maneiras criativas de despertar atenção e imaginação.
> 6. É mãe, pai ou cuidador e deseja transformar a leitura em um momento especial de
>    vínculo.
> 7. Ou simplesmente ama histórias e deseja aprender a contá-las.
>
> **Fecho:** Você não precisa chegar pronta. O curso existe justamente para ensinar o
> caminho.

## Bloco 7 — O método por trás

> **Eyebrow:** O CAMINHO
>
> **H2:** Você não vai aprender um monte de técnicas soltas. Existe um caminho.
>
> Depois de mais de 20 anos contando histórias, estudando, experimentando recursos e
> observando o que realmente acontece entre quem conta e quem escuta, organizei parte
> dessa experiência em um caminho que pudesse ser ensinado.
>
> Assim nasceu o **MÉTODO VISAR**.
>
> Um método que ensina você a fazer escolhas conscientes ao contar uma história,
> encontrando o seu próprio jeito de narrar — sem fórmulas engessadas e sem precisar
> imitar ninguém.
>
> Não é uma fórmula para transformar todo mundo no mesmo tipo de contador de histórias.
> Muito pelo contrário. É um caminho para ajudar você a encontrar a sua maneira de contar.
>
> Ao longo do Conte&Encante, você vai percorrer esse método nas aulas, nos exercícios e
> nas experiências práticas.
>
> **Painel de fecho:** E existe uma ideia fundamental por trás de tudo isso: não basta ter
> recursos. É preciso saber por que, quando e como utilizá-los.

**Não escreva mais nada neste bloco.** As cinco letras do VISAR existem e estão definidas
pela autora, mas por decisão dela ficam fora da página de vendas — são apresentadas dentro
do curso. Não liste, não resuma, não deduza as palavras a partir das iniciais.

## Bloco 8 — VIPE

> **Eyebrow:** O QUE VOCÊ VAI DESENVOLVER
>
> **H2:** O que é o VIPE?
>
> Se o VISAR é o método, o VIPE são os quatro requisitos que, para mim, todo contador de
> histórias precisa desenvolver:
>
> **V — Verdade**
> Encontrar a sua própria maneira de contar. Não é copiar outro contador, forçar uma voz
> ou interpretar um personagem o tempo inteiro. É existir de verdade naquela história.
>
> **I — Intenção**
> Saber por que está fazendo cada escolha. Uma pausa, um gesto, uma mudança de voz, um
> objeto, um silêncio: tudo precisa ter propósito.
>
> **P — Presença**
> Não basta saber a história. É preciso estar presente para quem está ouvindo: olhar,
> perceber, escutar as reações, adaptar-se e criar conexão.
>
> **E — Encantamento**
> É transformar a narrativa em experiência. Fazer o outro imaginar, sentir, participar e
> entrar naquele universo — mesmo que você tenha apenas uma história, seu corpo e sua voz.
>
> **Painel de remate:** Você não precisa ter dom para contar histórias. Mas precisa ter
> **VIPE**.
>
> **Verdade · Intenção · Presença · Encantamento**

## Bloco 9 — Você vai aprender a

> **H2:** Você não vai aprender apenas a contar. Vai aprender a encantar.
>
> Ao longo do Conte&Encante, você vai compreender como uma história deixa de ser apenas
> uma sequência de palavras e passa a se transformar em experiência.
>
> **Você vai aprender a:**
> - encontrar histórias que façam sentido para você e para quem vai ouvi-las;
> - preparar o ouvinte antes mesmo de começar a narrativa;
> - criar convites para entrar no mundo da imaginação;
> - desenvolver presença e conexão com o público;
> - compreender a intenção por trás de cada escolha;
> - explorar voz, ritmo, pausas, silêncio e musicalidade;
> - usar corpo, olhar, gestos e movimento de maneira consciente;
> - trabalhar com livros sem ficar presa apenas à leitura;
> - descobrir possibilidades narrativas em objetos simples;
> - usar recursos sem deixar que eles se tornem mais importantes do que a história;
> - lidar com turmas agitadas e diferentes respostas do público;
> - estimular a participação sem perder o fio da narrativa;
> - ampliar seu repertório;
> - adaptar aquilo que aprende à sua realidade;
> - e construir, pouco a pouco, a sua própria identidade como contadora de histórias.

## Bloco 10 — Recursos simples

> **H2:** Recursos simples. Grandes possibilidades.
>
> Talvez você já tenha visto contadores de histórias com cenários enormes, figurinos
> elaborados e materiais feitos especialmente para uma apresentação. Tudo isso pode ser
> lindo. Mas você não precisa começar por aí.
>
> No Conte&Encante, você vai descobrir que objetos comuns também podem abrir portas
> extraordinárias para a imaginação.
>
> **Transformações:** Uma mola vira lagarta. · Uma bola vira lua. · Um tecido vira céu. ·
> Uma taça vira personagem.
>
> O mais importante não é quanto custa o recurso. *É o que você consegue fazer o público
> imaginar através dele.*
>
> Essa é uma das razões pelas quais o curso trabalha diferentes linguagens e
> possibilidades: livros, objetos, voz, corpo, música, movimento e brincadeira. Não para
> você usar tudo ao mesmo tempo. Mas para ter uma caixa de ferramentas e saber escolher o
> que cada história pede.

## Bloco 11 — E se eu for tímida?

> **E se eu for tímida?** → Você pode contar histórias.
>
> **E se eu não souber fazer vozes?** → Você pode contar histórias.
>
> **E se eu não souber decorar?** → Você pode contar histórias.
>
> **E se eu não tiver materiais?** → Você pode contar histórias.
>
> **E se eu nunca tiver contado uma história na frente de uma turma?** → Então talvez este
> seja justamente o seu começo.
>
> Porque o Conte&Encante não parte da ideia de que existe um talento mágico reservado a
> algumas pessoas.
>
> Existe técnica. Existe repertório. Existe treino. Existe intenção. Existe experiência.
>
> E existe algo que ninguém pode ensinar você a copiar: **a sua verdade.**
>
> **CTA:** QUERO COMEÇAR

## Bloco 12 — Quem caminha com você

> **Eyebrow:** QUEM VAI CAMINHAR COM VOCÊ
>
> **H2 (Fredoka One):** Oi, eu sou a Lelê.
>
> Meu nome é Letícia Mourão, mas muita gente me conhece como **Lelê**, da Conta Lelê.
>
> Sou contadora de histórias, atriz, cordelista, escritora e mediadora de leitura e há
> **mais de 20 anos conto histórias profissionalmente**, transformando palavras, livros,
> objetos, músicas e gestos em encontros com a imaginação.
>
> Ao longo dessas duas décadas, contei histórias para crianças, famílias, educadores e
> públicos dos mais diferentes tamanhos, em escolas, bibliotecas, teatros, eventos e
> festivais pelo Brasil.
>
> Também sou formadora de educadores e contadores de histórias, e muito do que você
> encontrará no Conte&Encante nasceu não apenas dos meus estudos, mas da experiência de
> quem passou mais de duas décadas diante do público, contando, observando, experimentando
> e aprendendo.
>
> O Conte&Encante reúne aquilo que eu gostaria que alguém tivesse me mostrado quando
> comecei.
>
> Não quero ensinar você a contar histórias como eu. *Quero ajudar você a descobrir o seu
> jeito de contar.*
>
> **Selo:** +20 anos de palco

## Bloco 13 — Encontros ao vivo

> **H2:** E você não vai fazer esse caminho sozinha
>
> Além das aulas gravadas, o Conte&Encante terá **encontros ao vivo com a Lelê**.
>
> Serão momentos para estarmos juntas, conversar sobre o que você está aprendendo,
> aprofundar conteúdos, compartilhar experiências e continuar desenvolvendo sua prática.
>
> Porque algumas coisas você aprende assistindo. Outras, experimentando. E muitas delas
> acontecem justamente no encontro entre quem conta e quem escuta.
>
> **Painel:** [Periodicidade e quantidade dos encontros] `a definir`

## Bloco 14 — Depoimentos

> **Eyebrow:** O QUE MUDA DEPOIS
>
> **H2:** Quem já viveu uma história com a Lelê
>
> **Nota:** Não queremos apenas mostrar que as pessoas "gostaram". Queremos mostrar o que
> elas perceberam, aprenderam ou passaram a fazer diferente depois da experiência com a
> Lelê.
>
> **Conteúdo:** [Depoimentos em vídeo e texto de professoras, participantes de oficinas e
> formações] `a definir`

## Bloco 15 — Tudo o que você recebe

> **H2:** Tudo o que você recebe
>
> Ao entrar para o Conte&Encante, você terá acesso a:
>
> 1. **Curso completo Conte&Encante** — Aulas gravadas para assistir no seu ritmo e rever
>    sempre que precisar.
> 2. **Encontros ao vivo com a Lelê** — Momentos de troca, aprofundamento e acompanhamento
>    para levar o conteúdo das aulas para a prática.
> 3. **Exercícios e experiências práticas** — Porque contação de histórias não se aprende
>    apenas assistindo. É preciso experimentar.
> 4. **Materiais complementares** — Conteúdos que ajudam você a estudar, organizar os
>    aprendizados e aplicá-los.
> 5. **Recursos e repertório** — Possibilidades para ampliar sua caixa de ferramentas como
>    contadora de histórias.
> 6. **Histórias e ideias com objetos simples** — Para descobrir que encantamento não
>    depende de materiais caros.
> 7. **[Bônus]** `a definir`
> 8. **[Certificado]** `a definir` · **[Tempo de acesso]** `a definir`

## Bloco 16 — Investimento

> **H2:** Quanto vale conseguir transformar uma história em um momento que uma criança vai
> lembrar?
>
> Você não está comprando um conjunto de histórias para decorar. Está aprendendo
> princípios e ferramentas que poderá usar em *uma história, dez histórias, cem histórias*.
>
> São conhecimentos construídos ao longo de mais de 20 anos de experiência com a contação
> de histórias, agora organizados em um caminho para ajudar você a desenvolver a sua
> própria forma de contar.
>
> **Cartão de preço:**
> Entre para o Conte&Encante por
> **R$ 297**
> ou parcelado no cartão conforme as condições disponíveis no checkout.
>
> **CTA:** QUERO ENTRAR NO CONTE&ENCANTE
>
> **Garantia:** Você terá [número de dias] `a definir` **dias de garantia**, conforme as
> condições da oferta. Entre, conheça o curso, assista às aulas e veja se o Conte&Encante
> faz sentido para você.
>
> **Fecho:** E tem uma diferença importante: você não precisa esperar terminar o curso
> inteiro para começar a experimentar. A proposta é que você assista, experimente, conte,
> observe o que aconteceu e volte para aprender mais. Porque ninguém se torna contador de
> histórias apenas estudando contação. *A gente aprende a contar… contando.*

## Bloco 17 — FAQ

> **H2:** Dúvidas frequentes
>
> **Nunca contei histórias. O curso serve para mim?**
> Sim. Você não precisa ter experiência anterior. O Conte&Encante foi pensado também para
> quem deseja começar.
>
> **Sou tímida. Vou conseguir?**
> Sim. Você não precisa se transformar numa pessoa extrovertida para contar histórias.
> Vamos trabalhar presença, intenção e possibilidades para que você encontre o seu jeito
> de contar.
>
> **Preciso saber interpretar ou fazer vozes?**
> Não. Voz é uma ferramenta da contação, mas contar histórias é muito maior do que criar
> vozes diferentes para personagens.
>
> **Preciso comprar materiais?**
> Não. Um dos princípios do curso é justamente ampliar seu olhar para recursos simples e
> para aquilo que já existe ao seu redor.
>
> **Preciso decorar as histórias?**
> Não. Você vai aprender caminhos para compreender e se apropriar da narrativa sem
> depender simplesmente da memorização palavra por palavra.
>
> **As aulas são ao vivo?**
> As aulas principais são gravadas e você poderá assistir no seu ritmo. Além delas, o
> curso terá encontros ao vivo com a Lelê.
>
> **Posso assistir pelo celular?**
> Sim. O acesso ao curso é realizado pela plataforma Hotmart e pode ser feito pelos
> dispositivos compatíveis com a plataforma.
>
> **Por quanto tempo terei acesso?**
> [Tempo de acesso] `a definir`
>
> **Tem certificado?**
> [Informação sobre certificado] `a definir`

## Bloco 18 — Fechamento

> **H2:** Talvez você tenha chegado até aqui pensando que não nasceu para contar histórias.
>
> Talvez seja tímida. Talvez ache sua voz comum. Talvez não tenha cenário. Não tenha
> figurino. Não tenha uma mala cheia de recursos.
>
> Mas você tem algo que nenhum material pode substituir: **a possibilidade de criar
> conexão.**
>
> Uma história pode caber em um livro. Em uma música. Em um pedaço de tecido. Em uma mola.
> Nas suas mãos. Ou simplesmente na sua voz.
>
> E quando alguém se dispõe a contar e outra pessoa aceita imaginar… alguma coisa acontece
> entre as duas.
>
> É esse encontro que eu quero ensinar você a construir.
>
> **Você não precisa começar sem medo. Só precisa começar.**
>
> **Vem comigo?**
>
> *Conte&Encante — com Lelê*
>
> **CTA:** QUERO CONTAR E ENCANTAR

---

# 7. Antes de entregar, confira

- [ ] Um único arquivo HTML, sem dependência externa além das fontes.
- [ ] Os 18 blocos, na ordem, mais o topo e o rodapé mínimo.
- [ ] Nenhum link de navegação para outras páginas. A única saída é o checkout.
- [ ] Seis botões de compra apontando para `#CHECKOUT`: os cinco da copy (blocos 1, 4, 11,
      16 e 18) mais o "Quero entrar" do topo, que se repete na barra fixa.
- [ ] Barra fixa de compra aparecendo após 600px de scroll, e faixa inferior no mobile.
- [ ] Nenhuma cor fora da paleta. Nenhum rosa, nenhum preto puro, nenhum gradiente neon.
- [ ] Todo botão em pílula.
- [ ] `<em>` em Fredoka One laranja nos destaques de título.
- [ ] Girassol presente em pelo menos seis pontos da página, e nenhum outro ornamento
      decorativo.
- [ ] Nenhum emoji na interface.
- [ ] Café usado em exatamente duas seções inteiras (blocos 3 e 11) e num único painel
      interno, o remate do VIPE no bloco 8.
- [ ] Todo placeholder de foto com proporção, moldura e nome de arquivo corretos.
- [ ] Seis lacunas marcadas com o chip `a definir`, nenhuma preenchida por invenção.
- [ ] Uma única sigla aberta na página: **VIPE**. O VISAR é nomeado no bloco 7 e nunca
      destrinchado — sem lista de letras, sem cards por pilar, sem palavras deduzidas.
- [ ] O remate "Você não precisa ter dom para contar histórias. Mas precisa ter VIPE."
      presente no bloco 8, com o maior peso tipográfico da página depois do h1.
- [ ] Copy idêntica à seção 6, com toda a acentuação do português.
- [ ] Sem scroll horizontal de 320px a 1920px.
- [ ] Acordeão do FAQ navegável por teclado, com `aria-expanded`.
- [ ] Foco visível em todos os elementos interativos.
- [ ] Animações desligadas em `prefers-reduced-motion`.
- [ ] Nenhuma frase de coach, nenhum número inventado, nenhuma escassez fabricada.

===== FIM DO PROMPT =====
