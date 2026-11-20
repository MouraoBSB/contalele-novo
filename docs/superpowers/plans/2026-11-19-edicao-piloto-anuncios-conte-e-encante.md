# Edição dos anúncios do Conte & Encante — plano do piloto (vídeo 1, aberturas A e B)

> **Para agentes:** SUB-SKILL OBRIGATÓRIO: use superpowers:executing-plans (ou
> superpowers:subagent-driven-development) para executar este plano tarefa por tarefa.
> Os passos usam caixas (`- [ ]`) para acompanhamento.

**Objetivo:** entregar `v1-A.mp4` e `v1-B.mp4`, prontos para impulsionar. Ao mesmo tempo,
deixar a linha de edição pronta para repetir nos vídeos 2 a 6 só com um arquivo de
decupagem novo.

**Arquitetura:**
- **Decupagem:** um `edicao/decupagem.json` diz, para cada vídeo, quais trechos entram,
  com entrada, saída, tipo de som e texto da legenda. O enquadramento (`layout`, `x`,
  `x2`) vem do `estudo/roteiros.json`, que continua sendo a fonte única dos trechos.
- **Base:** o Python corta os trechos em 1080×1920, nivela a voz e monta com o ffmpeg uma
  base contínua, com borrão de transição nas trocas de bloco.
- **Grafismos:** um projeto HyperFrames gerado por código põe por cima abertura,
  legenda, selo e fechamento, mais a trilha com automação de volume.
- **Final:** o ffmpeg nivela o volume final em −14 LUFS e codifica. Uma conferência
  automática barra o arquivo fora da especificação.

**Ferramentas:**
- Python 3.12, `unittest` e ffmpeg 8.1;
- faster-whisper 1.2.1, com o modelo `large-v3` já em cache, CUDA e `int8_float16`;
- OpenCV 5;
- Node 24 e HyperFrames **0.9.20**, que usa o Chrome instalado.

**Desenho:** `docs/superpowers/specs/2026-11-17-anuncios-video-conte-e-encante-design.md`,
seções 6 a 8 e 10. A receita visual aprovada vem do depoimento da Manuela e do vídeo
"momentos". Os valores dela estão copiados aqui, porque as pastas temporárias de origem
podem ser apagadas.

## Restrições gerais

- **Pasta de trabalho:** `C:\Users\moura\Downloads\Anuncios Conte e Encante\` (git
  próprio). O trabalho é feito no ramo `edicao-anuncios`, nunca direto no `main`.
- **Pastas novas:**

  | Pasta | Conteúdo | Versionada? |
  |---|---|---|
  | `edicao/recursos/` | trilha, efeitos, fontes, girassol, logo | sim |
  | `edicao/decupagem.json` | decupagem | sim |
  | `edicao/ferramenta/` | HyperFrames | não |
  | `edicao/trabalho/` | intermediários | não |
  | `edicao/saida/` | entregas | não |

- **Aulas:** `E:\…` é só leitura.
- **Texto:** código, comentários, mensagens e commits em pt-BR.
- **Cabeçalho de arquivo novo:** `# Thiago Mourão — https://github.com/MouraoBSB — 19/11/2026`.
- **Commits:** terminam com `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- **Testes:** `python -m unittest discover -s ferramentas/tests`, rodado da raiz da pasta
  de trabalho. Os 48 testes atuais continuam passando.
- **Saída:**
  - MP4 de 1080×1920, 30 fps, H.264 `yuv420p`, AAC 48 kHz estéreo e `+faststart`;
  - de 20 a 30 s;
  - −14 LUFS ±1, pico verdadeiro de até −1,0 dBTP.
- **Área segura:**
  - todo texto entre y 300 e y 1250 px;
  - legenda presa pela base em `bottom: 670px`;
  - selo do curso em `top: 320px`.
- **Primeiro quadro:** já mostra o texto da abertura e movimento, sem fade de entrada.
- **Marca:**
  - cores creme `#fdf5d4`, amarelo `#ffdc3a`, café `#2b1300` e laranja `#c4390e`;
  - Nunito 900/800, com a palavra-chave em Fredoka One;
  - o girassol é o único ornamento;
  - botão sempre em pílula;
  - sem rosa e sem emoji.
- **Textos na tela:** passam por `gerar_estudo.termos_proibidos_em`. Nada de Black
  Friday, preço, desconto ou tom de coach. A sigla VISAR não é aberta.
- **Trilha:**
  - nunca toca sob cena cantada (`som: "cantada"` zera a trilha);
  - só entra a trilha pop já paga;
  - nada é gerado no Higgsfield neste plano.
- **Fechamento:**
  - logo da Conta Lelê;
  - "Conte & Encante" e "curso online de contação de histórias";
  - pílula "Saiba mais";
  - `contalele.com.br/conte-e-encante`;
  - sem preço.
- **HyperFrames:** sempre com `HYPERFRAMES_NO_TELEMETRY=1`, `DO_NOT_TRACK=1` e
  `HYPERFRAMES_SKIP_SKILLS=1`. Nunca por `npx`; sempre pelo binário local.

## Pontos de atenção na revisão

1. **Transcrição com contagem diferente do texto da legenda:** é o caso do Whisper que
   inventa "Tchau" ou junta duas palavras. Precisa dar erro claro mostrando as duas
   sequências, nunca alinhar errado em silêncio. Teste na Tarefa 3.
