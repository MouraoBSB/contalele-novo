<?php
/**
 * Login do painel administrativo, com bloqueio por tentativas.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
iniciar_sessao();

// Já logado? Vai para o painel.
if (usuario_logado() !== null) {
    header('Location: /admin/');
    exit;
}

$erro = null;
const MAX_TENTATIVAS = 5;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } else {
        $email = limpar_texto((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');

        $st = bd()->prepare('SELECT * FROM usuarios_admin WHERE email = ? AND ativo = 1');
        $st->execute([$email]);
        $u = $st->fetch();

        $bloqueado = $u && $u['bloqueado_ate'] !== null
            && strtotime((string) $u['bloqueado_ate']) > time();

        if ($bloqueado) {
            $erro = 'Conta temporariamente bloqueada por tentativas. Aguarde 15 minutos.';
        } elseif ($u && password_verify($senha, $u['senha_hash'])) {
            bd()->prepare(
                'UPDATE usuarios_admin SET tentativas_login = 0, bloqueado_ate = NULL,
                 ultimo_acesso = NOW() WHERE id = ?'
            )->execute([$u['id']]);
            logar($u);
            header('Location: /admin/');
            exit;
        } else {
            if ($u) {
                $tentativas = (int) $u['tentativas_login'] + 1;
                if ($tentativas >= MAX_TENTATIVAS) {
                    bd()->prepare(
                        'UPDATE usuarios_admin SET tentativas_login = 0,
                         bloqueado_ate = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?'
                    )->execute([$u['id']]);
                } else {
                    bd()->prepare('UPDATE usuarios_admin SET tentativas_login = ? WHERE id = ?')
                        ->execute([$tentativas, $u['id']]);
                }
            }
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}

$token = csrf_token();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Entrar — Painel Conta Lelê</title>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<main class="adm-conteudo" style="max-width:380px">
    <h1>Painel da Conta Lelê</h1>
    <?php if ($erro !== null): ?>
        <p class="adm-aviso adm-aviso--erro"><?= e($erro) ?></p>
    <?php endif; ?>
    <form method="post" action="/admin/login.php" class="adm-cartao">
        <input type="hidden" name="csrf" value="<?= e($token) ?>">
        <label class="adm-campo"><span>E-mail</span>
            <input type="email" name="email" required autofocus></label>
        <label class="adm-campo"><span>Senha</span>
            <input type="password" name="senha" required></label>
        <button class="adm-btn" type="submit">Entrar</button>
    </form>
</main>
</body>
</html>
