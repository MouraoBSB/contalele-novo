<?php
/**
 * Página de vendas do curso Cante & Encante.
 *
 * Layout do Claude Design (pacote download-cante-e-encante), com topo, barra e
 * rodapé próprios ($landingPropria). Enquanto não publicada, só abre com a senha
 * de pré-visualização e não entra em buscadores.
 *
 * Thiago Mourão — https://github.com/MouraoBSB — 03/11/2026
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/girassol.php';
require_once CL_RAIZ . '/includes/previa.php';

$publicada = exigir_previa('cante', 'Cante & Encante');

$layout         = 'landing';
$landingPropria = true;
$cssExtra       = ['/assets/css/cante-e-encante.css'];
$jsExtra        = ['/assets/js/cante-e-encante.js'];

// ── Dados da oferta ──────────────────────────────────────────────────────
// Link da oferta normal (R$ 97, código q5glzjoe), com o ?off. Vazio enquanto o
// produto estiver em rascunho na Hotmart: os botões levam ao cartão de preço.
$urlCheckout = trim(configuracao('cante_checkout_url', ''));
$preco       = 'R$ 97';
$certificado = trim(configuracao('cante_certificado', ''));

// ── Promoção ─────────────────────────────────────────────────────────────
// Black Friday por tempo limitado, sem data de término: liga e desliga aqui.
// Os valores precisam bater com a oferta "Black Friday 2026" da Hotmart (nn7nhybh).
// O link dela só existe quando o produto sair do rascunho; vazio, vale o do painel.
$promocao = [
    'ativa'    => true,
    'nome'     => 'Black Friday',
    'produto'  => 'Cante & Encante',
    'checkout' => '',
    'de'       => 'R$ 97',
    'por'      => 'R$ 67',
    'desconto' => 'R$ 30',
    'parcela'  => '12x de R$ 5,59',
];
$promocao = $promocao['ativa'] ? $promocao : null;
if ($promocao !== null && $promocao['checkout'] !== '') {
    $urlCheckout = $promocao['checkout'];
}

$urlCheckout = checkout_com_afiliado($urlCheckout, $_GET);
$externo     = $urlCheckout !== '';
// Atributos de todos os botões de compra: o mesmo destino em todos.
$cta = 'href="' . e($externo ? $urlCheckout : '#lp-investimento') . '"'
    . ($externo ? ' target="_blank" rel="noopener"' : '');

/** Valor do painel, ou o selo de pendência enquanto estiver vazio. */
$ouPendente = static function (string $valor, string $rotulo): string {
    return $valor !== '' ? e($valor) : '<span class="lp-pendente">[PENDENTE: ' . e($rotulo) . ']</span>';
};

// ── SEO ──────────────────────────────────────────────────────────────────
$seo['titulo']    = 'Cante & Encante — música na contação de histórias, com Lelê e Kleyton Santos';
$seo['descricao'] = 'Curso online para colocar música nas suas histórias mesmo sem tocar instrumento '
    . 'nem cantar bem. 6 aulas com Lelê e Kleyton Santos.';
$seo['imagem']    = $seo['url_base'] . '/assets/img/cante/aula1-lele-e-kleyton.jpg';

if (!$publicada) {
    $seo['robots'] = 'noindex, nofollow';
}

$seo['json_ld'] = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Course',
    'name'        => 'Cante & Encante',
    'description' => $seo['descricao'],
    'url'         => $seo['url_base'] . '/cante-e-encante',
    'inLanguage'  => 'pt-BR',
    'provider'    => [
        '@type' => 'Person',
        'name'  => 'Letícia Rocha Mourão Marques',
        'url'   => $seo['url_base'],
    ],
    'hasCourseInstance' => [
        '@type'      => 'CourseInstance',
        'courseMode' => 'online',
    ],
    'offers' => [
        '@type'         => 'Offer',
        'price'         => preg_replace('/\D/', '', $promocao['por'] ?? $preco),
        'priceCurrency' => 'BRL',
        'category'      => 'Paid',
        'url'           => $externo ? $urlCheckout : $seo['url_base'] . '/cante-e-encante',
    ],
];

$igRodape    = configuracao('instagram', 'https://www.instagram.com/contalele/');
$ytRodape    = configuracao('youtube', 'https://www.youtube.com/c/ContaLel%C3%AA');
$emailRodape = configuracao('email_contato', 'contato@contalele.com.br');