2. **Entrada ou saída fora da janela do trecho do estudo, ou além do fim da aula:** o
   erro diz o vídeo e a peça. Teste na Tarefa 2.
3. **Caminho com acento** ("Anúncios", "MÓDULO"): o projeto HyperFrames gerado em pasta
   com acento passa no `check`. Teste na Tarefa 5.
4. **Texto com `&`, `<` ou aspas** na abertura ou na legenda: sai escapado no HTML.
   Teste na Tarefa 5.
5. **Trecho quase mudo** (encantamento com pouca fala): o ganho não explode. Teste na
   Tarefa 4.

## Valores recuperados das edições aprovadas

- **Trilha `trilha-pop.mp3`:**
  - 96,03 s e −13,6 LUFS;
  - 117,99 BPM, com a primeira batida em 0,256 s (medido nesta sessão nos ataques
    fortes, com desvio mediano de 14 ms);
  - silêncio a partir de 94,6 s.
- **Legenda da Manuela:**
  - caixa `rgba(43,19,0,0.92)`, raio 24 px, padding `12px 30px 18px`, `max-width: 920px`;
  - Nunito 900, 64 px, `line-height 1.12`, `letter-spacing -0.01em`;
  - texto creme, com `box-shadow 0 10px 24px -12px rgba(43,19,0,0.55)`;
  - palavra: `margin 0 0.1em`, `transform-origin 50% 70%`;
  - ênfase: Fredoka One, 1.12em, amarelo.
- **Animação da legenda:**
  - o grupo abre com `{opacity:0, scale:0.9}→{1, 1}` em 0,2 s `back.out(2)`;
  - a palavra acende em amarelo e `scale 1.06` em 0,08 s `power2.out` e volta a creme em
    0,07 s `power2.inOut`;
  - o grupo sai com opacidade em 0,12 s `power2.in`.
- **Tempos da legenda:**
  - o grupo abre em `início da 1ª palavra − 0,06`;
  - se a pausa até o próximo grupo passa de 0,30 s, o grupo fecha em
    `fim da última + 0,35`; senão, fecha quando o próximo abre;
  - a palavra fica acesa até a próxima começar, com teto de
    `min(0.9, max(0.24, 0.085 × letras))`.
- **Zoom:**
  - alternado entre `ABERTO = 1` e `PERTO = 1.1`, com `transformOrigin 50% 52%`;
  - "assenta": `scale 1.14 → 1` em 0,5 s `expo.out`.
- **Chicote:**
  - saída: `x 0 → −D` e `scale → 1.16` em 0,12 s `power2.in`;
  - entrada: `x +D → 0` e `scale 1.16 → 1` em 0,2 s `power3.out`;
  - na vertical, `D = 80`, porque com `scale 1.16` sobram só 86 px de cada lado.
- **Borrão do ffmpeg:**
  - `avgblur=sizeX={tam}:sizeY=1:enable='eq(n\,{quadro})'` em cada corte;
  - perfil `{-3: 10, -2: 20, -1: 34, 0: 34, 1: 22, 2: 12, 3: 5}`.
- **Áudio:**
  - cada trecho é medido com `loudnorm` e recebe ganho `−16 − input_i`;
  - `afade` de 12 ms nas emendas;
  - `alimiter=limit=0.84:level=0:latency=1`;
  - a trilha do "momentos" ficava em 0,14 sob a fala e em 0,95 no fechamento, com
    limitador em −1,5 dB.
- **Transcrição trecho a trecho:**
  - divisão nas pausas reais: janela de 20 ms, limiar no percentil 5 + 10 dB,
    `PAUSA_MIN = 0.10`, `TRECHO_MIN = 0.70`, `MARGEM = 0.06`;
  - cada fala é transcrita sozinha, com `temperature=0`,
    `condition_on_previous_text=False`, `max_new_tokens=60`, sem `initial_prompt`,
    `language="pt"`, `word_timestamps=True` e `beam_size=5`.
- **Render:** `hyperframes render --quality delivery --crf 16 -o <arquivo>`, antes
  passando por `hyperframes check --json` sem erros.

---

### Tarefa 1: Ramo, recursos e HyperFrames

**Arquivos:**
- Criar:
  - `ferramentas/recursos.py`;
  - `ferramentas/tests/test_recursos.py`;
  - `edicao/recursos/…` (cópias).
- Alterar: `.gitignore`, acrescentando `edicao/ferramenta/`, `edicao/trabalho/` e
  `edicao/saida/`.

**Interfaces:**
- Produz:
  - pastas: `RAIZ`, `PASTA_RECURSOS`, `PASTA_TRABALHO`, `PASTA_SAIDA`;
  - `HYPERFRAMES` (`edicao/ferramenta/node_modules/.bin/hyperframes.cmd`);
  - trilha: `TRILHA_BPM = 117.99`, `TRILHA_PRIMEIRA_BATIDA = 0.256`,
    `TRILHA_DURACAO = 96.03`, `TRILHA_FIM_UTIL = 94.6`;
  - `AMBIENTE_HYPERFRAMES` (dict com as 3 variáveis);
  - `ARQUIVOS` (nome → caminho relativo);
  - `caminho(nome: str) -> Path`;
  - `faltando(pasta: Path = PASTA_RECURSOS) -> list[str]`.
