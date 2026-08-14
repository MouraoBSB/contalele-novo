# Hotmart — Conte&Encante · Status da configuração

**Data desta sessão:** 14/08/2026
**Lançamento previsto:** 15/08/2026
**Situação geral:** vendas **desligadas** enquanto a configuração é fechada.

Este documento é um handoff. Ele registra o que foi alterado na Hotmart nesta
sessão, as decisões tomadas com o motivo, os dados novos descobertos e o que
continua pendente.

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
| Descrição | Já escrita, 1262/2000 caracteres — boa, não precisa mexer |
| Marketplace | Publicado |
| Vendas ativas | **Desligado** |

### Conta da produtora

| Campo | Valor |
|---|---|
| Titular | Letícia Rocha Mourão Marques |
| E-mail da conta | `curso.conte.encante@gmail.com` |
| Nome público | `Conta Lelê` (alterado nesta sessão) |
| Usuário | `@contalele` (alterado nesta sessão) |
| Site no perfil | https://contalele.com.br |
| Natureza fiscal | **Pessoa Física (CPF)** — cadastro validado |

> O CPF e o endereço aparecem no painel, mas foram **deliberadamente omitidos deste
> arquivo** porque ele fica versionado no repositório.

---

## 2. Links (fonte da verdade)

```
Checkout (usar nos botões da página de vendas):
https://pay.hotmart.com/B107134315C?off=87ea4lrn

Checkout sem parâmetro de oferta (funciona hoje, menos robusto):
https://pay.hotmart.com/B107134315C

Página de Vendas gerada pela Hotmart (redireciona para a página externa):
https://go.hotmart.com/B107134315C

Página do Produto (Marketplace):
https://go.hotmart.com/B107134315C?dp=1

Página de vendas própria:
https://contalele.com.br/conte-e-encante
```

O `?off=87ea4lrn` fixa a oferta. Sem ele o link segue o "preço base" atual — funciona
hoje porque só existe uma oferta, mas quebra o dia em que criarem uma promoção.

---

## 3. O que foi alterado nesta sessão

### 3.1 Regra fiscal — corrigida

O produto estava marcado como **"Produto sem conteúdo educacional"**, contradizendo a
categoria "Educacional" e a própria natureza do produto.

Corrigido para:
- Formato: **Conteúdo com menor interação humana (ESS)** — aulas gravadas
- Conteúdo educacional: **Sim**

Resultado declarado pela Hotmart: *"Seu produto tem conteúdo digital ou assíncrono
(ESS) e tem conteúdo educacional."* Hotmart gerencia impostos em vendas para a União
Europeia; possível isenção no Chile.

> **Atenção:** a Hotmart avisa que pode exigir **comprovação do tipo do produto com 48h
> de prazo**, sob risco de suspensão da conta. A área de membros com aulas publicadas é
> essa comprovação.

### 3.2 Oferta — recriada para parcelamento sem juros

A oferta original cobrava os juros da compradora. O campo "Forma de pagamento" é
**irreversível** depois que a oferta é criada, então foi necessário criar uma nova.

| | Oferta antiga | Oferta atual |
|---|---|---|
| Nome | Preço base | `Padrao sem juros` |
| Código | `8y0ha3nf` | `87ea4lrn` |
| Forma de pagamento | Com taxas para o cliente | **Sem taxas para o cliente (produtora paga)** |
| Situação | **Removida** | **Preço base ativo** |

Descrição no checkout: `Curso Conte e Encante - acesso completo`.

**Ordem que a Hotmart exige:** não é possível remover a oferta que é o preço base.
Primeiro `Tornar preço base` na nova, depois `Remover oferta` na antiga.

### 3.3 Impacto financeiro do parcelamento sem juros

Comparação registrada do painel:

| | Cliente paga (antes) | Cliente paga (agora) |
|---|---|---|
| 12x | R$ 30,72 → **total R$ 368,64** | R$ 24,75 → **total R$ 297,00** |

Custo para a produtora — coluna "Valor base para taxas e comissões":

| Parcelamento | Base de cálculo |
|---|---|
| 1x | R$ 297,00 |
| 6x | R$ 263,85 |
| 12x | **R$ 239,31** |

Uma venda em 12x rende **R$ 57,69 a menos** de base que uma à vista, e a comissão da
Hotmart ainda incide sobre esse valor.

