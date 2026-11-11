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
require_once CL_RAIZ . '/includes/depoimentos.php';

$cssExtra        = ['/assets/css/cursos.css', '/assets/css/depoimentos.css'];
$jsExtra         = ['/assets/js/carrossel.js', '/assets/js/depoimentos-video.js'];
$cursos          = cursos_publicados();
$cordeisDestaque = array_slice(listar_cordeis(), 0, 3);
$depoimentos     = listar_depoimentos();

// Depoimentos em vídeo: endereços resolvidos (uploads/ ou assets/) e versionados para o cache.
$depoimentosVideo = array_map(static function (array $d): array {
    $capa = trim((string) ($d['capa'] ?? ''));
    $d['url_video'] = com_versao(url_video((string) $d['video']));
    $d['url_capa']  = $capa !== '' ? com_versao(url_imagem($capa)) : '';
    $d['url_link']  = link_interno((string) ($d['link'] ?? ''));
    return $d;
}, depoimentos_com_video(listar_depoimentos_video()));
$variosVideos = count($depoimentosVideo) > 1;

$seo['titulo']    = 'Conta Lelê — Contação de histórias, cordéis e cursos';
$seo['descricao'] = 'A Lelê conta e escreve histórias para escolas, eventos e famílias, '
    . 'e ensina a contar nos cursos online Conte&Encante e Cante & Encante.';
$nosVideo = array_values(array_filter(array_map(
    static fn (array $d): ?array => json_ld_depoimento_video($d, $seo['url_base'], $d['url_video'], $d['url_capa']),
    $depoimentosVideo
)));
$seo['json_ld'] = $nosVideo
    ? json_ld_grafo([json_ld_pessoa($seo['url_base']), ...$nosVideo])
    : json_ld_pessoa($seo['url_base']);

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
                <blockquote style="margin:0">"<?= e(texto_resumido((string) $depoHero['texto'], 130)) ?>"</blockquote>
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
                        <img src="<?= e(url_imagem($c['imagem_capa'])) ?>" alt="Capa de <?= e($c['titulo']) ?>" loading="lazy">
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

<?php if ($depoimentosVideo): ?>
<!-- Depoimentos em vídeo (antes dos escritos) ---------------------- -->
<section class="cl-secao cl-secao--ink dv<?= $variosVideos ? '' : ' dv--um' ?>" aria-labelledby="dv-titulo">
    <?php girassol(['tamanho' => 150, 'classe' => 'dv-girassol']); ?>
    <div class="cl-conteudo">
        <div class="dv-topo">
            <p class="cl-eyebrow">Em vídeo</p>
            <h2 class="cl-secao__titulo" id="dv-titulo">Quem fez o curso <span class="cl-em">conta</span></h2>
        </div>

        <div class="dv-carrossel"<?= $variosVideos
            ? ' data-carrossel role="region" aria-roledescription="carrossel" aria-labelledby="dv-titulo"' : '' ?>>
            <ul class="dv-lista<?= $variosVideos ? ' dp-trilho' : '' ?>" id="dv-trilho"<?= $variosVideos ? ' tabindex="0"' : '' ?>>
                <?php foreach ($depoimentosVideo as $i => $d): ?>
                    <li class="dv-item<?= $variosVideos ? ' dp-item' : '' ?>">
                        <article class="dv-cartao" aria-labelledby="dv-nome-<?= $i ?>">
                            <div class="dv-video">
                                <video id="dv-video-<?= $i ?>" controls preload="none" playsinline width="720" height="1280"
                                    <?php if ($d['url_capa'] !== ''): ?>poster="<?= e($d['url_capa']) ?>"<?php endif; ?>
                                    aria-label="Depoimento em vídeo de <?= e($d['autor']) ?>, com legenda">
                                    <source src="<?= e($d['url_video']) ?>" type="video/mp4">
                                    Seu navegador não reproduz este vídeo.
                                    <a href="<?= e($d['url_video']) ?>">Baixe o depoimento</a>.
                                </video>
                                <button class="dv-play" type="button" data-dv-play="dv-video-<?= $i ?>" hidden
                                    aria-label="Assistir ao depoimento de <?= e($d['autor']) ?>"><?= curso_icone('play', 30) ?></button>
                            </div>
                            <div class="dv-corpo">
                                <?php if (trim((string) $d['destaque']) !== ''): ?>
                                    <p class="dv-frase"><?= e(trim((string) $d['destaque'])) ?></p>
                                <?php endif; ?>
                                <p class="dv-pessoa">
                                    <strong id="dv-nome-<?= $i ?>"><?= e($d['autor']) ?></strong>
                                    <?php if (trim((string) $d['descricao']) !== ''): ?>
                                        <span><?= e(trim((string) $d['descricao'])) ?></span>
                                    <?php endif; ?>
                                </p>
                                <?php if ($d['url_link'] !== ''): ?>
                                    <a class="cl-btn cl-btn-yellow dv-link" href="<?= e($d['url_link']) ?>">Conhecer o curso<?= curso_icone('seta') ?></a>
                                <?php endif; ?>
                                <details class="dv-transcricao">
                                    <summary>Ler a transcrição</summary>
                                    <?php foreach (texto_em_paragrafos((string) $d['transcricao']) as $paragrafo): ?>
                                        <p><?= e($paragrafo) ?></p>
                                    <?php endforeach; ?>
                                </details>
                            </div>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($variosVideos): ?>
                <div class="dp-controles" hidden>
                    <button class="dp-seta dp-seta--anterior" type="button" data-dp-ir="-1"
                        aria-controls="dv-trilho" aria-label="Vídeo anterior"><?= curso_icone('seta', 20) ?></button>
                    <div class="dp-pontos" aria-hidden="true"></div>
                    <button class="dp-seta" type="button" data-dp-ir="1"
                        aria-controls="dv-trilho" aria-label="Próximo vídeo"><?= curso_icone('seta', 20) ?></button>
                </div>
                <p class="cl-sr" aria-live="polite" data-dp-aviso></p>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($depoimentos): ?>
