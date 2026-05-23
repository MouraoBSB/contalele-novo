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

$cordeisDestaque = array_slice(listar_cordeis(), 0, 3);
$depoimentos     = listar_depoimentos();
$whats           = configuracao('whatsapp', '5561991938603');

$seo['titulo']    = 'Conta Lelê — Contação de histórias, cordéis e cursos';
$seo['descricao'] = 'A Lelê conta e escreve histórias de verdade para escolas, '
    . 'eventos e famílias. Conheça o trabalho, os cordéis e os livros.';
$seo['json_ld']   = json_ld_pessoa($seo['url_base']);

// Depoimento para sobrepor no hero (pega o primeiro disponível).
$depoHero = $depoimentos[0] ?? null;

// Tons rotativos para os cards de "Para quem"
$tonsCards = ['cl-tom-amarelo', 'cl-tom-laranja', 'cl-tom-cafe', 'cl-tom-creme', 'cl-tom-amarelo', 'cl-tom-laranja'];
?>

<!-- HERO ----------------------------------------------------------- -->
<section class="cl-hero">
    <div class="cl-conteudo cl-hero__interno">
        <div class="cl-hero__copy">
            <p class="cl-eyebrow">Plataforma de contação de histórias</p>
            <h1 class="cl-titulo-hero">Oi, eu sou a <span class="cl-em">Lelê</span><br>
                ...e conto <span class="cl-em">histórias</span><br>de verdade.</h1>
            <p class="cl-hero__sub"><?= e($conteudo['hero_sub']) ?></p>
            <div class="cl-hero__ctas">
                <a class="cl-btn cl-btn-primary cl-btn-lg" href="/contacao">Ver o trabalho</a>
                <a class="cl-btn cl-btn-ghost cl-btn-lg" href="https://www.youtube.com/c/ContaLel%C3%AA" target="_blank" rel="noopener">
                    <svg class="cl-icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polygon points="6 4 20 12 6 20 6 4" fill="currentColor"/>
                    </svg>
                    Assistir a uma história
                </a>
            </div>
        </div>

        <div class="cl-hero__foto-wrap">
            <img class="cl-hero__foto" src="/assets/img/lele-hero.jpg"
                 alt="A Lelê, contadora de histórias" width="640" height="800">

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

<!-- Faixa café — toda sexta no canal ------------------------------- -->
<section class="cl-secao" style="padding-top:0">
    <div class="cl-conteudo">
        <div class="cl-faixa" data-cl-proxima-sexta>
            <div class="cl-faixa__interno">
                <div>
                    <p class="cl-eyebrow">Ao vivo toda sexta · 16:00</p>
                    <h2 class="cl-secao__titulo">Toda sexta tem uma <span class="cl-em">história nova</span> no canal.</h2>
                    <p>Histórias originais e clássicas reinventadas, gravadas com olho-no-olho para as crianças — e gratuitas. Inscreva-se para receber o lembrete.</p>
                    <div class="cl-faixa__ctas">
                        <a class="cl-btn cl-btn-yellow" href="https://www.youtube.com/c/ContaLel%C3%AA?sub_confirmation=1" target="_blank" rel="noopener">
                            <svg class="cl-icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.7 21a2 2 0 0 1-3.4 0"/>
                            </svg>
                            Me avisar
                        </a>
                        <a class="cl-btn cl-btn-ghost" href="https://www.youtube.com/c/ContaLel%C3%AA" target="_blank" rel="noopener">Ver o canal</a>
                    </div>
                </div>

                <div class="cl-contador" aria-live="polite">
                    <p class="cl-contador__titulo">Próxima história em</p>
                    <div class="cl-contador__grid">
                        <div>
                            <div class="cl-contador__valor" data-cl-dias>—</div>
                            <div class="cl-contador__rotulo">dias</div>
                        </div>
                        <div>
                            <div class="cl-contador__valor" data-cl-horas>—</div>
                            <div class="cl-contador__rotulo">h</div>
                        </div>
                        <div>
                            <div class="cl-contador__valor" data-cl-min>—</div>
                            <div class="cl-contador__rotulo">m</div>
                        </div>
                        <div>
                            <div class="cl-contador__valor" data-cl-seg>—</div>
                            <div class="cl-contador__rotulo">s</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
