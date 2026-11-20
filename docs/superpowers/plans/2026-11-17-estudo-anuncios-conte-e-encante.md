# Estudo dos anúncios do Conte & Encante — plano de execução

> **Para agentes:** SUB-SKILL OBRIGATÓRIO: use superpowers:executing-plans (ou
> superpowers:subagent-driven-development) para executar este plano tarefa por tarefa.
> Os passos usam caixas (`- [ ]`) para acompanhamento.

**Objetivo:** entregar o estudo dos 6 anúncios. Cada um sai com roteiro, trechos marcados
nas aulas, quadros verticais e cortes brutos em 9:16, para o Thiago aprovar antes da
edição.

**Arquitetura:** pequenas ferramentas em Python numa pasta de trabalho fora do site. Elas
fazem quatro coisas:
- transcrevem as 33 aulas com o tempo de cada palavra;
- tiram quadros verticais e medem a nitidez;
- geram cortes brutos;
- montam a página do estudo a partir de um único `roteiros.json`.

A escolha dos trechos é trabalho de julgamento, feito com leitura das transcrições e
conferência visual.

**Ferramentas:** Python 3.12, faster-whisper 1.2.1 (modelo `large-v3-turbo` já em cache,
CUDA na RTX 3060 6 GB), OpenCV 5, ffmpeg 8.1, `unittest` da biblioteca padrão (o pytest
não está instalado e não será instalado).

**Desenho:** `docs/superpowers/specs/2026-11-17-anuncios-video-conte-e-encante-design.md`.
Quem executa lê os dois documentos.

## Restrições gerais