$checkSvg = '<span class="lp-ck"><svg aria-hidden="true" focusable="false"><use href="#ck-sym"></use></svg></span>';
?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs>
<symbol id="ck-sym" viewBox="0 0 24 24"><path d="M4 12.5l5 5 11-11" fill="none" stroke="#2b1300" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></symbol>
<symbol id="chev-sym" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6" fill="none" stroke="#2b1300" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></symbol>
</defs></svg>

<?php if (!$publicada): ?>
<div class="lp-previa">
    <div class="lp-wrap">
        <strong>Pré-visualização.</strong> Esta página ainda não está publicada e não aparece em buscadores.
    </div>
</div>
<?php endif; ?>

<?php if ($promocao !== null): ?>
<div class="lp-bf-faixa" role="region" aria-label="<?= e($promocao['nome']) ?>">
    <div class="lp-wrap"><p><span class="lp-bf-curto"><b><?= e($promocao['nome']) ?></b>: de <?= e($promocao['de']) ?>
        por <?= e($promocao['por']) ?>, por tempo limitado.</span><span class="lp-bf-longo"><b><?= e($promocao['nome']) ?></b>:
        o <?= e($promocao['produto']) ?> sai de <?= e($promocao['de']) ?> por <?= e($promocao['por']) ?>, por tempo
        limitado.</span><a href="#lp-investimento">Ver a oferta</a></p></div>
</div>
<?php endif; ?>

<header class="lp-topo">
    <div class="lp-wrap">
        <?php if ($promocao !== null): ?>
            <img class="lp-logo" src="/assets/img/logo-amarela.png" alt="Conta Lelê" width="180" height="132">
        <?php else: ?>
            <img class="lp-logo" src="/assets/img/logo.png" alt="Conta Lelê" width="1006" height="739">
        <?php endif; ?>
        <a class="lp-btn lp-btn-yellow lp-btn-sm" <?= $cta ?>>Quero entrar</a>
    </div>
</header>

<div class="lp-barra" id="lp-barra">
    <div class="lp-wrap">
        <img class="lp-logo" src="/assets/img/logo.png" alt="Conta Lelê" width="1006" height="739">
        <div class="lp-barra-dir">
            <?php if ($promocao !== null): ?>
                <span class="lp-bf-barra-txt" aria-hidden="true"><b><?= e($promocao['nome']) ?></b>
                    <s><?= e($promocao['de']) ?></s></span>
            <?php endif; ?>
            <a class="lp-btn lp-btn-primary lp-btn-sm" <?= $cta ?>>Quero entrar<?= $promocao !== null
                ? '<span>por ' . e($promocao['por']) . '</span>' : '' ?></a>
        </div>
    </div>
</div>

<main id="conteudo">
<!-- 1 · Topo -->
<section class="lp-sec lp-hero">
    <?php girassol_ref(['tamanho' => 340, 'classe' => 'lp-girassol lp-deco']); ?>
    <div class="lp-wrap">
        <div class="lp-hero-grid">
            <div class="lp-rv">
                <p class="lp-eyebrow">Cante &amp; Encante · com Lelê e Kleyton Santos</p>
                <?php if ($promocao !== null): ?>
                    <a class="lp-bf-selo" href="#lp-investimento"><b><?= e($promocao['nome']) ?></b> ·
                        <?= e($promocao['desconto']) ?> de desconto</a>
                <?php endif; ?>
                <h1>Você não precisa saber tocar nem cantar bem para colocar <em>música</em> nas suas histórias.</h1>
                <p class="lp-lead lp-mt">Aprenda a usar a música para chamar a atenção, criar rituais, apresentar
                    personagens e encantar as crianças, com a sua voz, cantigas que elas já conhecem e objetos
                    simples.</p>
                <div style="margin-top:28px"><a class="lp-btn lp-btn-primary" <?= $cta ?>>Quero cantar e encantar</a></div>
                <ul class="lp-chips">
                    <li class="lp-chip">Para quem não toca instrumento</li>
                    <li class="lp-chip">Com cantigas que as crianças já conhecem</li>
                    <li class="lp-chip">Com a sua voz e objetos simples</li>
                </ul>
            </div>
            <figure class="lp-foto lp-hero-foto lp-rv"><img src="/assets/img/cante/estudio-lele-pandeiro-de-fitas-vertical.jpg"
                alt="Lelê sorrindo e olhando para cima, segurando sobre a cabeça um pandeiro com fitas coloridas penduradas"
                width="1000" height="1500" style="object-position:50% 20%"></figure>
        </div>
    </div>
