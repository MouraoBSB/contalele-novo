<?php
/**
 * Solicitação de recuperação de senha do admin (anti-enumeração + throttle).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require CL_RAIZ . '/includes/repositorio.php';
require CL_RAIZ . '/includes/tokens.php';
require CL_RAIZ . '/includes/email.php';
require CL_RAIZ . '/includes/limites.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
iniciar_sessao();

$enviado = false;
$erro = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } else {
        $email = limpar_texto((string) ($_POST['email'] ?? ''));
        $st = bd()->prepare('SELECT * FROM usuarios_admin WHERE email = ? AND ativo = 1 LIMIT 1');
        $st->execute([$email]);
        $a = $st->fetch();
        if ($a && !acao_excedida('recuperacao_admin', 5, 3600)
            && !token_recente('admin', (int) $a['id'], 'recuperacao', 60)) {
            registrar_acao('recuperacao_admin');
            $base = rtrim($config['site']['url'], '/');
            $tk = criar_token('admin', (int) $a['id'], 'recuperacao', TOKEN_TTL_RECUPERACAO);
            $url = "{$base}/admin/redefinir-senha.php?token=" . $tk;
            enviar_email(
                (string) $a['email'],
                'Redefinir senha do painel — Conta Lelê',
                "Para redefinir a senha do painel, acesse:\n{$url}\nO link vale por 1 hora.",
                null,
                template_email('Redefinir senha do painel',
                    ['Recebemos um pedido para redefinir a senha do painel administrativo.',
                     'Clique no botão abaixo. O link vale por 1 hora.'],
                    'Redefinir senha', $url)
            );
        }
        $enviado = true;
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
    <title>Recuperar senha — Painel Conta Lelê</title>
    <link rel="stylesheet" href="/assets/css/brand-tokens.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<main class="adm-conteudo" style="max-width:380px">
    <h1>Recuperar senha</h1>
    <?php if ($enviado): ?>
        <p class="adm-aviso adm-aviso--ok">Se houver uma conta com esse e-mail, enviamos um link.</p>
        <p><a href="/admin/login.php">Voltar ao login</a></p>
    <?php else: ?>
        <?php if ($erro !== null): ?>
            <p class="adm-aviso adm-aviso--erro"><?= e($erro) ?></p>
        <?php endif; ?>
        <form method="post" action="/admin/recuperar-senha.php" class="adm-cartao">
            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <label class="adm-campo"><span>E-mail do painel</span>
                <input type="email" name="email" required autofocus></label>
            <button class="adm-btn" type="submit">Enviar link</button>
        </form>
        <p style="margin-top:12px"><a href="/admin/login.php">Voltar ao login</a></p>
    <?php endif; ?>
</main>
</body>
</html>
