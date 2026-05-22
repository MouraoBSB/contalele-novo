# Conta Lelê — Diretrizes da Marca

Este arquivo é a **fonte da verdade** da identidade visual da Conta Lelê.
Qualquer pessoa (ou agente) que trabalhe neste projeto deve ler este documento antes de criar interfaces, peças gráficas, posts, e-mails ou novas telas.

> "Antes do livro vem o som."

---

## 1. O que é a Conta Lelê

- **Marca de contação de histórias** + **plataforma de cursos online** para professores, mediadores e pais.
- Personagem central: **Lelê** — uma contadora de histórias real, com palco, escolas, canal no YouTube (toda sexta às 16h) e livros próprios.
- Tom de marca: **caloroso, lúdico, profissional**. Nunca infantilizado a ponto de virar "fofo demais". Os professores precisam levar a sério.

### Públicos
1. **Professores e mediadores** — compram os cursos (foco comercial principal).
2. **Pais e crianças** — assistem aos vídeos do canal.
3. **Pedagogos e bibliotecários** — compram livros.

### Voz
- 2ª pessoa direta ("Você vai aprender…", "A Lelê te conta…").
- Diminutivos suaves só quando se referir a histórias/objetos ("sementinha", "girassol"). Nunca para falar com adultos.
- Verbos sensoriais: contar, encantar, escutar, brincar, mediar.
- Evite: "incrível", "imperdível", "exclusivo", "transforme sua vida". Soa coach.

---

## 2. Logo

### Versões
| Arquivo | Quando usar |
|---|---|
| `assets/logo-black.png` | **Padrão.** Fundos claros (creme, amarelo, branco). |
| `assets/logo-white.png` | Fundos escuros (café `#2b1300`, roxo). |
| `assets/logo-yellow.png` | Fundo café escuro como destaque festivo. |
| `assets/logo-purple.png` | Selo / favicon / contextos onde precisa de fundo próprio. |

### Regras
- **Altura mínima:** 32 px em tela, 12 mm em impressão.
- **Margem de respiro:** o equivalente à altura do girassol em cada lado.
- **Nunca** estique, gire, troque cores das letras ou aplique sombra extra. O sombreado já vem desenhado no arquivo.
- O girassol é parte do logo — não use as palavras "Conta Lelê" sem ele em peças de comunicação.

---

## 3. Paleta

A paleta é **quente, terrosa, com 1 acento doce**. O amarelo girassol é a estrela; o café é a base de leitura; o laranja queimado é o destaque emocional.

### Tema padrão — **Amarelo (modo claro)**

| Token | Hex | Uso |
|---|---|---|
| `--bg` | `#fdf5d4` | Creme — background principal |
| `--bg-2` | `#fbe87f` | Amarelo claro — superfícies secundárias, seções alternadas |
| `--bg-3` | `#ffdc3a` | **Amarelo girassol** — CTAs amarelos, destaques |
| `--ink` | `#2b1300` | Café profundo — texto, botões primários, banners "stage" |
| `--ink-dim` | `rgba(43,19,0,0.58)` | Texto secundário |
| `--accent-2` | `#c4390e` | Laranja queimado — eyebrows, links, números de seção |
| `surface` | `#ffffff` | Cards |
| `borda` | `rgba(43,19,0,0.08)` | Hairline em cards e divisores |

### Tema secundário — **Roxo de palco (modo escuro)**

Para momentos teatrais (telas de espera, splash, materiais de evento ao vivo). **Não misturar amarelo + roxo na mesma peça** — escolha um modo.

| Token | Hex |
|---|---|
| `--bg` | `#3d0738` |
| `--bg-2` | `#5a0d52` |
| `--bg-3` | `#7b0968` |
| `--ink` | `#ffffff` |
| `--accent` | `#ffdc3a` |