</section>

<!-- 2 · Identificação -->
<section class="lp-sec lp-s-creme">
    <div class="lp-wrap">
        <div class="lp-col2 lp-col2-txt-foto">
            <div>
                <h2 class="lp-rv">Você já sentiu que uma música <em>salvaria</em> aquele momento da história?</h2>
                <p class="lp-lead lp-mt lp-rv">A turma dispersa, você quer chamar todo mundo de volta… mas aparece a dúvida:</p>
                <ul class="lp-duvidas lp-rv">
                    <li>E se eu desafinar?</li>
                    <li>E se eu não souber o que cantar?</li>
                    <li>E se eu não tiver tempo de criar uma música?</li>
                </ul>
            </div>
            <figure class="lp-foto lp-recorte lp-r45 lp-rv" style="max-width:440px;margin:32px auto 0"><img
                src="/assets/img/cante/estudio-lele-chamando-vertical.jpg"
                alt="Lelê com as mãos em volta da boca, como quem chama alguém de longe"
                width="1000" height="1500" loading="lazy"></figure>
        </div>
    </div>
</section>

<!-- 3 · A virada -->
<section class="lp-sec lp-s-cafe">
    <div class="lp-wrap">
        <div class="lp-col2">
            <div>
                <p class="lp-eyebrow lp-rv">A história vem primeiro</p>
                <h2 class="lp-rv">A música não substitui a história. <em>Amplia</em> o que ela já tem de bom.</h2>
                <p class="lp-mt lp-rv">No Cante &amp; Encante você aprende a usar a música a favor da narrativa: no lugar
                    certo, com intenção e com a voz que você já tem. Não tem teoria musical nem aula de instrumento.</p>
                <p class="lp-rv">A própria Lelê não toca violão. Mesmo assim, depois de uma apresentação, uma professora
                    veio conversar achando que ela era professora de música.</p>
            </div>
            <figure class="lp-foto lp-r169 lp-rv" style="margin-top:8px"><img src="/assets/img/cante/aula1-lele-e-kleyton.jpg"
                alt="Lelê, de figurino azul, sorri com a mão no ombro do Kleyton, que está sentado com o violão no palco"
                width="1200" height="675" loading="lazy"></figure>
        </div>
        <div class="lp-rv" style="margin-top:48px;max-width:860px">
            <p class="lp-fala">“Eu tenho violão? Não. Eu sei tocar? Não.”</p>
            <p class="lp-fala-quem">Lelê, na primeira aula</p>
            <p class="lp-citacao">“A música tem o papel de ampliar aquilo que na história já é muito bom.”</p>
        </div>
    </div>
</section>

<!-- 4 · Onde a música entra -->
<section class="lp-sec lp-s-branco">
    <div class="lp-wrap">
        <p class="lp-eyebrow lp-rv">Onde a música entra na história</p>
        <h2 class="lp-rv" style="max-width:860px">A música não entra em qualquer momento. Tem lugar certo e tem <em>intenção</em>.</h2>
        <p class="lp-lead lp-mt lp-rv lp-narrow">No curso, você conhece 12 funções que a música pode ter dentro de uma narrativa:</p>
        <ol class="lp-funcoes lp-rv">
            <?php
            $funcoes = [
                'Preparar a escuta', 'Criar um ritual', 'Apresentar personagens', 'Favorecer a participação',
                'Criar ritmo para a narrativa', 'Destacar momentos importantes', 'Reforçar a memória',
                'Intensificar emoções', 'Facilitar a compreensão da narrativa',
                'Favorecer diferentes formas de participação', 'Marcar transições', 'Encerrar a experiência',
            ];
            foreach ($funcoes as $i => $funcao): ?>
                <li><span class="lp-n" aria-hidden="true"><?= $i + 1 ?></span><?= e($funcao) ?></li>
            <?php endforeach; ?>
        </ol>
        <div class="lp-col2 lp-rv" style="margin-top:40px">
            <figure class="lp-mapa" style="margin:0"><img src="/assets/img/cante/material-mapa-mental-12-funcoes.jpg"
                alt="Trecho do mapa mental colorido “12 funções que a música pode exercer dentro de uma narrativa”"
                width="1536" height="1024" loading="lazy"><figcaption>Amostra do mapa mental que vem com o curso</figcaption></figure>
            <p class="lp-manda">“Lembre-se: quem manda sempre é a história.”</p>
        </div>
    </div>
</section>

