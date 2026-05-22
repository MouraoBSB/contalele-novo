<?php
/**
 * Troca de senha do administrador (obrigatória no primeiro acesso).
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
exigir_login();

$u = usuario_logado();
$aviso = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } else {
        $nova = (string) ($_POST['nova'] ?? '');
        $conf = (string) ($_POST['confirmacao'] ?? '');
        if (mb_strlen($nova) < 8) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A senha precisa ter pelo menos 8 caracteres.'];
        } elseif ($nova !== $conf) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A confirmação não confere com a nova senha.'];
        } else {
            $hash = password_hash($nova, PASSWORD_DEFAULT);
            bd()->prepare(
                'UPDATE usuarios_admin SET senha_hash = ?, precisa_trocar_senha = 0 WHERE id = ?'
            )->execute([$hash, $u['id']]);
            $_SESSION['admin']['precisa_trocar_senha'] = 0;
            header('Location: /admin/');
            exit;
        }
    }
}

$token = csrf_token();
$tituloPagina = 'Trocar senha';
require __DIR__ . '/incluir/topo.php';
?>
<h1>Trocar senha</h1>
<?php if (!empty($u['precisa_trocar_senha'])): ?>
    <p>Este é o seu primeiro acesso. Defina uma senha pessoal para continuar.</p>
<?php endif; ?>
<?php if ($aviso !== null): ?>
    <p class="adm-aviso adm-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
<?php endif; ?>
<form method="post" action="/admin/trocar-senha.php" class="adm-cartao">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    <label class="adm-campo"><span>Nova senha (mínimo 8 caracteres)</span>
        <input type="password" name="nova" required minlength="8"></label>
    <label class="adm-campo"><span>Confirme a nova senha</span>
        <input type="password" name="confirmacao" required minlength="8"></label>
    <button class="adm-btn" type="submit">Salvar nova senha</button>
</form>
<?php require __DIR__ . '/incluir/rodape.php'; ?>