- Nomes em `ARQUIVOS`:
  - `trilha`: `audio/trilha-pop.mp3`;
  - `whoosh-1`, `whoosh-2` e `whoosh-3`: `audio/whoosh-N.wav`;
  - `nunito`: `fontes/Nunito-wght.ttf`;
  - `fredoka`: `fontes/FredokaOne-Regular.ttf`;
  - `licenca-nunito`: `fontes/OFL-nunito.txt`;
  - `girassol`: `img/girassol.svg`;
  - `logo`: `img/logo.png`.

- [ ] **Passo 1:** `git switch -c edicao-anuncios`.
- [ ] **Passo 2: teste que falha.** Em `test_recursos.py`:
  - `test_faltando_lista_ausentes`: numa pasta temporária vazia, `faltando(pasta)`
    devolve os 10 nomes de `ARQUIVOS`;
  - `test_faltando_vazio_quando_completo`: com arquivos de 1 byte em todos os caminhos,
    devolve `[]`;
  - `test_caminho_desconhecido`: `caminho("xyz")` levanta `KeyError` cuja mensagem cita
    os nomes válidos.
- [ ] **Passo 3:** rodar e ver falhar (`ModuleNotFoundError: recursos`).
- [ ] **Passo 4:** implementar `recursos.py`.
- [ ] **Passo 5:** rodar a suíte inteira. Esperado: 51 testes OK.
- [ ] **Passo 6: copiar os recursos.**
  - De `C:\Users\moura\AppData\Local\Temp\claude\d--Claude-Code---Projetos-Contalele---Novo\dc634cb1-5322-469b-b354-07c93ea0563c\scratchpad\hf\horizontal\assets\`:
    - `audio/trilha-pop.mp3` e `audio/whoosh-{1,2,3}.wav`;
    - `fonts/Nunito-wght.ttf`, `fonts/FredokaOne-Regular.ttf` e `fonts/OFL-nunito.txt`
      (para `fontes/`);
    - `img/girassol.svg`.
  - De `d:\Claude Code - Projetos\Contalele - Novo\assets\img\logo.png`: o logo escuro,
    1006×739, para fundo claro.
  - Conferir com
    `python -c "import sys; sys.path.insert(0,'ferramentas'); import recursos; print(recursos.faltando())"`.
    Esperado: `[]`.
- [ ] **Passo 7: instalar o HyperFrames** em `edicao/ferramenta/`, com
  `HYPERFRAMES_NO_TELEMETRY=1 DO_NOT_TRACK=1`, `npm init -y` e
  `npm install --no-fund --no-audit hyperframes@0.9.20`.
  - Rodar `"$HF" --version`. Esperado: `0.9.20`.
- [ ] **Passo 8: commit** de `recursos.py`, do teste, de `edicao/recursos/` e do
  `.gitignore`, com a mensagem "Reúne recursos da edição e instala o HyperFrames".

### Tarefa 2: Decupagem e linha do tempo

**Arquivos:**
- Criar:
  - `ferramentas/decupagem.py`;
  - `ferramentas/tests/test_decupagem.py`;
  - `edicao/decupagem.json`, com o v1 inicial (abaixo).

**Interfaces:**
- Consome: `aulas.tempo_para_segundos`, `aulas.listar_aulas`,
  `gerar_estudo.termos_proibidos_em` e `recursos.TRILHA_BPM`.
- Produz:
  - `Peca`, dataclass congelada: `indice: int`, `bloco: str`, `som: str`, `aula: str`,
    `inicio: float`, `fim: float`, `layout: str`, `x: float`, `x2: float | None`,
    `posicao: float` e `legenda: tuple[str, ...]`;
  - propriedades da `Peca`: `duracao` e `nome` (`f"{indice:02d}-{bloco}"`);
  - `PlanoVideo`, dataclass congelada: `id`, `aberturas: dict[str, str]`,
    `pecas: tuple[Peca, ...]`, `inicio_fechamento: float` e `duracao_total: float`;
  - propriedade do `PlanoVideo`: `trocas_de_bloco -> list[float]`, as posições onde o
    bloco muda;
  - `carregar_plano(decupagem: Path, roteiros: Path, ident: str, exigir_legenda: bool = True, duracao_aulas: dict[str, float] | None = None) -> PlanoVideo`;
  - `sem_marcas(texto: str) -> str`, que tira os `*`;
  - `duracao_batidas(n: int) -> float`;
  - constantes: `FPS = 30`, `SOBREPOSICAO_FECHAMENTO = 0.35`,
    `DURACAO_MIN, DURACAO_MAX = 20.0, 30.0`, `ABERTURA_MIN, ABERTURA_MAX = 2.0, 3.5`,
    `TOLERANCIA_JANELA = 1.0`, `BLOCOS` e `SONS`.

**Formato da peça na decupagem:**

```json
{"bloco": "resposta", "de": "resposta.0", "inicio": "00:00:09.5", "fim": "00:00:14.2",
 "som": "fala", "legenda": ["E agora, o que eu faço?", "Aí muita gente pensa:", "*ler!*"]}