<!-- 5 · Vídeo -->
<section class="lp-sec lp-s-amarelo2">
    <div class="lp-wrap">
        <div class="lp-video-grid">
            <div class="lp-rv">
                <div class="lp-video"><video controls preload="none" playsinline
                    poster="/assets/img/cante/sucolandia-capa.jpg" aria-describedby="lp-video-desc"><source
                    src="/assets/video/cante-e-encante-sucolandia.mp4" type="video/mp4">Seu navegador não reproduz este vídeo.</video></div>
                <p class="lp-video-desc" id="lp-video-desc"><b>O que acontece na cena (1min15s):</b> no palco, a Lelê canta
                    e marca o ritmo no pandeiro enquanto o Kleyton toca violão. Cada verso puxa uma rima com o nome de um
                    suco, como “Eu vou ali e volto já! Suco de maracujá”.</p>
            </div>
            <div class="lp-rv">
                <p class="lp-eyebrow">Um trecho da Sucolândia</p>
                <h2>Veja e escute como fica na <em>prática</em></h2>
                <p class="lp-lead lp-mt">A Sucolândia, brincadeira de rimas criada pela Lelê e pelo Kleyton: uma frase puxa
                    a outra e as crianças adivinham qual é o suco.</p>
            </div>
        </div>
    </div>
</section>

<!-- 6 · As 6 aulas -->
<section class="lp-sec lp-s-creme">
    <div class="lp-wrap">
        <p class="lp-eyebrow lp-rv">O curso</p>
        <h2 class="lp-rv" style="max-width:860px">6 aulas gravadas num palco, com a Lelê contando e o Kleyton ao <em>violão</em></h2>
        <ul class="lp-resumo lp-rv"><li>6 aulas</li><li>Cerca de 2h30</li><li>No seu ritmo</li><li>1 ano de acesso</li></ul>
        <ul class="lp-aulas">
            <?php
            // Textos com <i> nos títulos de livros: conteúdo fixo, escrito aqui mesmo.
            $aulas = [
                ['aula1-lele-cantando.jpg', 'Lelê, de figurino azul, canta de olhos fechados no palco',
                 'A música como aliada da narrativa',
                 'A Lelê conta <i>Os Entalados</i> com o Kleyton e para em cada ponto em que a música entra: começo, '
                 . 'personagens, suspense, efeitos com a voz, emoção e encerramento. E mostra as “músicas coringa”, que '
                 . 'servem para qualquer história.'],
                ['aula2-rituais-pandeiro.jpg', 'Lelê, de figurino laranja e verde, toca pandeiro ao lado do Kleyton ao violão',
                 'Rituais de começo e de fim',
                 'Músicas que preparam a escuta e avisam que a história começou ou terminou, como Samba Lelê, Cai, cai, '
                 . 'balão, “A Barata diz que tem” e a Sucolândia. Inclui um ritual pensado para público adulto.'],
                ['aula3-cantigas-e-parodias.jpg', 'Lelê, de blusa listrada, canta com expressão de espanto; o Kleyton ao fundo com o violão',
                 'Cantigas de roda e paródias',
                 'Como aproveitar músicas que as crianças já conhecem e criar letras novas sobre melodias prontas, com a '
                 . 'história <i>A Bruxa que Virou Princesa</i>.'],
                ['aula4-lagarta-sol.jpg', 'Lelê, de figurino verde, segura um sol de tecido laranja enquanto o Kleyton canta ao violão',
                 'Criando músicas para histórias',
                 '<i>Uma Lagarta Muito Comilona</i> com a música que o Kleyton compôs para ela. Depois, músicas criadas ao '
                 . 'vivo a partir do texto de <i>O Sapo Bocarrão</i> e <i>Bruxa, Bruxa, Venha à Minha Festa</i>, e a '
                 . '<i>Sapa Cristina</i>, cordel da Lelê.'],
                ['aula5-livro-que-ja-traz-musica.jpg', 'Lelê, de vestido de chita, mostra as páginas abertas de um livro',
                 'Música sem complicação',
                 'Livros que já trazem música, cantar o próprio texto, usar só a voz e instrumentos simples que não pedem estudo.'],
                ['aula6-oficina-circo-encantado.jpg', 'Lelê, de vestido listrado colorido, mostra a página “Vamos adivinhar?” para o Kleyton',
                 'Oficina musical',
                 'Brincadeiras que juntam música, rima e adivinha: o e-book <i>Circo Encantado</i>, adivinhas de animais e '
                 . 'brincadeiras de corpo e ritmo.'],
            ];
            foreach ($aulas as $i => [$foto, $alt, $titulo, $texto]): ?>
                <li class="lp-aula lp-rv"><figure class="lp-foto"><img src="/assets/img/cante/<?= e($foto) ?>"
                    alt="<?= e($alt) ?>" width="800" height="450" loading="lazy"></figure><div class="lp-aula-txt"><p
                    class="lp-aula-num">Aula <?= $i + 1 ?></p><h3><?= e($titulo) ?></h3><p class="lp-dim"><?= $texto ?></p></div></li>
            <?php endforeach; ?>
        </ul>
        <div class="lp-cta lp-rv"><a class="lp-btn lp-btn-primary" <?= $cta ?>>Quero cantar e encantar</a></div>
    </div>
