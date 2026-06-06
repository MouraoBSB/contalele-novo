<?php
/**
 * Troca/definição de senha do cursista logado.
 * Conta só-Google define a primeira senha sem exigir a atual.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';

exigir_cursista();

$logado = cursista_logado();
$c = cursista_por_id((int) $logado['id']);
if ($c === null) {
    deslogar_cursista();
    header('Location: /entrar');
    exit;
}

$temSenha = $c['senha_hash'] !== null;
$aviso = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } else {
        $atual = (string) ($_POST['atual'] ?? '');
        $nova  = (string) ($_POST['nova'] ?? '');
        $conf  = (string) ($_POST['confirmacao'] ?? '');

        if ($temSenha && !password_verify($atual, (string) $c['senha_hash'])) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A senha atual está incorreta.'];
        } elseif (!senha_forte($nova)) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A nova senha precisa ter pelo menos 8 caracteres.'];
        } elseif ($nova !== $conf) {
            $aviso = ['tipo' => 'erro', 'texto' => 'A confirmação não confere com a nova senha.'];
        } else {
            atualizar_senha_cursista((int) $c['id'], password_hash($nova, PASSWORD_DEFAULT));
            $aviso = ['tipo' => 'ok', 'texto' => 'Senha salva com sucesso.'];
            $temSenha = true;
        }
    }
}

$token = csrf_token();
$seo['titulo'] = 'Senha | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-secao" style="padding-top:48px">
    <div class="cl-conteudo" style="max-width:460px">
        <h1><?= $temSenha ? 'Trocar senha' : 'Definir senha' ?></h1>
        <?php if ($aviso): ?>
            <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
        <?php endif; ?>
        <form class="cl-form" method="post" action="/minha-conta/senha" novalidate>
            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <div class="cl-form__grid">
                <?php if ($temSenha): ?>
                    <label class="cl-campo cl-campo--full"><span>Senha atual</span>
                        <input type="password" name="atual" required autocomplete="current-password"></label>
                <?php endif; ?>
                <label class="cl-campo cl-campo--full"><span>Nova senha (mínimo 8)</span>
                    <input type="password" name="nova" required minlength="8" autocomplete="new-password"></label>
                <label class="cl-campo cl-campo--full"><span>Confirme a nova senha</span>
                    <input type="password" name="confirmacao" required minlength="8" autocomplete="new-password"></label>
            </div>
            <div class="cl-form__rodape">
                <button class="cl-btn cl-btn-primary cl-btn-lg" type="submit">Salvar</button>
                <a class="cl-btn cl-btn-ghost" href="/minha-conta">Voltar</a>
            </div>
        </form>
    </div>
</section>
