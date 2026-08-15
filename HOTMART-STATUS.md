# Hotmart — Conte&Encante · Status da configuração

**Última atualização:** 14/08/2026
**Lançamento:** 15/08/2026
**Situação geral:** **vendas ativas**. Produto no ar, página publicada, fluxo de
compra testado de ponta a ponta.

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
Checkout (usado nos botões da página de vendas):
https://pay.hotmart.com/B107134315C?off=87ea4lrn

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
| Preço | R$ 297,00 |
| Parcelamento | Até **12x de R$ 24,75 sem juros** (produtora paga as taxas) |
| Garantia | **7 dias** (mínimo legal — CDC art. 49) |
| Formas de pagamento ativas | Cartão, **Pix**, **Boleto**, **PayPal** |
| Recuperador automático | Ativo, até 12 parcelas |
| Conversão de moeda automática | Ativa |
| Vendas internacionais | Ligado |

### Custo do parcelamento sem juros

Base de cálculo sobre a qual incidem taxas e comissões:

| Parcelamento | Base |
|---|---|
| 1x | R$ 297,00 |
| 6x | R$ 263,85 |
| 12x | **R$ 239,31** |

Uma venda em 12x rende **R$ 57,69 a menos** de base que uma à vista.

> **Divergência com decisão anterior:** o status de 14/08 registrava o boleto como
> "desativado por decisão — baixa conversão". Ele está **ativo**, e o PayPal também.
> O boleto atrasa o acesso (compensação em até 3 dias). Decisão de manter ou desativar
> ficou em aberto. A página de vendas menciona apenas Pix e cartão.

> **Oportunidade não implementada:** desconto no Pix. Como o Pix não tem custo de
> antecipação, um preço em torno de R$ 267 no Pix ainda superaria o líquido de uma
> venda em 12x.

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

**Aba Adicional:** módulo `Cante & Encante`, 6 aulas — **sem nome e não publicadas**.
Previsão de liberação: **22/08/2026**.

**Segundo bônus** (`Pequenos Objetos, Grandes Histórias`): ainda não existe na área.
Mesma previsão.

### Tempo de acesso

Configurado por **agendamento em massa**, nos módulos 01 a 05 da aba Principal:

```
Agendar liberação do conteúdo ....... DESLIGADO  (libera na compra)
Agendar expiração ................... 365 dias após a liberação
Turmas .............................. Todas as turmas
```

O **Módulo 0 ficou de fora** de propósito — segue vitalício, como porta de entrada.

> **Pendente:** repetir o agendamento na aba **Adicional**, para o Cante & Encante.
> Sem isso o bônus fica vitalício enquanto o curso expira em 1 ano.

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
| Preço e parcelamento | R$ 297 · 12x de R$ 24,75 sem juros · Pix ou cartão |
| Garantia | 7 dias, com a redação da Lelê |
| Método | VISAR nomeado, **não destrinchado** (decisão dela: uma sigla só na página) |
| VIPE | Bloco completo, com o remate "Você não precisa ter dom… Mas precisa ter VIPE" |
| Bônus | Os dois, com selo "Liberado em 22 de agosto" |
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
o campo preenchido sobrescreve.

```
curso_publicado          curso_previa_hash        curso_checkout_url
curso_preco              curso_parcelamento       curso_garantia_dias
curso_certificado        curso_tempo_acesso       curso_bonus
curso_bonus_liberacao    curso_encontros_ao_vivo  curso_whatsapp_grupo
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

### Ação imediata

- [ ] **Agendar 365 dias na aba Adicional** (Cante & Encante) — sem isso o bônus fica
      vitalício enquanto o curso expira
- [ ] **Publicar as 6 aulas do Cante & Encante** e montar o *Pequenos Objetos, Grandes
      Histórias* até **22/08** — a página anuncia essa data
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
