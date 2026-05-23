<?php
/**
 * Página "Contação de Histórias" — serviços, públicos e diferenciais.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

$conteudo = require CL_RAIZ . '/includes/conteudo.php';
require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/girassol.php';

$whats = configuracao('whatsapp', '5561991938603');

$seo['titulo']    = 'Contação de Histórias — contrate a Lelê | Conta Lelê';
$seo['descricao'] = 'Contação de histórias para escolas, eventos, empresas e famílias. '
    . 'Conheça os serviços da Lelê e para quem a contação de histórias encanta.';

$tonsCards = ['cl-tom-amarelo', 'cl-tom-laranja', 'cl-tom-cafe', 'cl-tom-creme', 'cl-tom-amarelo', 'cl-tom-laranja'];
?>

<section class="cl-hero">
    <div class="cl-conteudo cl-hero__interno">
        <div class="cl-hero__copy">
            <p class="cl-eyebrow">Contação de Histórias</p>
            <h1 class="cl-titulo-hero">Torne o seu evento <span class="cl-em">encantador</span>.</h1>
            <p class="cl-hero__sub">A Lelê apresenta-se em escolas, festivais, livrarias,
                eventos e casas — de forma presencial ou online.</p>
            <div class="cl-hero__ctas">
                <a class="cl-btn cl-btn-primary cl-btn-lg" href="https://wa.me/<?= e($whats) ?>" target="_blank" rel="noopener">Pedir um orçamento</a>
                <a class="cl-btn cl-btn-ghost cl-btn-lg" href="/contato">Falar com a equipe</a>
            </div>
        </div>
        <div class="cl-hero__foto-wrap">
            <img class="cl-hero__foto" src="/assets/img/lele-contacao.jpg"
                 alt="A Lelê contando histórias" width="640" height="800" loading="lazy">
            <span class="cl-hero__girassol" aria-hidden="true">
                <?php girassol(['tamanho' => 130, 'gira' => true]); ?>
            </span>
        </div>
    </div>
</section>

<section class="cl-secao cl-secao--alt">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">O que a Lelê faz</p>
        <h2 class="cl-secao__titulo"><span class="cl-em">Serviços</span></h2>
        <div class="cl-grade cl-grade--3 cl-stagger">
            <?php foreach ($conteudo['servicos'] as $i => $s):
                $tom = $tonsCards[$i % count($tonsCards)]; ?>
                <article class="cl-card-tom <?= $tom ?>">
                    <span class="cl-card-tom__badge">Serviço</span>
                    <h3><?= e($s['titulo']) ?></h3>
                    <p><?= e(mb_strimwidth($s['texto'], 0, 130, '…')) ?></p>
                    <span class="cl-card-tom__girassol" aria-hidden="true">
                        <?php girassol(['tamanho' => 130]); ?>
                    </span>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="cl-secao">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Para quem</p>
        <h2 class="cl-secao__titulo">Para quem contar <span class="cl-em">histórias</span></h2>
        <div class="cl-grade cl-grade--3 cl-stagger">
            <?php foreach ($conteudo['publicos'] as $p): ?>
                <article class="cl-card">
                    <h3><?= e($p['titulo']) ?></h3>
                    <p><?= e($p['texto']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="cl-secao cl-secao--alt">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Por que a Lelê</p>
        <h2 class="cl-secao__titulo"><span class="cl-em">Diferenciais</span></h2>
        <div class="cl-grade cl-grade--3 cl-stagger">
            <?php foreach ($conteudo['diferenciais'] as $d): ?>
                <article class="cl-card">
                    <h3><?= e($d['titulo']) ?></h3>
                    <p><?= e($d['texto']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="cl-faixa cl-mt-4">
            <div class="cl-faixa__interno">
                <div>
                    <p class="cl-eyebrow">Vamos juntos?</p>
                    <h2 class="cl-secao__titulo">Vamos <span class="cl-em">encantar</span> o seu evento?</h2>
                    <p>Conte um pouco do que você imagina — a equipe da Lelê responde rapidinho.</p>
                    <div class="cl-faixa__ctas">
                        <a class="cl-btn cl-btn-yellow" href="/contato">Falar com a Lelê</a>
                        <a class="cl-btn cl-btn-ghost" href="https://wa.me/<?= e($whats) ?>" target="_blank" rel="noopener">WhatsApp</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
