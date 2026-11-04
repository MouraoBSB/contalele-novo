# Hotmart — Status da configuração

**Última atualização:** 04/11/2026

| Produto | Lançamento | Situação |
|---|---|---|
| Conte&Encante (seções 1 a 8) | 15/08/2026 | **Vendas ativas.** Black Friday ativa desde 03/11/2026 (seção 3.1) |
| Cante & Encante (seção 9) | 03/11/2026 | **Vendas ativas.** Lançado já com o preço de Black Friday |

Este documento é um handoff. Registra o que foi configurado na Hotmart, as decisões
tomadas com o motivo, e o que continua pendente.

---

## 1. Identificação do produto

| Campo | Valor |
|---|---|
| Nome cadastrado | `Conte & Encante: transforme histórias em experiências inesquecíveis` |
| ID do produto | `8301197` |
| Categoria | Educacional |
| Formato | Cursos Online, Área de Membros, Serviços de Assinatura |
| Idioma | Português (Brasil) |
| Principal país | Brasil |
| Capa | 600×600, fundo café com girassóis, lettering Conte&Encante + logo Conta Lelê |
| Descrição | 1262/2000 caracteres — pronta |
| Marketplace | Publicado |
| Vendas ativas | **Ligado** |

### Conta da produtora

| Campo | Valor |
|---|---|
| Titular | Letícia Rocha Mourão Marques |
| E-mail da conta | `curso.conte.encante@gmail.com` |
| Nome público | `Conta Lelê` — **confirmado**, aparece como "Autor" no checkout |
| Usuário | `@contalele` |
| Site no perfil | https://contalele.com.br |
| Natureza fiscal | **Pessoa Física (CPF)** — cadastro validado |

> O CPF e o endereço aparecem no painel, mas foram **deliberadamente omitidos deste
> arquivo** porque ele fica versionado no repositório.

---

## 2. Links (fonte da verdade)

```
Checkout da oferta normal (R$ 397; botões da página fora da Black Friday):
https://pay.hotmart.com/B107134315C?off=87ea4lrn

Checkout da Black Friday (R$ 297; botões da página enquanto a promoção estiver ligada):
https://pay.hotmart.com/B107134315C?off=36l1f851

Página de Vendas gerada pela Hotmart (redireciona para a página externa):
https://go.hotmart.com/B107134315C

Página do Produto (Marketplace):
https://go.hotmart.com/B107134315C?dp=1

Página de vendas própria (publicada):
https://contalele.com.br/conte-e-encante

Área de membros:
https://hotmart.com/pt-br/club/leticia-rocha-mourao
```

O `?off=87ea4lrn` fixa a oferta. Sem ele o link segue o preço base vigente, o que
mudaria sozinho no dia em que existir uma promoção.

> **O link do botão "Copiar link de pagamento"** da tela de Links de divulgação vem
> **sem** o `?off`. Não use esse — use o de cima.

---

## 3. Oferta e pagamento

| Item | Valor |
|---|---|
| Oferta ativa | `Padrao sem juros` — código `87ea4lrn` |
| Preço | R$ 397,00 (o R$ 297,00 anterior era promocional) |
| Parcelamento | Até **12x de R$ 33,09 sem juros** (produtora paga as taxas) |
| Garantia | **7 dias** (mínimo legal — CDC art. 49) |
| Formas de pagamento ativas | Cartão, **Pix**, **Boleto**, **PayPal** |
| Recuperador automático | Ativo, até 12 parcelas |
| Conversão de moeda automática | Ativa |
| Vendas internacionais | Ligado |

### Custo do parcelamento sem juros

Base de cálculo sobre a qual incidem taxas e comissões:

| Parcelamento | Base |
|---|---|
| 1x | R$ 397,00 |
| 6x | R$ 352,71 |
| 12x | **R$ 319,95** |

Uma venda em 12x rende **R$ 77,05 a menos** de base que uma à vista.

> **Divergência com decisão anterior:** o status de 14/08 registrava o boleto como
> "desativado por decisão — baixa conversão". Ele está **ativo**, e o PayPal também.
> O boleto atrasa o acesso (compensação em até 3 dias). Decisão de manter ou desativar
> ficou em aberto. A página de vendas menciona apenas Pix e cartão.