</section>

<!-- 7 · Instrumentos -->
<section class="lp-sec lp-s-branco">
    <div class="lp-wrap">
        <p class="lp-eyebrow lp-rv">Instrumentos que não pedem aula</p>
        <h2 class="lp-rv" style="max-width:860px">Você não precisa de um violão. Muita coisa que faz música já está ao seu <em>alcance</em>.</h2>
        <ul class="lp-instr">
            <?php
            $instrumentos = [
                ['instrumento-caxixi.jpg', 'Lelê balança um caxixi colorido feito com garrafinhas', 'Caxixi',
                 'Garrafa PET com feijão ou arroz.'],
                ['instrumento-caixa-do-oceano.jpg', 'Lelê segura uma caixa azul decorada com peixinhos', 'Caixa do oceano',
                 'Caixa reaproveitada com pedrinhas de aquário, que imita o som do mar.'],
                ['instrumento-pandeirola.jpg', 'Lelê toca uma pandeirola lilás em forma de meia-lua', 'Pandeirola',
                 'Também chamada de meia-lua.'],
                ['instrumento-fitofone.jpg', 'Lelê segura um feixe de fitas coloridas amarradas', 'Fitofone',
                 'Fitas amarradas que fazem som e ainda ficam bonitas.'],
            ];
            foreach ($instrumentos as [$foto, $alt, $nome, $texto]): ?>
                <li class="lp-rv"><figure class="lp-foto"><img src="/assets/img/cante/<?= e($foto) ?>" alt="<?= e($alt) ?>"
                    width="640" height="360" loading="lazy"></figure><b><?= e($nome) ?></b><span><?= e($texto) ?></span></li>
            <?php endforeach; ?>
            <li class="lp-tom lp-tom-laranja lp-rv"><?php girassol_ref(['tamanho' => 96, 'classe' => 'lp-girassol lp-deco']); ?><b>Chocalho</b><span>Qualquer um que você já tenha por perto.</span></li>
            <li class="lp-tom lp-tom-cafe lp-rv"><?php girassol_ref(['tamanho' => 96, 'classe' => 'lp-girassol lp-deco']); ?><b>O corpo e a voz</b><span>O mais fácil de todos.</span></li>
        </ul>
    </div>
</section>

<!-- 8 · Quem ensina -->
<section class="lp-sec lp-s-creme">
    <div class="lp-wrap">
        <p class="lp-eyebrow lp-rv">Quem ensina</p>
        <h2 class="lp-rv">“A gente faz o casamento perfeito de história com <em>música</em>.”</h2>
        <div class="lp-pessoas">
            <div class="lp-pessoa lp-pessoa-l">
                <div class="lp-pessoa-foto lp-rv">
                    <figure class="lp-foto lp-recorte lp-r32"><img src="/assets/img/cante/estudio-lele-pandeiro-e-sombrinha.jpg"
                        alt="Lelê sorrindo, com flores no cabelo, segurando um pandeiro e uma sombrinha de frevo colorida"
                        width="1000" height="667" loading="lazy"></figure>
                    <div class="lp-selo">+20 anos contando histórias</div>
                </div>
                <div class="lp-rv">
                    <p class="lp-nome">Lelê</p>
                    <p class="lp-papel">Letícia Mourão · contadora de histórias</p>
                    <p>Contadora de histórias, atriz, cordelista, escritora e mediadora de leitura, a Lelê conta histórias
                        profissionalmente há mais de 20 anos e trabalha também numa biblioteca pública.</p>
                    <p>Em 2019, foi a única não musicista num curso de musicalização em São Paulo, e se diz “a pessoa mais
                        feliz do curso”. A Sucolândia, que ela criou com o Kleyton, hoje é cantada por gente de vários
                        lugares do Brasil.</p>
                    <div class="lp-aspas"><p>“Você também é um ser musical, mesmo que não seja do campo da música.”</p><small>Lelê</small></div>
                </div>
            </div>
            <div class="lp-pessoa lp-pessoa-k">
                <div class="lp-circulo lp-rv"><img src="/assets/img/cante/kleyton-santos.jpg"
                    alt="Retrato do Kleyton Santos sorrindo, de camisa listrada vinho e branca" width="1122" height="1402" loading="lazy"></div>
                <div class="lp-rv">
                    <p class="lp-nome">Kleyton Santos</p>
                    <p class="lp-papel">Músico · ao violão</p>
                    <p style="margin:0">Professor de música e de matemática e parceiro da Lelê há muitos anos. Compôs a
                        música da Lagarta que o curso ensina e criou com ela a Sucolândia.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 9 · Histórias e livros -->