> **Oportunidade não implementada:** desconto no Pix ou no à vista. Como o Pix não tem
> custo de antecipação, um preço tipo R$ 267 no Pix ainda superaria o líquido de uma
> venda em 12x. Fica registrado para avaliação futura.

### 3.4 Recuperador automático — ativado

Estava inativo. Ligado na nova oferta, configurado para **recuperar vendas de até 12
parcelas**. Recupera Pix não pago e carrinho abandonado. Sem custo.

### 3.5 Perfil da conta — ajustado

- Nome público: `Contalelê` → **`Conta Lelê`** (a marca tem espaço)
- Usuário: `@curso_conte_en299663` → **`@contalele`**

Ambos os campos apareceram como "Campo validado com sucesso", mas o botão **Salvar
estava acinzentado** no último print. **Confirmar se a alteração foi gravada.**

---

## 4. Configurações confirmadas (sem alteração necessária)

| Item | Estado |
|---|---|
| Preço base | R$ 297,00 |
| Garantia / prazo de reembolso | **7 dias** (mínimo legal — CDC art. 49) |
| Formas de pagamento | Pix + cartão de crédito (confirmado pelo usuário) |
| Boleto | Desativado por decisão — baixa conversão real |
| Conversão de moeda automática | Ativa (obrigatória para preços em BRL) |
| Vendas internacionais em outras moedas | Ligado |
| Hotmart One (doação social) | Inativo — opcional, sem impacto |

---

## 5. Descobertas relevantes

### 5.1 Cadastro financeiro só abre depois da primeira venda

A tela `account.hotmart.com/financial` exibe:

> **"Faça uma venda antes de cadastrar seus documentos"**

Não é bloqueio por tempo, é bloqueio por marco. **Não é possível cadastrar dados
bancários antes de vender.** A primeira venda entra e fica em saldo até o cadastro ser
completado — o valor não se perde, e a Hotmart já retém por um período de segurança de
qualquer forma.

Consequência: **o cadastro financeiro não pode ser pré-requisito para religar as
vendas.** A ordem é a inversa.

### 5.2 Reconhecimento facial já está liberado

A tela de verificação de identidade está acessível **agora**, mesmo com o financeiro
travado. Ela destrava a conquista "Cadastro Completo", exigida para saque.

Requisitos: documento em mãos, celular, e **a administradora principal da conta precisa
fazer pessoalmente** — ou seja, a Letícia, não terceiros.

### 5.3 Decisão tributária adiada

O cadastro é **Pessoa Física**. Foi levantado que o MEI tende a ter carga efetiva
bastante menor sobre o mesmo faturamento, e que mudar a natureza do cadastro depois de
já haver faturamento dá mais trabalho.

**Decisão do cliente: manter CPF.** A Letícia não tem MEI e o lançamento é amanhã, sem
margem para abrir. Fica registrado como assunto a revisitar com um contador depois do
lançamento. *Não houve orientação contábil nesta sessão — apenas o alerta.*

### 5.4 O `&` no nome do produto

O painel da própria Hotmart renderizou **"Conte &amp; Encante"** no card de produtos —
escape de HTML mal resolvido. Esse nome viaja para e-mail de confirmação, recibo, área
de membros e descritor da fatura do cartão.

**Recomendação (não aplicada):** trocar o nome cadastrado para
`Conte e Encante: transforme histórias em experiências inesquecíveis`, mantendo o `&`
apenas na logomarca e na página de vendas, onde é assinatura visual e nada escapa.

### 5.5 Parâmetro de afiliação na página externa

A configuração **"Adicionar Código de Afiliação à página externa dos meus produtos"**
está **ligada**. Quando alguém chegar por link de afiliado, a Hotmart anexa um parâmetro
na URL (`?a=CODIGO`).

**A página de vendas precisa repassar esse parâmetro para o link do checkout**, senão a
comissão não é creditada. Ainda não há programa de afiliados ativo, então não é urgente
— mas é o tipo de falha que só aparece quando um afiliado reclama.

### 5.6 Contador de ofertas inconsistente

Após a remoção da oferta antiga, a tabela exibia **"Mostrando 1 de 2 registros"** com
apenas uma linha visível. Provavelmente cache de contador. **Não verificado** — vale
recarregar e confirmar que a oferta antiga sumiu de fato.

### 5.7 Site bloqueia acesso automatizado

`contalele.com.br` e `contalele.com.br/conte-e-encante` retornam **403 Forbidden** para
requisições automatizadas. Como a raiz também bloqueia, é regra de servidor
(`.htaccess`/firewall), não página ausente. **O estado da página de vendas não foi
verificado nesta sessão.**

