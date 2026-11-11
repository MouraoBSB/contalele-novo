<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/seo.php';

// json_ld_depoimento_video() — VideoObject do depoimento, com endereços absolutos e a transcrição
$depo = [
    'autor'       => 'Manuela',
    'descricao'   => 'Aluna do Conte&Encante',
    'destaque'    => 'Você pode contar histórias de formas simples.',
    'transcricao' => "Olá, meu nome é Manuela.\n\nVale a pena.",
    'criado_em'   => '2026-11-10 21:30:00',
];
$video = json_ld_depoimento_video($depo, 'https://contalele.com.br/', '/assets/video/depo.mp4?v=1', '/assets/img/capa.jpg?v=2');
afirmar_igual('VideoObject', $video['@type'] ?? null, 'json_ld_depoimento_video() descreve um VideoObject');
afirmar_igual('https://contalele.com.br/assets/video/depo.mp4?v=1', $video['contentUrl'] ?? null, 'json_ld_depoimento_video() usa endereço absoluto do vídeo');
afirmar_igual('https://contalele.com.br/assets/img/capa.jpg?v=2', $video['thumbnailUrl'] ?? null, 'json_ld_depoimento_video() usa endereço absoluto da capa');
afirmar_igual('2026-11-10', $video['uploadDate'] ?? null, 'json_ld_depoimento_video() usa só a data do cadastro');
afirmar_igual('Olá, meu nome é Manuela. Vale a pena.', $video['transcript'] ?? null, 'json_ld_depoimento_video() junta a transcrição numa linha');
afirmar_igual('Você pode contar histórias de formas simples.', $video['description'] ?? null, 'json_ld_depoimento_video() descreve com a frase em destaque');
afirmar(!isset($video['@context']), 'json_ld_depoimento_video() devolve um nó para o @graph, sem @context');
afirmar_igual(null, json_ld_depoimento_video($depo, 'https://contalele.com.br', '/assets/video/depo.mp4', ''), 'json_ld_depoimento_video() não descreve vídeo sem capa');

$semDestaque = json_ld_depoimento_video(['destaque' => ''] + $depo, 'https://contalele.com.br', '/v.mp4', '/c.jpg');
afirmar_igual('Olá, meu nome é Manuela. Vale a pena.', $semDestaque['description'] ?? null, 'json_ld_depoimento_video() usa o começo da transcrição sem frase em destaque');

// json_ld_grafo() — vários nós num bloco só, com um @context na raiz
$grafo = json_ld_grafo([['@context' => 'https://schema.org', '@type' => 'Person'], ['@type' => 'VideoObject']]);
afirmar_igual('https://schema.org', $grafo['@context'] ?? null, 'json_ld_grafo() põe o @context na raiz');
afirmar_igual([['@type' => 'Person'], ['@type' => 'VideoObject']], $grafo['@graph'] ?? null, 'json_ld_grafo() tira o @context de cada nó');