> **Oportunidade não implementada:** desconto no Pix. Como o Pix não tem custo de
> antecipação, um preço em torno de R$ 267 no Pix ainda superaria o líquido de uma
> venda em 12x.

### 3.1 Black Friday 2026

| Item | Valor |
|---|---|
| Oferta | `Black Friday 2026` — código `36l1f851` (criada em 03/11/2026) |
| Preço | R$ 297,00 (R$ 100 abaixo da oferta normal) |
| Parcelamento | Até **12x de R$ 24,75 sem juros** (produtora paga as taxas) |
| Descrição no checkout | `Curso Conte e Encante - acesso completo - Black Friday` |
| Formas de pagamento | Cartão, Pix, Boleto, PayPal (as mesmas da oferta normal) |
| Recuperador automático | Ativo, até 12 parcelas |
| Conversão de moeda automática | Ativa |
| Prazo | **Sem data de término.** Por tempo limitado, encerrada manualmente |

A oferta normal `87ea4lrn` e o preço base (R$ 397) **não foram alterados**. Só a
página de vendas troca de checkout durante a promoção.

**Na página:** o array `$promocao` em `paginas/conte-e-encante.php` liga e desliga a
Black Friday (`'ativa' => true/false`). Ligada, todos os botões vão para `36l1f851`,
com faixa, topo café, selo e cartão "de R$ 397 por R$ 297". Desligada, o HTML volta
igual ao da tag git `conte-e-encante-pre-black-friday`.

> **Fora do site, continuam em R$ 397:** os QR codes da seção 6 (`qr-conte-encante-*`)
> e qualquer link de checkout colado em bio, WhatsApp ou grupo com `?off=87ea4lrn`.
> Para divulgar a Black Friday, use o link da `36l1f851`.

---

## 4. Área de membros (Hotmart Club)

**Club:** `Conta Lelê` · URL `hotmart.com/club/leticia-rocha-mourao` (URL não é editável)

### Conteúdo — tudo publicado

| Módulo | Aulas |
|---|---|
| 0 — Comece aqui | 3 |
| 01 — Peguei o Livro e agora? | 5 (inclui *Método VISAR: Os 5 Pilares do Encantamento*) |
| 02 — Do livro a História | 5 |
| 03 — Como Encantar e Prender a Atenção das Crianças | 4 |
| 04 — Recursos que Encantam | 6 |
| 05 — O Encantamento Continua | 4 |
| **Total** | **27 aulas em 6 módulos** |

**Aba Adicional** (conferida em 04/11/2026) — tudo publicado, com expiração de 365 dias:

| Módulo | Conteúdos | Liberação |
|---|---|---|
| Cante & Encante (bônus) | 6 aulas | Desde 22/08/2026 |
| Pequenos Objetos, Grandes Histórias (bônus) | 24 (Comece aqui + 23 histórias) | 7 dias após a compra |
| Encontros de Mentoria | 1 (*Encontro 01 — Laboratório do Imaginar*) | Na compra, sem expiração |

### Tempo de acesso

Configurado por **agendamento em massa**, nos módulos 01 a 05 da aba Principal:

```
Agendar liberação do conteúdo ....... DESLIGADO  (libera na compra)
Agendar expiração ................... 365 dias após a liberação
Turmas .............................. Todas as turmas
```

O **Módulo 0 ficou de fora** de propósito — segue vitalício, como porta de entrada.
Os módulos de bônus da aba **Adicional** também expiram em 365 dias.

Para conferir ou alterar: selecione os módulos → **Agendar**. Não existe "editar" —
um novo agendamento substitui o anterior. Há também "Remover agendamentos".

### Certificado

**Ativado.** Fundo personalizado na identidade da marca (creme, moldura café e laranja,
girassóis, logo Conta Lelê). Textos em português. O nome do curso foi escrito **fixo**
como "Conte&Encante" em vez da tag `*|CURSO|*`, para evitar o título longo e o `&`
virando `&amp;`.

Arquivo: `Identidade Visual/Conte e Encante/qr/certificado-fundo-2000x1414.jpg`

### Personalização

