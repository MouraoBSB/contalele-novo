<?php
/**
 * Metadados dos recursos de conteúdo do painel.
 * Cada recurso define a tabela, os rótulos e os campos do formulário.
 * O motor de CRUD (admin/conteudo.php) é dirigido por este arquivo.
 *
 * Tipos de campo: 'texto', 'area', 'numero', 'data', 'imagem', 'pdf'.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

return [
    'cordeis' => [
        'singular' => 'Cordel',
        'plural'   => 'Cordéis',
        'tabela'   => 'cordeis',
        'rotulo'   => 'titulo',
        'slug'     => true,
        'campos'   => [
            'titulo'        => ['rotulo' => 'Título',        'tipo' => 'texto',  'obrigatorio' => true],
            'sinopse'       => ['rotulo' => 'Sinopse',       'tipo' => 'area',   'obrigatorio' => true],
            'video_youtube' => ['rotulo' => 'Vídeo (URL do YouTube)', 'tipo' => 'texto'],
            'imagem_capa'   => ['rotulo' => 'Capa',          'tipo' => 'imagem'],
        ],
    ],
    'ebooks' => [
        'singular' => 'Livro',
        'plural'   => 'Livros',
        'tabela'   => 'ebooks',
        'rotulo'   => 'titulo',
        'slug'     => true,
        'campos'   => [
            'titulo'      => ['rotulo' => 'Título',  'tipo' => 'texto', 'obrigatorio' => true],
            'sinopse'     => ['rotulo' => 'Sinopse', 'tipo' => 'area',  'obrigatorio' => true],
            'arquivo_pdf' => ['rotulo' => 'Arquivo PDF', 'tipo' => 'pdf'],
            'imagem_capa' => ['rotulo' => 'Capa',    'tipo' => 'imagem'],
        ],
    ],
    'noticias' => [
        'singular' => 'Notícia',
        'plural'   => 'Notícias',
        'tabela'   => 'noticias',
        'rotulo'   => 'titulo',
        'slug'     => false,
        'campos'   => [
            'titulo'          => ['rotulo' => 'Título',  'tipo' => 'texto', 'obrigatorio' => true],
            'veiculo'         => ['rotulo' => 'Veículo', 'tipo' => 'texto'],
            'url'             => ['rotulo' => 'Link',    'tipo' => 'texto', 'obrigatorio' => true],
            'data_publicacao' => ['rotulo' => 'Data',    'tipo' => 'data'],
            'imagem'          => ['rotulo' => 'Imagem',  'tipo' => 'imagem'],
        ],
    ],
    'depoimentos' => [
        'singular' => 'Depoimento',
        'plural'   => 'Depoimentos',
        'tabela'   => 'depoimentos',
        'rotulo'   => 'autor',
        'slug'     => false,
        'campos'   => [
            'autor' => ['rotulo' => 'Autor', 'tipo' => 'texto', 'obrigatorio' => true],
            'texto' => ['rotulo' => 'Depoimento', 'tipo' => 'area', 'obrigatorio' => true],
            'foto'  => ['rotulo' => 'Foto', 'tipo' => 'imagem'],
        ],
    ],
    'festivais' => [
        'singular' => 'Festival',
        'plural'   => 'Festivais',
        'tabela'   => 'festivais',
        'rotulo'   => 'titulo',
        'slug'     => false,
        'campos'   => [
            'titulo'    => ['rotulo' => 'Título',    'tipo' => 'texto', 'obrigatorio' => true],
            'descricao' => ['rotulo' => 'Descrição', 'tipo' => 'area',  'obrigatorio' => true],
            'ano'       => ['rotulo' => 'Ano',       'tipo' => 'numero'],
            'videos'    => ['rotulo' => 'Vídeos (uma URL por linha)', 'tipo' => 'area'],
            'imagem'    => ['rotulo' => 'Imagem',    'tipo' => 'imagem'],
        ],
    ],
    'premios' => [
        'singular' => 'Prêmio',
        'plural'   => 'Prêmios',
        'tabela'   => 'premios',
        'rotulo'   => 'titulo',
        'slug'     => false,
        'campos'   => [
            'titulo' => ['rotulo' => 'Título', 'tipo' => 'texto', 'obrigatorio' => true],
            'ano'    => ['rotulo' => 'Ano',    'tipo' => 'numero'],
        ],
    ],
];
