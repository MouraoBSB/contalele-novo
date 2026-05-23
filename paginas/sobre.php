<?php
/**
 * Página "A Lelê" — biografia, propósito, prêmios e equipe.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

$conteudo = require CL_RAIZ . '/includes/conteudo.php';
require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/girassol.php';

$premios = listar_premios();

$seo['titulo']    = 'A Lelê — quem conta as histórias | Conta Lelê';
$seo['descricao'] = 'Conheça Letícia Rocha Mourão, a Lelê: contadora de histórias, '
    . 'cordelista e escritora de Planaltina-DF, com mais de 20 anos de palco.';
$seo['json_ld']   = json_ld_pessoa($seo['url_base']);
?>

<section class="cl-hero">
    <div class="cl-conteudo cl-hero__interno">
        <div class="cl-hero__copy">
            <p class="cl-eyebrow">A Lelê</p>
            <h1 class="cl-titulo-hero">Eu sou a <span class="cl-em">Lelê</span>,<br>
                e vou contar uma <span class="cl-em">história</span> pra você.</h1>
            <p class="cl-hero__sub" style="max-width:580px"><?= e($conteudo['bio']) ?></p>
        </div>
        <div class="cl-hero__foto-wrap">
            <img class="cl-hero__foto" src="/assets/img/lele-sobre.jpg"
                 alt="A Lelê" width="640" height="800" loading="lazy">
            <span class="cl-hero__girassol" aria-hidden="true">
                <?php girassol(['tamanho' => 130, 'gira' => true]); ?>
            </span>
        </div>
    </div>
</section>

<section class="cl-secao cl-secao--alt">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Por que contar histórias</p>
        <h2 class="cl-secao__titulo">O <span class="cl-em">propósito</span></h2>
        <p style="max-width:720px;font-size:16.5px;line-height:1.65"><?= e($conteudo['proposito']) ?></p>
    </div>
</section>

<?php if ($premios): ?>
<section class="cl-secao">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Reconhecimento</p>
        <h2 class="cl-secao__titulo"><span class="cl-em">Prêmios</span> e participações</h2>
        <ul class="cl-lista cl-grade cl-grade--3 cl-stagger" style="margin-top:24px">
            <?php foreach ($premios as $p): ?>
                <li class="cl-card">
                    <h3><?= e($p['titulo']) ?></h3>
                    <?php if (!empty($p['ano'])): ?>
                        <p><?= e((string) $p['ano']) ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<section class="cl-secao cl-secao--alt">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Quem faz acontecer</p>
        <h2 class="cl-secao__titulo">A <span class="cl-em">equipe</span></h2>
        <div class="cl-grade cl-grade--3 cl-stagger">
            <?php foreach ($conteudo['equipe'] as $m): ?>
                <article class="cl-card cl-card-midia">
                    <img src="/assets/img/<?= e($m['foto']) ?>" alt="<?= e($m['nome']) ?>" loading="lazy">
                    <div class="cl-card-midia__corpo">
                        <h3><?= e($m['nome']) ?></h3>
                        <p><?= e($m['funcao']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
