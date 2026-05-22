<?php
/**
 * Cabeçalho compartilhado do site Conta Lelê.
 * Thiago Mourão — https://github.com/MouraoBSB
 *
 * @var array $seo  Dados de SEO (definidos em index.php, ajustáveis na página).
 */

declare(strict_types=1);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php seo_render($seo); ?>
    <?php if (!empty($seo['json_ld'])) { json_ld($seo['json_ld']); } ?>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Fredoka+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<a class="cl-skip" href="#conteudo">Pular para o conteúdo</a>
<header class="cl-cabecalho">
    <div class="cl-conteudo cl-cabecalho__interno">
        <a class="cl-logo" href="/" aria-label="Conta Lelê — página inicial">
            <img src="/assets/img/logo.png" alt="Conta Lelê">
        </a>
        <button class="cl-menu-btn" type="button" aria-expanded="false" aria-label="Abrir menu">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <nav class="cl-nav" aria-label="Navegação principal">
            <a href="/">Início</a>
            <a href="/sobre">A Lelê</a>
            <a href="/contacao">Contação de Histórias</a>
            <a href="/cordeis">Cordéis</a>
            <a href="/livros">Livros</a>
            <a href="/contato">Contato</a>
            <a class="cl-btn cl-btn-primary" href="/cursos">Cursos</a>
        </nav>
    </div>
</header>
<main id="conteudo">
