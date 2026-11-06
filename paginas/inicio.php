<?php
/**
 * Página inicial do site Conta Lelê.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

$conteudo = require CL_RAIZ . '/includes/conteudo.php';
require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/girassol.php';
require_once CL_RAIZ . '/includes/cursos.php';
require_once CL_RAIZ . '/includes/cursos-cartoes.php';

$cssExtra        = ['/assets/css/cursos.css'];
$cursos          = cursos_publicados();
$cordeisDestaque = array_slice(listar_cordeis(), 0, 3);
$depoimentos     = listar_depoimentos();

$seo['titulo']    = 'Conta Lelê — Contação de histórias, cordéis e cursos';
$seo['descricao'] = 'A Lelê conta e escreve histórias para escolas, eventos e famílias, '
    . 'e ensina a contar nos cursos online Conte&Encante e Cante & Encante.';
$seo['json_ld']   = json_ld_pessoa($seo['url_base']);

// Depoimento para sobrepor no hero (pega o primeiro disponível).
$depoHero = $depoimentos[0] ?? null;

// Tons rotativos para os cards de "Para quem"
$tonsCards = ['cl-tom-amarelo', 'cl-tom-laranja', 'cl-tom-cafe', 'cl-tom-creme', 'cl-tom-amarelo', 'cl-tom-laranja'];
?>

<!-- HERO ----------------------------------------------------------- -->
<section class="cl-hero cl-hero--cursos">
    <div class="cl-conteudo cl-hero__interno">
        <div class="cl-hero__copy">
            <p class="cl-eyebrow">Cursos de contação de histórias</p>
            <h1 class="cl-titulo-hero">Oi, eu sou a <span class="cl-em">Lelê</span>. Vem aprender a contar
                histórias comigo.</h1>
            <p class="cl-hero__sub">Cursos online para professoras, mediadores de leitura e famílias, com o que
                eu aprendi em mais de 20 anos contando histórias.</p>
            <div class="cl-hero__ctas">
                <a class="cl-btn cl-btn-primary cl-btn-lg" href="/cursos">Ver os cursos</a>
                <a class="cl-btn cl-btn-ghost cl-btn-lg" href="/sobre">Conhecer a Lelê</a>
            </div>
        </div>

        <div class="cl-hero__foto-wrap">
            <img class="cl-hero__foto" src="/assets/img/lele-hero.jpg"
                 alt="Lelê sorri e acena para a câmera, com flores laranja no cabelo, diante de um fundo azul"
                 width="1600" height="1066" fetchpriority="high">

            <?php if ($depoHero): ?>
            <figure class="cl-hero__depo">
                <div class="cl-hero__depo__estrelas" aria-label="5 estrelas">★★★★★</div>
                <blockquote style="margin:0">"<?= e(mb_strimwidth($depoHero['texto'], 0, 130, '…')) ?>"</blockquote>
                <figcaption class="cl-hero__depo__autor"><?= e($depoHero['autor']) ?></figcaption>
            </figure>
            <?php endif; ?>

            <span class="cl-hero__girassol" aria-hidden="true">
                <?php girassol(['tamanho' => 140, 'gira' => true]); ?>
            </span>
        </div>

        <?php if ($cursos): ?>
            <div class="cl-hero__cursos">
                <h2>Os cursos</h2>
                <div class="cl-mini-lista">
                    <?php foreach ($cursos as $curso) { curso_mini($curso); } ?>
                </div>
                <a class="cl-hero__todos" href="/cursos">Comparar os cursos<?= curso_icone('seta') ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- O que a Lelê faz — cards coloridos ----------------------------- -->
<section class="cl-secao cl-secao--alt">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">O que a Lelê faz</p>
        <h2 class="cl-secao__titulo">Histórias, cordéis e <span class="cl-em">cursos</span>.</h2>
        <p class="cl-secao__sub">Um trabalho que mistura palco, palavra, rima e formação. Escolha por onde começar.</p>

        <div class="cl-grade cl-grade--3 cl-stagger">
            <?php
            $servicos = array_slice($conteudo['servicos'], 0, 6);
            foreach ($servicos as $i => $s):
                $tom = $tonsCards[$i % count($tonsCards)];
            ?>
                <article class="cl-card-tom <?= $tom ?>">
                    <span class="cl-card-tom__badge"><?= e(strtoupper(explode(' ', $s['titulo'])[0])) ?></span>
                    <h3><?= e($s['titulo']) ?></h3>
                    <p><?= e(mb_strimwidth($s['texto'], 0, 110, '…')) ?></p>
                    <span class="cl-card-tom__girassol" aria-hidden="true">
                        <?php girassol(['tamanho' => 130]); ?>
                    </span>
                    <a class="cl-card-tom__seta" href="/contacao" aria-label="Saiba mais sobre <?= e($s['titulo']) ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="13 6 19 12 13 18"/>
                        </svg>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="cl-mt-4"><a class="cl-btn cl-btn-primary" href="/contacao">Ver tudo sobre contação</a></p>
    </div>
</section>

<?php if ($cordeisDestaque): ?>
<!-- Cordéis em destaque -------------------------------------------- -->
<section class="cl-secao cl-secao--alt">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Literatura de cordel</p>
        <h2 class="cl-secao__titulo">Cordéis da <span class="cl-em">Lelê</span></h2>
        <p class="cl-secao__sub">Histórias rimadas, com aquele tempero especial — para ler, ouvir e contar de novo.</p>
        <div class="cl-grade cl-grade--3 cl-stagger">
            <?php foreach ($cordeisDestaque as $c): ?>
                <article class="cl-card cl-card-midia">
                    <?php if ($c['imagem_capa']): ?>
                        <img src="/assets/img/<?= e($c['imagem_capa']) ?>" alt="Capa de <?= e($c['titulo']) ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="cl-card-midia__corpo">
                        <h3><?= e($c['titulo']) ?></h3>
                        <p><?= e(mb_strimwidth((string)($c['sinopse'] ?? ''), 0, 120, '…')) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="cl-mt-4"><a class="cl-btn cl-btn-ghost" href="/cordeis">Ver todos os cordéis</a></p>
    </div>
</section>
<?php endif; ?>

<?php if ($depoimentos): ?>
<!-- Depoimentos ---------------------------------------------------- -->
<section class="cl-secao">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">O que dizem</p>
        <h2 class="cl-secao__titulo">Quem já viveu uma <span class="cl-em">história</span></h2>
        <div class="cl-grade cl-grade--3 cl-stagger">
            <?php foreach ($depoimentos as $d): ?>
                <figure class="cl-depo" style="margin:0">
                    <blockquote>"<?= e($d['texto']) ?>"</blockquote>
                    <figcaption class="cl-depo__autor">
                        <?php if (!empty($d['foto'])): ?>
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