```

- `de` aponta para o trecho do roteiro (`abertura_cena`, `resposta.N`, `encantamento.N`
  ou `reserva.N`). Dele saem a `aula`, o `layout`, o `x` e o `x2`.
- Peça de encantamento pode trazer `"batidas": 4` no lugar de `fim`. Nesse caso,
  `fim = inicio + duracao_batidas(4)`.
- Toda duração é arredondada para quadros inteiros (1/30 s).
- As peças são encadeadas sem sobreposição a partir de 0.
- O fechamento começa em `fim da última − 0,35` e dura o valor de `"fechamento"` do vídeo.

**Regras de validação** (juntar todos os erros numa só `ValueError` que cita o vídeo e a
peça):
- exatamente uma peça `abertura`, na primeira posição, seguida de 1 ou mais `resposta` e
  1 ou mais `encantamento`, nessa ordem;
- a abertura dura de 2,0 a 3,5 s, e o total, de 20 a 30 s;
- `inicio` e `fim` ficam dentro da janela do trecho do roteiro ±1,0 s, entre 0 e a
  duração da aula, com `fim > inicio`;
- `som` está em `SONS` e `bloco` está em `BLOCOS`;
- toda peça `fala` tem `legenda` não vazia quando `exigir_legenda=True`;
- as aberturas `A` e `B`, sem os `*`, são iguais a `aberturas[0]` e `aberturas[1]` do
  roteiro;
- nenhum texto (aberturas e legendas) tem termo proibido.

- [ ] **Passo 1: testes que falham.** Em `test_decupagem.py`, com roteiro e decupagem
  temporários e `duracao_aulas={"m00a01": 1200.0, …}` para não depender do disco E:.
  - `test_linha_do_tempo_encadeia`: com abertura de 2,8 s, falas de 4,7 s e 6,6 s, 3
    encantamentos de 4 batidas e fechamento de 4,5 s:
    - posições `[0, 2.8, 7.5, 14.1, 16.133, 18.167]`, arredondadas em 3 casas;
    - `inicio_fechamento ≈ 19.85`;
    - `duracao_total ≈ 24.35`.
  - `test_batidas_viram_quadros_inteiros`: `duracao_batidas(4)` está a menos de 1 ms de
    2,0341, e a peça dura `61/30`.
  - `test_trocas_de_bloco`: `[2.8, 14.1]` no plano acima.
  - `test_rejeita_fora_da_janela`: com `inicio` 2 s antes do trecho, a mensagem cita
    `v1` e `peça 2`.
  - `test_rejeita_alem_da_aula`: com `fim` além de `duracao_aulas`, a mensagem cita a
    aula.
  - `test_rejeita_ordem_de_blocos`: um encantamento antes da resposta dá `ValueError`.
  - `test_rejeita_total_curto` (18 s) e `test_rejeita_total_longo` (31 s).
  - `test_rejeita_abertura_diferente_do_roteiro`.
  - `test_rejeita_termo_proibido_na_legenda`: "Curso incrível".
  - `test_fala_sem_legenda`: dá erro com `exigir_legenda=True` e passa com `False`.
  - `test_sem_marcas`: `"Você *não sabe* começar"` vira `"Você não sabe começar"`.
- [ ] **Passo 2:** rodar e ver falhar.
- [ ] **Passo 3:** implementar `decupagem.py`. Sem `duracao_aulas`, consultar o
  `ffprobe` de `aulas.listar_aulas()`.
- [ ] **Passo 4:** rodar a suíte. Esperado: tudo OK.
- [ ] **Passo 5: escrever `edicao/decupagem.json` com o v1 inicial.** Os tempos são do
  estudo; a Tarefa 7 os refina com a transcrição trecho a trecho.
  - `"fechamento": 4.5`.
  - Aberturas:
    - A: `"Você pega o livro e *não sabe por onde começar?*"`;
    - B: `"Contar história *não é ler* em voz alta."`.
  - Peças:

    | # | Bloco | De | Início | Fim ou batidas | Som |
    |---|---|---|---|---|---|
    | 1 | abertura | `abertura_cena` | 04:48.2 | fim 04:51.0 | fundo |
    | 2 | resposta | `resposta.0` | 00:09.5 | fim 00:14.2 | fala |
    | 3 | resposta | `resposta.0` | 00:16.2 | fim 00:22.8 | fala |
    | 4 | encantamento | `encantamento.0` | 01:47.8 | 4 batidas | fundo |
    | 5 | encantamento | `encantamento.1` | 02:10.4 | 4 batidas | fundo |
    | 6 | encantamento | `encantamento.2` | 07:11.4 | 4 batidas | fundo |

  - A resposta vira duas peças para cortar a pausa de 2,5 s depois de "ler!".
  - Legendas:
    - peça 2: `["E agora, o que eu faço?", "Aí muita gente pensa:", "*ler!*"]`;
    - peça 3: `["Acontece que *ler*", "*não é contar.*", "E esse curso é voltado", "para *contar.*"]`.
  - Rodar `carregar_plano(..., "v1")`. Esperado: total entre 24 e 25 s.
- [ ] **Passo 6: commit** com a mensagem "Cria a decupagem dos anúncios e a linha do tempo".

### Tarefa 3: Legenda palavra a palavra

**Arquivos:**
- Criar: `ferramentas/legenda.py` e `ferramentas/tests/test_legenda.py`.

**Interfaces:**
- Consome: `decupagem.PlanoVideo`, `decupagem.Peca`, `decupagem.sem_marcas` e
  `transcrever.preparar_cuda`.
- Produz:
  - `Palavra(inicio: float, fim: float, texto: str)`, com tempos na aula;
  - `PalavraLegenda(texto: str, enfase: bool, acende: float, apaga: float)`;
  - `GrupoLegenda(abre: float, fecha: float, palavras: tuple[PalavraLegenda, ...])`,
    com tempos no vídeo final;
  - `dividir_em_falas(audio: np.ndarray, taxa: int = 16000) -> list[tuple[float, float]]`;
  - `carregar_modelo()`: `large-v3`, `cuda`, `int8_float16`;
  - `transcrever_janela(modelo, arquivo_aula: Path, inicio: float, fim: float) -> list[Palavra]`;
  - `alinhar(palavras: list[Palavra], grupos: list[str]) -> list[list[tuple[Palavra, str, bool]]]`;
  - `diferencas(palavras, grupos) -> list[tuple[str, str]]`;
  - `repeticoes_suspeitas(palavras) -> list[str]`;
  - `legendas_do_plano(plano: PlanoVideo, transcricoes: dict[int, list[Palavra]]) -> list[GrupoLegenda]`.
- O texto mostrado vem sempre da `legenda` da decupagem (grafia revisada). O Whisper só
  dá os tempos.
- As palavras usadas são as que caem dentro de `[peca.inicio, peca.fim]`.
- O tempo final é `peca.posicao + (t − peca.inicio)`.
- O último grupo de uma peça fecha em `min(fim da peça no vídeo, fim da última palavra + 0,6)`.

- [ ] **Passo 1: testes que falham.**
  - `test_dividir_em_falas`: um áudio sintético de tom 1 s + silêncio 0,5 s + tom 1 s
    dá 2 falas, cada uma a até 0,08 s das bordas reais.
  - `test_alinhar_ok`: 5 palavras e grupos `["a b", "*c* d e"]` dão 2 grupos, com a
    ênfase só em `c`.
  - `test_alinhar_rejeita_contagem_diferente`: 6 palavras do Whisper (com "Tchau" a
    mais) contra 5 do texto dão `ValueError`, com as duas sequências na mensagem.
  - `test_diferencas_normaliza`: `"Ler!"` × `"ler"` não conta como diferença;
    `"lê"` × `"ler"` conta.
  - `test_repeticao_suspeita`: `["fácil,", "fácil"]` é apontada.
  - `test_janelas_pausa_longa`: com 0,5 s entre grupos, o 1º fecha em
    `último fim + 0.35`.
  - `test_janelas_pausa_curta`: com 0,1 s, fecha quando o próximo abre.
  - `test_acendimento_teto`: uma palavra de 3 letras seguida de pausa de 2 s apaga em
    `acende + 0.255`.
  - `test_tempos_no_video_final`: uma peça com `posicao = 2.8` e `inicio = 9.5` leva a
    palavra em 10,0 s da aula para `acende ≈ 3.3`.
  - `test_grupo_nao_atravessa_corte`: o último grupo fecha antes de a peça acabar no
    vídeo.
- [ ] **Passo 2:** rodar e ver falhar.
- [ ] **Passo 3:** implementar `legenda.py`.
  - O áudio da janela vem do ffmpeg com `-ss`/`-t` em f32le, 16 kHz e mono, como
    `transcrever.carregar_audio`.
  - Usar os parâmetros de transcrição listados em "Valores recuperados".
- [ ] **Passo 4:** rodar a suíte. Esperado: tudo OK.
- [ ] **Passo 5: prova real na GPU.** `transcrever_janela` em `m01a02`, de 8,5 a 23,8 s.
  Esperado:
  - as palavras de "E agora, o que eu faço?" até "para contar.";
  - "ler!" e "Acontece" separados por mais de 2 s.
- [ ] **Passo 6: commit** com a mensagem "Gera a legenda palavra a palavra a partir da transcrição por trecho".

### Tarefa 4: Peças e base em 1080×1920

**Arquivos:**
- Criar: `ferramentas/montagem.py` e `ferramentas/tests/test_montagem.py`.

**Interfaces:**
- Consome: `midia.filtro_vertical(layout, x, x2, saida=(1080, 1920))`, `decupagem.Peca`
  e `decupagem.PlanoVideo.trocas_de_bloco`.
- Produz:
  - constantes: `SAIDA = (1080, 1920)`, `ALVO_LUFS_PECA = -16.0`,
    `LIMITE_GANHO = (-10.0, 15.0)`, `PERFIL_BORRAO` (acima), `FADE_EMENDA = 0.012`;
  - `ganho_para_alvo(input_i: float, alvo: float = ALVO_LUFS_PECA) -> float`;
  - `filtro_borrao(quadros: list[int]) -> str`;
  - `medir_loudness(arquivo: Path, inicio: float | None = None, duracao: float | None = None) -> dict[str, float]`;
  - `extrair_peca(peca: Peca, arquivo_aula: Path, destino: Path) -> Path`;
  - `montar_base(arquivos: list[Path], quadros_chicote: list[int], destino: Path) -> Path`.
- **Peça:**
  - vídeo: `filtro_vertical + ",fps=30"`, `libx264 -preset slow -crf 14 -pix_fmt yuv420p -g 30`;
  - áudio: `volume={ganho}dB`, `aresample=48000`, estéreo, AAC 256k.
- **Base:**
  - `concat` de todas as peças, com `afade` de entrada e saída de 12 ms em cada uma;
  - o borrão nos quadros `round(t × 30)` de cada troca de bloco;
  - `alimiter`;
  - mesmos parâmetros de codificação da peça.

- [ ] **Passo 1: testes que falham.**
  - `test_ganho`: `ganho_para_alvo(-22.0)` dá `6.0`, e `ganho_para_alvo(-5.0)` dá `-10.0`.
  - `test_ganho_trecho_mudo`: `ganho_para_alvo(float("-inf"))` e
    `ganho_para_alvo(-75.0)` dão `0.0` (não amplifica silêncio).
  - `test_filtro_borrao`: com `[84]`, dá 7 filtros e contém
    `avgblur=sizeX=34:sizeY=1:enable='eq(n\,84)'` e `eq(n\,81)`.
  - `test_filtro_borrao_vazio`: `[]` dá `"null"`.
  - `test_montar_base_sintetica`: duas peças sintéticas de 1 s, geradas por `lavfi`
    (`testsrc2=size=1080x1920:rate=30` + `sine`). Esperado:
    - base de 1080×1920, 30 fps;
    - duração de 2,0 s ±1 quadro;
    - um único fluxo de áudio a 48 kHz.
- [ ] **Passo 2:** rodar e ver falhar.
- [ ] **Passo 3:** implementar `montagem.py`, reaproveitando o padrão de
  `midia._rodar_ffmpeg` (erro com o comando e a mensagem do ffmpeg).
- [ ] **Passo 4:** rodar a suíte. Esperado: tudo OK.
- [ ] **Passo 5: commit** com a mensagem "Monta a base vertical com nivelamento e borrão de transição".

### Tarefa 5: Projeto HyperFrames

**Arquivos:**
- Criar: `ferramentas/composicao.py` e `ferramentas/tests/test_composicao.py`.

**Interfaces:**
- Consome: `PlanoVideo`, `GrupoLegenda`, `recursos.*` e `decupagem.sem_marcas`.
- Produz:
  - `NIVEIS = {"fala": (1.0, 0.14), "fundo": (0.3, 0.7), "cantada": (1.0, 0.0)}`, com o
    par (base, trilha);
  - `TRILHA_FECHAMENTO = 0.95`, `RAMPA = 0.12`, `DESLOCAMENTO_CHICOTE = 80`,
    `LEGENDA_BOTTOM = 670`, `SELO_TOP = 320`;
  - `automacao(plano: PlanoVideo, faixa: str) -> list[tuple[float, float]]`, com `faixa`
    igual a `"base"` ou `"trilha"`;
  - `inicio_na_trilha(plano: PlanoVideo) -> float`;
  - `gerar_projeto(plano: PlanoVideo, abertura: str, legendas: list[GrupoLegenda], base: Path, destino: Path) -> Path`.
- **Arquivos do projeto:**
  - `index.html`, `hyperframes.json` (o padrão do registro, com `media.autoProxy: true`)
    e `meta.json`;
  - `compositions/abertura.html`, `legendas.html`, `marca.html` e `fechamento.html`;
  - `assets/{video,audio,fontes,img}`;
  - fontes por `@font-face` local, sem Google Fonts;
  - GSAP 3.15.0 do jsDelivr, como nos projetos aprovados.
- **Raiz:**
  - `data-width="1080" data-height="1920" data-duration={duracao_total}`;
  - fundo `#2b1300`;
  - `#quadro` com o `<video>` da base, com `data-automation` de volume vindo de
    `automacao(plano, "base")`.
