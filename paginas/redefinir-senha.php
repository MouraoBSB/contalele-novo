<?php
/**
 * Redefinição de senha do cursista via token.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/tokens.php';

iniciar_sessao_site();

$aviso = null;
$concluido = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
        $tokenCru = (string) ($_POST['token'] ?? '');
    } else {
        $tokenCru = (string) ($_POST['token'] ?? '');
        $nova = (string) ($_POST['nova'] ?? '');
        $conf = (string) ($_POST['confirmacao'] ?? '');
        if (!senha_forte($nova)) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A senha precisa ter pelo menos 8 caracteres.'];
        } elseif ($nova !== $conf) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A confirmação não confere com a nova senha.'];
        } else {
            $linha = consumir_token($tokenCru, 'recuperacao');
            if ($linha === null || $linha['escopo'] !== 'cursista') {
                $aviso = ['tipo' => 'erro', 'texto' => 'Link inválido ou expirado. Peça um novo.'];
            } else {
                $id = (int) $linha['usuario_id'];
                atualizar_senha_cursista($id, password_hash($nova, PASSWORD_DEFAULT));
                marcar_email_verificado($id); // ter recebido o e-mail comprova o endereço
                invalidar_tokens('cursista', $id, 'recuperacao');
                $concluido = true;
            }
        }
    }
} else {
    $tokenCru = (string) ($_GET['token'] ?? '');
}

$valido = false;
if (!$concluido && $tokenCru !== '') {
    // Confere validade sem consumir (peek), para exibir o formulário.
    $st = bd()->prepare(
        'SELECT 1 FROM tokens_autenticacao
         WHERE token_hash = ? AND finalidade = ? AND escopo = ? AND usado_em IS NULL AND expira_em > NOW()'
    );
    $st->execute([hash_token($tokenCru), 'recuperacao', 'cursista']);
    $valido = (bool) $st->fetchColumn();
}

$token = csrf_token();
$seo['titulo'] = 'Redefinir senha | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-secao" style="padding-top:48px">
    <div class="cl-conteudo" style="max-width:460px">
        <h1>Redefinir senha</h1>
        <?php if ($concluido): ?>
            <p class="cl-aviso cl-aviso--ok">Senha redefinida! Agora é só <a href="/entrar">entrar</a>
                com a nova senha.</p>
        <?php elseif (!$valido): ?>
            <p class="cl-aviso cl-aviso--erro"><?= e($aviso['texto'] ?? 'Link inválido ou expirado.') ?></p>
            <p><a href="/recuperar-senha">Pedir um novo link</a></p>
        <?php else: ?>
            <?php if ($aviso): ?>
                <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
            <?php endif; ?>
            <form class="cl-form" method="post" action="/redefinir-senha" novalidate>
                <input type="hidden" name="csrf" value="<?= e($token) ?>">
                <input type="hidden" name="token" value="<?= e($tokenCru) ?>">
                <div class="cl-form__grid">
                    <label class="cl-campo cl-campo--full"><span>Nova senha (mínimo 8)</span>
                        <input type="password" name="nova" required minlength="8" autocomplete="new-password"></label>
                    <label class="cl-campo cl-campo--full"><span>Confirme a nova senha</span>
                        <input type="password" name="confirmacao" required minlength="8" autocomplete="new-password"></label>
                </div>
                <div class="cl-form__rodape">
                    <button class="cl-btn cl-btn-primary cl-btn-lg" type="submit">Salvar nova senha</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>
