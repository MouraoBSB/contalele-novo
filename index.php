<?php
/**
 * Front-controller do site Conta Lelê.
 * Resolve a rota e monta cabeçalho + página + rodapé.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', __DIR__);

// Servidor embutido do PHP (php -S): serve arquivos estáticos reais direto.
if (PHP_SAPI === 'cli-server') {
    $arquivoEstatico = CL_RAIZ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($arquivoEstatico)) {
        return false;
    }
}

$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/seo.php';

ativar_tratamento_erros($config['site']['ambiente']);

// Mapa de rotas: caminho da URL => arquivo em paginas/.
$rotas = [
    ''          => 'inicio',
    'sobre'     => 'sobre',
    'contacao'  => 'contacao',
    'cordeis'   => 'cordeis',
    'livros'    => 'livros',
    'festivais' => 'festivais',
    'cursos'    => 'cursos',
    'contato'   => 'contato',
];

$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$rota = trim(rawurldecode($caminho), '/');

$pagina = $rotas[$rota] ?? null;
if ($pagina === null || !is_file(CL_RAIZ . '/paginas/' . $pagina . '.php')) {
    http_response_code(404);
    $pagina = 'erro-404';
}

// Variáveis de SEO disponíveis para cada página (sobrescritas dentro dela).
$seo = seo_padrao($config['site']['url']);

require CL_RAIZ . '/includes/cabecalho.php';
require CL_RAIZ . '/paginas/' . $pagina . '.php';
require CL_RAIZ . '/includes/rodape.php';