<section class="lp-sec lp-s-branco">
    <div class="lp-wrap">
        <p class="lp-eyebrow lp-rv">Repertório</p>
        <h2 class="lp-rv">Histórias e livros usados nas <em>aulas</em></h2>
        <ul class="lp-livros lp-rv">
            <?php
            $livros = [
                ['Os Entalados', 'de Solange Ribeiro, com ilustrações de Flávia, a Fafá'],
                ['A Bruxa que Virou Princesa', 'de Rita de Cássia, a Tia Maroca'],
                ['Uma Lagarta Muito Comilona', 'de Eric Carle'],
                ['A Sina da Sapa Cristina', 'cordel de Letícia Mourão'],
                ['Bruxa, Bruxa, Venha à Minha Festa', 'de Arden Druce'],
                ['O Sapo Bocarrão', 'de Keith Faulkner, com ilustrações de Jonathan Lambert'],
                ['Emengarda, a Barata', 'de Pierre André'],
                ['Dino Samuel', 'de Marina Bastos'],
            ];
            foreach ($livros as [$livro, $autoria]): ?>
                <li><i><?= e($livro) ?></i><span><?= e($autoria) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <p class="lp-nota lp-rv">As histórias completas não estão no curso. Valorize quem escreve: procure os livros.</p>
    </div>
</section>

<!-- 10 · O que você recebe -->
<section class="lp-sec lp-s-creme">
    <div class="lp-wrap">
        <h2 class="lp-rv">O que <em>você recebe</em></h2>
        <ul class="lp-recebe">
            <?php
            // [título, detalhe]; o detalhe pode vir vazio.
            $recebe = [
                ['6 aulas gravadas, cerca de 2h30', 'Para assistir no seu ritmo.'],
                ['Letras com cifras', 'Das músicas trabalhadas nas aulas.'],
                ['Mapa mental “12 funções que a música pode exercer dentro de uma narrativa”', ''],
                ['Mapa mental “Caminhos da música na contação de histórias”', ''],
                ['E-book Circo Encantado', 'Rimas, adivinhas e canções: 31 páginas para baixar, imprimir e adaptar.'],
                ['1 ano de acesso', ''],
            ];
            foreach ($recebe as [$titulo, $detalhe]): ?>
                <li class="lp-rv"><?= $checkSvg ?><span><b><?= e($titulo) ?></b><?php if ($detalhe !== ''): ?><span
                    class="lp-dim"><?= e($detalhe) ?></span><?php endif; ?></span></li>
            <?php endforeach; ?>
            <li class="lp-rv"><?= $checkSvg ?><span><b>Certificado</b><span class="lp-dim"><?= $ouPendente($certificado, 'confirmar certificado') ?></span></span></li>
        </ul>
        <div class="lp-previas">
            <figure class="lp-rv"><div class="lp-foto lp-r32"><img src="/assets/img/cante/material-letras-com-cifras-sucolandia.jpg"
                alt="Prévia da folha de letra com cifras da Sucolândia" width="900" height="600" loading="lazy"
                style="object-position:50% 50%"></div><figcaption>Letras com cifras</figcaption></figure>
            <figure class="lp-rv"><div class="lp-foto lp-r32"><img src="/assets/img/cante/material-ebook-circo-encantado-capa.jpg"
                alt="Capa do e-book Circo Encantado, com rimas, adivinhas e canções" width="900" height="637" loading="lazy"
                style="object-position:30% 40%"></div><figcaption>E-book <i>Circo Encantado</i></figcaption></figure>
            <figure class="lp-rv"><div class="lp-mapa lp-mapa-dir" style="aspect-ratio:3/2"><img
                src="/assets/img/cante/material-mapa-mental-12-funcoes.jpg" alt="Trecho do mapa mental das 12 funções da música"
                width="1536" height="1024" loading="lazy"></div><figcaption>Mapa mental das 12 funções (trecho)</figcaption></figure>
        </div>
    </div>