### Cores proibidas
- ❌ **Rosa choque** (`#f586d5`, `#ffbaf0`) — saíram da paleta no rebrand. Não use.
- ❌ Gradientes neon, holográficos ou multicor.
- ❌ Pure black (`#000`). Use sempre `--ink` (#2b1300).
- ❌ Pure white em texto. Use `--bg` (creme) em fundos escuros.

---

## 4. Tipografia

### Famílias
| Papel | Família | Pesos | Onde usar |
|---|---|---|---|
| **Display / lúdico** | `Fredoka One` | 400 | Eyebrows ("Oi, eu sou a Lelê"), nomes ("Lelê" em itálico nos títulos), cronômetro, "Pronto, professora!" |
| **UI / leitura** | `Nunito` | 400 / 600 / 700 / 800 / 900 | Tudo o resto: títulos, corpo, botões, números |

```html
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&family=Fredoka+One&display=swap" rel="stylesheet" />
```

### Escala (mobile)
| Token | Tamanho | Peso | Uso |
|---|---|---|---|
| `display-xl` | 52px Fredoka | 400 | Sucessos, splash |
| `h1` | 28–32px Nunito | 900 | Títulos de página |
| `h2` | 22px Nunito | 800 | Section headers |
| `h3` | 18px Nunito | 800 | Cards |
| `body` | 14px Nunito | 400/600 | Corpo |
| `caption` | 12px Nunito | 700 | Metadata |
| `eyebrow` | 11px Nunito | 800 + tracking 0.16em UPPERCASE | Categorias |

### Escala (desktop)
Multiplique mobile por ~1.6 nos títulos. H1 de página vai a 64px.

### Regras
- Use **itálico via Fredoka One** para destacar palavras dentro de títulos: `histórias <em>de verdade</em>`. Os `<em>` ganham `font-family: var(--display); font-weight: 400; font-size: 1.15em;`.
- **Letter-spacing negativo** (`-0.02em` a `-0.025em`) em títulos grandes.
- `text-wrap: pretty` em parágrafos. `text-wrap: balance` em títulos.

---

## 5. Fotografia

- **Fotos reais da Lelê** — nunca gere com IA, nunca desenhe SVG.
- Estilo: fundo colorido sólido (azul, branco fotográfico), figurino preto + saias coloridas + flor no cabelo, expressão risonha ou olhar pra cima.
- Recorte preferido: **3:4 ou 4:5 vertical**. Em hero desktop, `border-radius: 280px 280px 24px 24px` (arco no topo).
- `object-position: 50% 30%` deixa o rosto no terço superior.
- **Não filtre** as fotos (preserve o azul do estúdio).

### Placeholder
Se não tiver a foto certa: bloco com `aspect-ratio: 4/5`, fundo `--bg-2`, com texto em monospace dizendo `[foto: Lelê com balões]` no centro.

---

## 6. Iconografia

- **Linha simples, 1.7px stroke, 24px viewBox**, sem preenchimento (exceto check e estrela).
- Cor: `currentColor` — herda do componente.
- Set base: home, book, play, bag, user, bell, back, search, heart, star, check, chevron, clock, lock, pix, card, whats, plus, cart.

### Quando precisar de um ícone novo
1. Cheque se já existe em `screens.jsx → Icon`.
2. Desenhe **simples, sem detalhe**, alinhado ao set existente.
3. **Não use emojis** na UI (exceto nas capas dos livros como acento gráfico — emojis grandes 56px de tamanho).

### Girassol
- Componente `<Sunflower size={N} />` em `screens.jsx`.
- É **o único ornamento decorativo da marca**. Use generosamente: cantos de cards, fundos de hero, ao lado de títulos, no rodapé.
- Cor fixa: pétalas `#ffcc1f`, contorno `#c4390e`, miolo `#5a2a0a`.

---

## 7. Layout

### Mobile (default)
- Largura de referência: **402px** (iPhone 14 Pro).
- Padding lateral: **16px** nas seções, **18px** nos title heads.
- Bottom nav 64px com **5 abas**: Início · Cursos · Canal · Loja · Conta.
- Cards: `border-radius: 18–22px`, `padding: 14–18px`, fundo branco (`#fff`) com sombra `0 8px 18px -14px rgba(43,19,0,0.18)`.

### Desktop
- Largura máxima de conteúdo: **1180px** centralizado.
- Padding lateral: 32px.
- Grade de cursos: **3 colunas**.
- Seções alternam `--bg` (creme) com `#fff` para criar ritmo.

### Raios
- `8px` — chips, badges
- `12–14px` — inputs, módulos
- `18px` — cards padrão
- `22–24px` — cards de destaque, banners
- `999px` — botões (sempre pílula), avatares, tags

### Espaçamento (4px grid)
4 · 6 · 8 · 12 · 14 · 16 · 18 · 22 · 24 · 32 · 40 · 48 · 56 · 72

---

## 8. Componentes

### Botões
```css
.btn-primary { background: var(--ink); color: var(--bg-3); }  /* café com texto amarelo */
.btn-yellow  { background: var(--bg-3); color: var(--ink); border: 1.5px solid var(--ink); }
.btn-ghost   { background: transparent; color: var(--ink); border: 1.5px solid var(--ink); }
```
- Sempre **pílula** (`border-radius: 999px`).
- Peso 800 no texto.
- No hover (desktop): `transform: translateY(-1px)`.

### Cards de curso
Cada curso tem `accent` (fundo) e `ink` (texto). Use **apenas a paleta restrita**:
- Amarelo (`#ffdc3a`) + café
- Laranja (`#c4390e`) + creme
- Café (`#2b1300`) + amarelo
- Creme (`#fbe87f`) + café

O girassol fica no canto inferior direito, posicionado em `right: -22px; bottom: -22px;`.

### Banners "ao vivo" (sexta-feira)
- Fundo **café `--ink`** com texto creme.
- Número grande em **Fredoka One amarelo `--bg-3`**.
- Tag pílula `bg-3` no creme.

---

## 9. Não-fazeres (anti-padrões)

- ❌ Gradientes vermelho→roxo na mesma peça
- ❌ Rosa pastel ou rosa choque (saíram do rebrand)
- ❌ Sombras roxas em fundos amarelos
- ❌ Glassmorphism (vidro fosco) — não combina com a estética terrosa
- ❌ "Coach speak" (incrível, exclusivo, transforme)
- ❌ Emojis fora das capas de livro
- ❌ SVGs ilustrativos hand-drawn — use foto real ou o girassol
- ❌ Sans-serif moderna genérica (Inter, Roboto). Use Nunito.
- ❌ Botão retangular. Sempre pílula.

---

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

---

## 11. Para o Claude Code

Quando o usuário pedir uma nova funcionalidade, peça, conteúdo:
1. **Releia este CLAUDE.md.** Não improvise paleta nem tipografia.
2. **Use os tokens existentes** (`var(--ink)`, etc.). Não introduza hex novos sem consultar.
3. **Antes de gerar imagem ou SVG decorativo,** pergunte. A Lelê provavelmente tem o ativo real.
4. **Em dúvida sobre tom**, releia a seção 1.3 (Voz) e olhe o copy existente em `data.js`.
5. **Não recrie a paleta antiga** (rosa/roxo na mesma peça). O sistema atual está em modo amarelo claro.