- **Trilha:**
  - `<audio>` com `data-media-start={inicio_na_trilha}`, `data-fade-in="0.05"`,
    `data-fade-out="0.5"`;
  - limitador em −1,5 e `data-automation` vindo de `automacao(plano, "trilha")`.
- **Whoosh:** `whoosh-1` e `whoosh-2` nas trocas de bloco e `whoosh-3` no início do
  fechamento, com volume 0,55, 0,55 e 0,4.
- **Câmera (GSAP em `#quadro`):**
  - abertura: `scale 1 → 1.06` linear, ao longo da peça, para ter movimento já no
    quadro 0;
  - troca de bloco: chicote;
  - dentro da resposta: alterna `ABERTO` e `PERTO`;
  - dentro do encantamento: "assenta", alternando `transformOrigin` entre `50% 40%` e
    `50% 60%`.
- **Abertura:**
  - caixa amarela `#ffdc3a`, borda de 4 px café, raio 28 px, `max-width: 940px`, presa
    em `bottom: 670px`;
  - texto café em Nunito 900, 78 px, `line-height 1.08`;
  - palavras entre `*` em Fredoka One laranja `#c4390e`, 1.12em;
  - visível desde o quadro 0, sem `opacity: 0`: só um "soco" de `scale 1.12 → 1` em
    0,3 s `back.out(2)` no t = 0 e a ênfase pulsando em 0,25 s;
  - dura a peça de abertura.
