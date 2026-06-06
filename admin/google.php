<?php
/**
 * Inicia o login com Google do admin (gera state e redireciona).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require CL_RAIZ . '/includes/google_oauth.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
iniciar_sessao();

if (!google_configurado()) {
    header('Location: /admin/login.php');
    exit;
}

$state = google_state();
$_SESSION['google_state'] = $state;
$base = rtrim($config['site']['url'], '/');
$redirectUri = $base . '/admin/google-callback.php';

header('Location: ' . google_url_autorizacao($redirectUri, $state));
exit;