- Pasta de trabalho: `C:\Users\moura\Downloads\Anuncios Conte e Encante\`.
  - `ferramentas\`: scripts e testes.
  - `estudo\transcricoes\`, `estudo\quadros\`, `estudo\cortes\`: saídas.
  - `estudo\roteiros.json`: a fonte única do estudo.
- Versionamento: `git init` na raiz da pasta de trabalho. O `.gitignore` deixa de fora
  `estudo/cortes/`, `estudo/quadros/`, `estudo/transcricoes/*.json` e `*.mp4`. Os
  commits são em pt-BR e terminam com a linha de coautoria da sessão.
- Origem das aulas: `E:\Contalele - Arquivo\Curso Conte e Encante\Modulo NN\*.mp4`, em
  1920×1080. A origem é **só leitura**: nada é gravado lá.
- Todo texto (código, comentários, mensagens, página) em pt-BR. Cada script novo tem o
  cabeçalho `Thiago Mourão — https://github.com/MouraoBSB — 17/11/2026`.
- Tempos sempre no formato `HH:MM:SS.s` dentro dos JSON.
- Os cortes brutos saem em 720×1280, H.264, com o áudio original, sem legenda e sem
  trilha.
- Os textos de abertura não podem conter "black friday", "R$", "desconto", "incrível",
  "imperdível", "exclusivo" nem "transform" (sem diferenciar maiúsculas de minúsculas).
- Proibido abrir a sigla VISAR. Proibidas música de terceiros e capa de livro de outro
  autor como assunto do quadro (ver o desenho, seção 3).

## Pontos de atenção na revisão

1. **Nomes de arquivo irregulares:** espaço duplo em "MÓDULO 02  - AULA 05", acento em
   "MÓDULO" e sufixo em "AULA 00 - BOAS VINDAS". Todos precisam virar um id único do
   tipo `m02a05`. Teste na Tarefa 1.
2. **Whisper adianta até 1 s:** o corte bruto leva margem de 0,5 s antes e depois,
   limitada ao começo e ao fim da aula. Teste na Tarefa 3.
3. **Lelê na borda do quadro:** o centro do corte é ajustável e o recorte nunca sai da
   imagem. Por exemplo, `x=0.95` encosta na borda direita. Teste na Tarefa 3.
4. **Texto proibido nas aberturas:** o `roteiros.json` é rejeitado com uma mensagem que
   diz qual vídeo e qual palavra. Teste na Tarefa 5.
5. **Transcrição de cerca de 2 h interrompida:** ao rodar de novo, pula as aulas já
   feitas. A gravação é atômica (arquivo temporário, depois renomeia), então JSON pela
   metade nunca conta como pronto. Teste na Tarefa 1.

---

### Tarefa 1: Pasta de trabalho e transcrição das 33 aulas

**Arquivos:**
- Criar: `ferramentas\aulas.py`, `ferramentas\transcrever.py`,
  `ferramentas\tests\test_aulas.py`, `.gitignore`

**Interfaces:**
- Produz:
  - `aulas.id_da_aula(nome_arquivo: str) -> str`, que devolve `"m02a05"`;
  - `aulas.listar_aulas(raiz: Path) -> dict[str, Path]`, ordenado por id;
  - `aulas.formatar_tempo(segundos: float) -> str`, que devolve `"00:12:03.4"`;
  - `aulas.tempo_para_segundos(texto: str) -> float`, que aceita `MM:SS`, `HH:MM:SS`
    e decimais;
  - `transcrever.precisa_transcrever(caminho_json: Path) -> bool`.
- Saída, por aula:
  - `estudo\transcricoes\<id>.json`, no formato
    `{"aula": id, "arquivo": str, "duracao": float, "segmentos": [{"inicio", "fim", "texto", "palavras": [{"inicio", "fim", "palavra"}]}]}`;
  - `estudo\transcricoes\<id>.txt`, com uma linha por segmento: `[HH:MM:SS] texto`.

- [ ] **Passo 1:** criar a pasta de trabalho, rodar `git init` e gravar o `.gitignore`
  das restrições gerais.
- [ ] **Passo 2:** escrever `test_aulas.py`, com os testes:
  - `test_id_espaco_duplo`: `"MÓDULO 02  - AULA 05.mp4"` vira `"m02a05"`;
  - `test_id_sufixo`: `"MÓDULO 00 - AULA 00 - BOAS VINDAS.mp4"` vira `"m00a00"`;
  - `test_id_invalido`: `"foto.jpg"` levanta `ValueError`;
  - `test_listar_33`: `listar_aulas(raiz_real)` tem 33 itens, ids sem repetição;
  - `test_tempo_ida_volta`: `tempo_para_segundos(formatar_tempo(723.4)) == 723.4`;
  - `test_tempo_mmss`: `"12:03"` vira `723.0`;
  - `test_json_parcial`: um JSON truncado faz `precisa_transcrever` devolver `True`.
- [ ] **Passo 3:** rodar `python -m unittest discover -s ferramentas/tests`. Esperado:
  FAIL (os módulos ainda não existem).
- [ ] **Passo 4:** implementar `aulas.py`, com o regex `M[ÓO]DULO\s*(\d+)\s*-\s*AULA\s*(\d+)`
  e Unicode normalizado em NFC antes.
- [ ] **Passo 5:** implementar `transcrever.py`:
  - modelo `large-v3-turbo`, `device="cuda"`, `compute_type="int8_float16"`;
  - `language="pt"`, `word_timestamps=True`, `vad_filter=True`;
  - `initial_prompt="Lelê, Kleyton Santos, Conte & Encante, contação de histórias, VISAR, VIPE"`;
  - argumento opcional `--so <id>` e `--ate <segundos>`, para o teste de fumaça;
  - log por aula no formato `[transcrever] m02a05 ok 21,4 min em 3,1 min`;
  - se uma aula falhar, registra o erro e segue para a próxima.
- [ ] **Passo 6:** rodar os testes. Esperado: PASS.
- [ ] **Passo 7:** teste de fumaça com `python ferramentas/transcrever.py --so m00a00 --ate 60`.
  Esperado: JSON e TXT de `m00a00` com fala coerente. Depois apagar os dois arquivos.
- [ ] **Passo 8:** rodar as 33 aulas em segundo plano. Esperado: 33 JSON e 33 TXT e
  nenhum erro no log. Aulas que falharem rodam de novo sozinhas, porque o script pula as
  prontas.
- [ ] **Passo 9:** commit com a mensagem "Cria a transcrição em lote das aulas do Conte & Encante".

### Tarefa 2: Garimpo de candidatos

**Arquivos:**
- Criar: `estudo\candidatos.json`

**Interfaces:**
- Consome: os `estudo\transcricoes\*.txt` da Tarefa 1.
- Produz `candidatos.json`:

  ```json
  {"v1".."v6","reserva": [
     {"aula": "m01a03", "inicio": "HH:MM:SS.s", "fim": "HH:MM:SS.s",
      "fala": "...", "papel": "resposta|encantamento|abertura",
      "por_que": "..."}
  ]}
  ```

  Além disso, a chave `"encantamento_geral"` guarda cenas que servem a qualquer vídeo.

- [ ] **Passo 1:** disparar 3 subagentes em paralelo, um para os módulos 00–01, outro
  para 02–03 e outro para 04–06. Cada um lê só os TXT dos seus módulos e recebe:
  - a tabela de dificuldades da seção 5 do desenho;
  - as restrições gerais;
  - os critérios abaixo.

  Os critérios:
  - **resposta:** fala da Lelê que se entende sozinha, de 6 a 15 s, começando no início
    de uma frase, e que responde à dificuldade;
  - **encantamento:** contação com objeto, gesto, onomatopeia, risada ou cantiga de
    domínio público, de 2 a 4 s;
  - **abertura:** momento de expressão forte, como exclamação, "era uma vez" ou som.

  Cada subagente devolve de 3 a 5 candidatos de resposta por vídeo (quando houver) e de
  5 a 10 de encantamento.
- [ ] **Passo 2:** juntar as 3 respostas em `candidatos.json`. Conferir por amostragem
  (2 por vídeo) se a `fala` bate com o TXT no tempo indicado. Se um vídeo ficar com menos
  de 2 respostas boas, ele cai e a reserva entra no lugar, com o motivo anotado.

### Tarefa 3: Ferramenta de quadros e cortes verticais

**Arquivos:**
- Criar: `ferramentas\midia.py`, `ferramentas\tests\test_midia.py`

**Interfaces:**
- Consome: `aulas.listar_aulas` e `aulas.tempo_para_segundos`.
- Produz:
  - `janela_corte(inicio: float, fim: float, duracao: float, margem: float = 0.5) -> tuple[float, float]`;
  - `filtro_vertical(layout: str, x: float = 0.5, x2: float | None = None, saida: tuple[int, int] = (720, 1280)) -> str`,
    em que `layout` é `"centro"` ou `"dividido"`.
    - `"centro"`: recorta 608×1080 com o centro em `x` (fração da largura) e limita
      `0 ≤ offset ≤ 1312`.
    - `"dividido"`: cada faixa recorta uma área de 810×720 da origem (proporção 9:8),
      uma centrada em `x` e outra em `x2`, com a mesma trava de borda e o eixo vertical
      no centro (offset 180). As duas faixas são escaladas para meia saída (720×640) e
      empilhadas com `vstack`. Se o rosto ficar cortado no teste de fumaça, acrescentar
      `--y`.
  - `nitidez(caminho_imagem: Path) -> float`: variância do Laplaciano em tons de cinza.
  - CLI `python ferramentas/midia.py quadros <aula> <inicio> <fim> [--layout] [--x] [--x2]`:
    gera `estudo\quadros\<aula>-<inicio>.jpg`, uma folha com 4 quadros verticais
    igualmente espaçados, e imprime a nitidez de cada um.
  - CLI `python ferramentas/midia.py corte <aula> <inicio> <fim> --nome <nome> [--layout] [--x] [--x2]`:
    gera `estudo\cortes\<nome>.mp4`.

- [ ] **Passo 1:** escrever `test_midia.py`, com os testes:
  - `test_margem`: `janela_corte(10, 20, 100) == (9.5, 20.5)`;
  - `test_margem_limite`: `janela_corte(0.2, 99.8, 100) == (0.0, 100.0)`;
  - `test_centro_padrao`: o filtro contém `crop=608:1080:656:0`;
  - `test_borda_direita`: com `x=0.95`, o offset é `1312`;
  - `test_borda_esquerda`: com `x=0.0`, o offset é `0`;
  - `test_dividido_exige_x2`: `x2=None` levanta `ValueError`;
  - `test_dividido_vstack`: o filtro contém `vstack`.
- [ ] **Passo 2:** rodar os testes. Esperado: FAIL.
- [ ] **Passo 3:** implementar `midia.py`. A busca do ffmpeg usa `-ss` antes de `-i`; a
  saída usa `-c:v libx264 -crf 20 -preset veryfast -c:a aac -b:a 160k`. Se o ffmpeg der
  erro, o script imprime o comando e o stderr.
- [ ] **Passo 4:** rodar os testes. Esperado: PASS.
- [ ] **Passo 5:** teste de fumaça com um candidato real e um corte `dividido` do módulo
  06. Abrir a folha de quadros e conferir que a Lelê, ou a Lelê e o Kleyton, aparecem
  inteiros.
- [ ] **Passo 6:** commit com a mensagem "Cria a ferramenta de quadros e cortes verticais".

### Tarefa 4: Escolha dos trechos e roteiros

**Arquivos:**
- Criar: `estudo\roteiros.json`, os quadros e os cortes escolhidos.

**Interfaces:**
- Consome: `candidatos.json` e a CLI da Tarefa 3.
- Produz `roteiros.json`:

  ```json
  {"videos": [{"id": "v1", "dificuldade": "...", "aberturas": ["A", "B"],
     "abertura_cena": T, "resposta": [T], "encantamento": [T, T, T],
     "reserva": [T], "duracao_estimada": 28}]}
  ```

  Aqui `T = {"aula", "inicio", "fim", "fala", "layout", "x", "x2", "quadro", "corte"}`,
  com `quadro` e `corte` como caminhos relativos a `estudo\`.

- [ ] **Passo 1:** para cada vídeo, rodar `quadros` nos candidatos e escolher. Vale o
  critério da memória: nitidez sem desfoque de movimento e rosto e olhar da Lelê
  presentes. Ajustar `x` até o enquadramento ficar bom.
- [ ] **Passo 2:** ajustar as aberturas A e B à fala real escolhida. Seguir a voz da
  marca: segunda pessoa, sem tom de coach.
- [ ] **Passo 3:** gerar os cortes brutos dos trechos escolhidos (abertura, resposta,
  encantamento e reserva). Nome no padrão `v1-resposta-1`, `v1-encanto-2` etc.
- [ ] **Passo 4:** conferir a soma dos tempos: abertura ~3 s, resposta ≤ 15 s,
  encantamento ~6 s e fechamento 5 s, total ≤ 30 s. Assistir a cada corte por inteiro,
  conferindo palavra cortada, música de terceiros e capa em destaque.
- [ ] **Passo 5:** commit do `roteiros.json` com a mensagem "Registra os roteiros do estudo dos anúncios".

### Tarefa 5: Página do estudo e entrega

**Arquivos:**
- Criar: `ferramentas\gerar_estudo.py`, `ferramentas\tests\test_gerar_estudo.py`,
  `estudo\pagina\index.html`

**Interfaces:**
- Consome: `roteiros.json` da Tarefa 4.
- Produz:
  - `carregar_roteiros(caminho: Path) -> dict`, que levanta `ValueError` com mensagem
    clara quando:
    - falta um campo;
    - `fim <= inicio`;
    - o id de aula é desconhecido;
    - há texto proibido nas aberturas;
    - um arquivo de quadro ou de corte não existe.
  - `gerar_pagina(roteiros: dict, destino: Path) -> Path`, que copia os quadros para
    `estudo\pagina\quadros\`.

- [ ] **Passo 1:** escrever `test_gerar_estudo.py`, com os testes:
  - `test_rejeita_black_friday`: abertura `"Só nesta Black Friday"` levanta erro que
    cita `"v1"` e `"black friday"`;
  - `test_rejeita_preco`: `"Por R$ 297"` levanta erro;
  - `test_rejeita_tempo_invertido`: erro;
  - `test_aceita_valido`: um roteiro mínimo válido passa;
  - `test_pagina_tem_6_videos`: o HTML gerado tem 6 seções `data-video`.
- [ ] **Passo 2:** rodar os testes. Esperado: FAIL.
- [ ] **Passo 3:** antes de escrever o HTML, chamar o Artifact `quickstart` com
  `intent: "other"` e seguir as orientações de página. O visual segue a marca: modo
  amarelo, Nunito e Fredoka One, cards brancos com raio de 18 px, girassol como
  ornamento.
- [ ] **Passo 4:** implementar `gerar_estudo.py`. A página é uma seção por vídeo, com:
  - a dificuldade;
  - as aberturas A e B;
  - a linha do tempo dos blocos e a duração;
  - para cada trecho: quadro vertical, aula, tempos, fala e nome do corte bruto;
  - a reserva.

  No topo da página, a pasta dos cortes brutos e como assistir.
- [ ] **Passo 5:** rodar os testes. Esperado: PASS. Depois gerar a página real.
- [ ] **Passo 6:** publicar como Artifact privado, com os quadros em `files`. Conferir no
  link publicado se as imagens aparecem e se a página funciona na largura de celular.
- [ ] **Passo 7:** commit com a mensagem "Gera a página do estudo dos anúncios". Entregar
  ao Thiago o link, a pasta dos cortes e qualquer vídeo trocado pela reserva, com o
  motivo.

---

## Depois do estudo

A edição (desenho, seção 10) ganha um plano próprio depois que o Thiago aprovar o estudo,
porque depende dos trechos escolhidos.
