<?php
/**
 * Página "Festivais & Imprensa" — festivais, prêmios e publicações.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$festivais = listar_festivais();
$premios   = listar_premios();
$noticias  = listar_noticias();

$seo['titulo']    = 'Festivais & Imprensa — a trajetória da Lelê | Conta Lelê';
$seo['descricao'] = 'Festivais literários, prêmios e publicações na imprensa: '
    . 'a trajetória da Conta Lelê.';
?>

<section class="cl-hero-simples">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Trajetória</p>
        <h1>Festivais &amp; <span class="cl-em">Imprensa</span></h1>
        <p>Onde a Lelê já contou histórias e o que dizem sobre o trabalho dela.</p>
    </div>
</section>

<?php if ($festivais): ?>
<section class="cl-secao" style="padding-top:48px">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Participações</p>
        <h2 class="cl-secao__titulo">Festivais</h2>
        <div class="cl-grade cl-grade--3 cl-stagger cl-lista--festivais">
            <?php foreach ($festivais as $f): ?>
                <article class="cl-card">
                    <h3>
                        <?= e($f['titulo']) ?>
                        <?php if (!empty($f['ano'])): ?>
                            <small style="color:var(--cl-ink-dim);font-weight:700"> · <?= e((string) $f['ano']) ?></small>
                        <?php endif; ?>
                    </h3>
                    <p><?= e($f['descricao']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($premios): ?>
<section class="cl-secao cl-secao--alt">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Reconhecimento</p>
        <h2 class="cl-secao__titulo">Prêmios</h2>
        <div class="cl-grade cl-grade--3 cl-stagger">
            <?php foreach ($premios as $p): ?>
                <article class="cl-card">
                    <h3><?= e($p['titulo']) ?></h3>
                    <?php if (!empty($p['ano'])): ?>
                        <p><?= e((string) $p['ano']) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($noticias): ?>
<section class="cl-secao">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Na mídia</p>
        <h2 class="cl-secao__titulo">Imprensa</h2>
        <ul class="cl-lista">
            <?php foreach ($noticias as $n): ?>
                <li class="cl-card">
                    <a href="<?= e($n['url']) ?>" target="_blank" rel="noopener"><?= e($n['titulo']) ?></a>
                    <?php if (!empty($n['veiculo'])): ?>
                        <span style="color:var(--cl-ink-dim)"> — <?= e($n['veiculo']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>