- **Legenda:** a receita da Manuela, com `.grupo { bottom: 670px }` no lugar de `top`.
  Cobre só as peças `fala`.
- **Selo "CONTE&ENCANTE":**
  - pílula amarela com borda de 3 px café, girassol de 58 px, Nunito 900, 30 px,
    `letter-spacing 0.14em`;
  - em `top: 320px; left: 64px`;
  - aparece do fim da abertura até o início do fechamento.
- **Fechamento:**
  - íris creme `#fdf5d4` abrindo do centro do girassol do selo em 0,42 s `power2.in`;
  - logo de 520 px de largura, centrado, em top 360;
  - "Conte & Encante" em Fredoka One, 104 px, café, por volta de y 760;
  - "curso online de contação de histórias" em Nunito 800, 44 px, café a 0,75, por
    volta de y 890;
  - pílula café com "Saiba mais" em amarelo, Nunito 900, 48 px, 560 px de largura,
    por volta de y 990;
  - `contalele.com.br/conte-e-encante` em Nunito 800, 40 px, laranja, por volta de
    y 1140;
  - dois girassóis decorativos saindo pelas bordas, fora da faixa de texto;
  - entradas escalonadas como na cartela da Manuela.
- **Texto no HTML:** sempre por `html.escape`.

- [ ] **Passo 1: testes que falham.**
  - `test_automacao_trilha_baixa_na_fala`: o valor no meio de cada peça `fala` é 0,14,
    e no meio do fechamento, 0,95.
  - `test_automacao_trilha_muda_em_cantada`: com uma peça `cantada`, o valor no meio
    dela é `0.0`.
  - `test_automacao_ordenada_e_limitada`: os tempos são estritamente crescentes, os
    valores ficam em [0, 1] e o último ponto é igual a `duracao_total`.
  - `test_inicio_na_trilha_cai_na_batida`:
    `(inicio_na_trilha + inicio_do_1º_encantamento − 0.256) mod (60/117.99)` dá menos
    de 0,002 ou mais que o período menos 0,002.
  - `test_trilha_curta_falha`: um plano de 95 s dá `ValueError`, citando
    `TRILHA_FIM_UTIL`.
  - `test_projeto_escapa_texto`: uma abertura com `"A & B <i>"` aparece como
    `A &amp; B &lt;i&gt;` no HTML.
  - `test_abertura_visivel_no_quadro_0`: `abertura.html` não tem `opacity: 0` nem
    `opacity:0`.
  - `test_area_segura`: `legendas.html` e `abertura.html` têm `bottom: 670px`, e
    `marca.html` tem `top: 320px`.
  - `test_fechamento_textos`: o HTML tem "Saiba mais", `contalele.com.br/conte-e-encante`
    e "curso online de contação de histórias", e nenhum `R$`.
  - `test_projeto_passa_no_check_em_pasta_com_acento`: com `skipUnless`, se o
    `recursos.HYPERFRAMES` existe. Gera um projeto em `tmp/Anúncios/` com base sintética
    de 25 s (`lavfi`), roda `check --json` com `AMBIENTE_HYPERFRAMES` e espera zero
    erros.