</section>

<!-- 11 · Preço -->
<section class="lp-sec lp-s-amarelo lp-s-preco" id="lp-investimento">
    <?php girassol_ref(['tamanho' => 260, 'classe' => 'lp-girassol lp-deco esq']); ?>
    <?php girassol_ref(['tamanho' => 260, 'classe' => 'lp-girassol lp-deco dir']); ?>
    <div class="lp-wrap">
        <h2 class="lp-rv" style="text-align:center;max-width:820px;margin:0 auto;position:relative;z-index:1">“Primeiro vem a
            história. Depois, as palavras. E então, a <em style="color:var(--ink)">música</em>.”</h2>
        <?php if ($promocao !== null): ?>
            <div class="lp-bf-cartao lp-rv">
                <figure class="lp-bf-foto"><img src="/assets/img/cante/estudio-lele-pandeiro-risada.jpg"
                    alt="Lelê dando uma gargalhada enquanto toca um pandeiro com fitas" width="1000" height="667" loading="lazy"></figure>
                <div class="lp-bf-corpo">
                    <?php girassol_ref(['tamanho' => 96, 'classe' => 'lp-girassol lp-bf-g']); ?>
                    <p class="lp-bf-tag"><b><?= e($promocao['nome']) ?></b> · por tempo limitado</p>
                    <p class="lp-rotulo">Entre para o Cante &amp; Encante</p>
                    <p class="lp-bf-precos"><span class="lp-sr">Preço anterior: <?= e($promocao['de']) ?>; preço de
                        <?= e($promocao['nome']) ?>: <?= e($promocao['por']) ?>.</span><span class="lp-bf-de"
                        aria-hidden="true">de <s><?= e($promocao['de']) ?></s></span><span class="lp-bf-por"
                        aria-hidden="true">por <strong><?= e($promocao['por']) ?></strong></span></p>
                    <p class="lp-bf-desconto"><?= e($promocao['desconto']) ?> de desconto</p>
                    <p class="lp-bf-parcela">ou <?= e($promocao['parcela']) ?> sem juros · Pix ou cartão de crédito</p>
                    <a class="lp-btn lp-btn-yellow" <?= $cta ?>>Quero entrar no Cante &amp; Encante</a>
                </div>
            </div>
        <?php else: ?>
            <div class="lp-cartao lp-rv">
                <p class="lp-rotulo">Entre para o Cante &amp; Encante</p>
                <p class="lp-preco"><?= e($preco) ?></p>
                <p class="lp-dim" style="margin:0">Pix ou cartão de crédito</p>
                <a class="lp-btn lp-btn-primary" <?= $cta ?>>Quero entrar no Cante &amp; Encante</a>
            </div>
        <?php endif; ?>
        <div class="lp-garantia lp-rv">
            <span class="lp-selo-g"><span><b>7</b>dias de garantia</span></span>
            <p>Você tem 7 dias para entrar, assistir às aulas e ver se o Cante &amp; Encante faz sentido para você. Se não
                fizer, pede o reembolso pela própria plataforma.</p>
        </div>
    </div>
</section>