---

## 6. Pendências

### Bloqueiam o lançamento

- [ ] **Área de membros / gestão do curso** — não verificada. Confirmar:
  - quantos **módulos** e quantas **aulas**, e se estão **publicados** ou em rascunho
  - **tempo de acesso** (vitalício ou por período)
  - **certificado** ativado ou não
  - **materiais complementares** anexados
  > Se o conteúdo não estiver publicado, a aluna paga e entra numa área vazia. É o
  > único item capaz de virar problema de reputação no dia 1.
- [ ] **Religar "Ativar vendas"** — último clique, depois de tudo conferido

### Iniciadas, status desconhecido

- [ ] **Passo 7 — associar página externa**: colar `https://contalele.com.br/conte-e-encante`
      em *Produto → Página do produto → Páginas externas → Configurar Página*
- [ ] **Descartar a Hotmart Pages** parada em "1/2 passos" (não será usada)
- [ ] Confirmar se o **Salvar** do perfil (nome público / usuário) foi efetivado

### Não bloqueiam, mas rendem

- [ ] **Reconhecimento facial** (só a Letícia)
- [ ] Trocar o nome do produto para remover o `&`
- [ ] **Aparência da página de pagamento** — hoje é o checkout padrão. A compradora sai
      de uma página creme-e-girassol e cai numa tela genérica. Dá para aplicar capa e cores.
- [ ] Cupons de lançamento
- [ ] Programa de afiliados
- [ ] ListBoss (segmentação de leads)

### Travado por dependência

- [ ] **Cadastro financeiro / dados bancários** — só abre após a primeira venda

---

## 7. Dados que a página de vendas consome

Valores já definidos e prontos para uso na copy:

| Elemento | Valor final |
|---|---|
| Preço à vista | **R$ 297,00** |
| Parcelamento | **12x de R$ 24,75 sem juros** (total R$ 297,00) |
| Garantia | **7 dias** |
| Pagamento | Pix e cartão de crédito |
| Link dos botões | `https://pay.hotmart.com/B107134315C?off=87ea4lrn` |

### Lacunas da copy que a área de membros vai fechar

- `[INSERIR TEMPO DE ACESSO]` — aparece em 3 pontos do material
- `[CERTIFICADO — SE HOUVER]`
- Grade do curso (nº de módulos e aulas) — **a copy nunca informa o tamanho do curso**,
  que é a pergunta que toda compradora faz antes de decidir

### Lacunas da copy que continuam abertas (independem da Hotmart)

- **VISAR** — o significado das 5 letras está como `[ENTRA AQUI O SIGNIFICADO]`. É o
  método que dá nome à autoridade da Lelê e é a seção-âncora da página.
- **Depoimentos** — nenhum. Os 5 do site antigo são de contratantes de contação, não de
  alunas: servem como autoridade, não como prova de resultado do curso.
- **Bônus definitivos**
- **Periodicidade e quantidade dos encontros ao vivo**

---

## 8. Decisões de design já tomadas (contexto para a página)

Registradas aqui apenas para evitar retrabalho — o desenvolvimento da página segue em
outra frente.

- **Direção visual:** blocos de cor chapada aproveitando os fundos do ensaio fotográfico
  (creme, amarelo girassol, azul, branco) + camada artesanal (girassol, fitas, textura de
  papel). Alinhada ao design system já existente em `CLAUDE.md` e `brand-tokens.css`.
- **Hero:** sem vídeo, foto estática + título + CTA.
- **Prova social:** existe acervo de fotos da Lelê contando história com público —
  **localização ainda não informada**.
- **Acervo disponível:** `Identidade Visual/Foto Lele` (70 fotos de estúdio, fundos
  branco/azul/amarelo), `Identidade Visual/Cante e Encante` (5 fotos),
  `Identidade Visual/Logo` (3 versões), `Identidade Visual/Ícones` (girassol).
  *Observação: a pasta chama-se "Cante e Encante", mas o curso é "Conte&Encante".*
- **Estrutura da copy:** o PDF de origem contém **duas versões coladas** — páginas 1–20
  (v1) e 21–27 (revisão). Nas seções duplicadas (bio, o que você recebe, investimento),
  **vale a versão das páginas 21–27**. O VIPE aparece nas duas: o texto longo (p. 7–10) é
  melhor, mas na v2 ele passou a ser complemento do VISAR — precisa virar uma seção só.
