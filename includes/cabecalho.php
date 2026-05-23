<?php
/**
 * Cabeçalho compartilhado do site Conta Lelê.
 * Thiago Mourão — https://github.com/MouraoBSB
 *
 * @var array $seo  Dados de SEO (definidos em index.php, ajustáveis na página).
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/girassol.php';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fdf5d4">
    <?php seo_render($seo); ?>
    <?php if (!empty($seo['json_ld'])) { json_ld($seo['json_ld']); } ?>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Fredoka+One&display=swap" rel="stylesheet">
    <?php
    // Cache-busting: a URL muda quando o arquivo muda, forçando CDN e browser a buscar a versão nova.
    $vTokens = @filemtime(CL_RAIZ . '/assets/css/brand-tokens.css') ?: time();
    $vEstilo = @filemtime(CL_RAIZ . '/assets/css/style.css') ?: time();
    $vJs     = @filemtime(CL_RAIZ . '/assets/js/main.js') ?: time();
    ?>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css?v=<?= $vTokens ?>">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= $vEstilo ?>">
</head>
<body>
<a class="cl-skip" href="#conteudo">Pular para o conteúdo</a>
<header class="cl-cabecalho">
    <div class="cl-conteudo cl-cabecalho__interno">
        <a class="cl-logo" href="/" aria-label="Conta Lelê — página inicial">
            <img src="/assets/img/logo.png" alt="Conta Lelê">
        </a>
        <nav class="cl-nav" aria-label="Navegação principal">
            <a href="/">Início</a>
            <a href="/sobre">A Lelê</a>
            <a href="/contacao">Contação</a>
            <a href="/cordeis">Cordéis</a>
            <a href="/livros">Livros</a>
            <a href="/festivais">Festivais</a>
            <a href="/contato">Contato</a>
        </nav>
        <div class="cl-cabecalho__cta">
            <a class="cl-btn cl-btn-primary" href="/cursos">Quero contar histórias</a>
            <button class="cl-menu-btn" type="button" aria-expanded="false" aria-label="Abrir menu" aria-controls="cl-nav-principal">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <line x1="4" y1="7" x2="20" y2="7"/>
                    <line x1="4" y1="12" x2="20" y2="12"/>
                    <line x1="4" y1="17" x2="20" y2="17"/>
                </svg>
            </button>
        </div>
    </div>
</header>
<main id="conteudo">
