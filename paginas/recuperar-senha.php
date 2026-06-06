<?php
/**
 * Solicitação de recuperação de senha do cursista (anti-enumeração + throttle).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/tokens.php';
require_once CL_RAIZ . '/includes/email.php';

iniciar_sessao_site();

$enviado = false;
$aviso = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } else {
        $email = limpar_texto((string) ($_POST['email'] ?? ''));
        $c = $email !== '' ? cursista_por_email($email) : null;
        if ($c && (int) $c['ativo'] === 1
            && !token_recente('cursista', (int) $c['id'], 'recuperacao', 60)) {
            $base = rtrim($config['site']['url'], '/');
            $tk = criar_token('cursista', (int) $c['id'], 'recuperacao', TOKEN_TTL_RECUPERACAO);
            $url = "{$base}/redefinir-senha?token=" . $tk;
            enviar_email(
                (string) $c['email'],
                'Redefinir a sua senha — Conta Lelê',
                "Para redefinir a sua senha, acesse:\n{$url}\nO link vale por 1 hora.",
                null,
                template_email('Redefinir a sua senha',
                    ['Recebemos um pedido para redefinir a sua senha.',
                     'Clique no botão abaixo. O link vale por 1 hora. Se não foi você, ignore este e-mail.'],
                    'Redefinir senha', $url)
            );
        }
        $enviado = true;
    }
}

$token = csrf_token();
$seo['titulo'] = 'Recuperar senha | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-hero-simples">
    <div class="cl-conteudo"><p class="cl-eyebrow">Sua conta</p><h1>Recuperar <span class="cl-em">senha</span></h1></div>
</section>
<section class="cl-secao" style="padding-top:24px">
    <div class="cl-conteudo" style="max-width:460px">
        <?php if ($enviado): ?>
            <p class="cl-aviso cl-aviso--ok">Se houver uma conta com esse e-mail, enviamos um link
                para redefinir a senha. Verifique a sua caixa de entrada.</p>
        <?php else: ?>
            <?php if ($aviso): ?>
                <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
            <?php endif; ?>
            <form class="cl-form" method="post" action="/recuperar-senha" novalidate>
                <input type="hidden" name="csrf" value="<?= e($token) ?>">
                <div class="cl-form__grid">
                    <label class="cl-campo cl-campo--full"><span>E-mail da conta</span>
                        <input type="email" name="email" required autocomplete="email" autofocus></label>
                </div>
                <div class="cl-form__rodape">
                    <button class="cl-btn cl-btn-primary cl-btn-lg" type="submit">Enviar link</button>
                </div>
            </form>
            <p style="margin-top:16px"><a href="/entrar">Voltar para entrar</a></p>
        <?php endif; ?>
    </div>
</section>