- [ ] **Passo 2:** rodar e ver falhar.
- [ ] **Passo 3:** implementar `composicao.py`.
  - Os modelos de HTML ficam em constantes do módulo.
  - Cada seletor de composição leva o prefixo `[data-composition-id="…"]`.
  - Cada composição registra `window.__timelines["<id>"]`.
  - A automação é constante por peça, com rampa linear de ±`RAMPA` em cada fronteira.
- [ ] **Passo 4:** rodar a suíte. Esperado: tudo OK. Avisos do `check` são aceitos e
  impressos; erros, não.
- [ ] **Passo 5: commit** com a mensagem "Gera o projeto HyperFrames dos anúncios".

### Tarefa 6: Render, volume final e conferência

**Arquivos:**
- Criar: `ferramentas/finalizar.py` e `ferramentas/tests/test_finalizar.py`.

**Interfaces:**
- Consome: `recursos.HYPERFRAMES`, `recursos.AMBIENTE_HYPERFRAMES`,
  `montagem.medir_loudness`, `midia.gravar_imagem`, `legenda.transcrever_janela` e
  `decupagem.sem_marcas`.
- Produz:
  - `renderizar(projeto: Path, saida: Path, tempo_limite: int = 1800) -> Path`:
    - roda `check --json` e para com erro se houver erros;
    - depois roda o render;
    - se estourar o tempo, encerra a árvore de processos com `taskkill /T /F /PID`,
      para não deixar Chrome solto;
  - `codificar_final(render: Path, saida: Path) -> Path`:
    - `loudnorm` em duas passagens com `I=-14:TP=-1.5:LRA=11`, mais os valores medidos e
      `linear=true`;
    - `libx264 -preset slow -crf 18 -maxrate 12M -bufsize 24M -profile:v high -pix_fmt yuv420p -r 30 -g 30`;
    - `aac -b:a 192k -ar 48000 -ac 2 -movflags +faststart`;
  - `conferir_video(caminho: Path) -> list[str]`: devolve a lista de problemas em pt-BR,
    ou vazia se estiver tudo certo;
  - `folha_conferencia(video: Path, tempos: list[float], destino: Path) -> Path`: quadros
    de 270 px de largura lado a lado, com o tempo escrito em cada um;
  - `comparar_fala(video: Path, plano: PlanoVideo, modelo) -> list[tuple[str, float]]`:
    para cada peça `fala`, a razão do `difflib` entre a transcrição do vídeo final e o
    texto da legenda, normalizados.