<!-- 12 · FAQ -->
<section class="lp-sec lp-s-branco">
    <div class="lp-wrap">
        <h2 class="lp-rv" style="text-align:center">Perguntas <em>frequentes</em></h2>
        <div class="lp-faq" id="lp-faq">
            <?php
            // Respostas em texto puro (escapadas), exceto o certificado, que pode trazer o selo de pendência.
            $faq = [
                ['Preciso saber cantar?', e('Não. Você vai usar a sua voz do jeito que ela é. Muitas músicas do curso são '
                    . 'cantigas que as crianças já conhecem e cantam junto com você.')],
                ['Preciso tocar algum instrumento?', e('Não. A Lelê não toca violão. O curso mostra como usar a voz, o corpo '
                    . 'e instrumentos simples, alguns feitos com material reaproveitado.')],
                ['Preciso ler partitura?', e('Não. Os materiais trazem as letras com as cifras, para quem quiser tocar ou '
                    . 'pedir ajuda a alguém que toque. Para cantar, a letra basta.')],
                ['Serve para bebês e crianças bem pequenas?', e('Sim. Muitas crianças entram na história primeiro pela música '
                    . 'e pelo ritmo, antes mesmo da palavra.')],
                ['E para contar histórias para adultos?', e('Sim. Há um ritual de começo pensado para adultos, e brincadeiras '
                    . 'como a Sucolândia funcionam com adolescentes e adultos.')],
                ['Posso usar as músicas nas minhas contações?', e('As cantigas de roda são de domínio público. A música da '
                    . 'Lagarta foi liberada pelo Kleyton para os alunos. Quando usar músicas de outros autores, cite quem '
                    . 'compôs; em apresentações pagas, respeite os direitos autorais.')],
                ['Por quanto tempo terei acesso?', e('Por 1 ano.')],
                ['Tem certificado?', $ouPendente($certificado, 'confirmar certificado')],
                ['Já comprei o Conte&Encante. Preciso comprar este?', e('Não. O Cante & Encante já está incluído como bônus '
                    . 'no Conte&Encante, na sua área de membros.')],
                ['Como funciona a garantia?', e('Você tem 7 dias para conhecer o curso. Se não fizer sentido para você, pede '
                    . 'o reembolso pela plataforma.')],
            ];
            foreach ($faq as $i => [$pergunta, $resposta]):
                $n = $i + 1;
                $aberta = $n === 1;
            ?>
                <div class="lp-faq-item"><h3><button class="lp-faq-btn" type="button" aria-expanded="<?= $aberta ? 'true' : 'false' ?>"
                    aria-controls="lp-faq<?= $n ?>" id="lp-faqb<?= $n ?>"><?= e($pergunta) ?><svg width="22" height="22"
                    aria-hidden="true" focusable="false"><use href="#chev-sym"></use></svg></button></h3><div
                    class="lp-faq-resp<?= $aberta ? ' on' : '' ?>" id="lp-faq<?= $n ?>" role="region"
                    aria-labelledby="lp-faqb<?= $n ?>"><p><?= $resposta ?></p></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 13 · Fechamento -->
<section class="lp-sec lp-s-creme">
    <?php girassol_ref(['tamanho' => 200, 'classe' => 'lp-girassol lp-deco lp-gira', 'estilo' => 'right:-60px;top:-60px;opacity:.3']); ?>
    <div class="lp-wrap">
        <div class="lp-fim-grid">
            <figure class="lp-foto lp-fim-foto lp-rv"><img src="/assets/img/cante/estudio-lele-sombrinha-fundo-azul-vertical.jpg"
                alt="Lelê, em fundo azul, olha para cima sorrindo, segurando uma sombrinha de frevo colorida"
                width="1000" height="1500" loading="lazy" style="object-position:50% 25%"></figure>
            <div class="lp-fim-txt lp-rv">
                <p>Uma história pode caber num livro, numa caixa de sapato, numa garrafa com feijão… ou numa música que as
                    crianças vão cantar no caminho de casa.</p>
                <p class="lp-vem">Vem cantar comigo?</p>
                <a class="lp-btn lp-btn-primary" <?= $cta ?>>Quero cantar e encantar</a>
            </div>
        </div>
    </div>
</section>
</main>

<!-- 14 · Nota para alunos do Conte&Encante -->
<aside class="lp-alunos" aria-label="Alunos do Conte&amp;Encante">
    <div class="lp-wrap">
        <p>Já é aluna ou aluno do Conte&amp;Encante? O Cante &amp; Encante já está na sua área de membros, como bônus.</p>
        <p>Quer aprofundar a técnica de contar histórias? <a href="/conte-e-encante">Conheça o Conte&amp;Encante</a></p>
    </div>
</aside>

<footer class="lp-rodape">
    <div class="lp-wrap">
        <img src="/assets/img/logo-branca.png" alt="Conta Lelê" width="213" height="156" loading="lazy">
        <div class="lp-links">
            <a href="<?= e($igRodape) ?>" target="_blank" rel="noopener">Instagram</a>
            <a href="<?= e($ytRodape) ?>" target="_blank" rel="noopener">YouTube</a>
            <a href="mailto:<?= e($emailRodape) ?>"><?= e($emailRodape) ?></a>
        </div>
        <small>&copy; <?= date('Y') ?> Conta Lelê</small>
    </div>
</footer>
