<?php
/**
 * Callback do login com Google do admin. Regra: só vincula a admin já existente, nunca cria.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require CL_RAIZ . '/includes/repositorio.php';
require CL_RAIZ . '/includes/google_oauth.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
iniciar_sessao();

$erro = null;
$code  = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');
$stateSessao = (string) ($_SESSION['google_state'] ?? '');
unset($_SESSION['google_state']);

if (!google_configurado()) {
    $erro = 'O login com Google não está disponível.';
} elseif ($code === '' || $state === '' || !hash_equals($stateSessao, $state)) {
    $erro = 'Falha na verificação de segurança. Tente novamente.';
} else {
    $base = rtrim($config['site']['url'], '/');
    $redirectUri = $base . '/admin/google-callback.php';
    $tokens = google_trocar_codigo($code, $redirectUri);
    $perfil = $tokens !== null ? google_perfil($tokens['access_token']) : null;

    if ($perfil === null || empty($perfil['sub']) || empty($perfil['email'])
        || ($perfil['email_verified'] ?? false) !== true) {
        $erro = 'Não foi possível confirmar a sua conta Google.';
    } else {
        $sub   = (string) $perfil['sub'];
        $email = mb_strtolower(trim((string) $perfil['email']));

        // 1) admin já vinculado a esse google_id?
        $st = bd()->prepare('SELECT * FROM usuarios_admin WHERE google_id = ? AND ativo = 1 LIMIT 1');
        $st->execute([$sub]);
        $a = $st->fetch();

        // 2) senão, admin com esse e-mail? (vincula)
        if (!$a) {
            $st = bd()->prepare('SELECT * FROM usuarios_admin WHERE email = ? AND ativo = 1 LIMIT 1');
            $st->execute([$email]);
            $a = $st->fetch();
            if ($a) {
                bd()->prepare('UPDATE usuarios_admin SET google_id = ? WHERE id = ?')
                    ->execute([$sub, (int) $a['id']]);
            }
        }

        if ($a) {
            bd()->prepare(
                'UPDATE usuarios_admin SET tentativas_login = 0, bloqueado_ate = NULL, ultimo_acesso = NOW() WHERE id = ?'
            )->execute([(int) $a['id']]);
            logar($a);
            header('Location: /admin/');
            exit;
        }
        $erro = 'Esta conta Google não tem acesso ao painel.';
    }
}

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Entrar com Google — Painel Conta Lelê</title>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<main class="adm-conteudo" style="max-width:380px">
    <h1>Não deu certo</h1>
    <p class="adm-aviso adm-aviso--erro"><?= e($erro ?? 'Erro inesperado.') ?></p>
    <p><a href="/admin/login.php">Voltar ao login</a></p>
</main>
</body>
</html>