| Item | Estado |
|---|---|
| Nome do Club | ✅ Conta Lelê |
| Logo tema escuro | ✅ `club-logo-tema-escuro.png` (logo branca, 131×96) |
| Logo tema claro | ✅ `club-logo-tema-claro.png` (logo preta, 131×96) |
| Fundo da página de login | ✅ `club-login-fundo-2912x2160.jpg` |
| Cor de destaque | ✅ definida |
| Vitrine | **Não publicada** — decisão: com um produto só, ela cria uma tela
intermediária inútil. A página inicial é o próprio curso |
| Aplicativo Web | Não configurado (atalho na tela do celular) |
| Pixel de rastreamento | Inativo |
| Tutor (IA) | Inativo |

---

## 5. Página de vendas

`https://contalele.com.br/conte-e-encante` — **publicada**, sem senha, indexável.

Landing isolada, sem menu: a única saída é o checkout. 19 blocos.

| Elemento | Estado |
|---|---|
| Preço e parcelamento | R$ 397 · 12x de R$ 33,09 sem juros · Pix ou cartão. **Na Black Friday:** de R$ 397 por R$ 297 · 12x de R$ 24,75 (ver 3.1) |
| Garantia | 7 dias, com a redação da Lelê |
| Método | VISAR nomeado, **não destrinchado** (decisão dela: uma sigla só na página) |
| VIPE | Bloco completo, com o remate "Você não precisa ter dom… Mas precisa ter VIPE" |
| Bônus | Os dois, com selo "Já disponível" |
| Depoimentos | 3, com foto: Valdenira Agostinho, Vitória Carolina, Regina Melo |
| Tempo de acesso | 1 ano, justificado pelos 5 encontros ao vivo |
| Certificado | Anunciado |
| Grupo do WhatsApp | Convite no fim, abaixo do último CTA |
| Linguagem | Neutra quanto a gênero (ver 7.3) |
| Selos "a definir" | **Nenhum** |

### Repasse de código de afiliado

A configuração *"Adicionar Código de Afiliação à página externa"* está **ligada**: a
Hotmart anexa `?a=CODIGO` na URL da página. A página **repassa** `a`, `src`, `sck` e
`xcod` para o link do checkout, com lista fechada de parâmetros e valor sanitizado.
Sem isso a comissão não seria creditada.

### Campos editáveis no painel

`/admin/configuracoes.php` → seção **Curso Conte&Encante**. Todos têm padrão no código;
o campo preenchido sobrescreve. Checkout, preço e parcelamento em branco voltam ao
padrão (o formulário grava todos os campos de uma vez, então "em branco" é comum).
Nos demais, em branco tem efeito na página: o item some ou mostra "a definir".

```
curso_publicado          curso_previa_hash        curso_checkout_url
curso_preco              curso_parcelamento       curso_garantia_dias
curso_certificado        curso_tempo_acesso       curso_bonus
curso_carga_horaria      curso_encontros_ao_vivo  curso_whatsapp_grupo
```

---

## 6. Peças gráficas produzidas

Todas em `Identidade Visual/Conte e Encante/`. **Esta pasta está no `.gitignore`** —
não vai para o GitHub. Confirmar que o backup à parte está atualizado.

| Peça | Arquivo |
|---|---|
| Arte do curso (3 formatos × 2 cores) | `arte/conte-encante-*.png` |
| Banner do produto na Hotmart | `qr/banner-hotmart-1920x640.jpg` |
| Fundo do certificado | `qr/certificado-fundo-2000x1414.jpg` |
| QR do checkout (3 formatos) | `qr/qr-conte-encante-*.jpg` |
| Logos do Club | `qr/club-logo-tema-*.png` |
| Fundo do login do Club | `qr/club-login-fundo-2912x2160.jpg` |
| Live no YouTube (thumb, post, story) | `qr/live-youtube-*.jpg` |

Os arquivos `.html` ao lado são as **fontes** — abrem no navegador e reexportam por
Chrome headless, com o comando no comentário do topo de cada um.

> Os QR codes foram **validados por leitura de volta**: o script decodifica o próprio
> arquivo salvo e compara com a URL. Se regerar, mantenha essa checagem — a primeira
> versão não era legível por falta de quiet zone.

---

## 7. Decisões tomadas, com o motivo

### 7.1 VISAR nomeado, não destrinchado

A Lelê definiu as 5 letras (Verdade, Intenção, Simbolismo, Adaptação, Ritmo), mas
decidiu **não abri-las na página** — VISAR e VIPE compartilhavam Verdade e Intenção
com a mesma definição, e duas siglas em blocos seguidos confundiam. A página nomeia o
método; quem ensina as letras é a Aula 05 do Módulo 01.

