<?php
/**
 * Cabeçalho do painel administrativo.
 * Thiago Mourão — https://github.com/MouraoBSB
 *
 * @var string $tituloPagina  Título da página (definido antes do require).
 */

declare(strict_types=1);

$u = usuario_logado();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($tituloPagina ?? 'Painel') ?> — Conta Lelê</title>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<header class="adm-topo">
    <strong>Conta Lelê</strong>
    <nav>
        <a href="/admin/">Painel</a>
        <a href="/admin/conteudo.php?recurso=cordeis">Cordéis</a>
        <a href="/admin/conteudo.php?recurso=ebooks">Livros</a>
        <a href="/admin/conteudo.php?recurso=noticias">Notícias</a>
        <a href="/admin/conteudo.php?recurso=depoimentos">Depoimentos</a>
        <a href="/admin/conteudo.php?recurso=festivais">Festivais</a>
        <a href="/admin/conteudo.php?recurso=premios">Prêmios</a>
        <a href="/admin/mensagens.php">Mensagens</a>
        <a href="/admin/configuracoes.php">Configurações</a>
    </nav>
    <a class="adm-sair" href="/admin/sair.php">Sair (<?= e($u['nome'] ?? '') ?>)</a>
</header>
<main class="adm-conteudo">