- [ ] **Passo 1: testes que falham** (vídeos sintéticos `lavfi`):
  - `test_conferir_aceita_conforme`: um vídeo de 21 s em 1080×1920, 30 fps, H.264
    `yuv420p`, AAC 48 kHz estéreo, passado por `codificar_final`, dá `[]`.
  - `test_conferir_resolucao_errada`: 720×1280 gera um problema com "1080×1920".
  - `test_conferir_duracao`: 12 s gera um problema com "20 a 30 s".
  - `test_conferir_volume`: um seno a −30 LUFS sem normalizar gera um problema com
    "LUFS".
  - `test_folha_conferencia`: 3 tempos dão uma imagem de 810 px de largura.
- [ ] **Passo 2:** rodar e ver falhar.
- [ ] **Passo 3:** implementar `finalizar.py`.
  - Ler as medidas pelo `ffprobe` e pelo `loudnorm` com `print_format=json`.
  - Toda mensagem de erro em pt-BR, sem traceback cru para o usuário.
- [ ] **Passo 4:** rodar a suíte. Esperado: tudo OK.
- [ ] **Passo 5: commit** com a mensagem "Renderiza, nivela em -14 LUFS e confere os anúncios".

### Tarefa 7: Comando de edição e piloto do v1

**Arquivos:**
- Criar: `ferramentas/editar.py` e `ferramentas/tests/test_editar.py`.
- Alterar: `edicao/decupagem.json`, refinando o v1.

**Interfaces:**
- Consome: tudo acima.
- Produz a linha de comando:
  - `python ferramentas/editar.py <video> transcrever`:
    - transcreve cada peça `fala` na janela `[inicio − 1, fim + 1]`;
    - grava em `edicao/trabalho/<video>/transcricoes/<nn>.json`, como cache, com a
      janela registrada;
    - imprime as palavras com o tempo na aula, as diferenças para a legenda e as
      repetições suspeitas;
    - carrega o plano com `exigir_legenda=False`;
  - `python ferramentas/editar.py <video> quadros`:
    - extrai as peças para `edicao/trabalho/<video>/pecas/`;
    - gera uma folha de 4 quadros por peça, a 10, 37, 63 e 90% da duração;
  - `python ferramentas/editar.py <video> produzir --aberturas A B`:
    - faz, em ordem, `recursos.faltando` (para se faltar algo), peças, base, legendas,
      projeto por abertura, render, final, conferência e comparação de fala;
    - grava `edicao/saida/<video>-<abertura>.mp4` e `edicao/saida/<video>-<abertura>-conferencia.jpg`;
    - termina com código 1 se `conferir_video` acusar problema ou se alguma razão de
      fala ficar abaixo de 0,90.

- [ ] **Passo 1: testes que falham.**
  - `test_produzir_para_sem_recursos`: com `recursos.PASTA_RECURSOS` apontando para uma
    pasta vazia, `main(["v1", "produzir"])` devolve 1 e imprime "faltam recursos" com os
    nomes.
  - `test_video_inexistente`: `main(["v9", "transcrever"])` devolve 2 e cita os ids
    válidos.
- [ ] **Passo 2:** rodar, ver falhar, implementar `editar.py`, rodar a suíte. Esperado:
  tudo OK.
- [ ] **Passo 3:** `python ferramentas/editar.py v1 transcrever`.
  - Ajustar na decupagem a entrada e a saída das peças 2 e 3 nas pausas reais: entrada
    0,15 s antes da 1ª palavra, saída 0,25 s depois da última, sem cortar palavra.
  - Corrigir a grafia na `legenda` se o Whisper divergir. As palavras precisam ter a
    mesma contagem.
  - Conferir as repetições suspeitas.
- [ ] **Passo 4:** `python ferramentas/editar.py v1 quadros`.
  - Olhar cada folha. Nos encantamentos, mover o `inicio` até a janela de 4 batidas
    pegar o gesto ou o objeto no auge, com a Lelê inteira no quadro e sem desfoque de
    movimento.
  - Repetir até as 6 folhas estarem boas.
- [ ] **Passo 5:** `python ferramentas/editar.py v1 produzir --aberturas A B`. Esperado:
  código 0, dois MP4 e duas folhas de conferência.
- [ ] **Passo 6: conferir as folhas.** Ler cada uma e verificar:
  - o quadro 0 já mostra a frase da abertura;
  - legenda e textos ficam na faixa y 300–1250;
  - o rosto da Lelê não fica coberto;
  - o fechamento está completo e com a grafia certa.
  - Se algo falhar, corrigir na decupagem ou na composição e voltar ao Passo 5.
- [ ] **Passo 7:** rodar a suíte completa. Esperado: tudo OK.
- [ ] **Passo 8: commit** do `editar.py`, do teste e da decupagem refinada, com a mensagem
  "Produz o piloto do anúncio 1 nas aberturas A e B".
- [ ] **Passo 9: entregar ao Thiago.**
  - Os caminhos de `v1-A.mp4` e `v1-B.mp4`.
  - Duração, LUFS e tamanho de cada um.
  - As decisões tomadas na edição.
  - O pedido de aprovação antes dos vídeos 2 a 6.
  - Atualizar a memória `contalele-anuncios-video-conte.md` com o estado.
