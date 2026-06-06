<?php
/**
 * Painel do cursista (área logada) — dados e edição de nome.
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

$aviso = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } else {
        $nome = limpar_texto((string) ($_POST['nome'] ?? ''));
        if ($nome === '') {
            $aviso = ['tipo' => 'erro', 'texto' => 'O nome não pode ficar vazio.'];
        } else {
            atualizar_nome_cursista((int) $c['id'], $nome);
            $_SESSION['cursista']['nome'] = $nome;
            $c['nome'] = $nome;
            $aviso = ['tipo' => 'ok', 'texto' => 'Dados atualizados.'];
        }
    }
}

$usaGoogle = $c['google_id'] !== null;
$temSenha = $c['senha_hash'] !== null;
$token = csrf_token();
$seo['titulo'] = 'Minha conta | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-hero-simples">
    <div class="cl-conteudo"><p class="cl-eyebrow">Sua conta</p>
        <h1>Olá, <span class="cl-em"><?= e((string) $c['nome']) ?></span>!</h1></div>
</section>
<section class="cl-secao" style="padding-top:24px">
    <div class="cl-conteudo" style="max-width:560px">
        <?php if ($aviso): ?>
            <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
        <?php endif; ?>

        <form class="cl-form" method="post" action="/minha-conta" novalidate>
            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <div class="cl-form__grid">
                <label class="cl-campo cl-campo--full"><span>Nome</span>
                    <input type="text" name="nome" required value="<?= e((string) $c['nome']) ?>"></label>
                <label class="cl-campo cl-campo--full"><span>E-mail</span>
                    <input type="email" value="<?= e((string) $c['email']) ?>" disabled></label>
            </div>
            <div class="cl-form__rodape">
                <button class="cl-btn cl-btn-primary" type="submit">Salvar</button>
            </div>
        </form>

        <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap">
            <a class="cl-btn cl-btn-yellow" href="/minha-conta/senha">
                <?= $temSenha ? 'Trocar senha' : 'Definir uma senha' ?></a>
            <a class="cl-btn cl-btn-ghost" href="/sair">Sair</a>
        </div>

        <?php if ($usaGoogle): ?>
            <p style="margin-top:16px;color:var(--cl-ink-dim)">Esta conta também entra com o Google.</p>
        <?php endif; ?>
    </div>
</section>