### 7.2 Acesso de 1 ano, não vitalício

O padrão da Hotmart é vitalício. A limitação foi decisão da Lelê, justificada pelos
5 encontros ao vivo — o prazo cobre a janela da consultoria. A página explica isso em
vez de só informar a restrição.

### 7.3 Linguagem aberta a homens

Homens que contam histórias reclamaram que a divulgação falava só no feminino. Onze
pontos da página foram neutralizados ("mesmo que a timidez trave você" no lugar de
"mesmo que você seja tímida"). Preservado o feminino onde é correto: a bio da Lelê e
as concordâncias com "história".

**Não é mudança de posicionamento** — o público segue majoritariamente feminino. O
objetivo foi remover a exclusão explícita. Depoimento masculino fica para o futuro.

### 7.4 A live não entra na página de vendas

O fluxo correto é live → página, não página → live. Quem está na página está mais
perto da compra do que quem está no YouTube, e o YouTube não devolve ninguém. A live
divulga por Instagram, WhatsApp e canal; o QR do checkout aparece na transmissão.

### 7.5 Widget de checkout descartado

A Hotmart oferece um widget que abre o pagamento em janela sobreposta. Não usado:
carrega script e CSS de terceiro na página, o botão é uma imagem verde genérica, e o
`?checkoutMode=2` não carrega o `?off`.

### 7.6 Programa de afiliados aberto

Decisão do cliente, ciente dos riscos apontados: divulgação fora de controle, anúncio
pago usando a marca, e uso da imagem da Lelê. Comissão definida pelo cliente.

### 7.7 Decisão tributária adiada

Cadastro é **Pessoa Física**. O MEI tende a ter carga efetiva menor, mas a Letícia não
tem MEI e o lançamento era no dia seguinte. **Assunto para revisitar com um contador.**
*Não houve orientação contábil — apenas o alerta.*

---

## 8. Pendências

### Ao encerrar a Black Friday (vale para os dois produtos)

- [ ] Conte: `'ativa' => false` em `$promocao` (`paginas/conte-e-encante.php`) e deploy
- [ ] Cante: `'ativa' => false` em `$promocao` (`paginas/cante-e-encante.php`) e deploy —
      os botões voltam sozinhos para a oferta `q5glzjoe` (R$ 97)
- [ ] Decidir se as ofertas `36l1f851` (Conte, R$ 297) e `nn7nhybh` (Cante, R$ 67) ficam
      inativas na Hotmart, para ninguém comprar com desconto por um link antigo

### Ação imediata

- [x] ~~Agendar 365 dias na aba Adicional~~ — feito (conferido em 04/11/2026)
- [x] ~~Publicar o Cante & Encante e o Pequenos Objetos~~ — feito (seção 4)
- [ ] **Reconhecimento facial** — só a Letícia pode fazer; destrava o saque

### Quando entrar a primeira venda

- [ ] **Cadastro bancário** — a tela `account.hotmart.com/financial` só abre depois da
      primeira venda ("Faça uma venda antes de cadastrar seus documentos"). Não é
      bloqueio por tempo, é por marco. O valor fica em saldo até o cadastro

### Sem pressa

- [ ] Decidir sobre **boleto e PayPal** (ver seção 3)
- [ ] **Personalização do Club**: Aplicativo Web (atalho no celular)
- [ ] Trocar o nome do produto para remover o `&` (o painel renderiza `Conte &amp;
      Encante`, e esse nome viaja para e-mail, recibo e fatura do cartão)
- [ ] **Aparência da página de pagamento** — hoje é o checkout padrão
- [ ] Cupons de lançamento
- [ ] ListBoss (segmentação de leads)
- [ ] **Produto gratuito** como isca: a área de membros só abre com compra registrada,
      então o Módulo 0 não é acessível sem comprar. Um produto de R$ 0 com uma aula
      prática (ex.: "Objetos que contam") capturaria e-mail e serviria de degustação
- [ ] **Bloco de grade do curso** na página de vendas — os 6 módulos e 27 aulas. É a
      informação que mais falta para quem está decidindo

### Atenção

> A Hotmart avisou que pode **exigir comprovação do tipo do produto em 48h**, sob risco
> de suspensão, por causa da correção da regra fiscal (ESS + conteúdo educacional). A
> área de membros com as 27 aulas publicadas é essa comprovação. Ficar de olho no
> e-mail da conta.

