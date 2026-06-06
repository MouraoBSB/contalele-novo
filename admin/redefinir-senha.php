<?php
/**
 * Redefinição de senha do admin via token.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require CL_RAIZ . '/includes/tokens.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
iniciar_sessao();

$erro = null;
$concluido = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $tokenCru = (string) ($_POST['token'] ?? '');
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } else {
        $nova = (string) ($_POST['nova'] ?? '');
        $conf = (string) ($_POST['confirmacao'] ?? '');
        if (mb_strlen($nova) < 8) {
            $erro = 'A senha precisa ter pelo menos 8 caracteres.';
        } elseif ($nova !== $conf) {
            $erro = 'A confirmação não confere com a nova senha.';
        } else {
            $linha = consumir_token($tokenCru, 'recuperacao');
            if ($linha === null || $linha['escopo'] !== 'admin') {
                $erro = 'Link inválido ou expirado. Peça um novo.';
            } else {
                $id = (int) $linha['usuario_id'];
                bd()->prepare(
                    'UPDATE usuarios_admin SET senha_hash = ?, precisa_trocar_senha = 0,
                     tentativas_login = 0, bloqueado_ate = NULL WHERE id = ?'
                )->execute([password_hash($nova, PASSWORD_DEFAULT), $id]);
                invalidar_tokens('admin', $id, 'recuperacao');
                $concluido = true;
            }
        }
    }
} else {
    $tokenCru = (string) ($_GET['token'] ?? '');
}

$valido = false;
if (!$concluido && $tokenCru !== '') {
    $st = bd()->prepare(
        'SELECT 1 FROM tokens_autenticacao
         WHERE token_hash = ? AND finalidade = ? AND escopo = ? AND usado_em IS NULL AND expira_em > NOW()'
    );
    $st->execute([hash_token($tokenCru), 'recuperacao', 'admin']);
    $valido = (bool) $st->fetchColumn();
}

$token = csrf_token();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Redefinir senha — Painel Conta Lelê</title>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<main class="adm-conteudo" style="max-width:380px">
    <h1>Redefinir senha</h1>
    <?php if ($concluido): ?>
        <p class="adm-aviso adm-aviso--ok">Senha redefinida! <a href="/admin/login.php">Entrar</a>.</p>
    <?php elseif (!$valido): ?>
        <p class="adm-aviso adm-aviso--erro"><?= e($erro ?? 'Link inválido ou expirado.') ?></p>
        <p><a href="/admin/recuperar-senha.php">Pedir um novo link</a></p>
    <?php else: ?>
        <?php if ($erro !== null): ?>
            <p class="adm-aviso adm-aviso--erro"><?= e($erro) ?></p>
        <?php endif; ?>
        <form method="post" action="/admin/redefinir-senha.php" class="adm-cartao">
            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <input type="hidden" name="token" value="<?= e($tokenCru) ?>">
            <label class="adm-campo"><span>Nova senha (mínimo 8)</span>
                <input type="password" name="nova" required minlength="8"></label>
            <label class="adm-campo"><span>Confirme a nova senha</span>
                <input type="password" name="confirmacao" required minlength="8"></label>
            <button class="adm-btn" type="submit">Salvar nova senha</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
