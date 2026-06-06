<?php
/**
 * Inicia o login com Google do cursista (gera state e redireciona ao Google).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/google_oauth.php';

iniciar_sessao_site();

if (!google_configurado()) {
    header('Location: /entrar');
    exit;
}

$state = google_state();
$_SESSION['google_state'] = $state;
$base = rtrim($config['site']['url'], '/');
$redirectUri = $base . '/entrar/google/callback';

header('Location: ' . google_url_autorizacao($redirectUri, $state));
exit;