---

## 9. Cante & Encante (produto solo)

Antes era só o bônus da aba Adicional do Conte&Encante (quem comprou o Conte continua
com ele lá). Virou produto próprio em 03/11/2026, lançado já com o preço de Black Friday.

### Identificação

| Campo | Valor |
|---|---|
| Nome cadastrado | `Cante e Encante: como usar a música para dar vida às histórias` — com "e", não "&", para não virar `&amp;` em e-mail, recibo e fatura |
| ID do produto | `8875349` |
| Categoria | Educacional · Curso Online |
| Capa | **Provisória** (foto da aula 1 + letreiro dos materiais). A definitiva sai de um frame de vídeo |
| Vendas ativas | **Ligado** — aprovado em 03/11/2026, depois de "Finalizar cadastro" (com aceite do Termo de Tratamento de Dados Pessoais) |

### Links

```
Checkout da oferta normal (R$ 97; botões da página fora da Black Friday):
https://pay.hotmart.com/O108321613K?off=q5glzjoe

Checkout da Black Friday (R$ 67; botões da página enquanto a promoção estiver ligada):
https://pay.hotmart.com/O108321613K?off=nn7nhybh

Página de vendas própria (cadastrada como página externa do produto):
https://contalele.com.br/cante-e-encante
```

### Ofertas

| Oferta | Código | Preço | Parcelamento |
|---|---|---|---|
| Preço base | `q5glzjoe` | R$ 97,00 | até 12x de R$ 8,09 sem juros |
| Black Friday 2026 | `nn7nhybh` | R$ 67,00 | até 12x de R$ 5,59 sem juros |

Garantia de **7 dias**. Black Friday **sem data de término**, encerrada manualmente
(checklist na seção 8).

### Área de membros

Mesmo Club `Conta Lelê`. Aba **Principal**, módulo `Cante & Encante`, tudo publicado com
expiração de **365 dias** para todas as turmas (via Agendar em massa):

| Aula | Título |
|---|---|
| 01 | A música como aliada da narrativa |
| 02 | Rituais de começo e de fim |
| 03 | Cantigas de roda e paródias |
| 04 | Criando músicas para histórias |
| 05 | Música sem complicação |
| 06 | Oficina musical |

Materiais anexados às aulas: letras **com cifras** (não partitura), os mapas mentais das
aulas 1 e 5 e o e-book *Circo Encantado* (aula 6).

A aba **Adicional** está vazia: as cópias importadas do Conte (6 aulas + "Cópia de
Aula 01", nenhuma publicada) foram excluídas em 04/11/2026.

### Certificado

**Em montagem — ainda desligado.** O fundo está pronto, com a mesma arte do Conte e o
selo "Cante & Encante": `Identidade Visual/Cante e Encante/certificado-fundo-2000x1414.jpg`
(fonte: `certificado-fundo.html` na mesma pasta). Falta subir na Hotmart, montar os
textos e ligar a publicação.

> A Hotmart não tem tag de carga horária no certificado (só participante, curso,
> organizador, data de término, turma e QR code). Se entrar, é texto fixo.

### Página de vendas

`paginas/cante-e-encante.php` — landing isolada, sem senha, indexável. O array
`$promocao` liga e desliga a Black Friday; desligada, os botões vão para `q5glzjoe`.
Repasse de afiliado igual ao do Conte (`checkout_com_afiliado()`).

Painel → seção **Curso Cante & Encante**:

```
cante_checkout_url   vazio = q5glzjoe
cante_certificado    vazio = a página não fala de certificado (nem na lista nem no FAQ)
```

### Pendências

- [ ] **Capa definitiva** (a partir de um frame de vídeo)
- [ ] **Certificado**: configurar e ligar na Hotmart; depois preencher `cante_certificado`
- [ ] Conferir se o produto tem programa de afiliados e "Adicionar Código de Afiliação à
      página externa" ligado, como o Conte
- [ ] Materiais para a Lelê corrigir: o anexo 3 da Lagarta credita "Nhoc nhoc nhoc" ao
      Kleyton (na aula é do Chauan); o rodapé do anexo 4 diz "CONTE & ENCANTE"; falta o
      recurso *Adivinhas dos Animais* / *Cordel dos Animais*, que a aula 6 menciona
