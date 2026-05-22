<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/funcoes.php';
require __DIR__ . '/../includes/repositorio.php';

// A validação de nome de tabela é pura (não toca o banco) e pode ser testada.
$erro = null;
try {
    listar_publicados('tabela_invalida; DROP TABLE x');
} catch (InvalidArgumentException $e) {
    $erro = $e;
}
afirmar($erro instanceof InvalidArgumentException, 'listar_publicados() rejeita nome de tabela fora da lista branca');

afirmar(function_exists('listar_cordeis'), 'listar_cordeis() existe');
afirmar(function_exists('listar_ebooks'), 'listar_ebooks() existe');
afirmar(function_exists('listar_noticias'), 'listar_noticias() existe');
afirmar(function_exists('listar_depoimentos'), 'listar_depoimentos() existe');
afirmar(function_exists('listar_festivais'), 'listar_festivais() existe');
afirmar(function_exists('listar_premios'), 'listar_premios() existe');
afirmar(function_exists('youtube_id'), 'youtube_id() existe');

// youtube_id() é pura — extrai o ID de várias formas de URL do YouTube.
afirmar_igual('2s-SudIPNXs', youtube_id('https://www.youtube.com/watch?v=2s-SudIPNXs'), 'youtube_id() de URL watch');
afirmar_igual('abc12345678', youtube_id('https://youtu.be/abc12345678'), 'youtube_id() de URL curta');
afirmar_igual('', youtube_id('texto sem url'), 'youtube_id() devolve vazio quando não há ID');
