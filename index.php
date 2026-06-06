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
    // Contas de cursistas
    'criar-conta'            => 'criar-conta',
    'entrar'                 => 'entrar',
    'sair'                   => 'sair',
    'verificar-email'        => 'verificar-email',
    'recuperar-senha'        => 'recuperar-senha',
    'redefinir-senha'        => 'redefinir-senha',
    'minha-conta'            => 'minha-conta',
    'minha-conta/senha'      => 'conta-senha',
    'entrar/google'          => 'google-iniciar',
    'entrar/google/callback' => 'google-callback',
];

$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$rota = trim(rawurldecode($caminho), '/');

$pagina = $rotas[$rota] ?? null;
if ($pagina === null || !is_file(CL_RAIZ . '/paginas/' . $pagina . '.php')) {
    http_response_code(404);
    $pagina = 'erro-404';
}

// SEO padrão; a página pode sobrescrever $seo antes de seu HTML.
$seo = seo_padrao($config['site']['url']);

// Fase 1: avalia a página em buffer. A página define $seo (se quiser) e
// produz seu HTML de conteúdo, capturado em $conteudoPagina.
ob_start();
require CL_RAIZ . '/paginas/' . $pagina . '.php';
$conteudoPagina = ob_get_clean();

// Fase 2: monta a resposta — cabeçalho (com o $seo final) + conteúdo + rodapé.
ob_start();
require CL_RAIZ . '/includes/cabecalho.php';
echo $conteudoPagina;
require CL_RAIZ . '/includes/rodape.php';
ob_end_flush();
