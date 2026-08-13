<?php
/**
 * Cabeçalho das landings (páginas de venda). Topo mínimo, sem navegação:
 * a única saída da página é o botão de compra.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 *
 * @var array  $seo       Dados de SEO (definidos em index.php, ajustáveis na página).
 * @var array  $cssExtra  Folhas de estilo específicas da página.
 * @var array  $ctaTopo   ['href' => string, 'texto' => string] do botão do topo.
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/girassol.php';

$cssExtra = $cssExtra ?? [];
$ctaTopo  = $ctaTopo  ?? ['href' => '#', 'texto' => 'Quero entrar'];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffdc3a">
    <?php seo_render($seo); ?>
    <?php if (!empty($seo['json_ld'])) { json_ld($seo['json_ld']); } ?>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Fredoka+One&family=Pacifico&display=swap" rel="stylesheet">
    <?php
    // Cache-busting: a URL muda quando o arquivo muda, forçando CDN e browser a buscar a versão nova.
    $vTokens = @filemtime(CL_RAIZ . '/assets/css/brand-tokens.css') ?: time();
    ?>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css?v=<?= $vTokens ?>">
    <?php foreach ($cssExtra as $css): ?>
        <link rel="stylesheet" href="<?= e($css) ?>?v=<?= @filemtime(CL_RAIZ . $css) ?: time() ?>">
    <?php endforeach; ?>
</head>
<body class="cl-landing">
<a class="cl-skip" href="#conteudo">Pular para o conteúdo</a>
<?php girassol_sprite(); ?>

<header class="lp-topo">
    <div class="lp-largura lp-topo__interno">
        <a class="lp-topo__logo" href="/" aria-label="Conta Lelê — página inicial">
            <img src="/assets/img/logo.png" alt="Conta Lelê" width="150" height="44">
        </a>
        <a class="lp-btn lp-btn--amarelo lp-btn--sm" href="<?= e($ctaTopo['href']) ?>"<?= $ctaTopo['externo'] ?? false ? ' target="_blank" rel="noopener"' : '' ?>>
            <?= e($ctaTopo['texto']) ?>
        </a>
    </div>
</header>

<div class="lp-barra" id="lp-barra" hidden>
    <div class="lp-largura lp-barra__interno">
        <span class="lp-barra__nome">Conte<em>&amp;</em>Encante</span>
        <a class="lp-btn lp-btn--primario lp-btn--sm" href="<?= e($ctaTopo['href']) ?>"<?= $ctaTopo['externo'] ?? false ? ' target="_blank" rel="noopener"' : '' ?>>
            <?= e($ctaTopo['texto']) ?>
        </a>
    </div>
</div>

<main id="conteudo">