<!-- Depoimentos ---------------------------------------------------- -->
<section class="cl-secao dp" aria-labelledby="dp-titulo">
    <?php girassol(['tamanho' => 120, 'classe' => 'dp-girassol']); ?>
    <div class="cl-conteudo">
        <div class="dp-topo">
            <p class="cl-eyebrow">O que dizem</p>
            <h2 class="cl-secao__titulo" id="dp-titulo">Quem já viveu uma <span class="cl-em">história</span></h2>
        </div>

        <div class="dp-carrossel" data-carrossel role="region" aria-roledescription="carrossel"
            aria-labelledby="dp-titulo">
            <ul class="dp-trilho" id="dp-trilho" tabindex="0">
                <?php foreach ($depoimentos as $i => $d): ?>
                    <li class="dp-item">
                        <figure class="dp-cartao">
                            <figcaption class="dp-pessoa">
                                <?php if (!empty($d['foto'])): ?>
                                    <img src="<?= e(url_imagem($d['foto'])) ?>" alt="" width="56" height="56"
                                        loading="lazy" decoding="async">
                                <?php endif; ?>
                                <strong><?= e($d['autor']) ?></strong>
                                <span class="dp-aspas" aria-hidden="true">&ldquo;</span>
                            </figcaption>
                            <blockquote class="dp-texto" id="dp-texto-<?= $i ?>">
                                <?php foreach (texto_em_paragrafos((string) $d['texto']) as $paragrafo): ?>
                                    <p><?= e($paragrafo) ?></p>
                                <?php endforeach; ?>
                            </blockquote>
                        </figure>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="dp-controles" hidden>
                <button class="dp-seta dp-seta--anterior" type="button" data-dp-ir="-1"
                    aria-controls="dp-trilho" aria-label="Depoimento anterior"><?= curso_icone('seta', 20) ?></button>
                <div class="dp-pontos" aria-hidden="true"></div>
                <button class="dp-seta" type="button" data-dp-ir="1"
                    aria-controls="dp-trilho" aria-label="Próximo depoimento"><?= curso_icone('seta', 20) ?></button>
            </div>
            <p class="cl-sr" aria-live="polite" data-dp-aviso></p>
        </div>
    </div>
</section>
<?php endif; ?>
