<?php
/**
 * Login do cursista (e-mail/senha) com bloqueio por tentativas.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/tokens.php';
require_once CL_RAIZ . '/includes/email.php';

iniciar_sessao_site();

$destino = destino_seguro($_GET['destino'] ?? null);
if (cursista_logado() !== null) {
    header('Location: ' . $destino);
    exit;
}

$aviso = null;
$naoVerificado = null; // guarda o cursista quando o login falha por falta de verificação

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } elseif (($_POST['reenviar'] ?? '') === '1') {
        // Reenvio do e-mail de verificação (botão da própria tela) — branch isolado.
        $c = cursista_por_email((string) ($_POST['email'] ?? ''));
        if ($c && (int) $c['email_verificado'] !== 1
            && !token_recente('cursista', (int) $c['id'], 'verificacao', 60)) {
            $base = rtrim($config['site']['url'], '/');
            $tk = criar_token('cursista', (int) $c['id'], 'verificacao', TOKEN_TTL_VERIFICACAO);
            $url = "{$base}/verificar-email?token=" . $tk;
            enviar_email(
                (string) $c['email'],
                'Confirme o seu e-mail — Conta Lelê',
                "Confirme o seu e-mail:\n{$url}\nO link vale por 24 horas.",
                null,
                template_email('Confirme o seu e-mail',
                    ['Aqui está um novo link de confirmação. Ele vale por 24 horas.'],
                    'Confirmar e-mail', $url)
            );
        }
        $aviso = ['tipo' => 'ok', 'texto' => 'Se a conta existir e ainda não estiver confirmada, enviamos um novo link.'];
    } else {
        $destino = destino_seguro($_POST['destino'] ?? null);
        $email = limpar_texto((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        $c = cursista_por_email($email);

        if ($c && cursista_bloqueado($c)) {
            $aviso = ['tipo' => 'erro', 'texto' => 'Conta temporariamente bloqueada por tentativas. Aguarde 15 minutos.'];
        } elseif ($c && (int) $c['ativo'] === 1 && $c['senha_hash'] !== null
                  && password_verify($senha, $c['senha_hash'])) {
            if ((int) $c['email_verificado'] !== 1) {
                $naoVerificado = $c;
                $aviso = ['tipo' => 'erro', 'texto' => 'Confirme o seu e-mail antes de entrar.'];
            } else {
                registrar_acesso_cursista((int) $c['id']);
                logar_cursista($c);
                header('Location: ' . $destino);
                exit;
            }
        } elseif ($c && $c['senha_hash'] === null) {
            $aviso = ['tipo' => 'erro', 'texto' => 'Esta conta usa entrada pelo Google. Use o botão "Entrar com Google".'];
        } else {
            if ($c) {
                registrar_tentativa_falha_cursista($c);
            }
            $aviso = ['tipo' => 'erro', 'texto' => 'E-mail ou senha incorretos.'];
        }
    }
}

$googleAtivo = configuracao('google_oauth_ativo', '0') === '1' && configuracao('google_client_id') !== '';
$token = csrf_token();
$seo['titulo'] = 'Entrar | Conta Lelê';
$seo['descricao'] = 'Entre na sua conta da Conta Lelê.';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-hero-simples">
    <div class="cl-conteudo"><p class="cl-eyebrow">Sua conta</p><h1><span class="cl-em">Entrar</span></h1></div>
</section>
<section class="cl-secao" style="padding-top:24px">
    <div class="cl-conteudo" style="max-width:460px">
        <?php if ($aviso): ?>
            <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
        <?php endif; ?>

        <?php if ($naoVerificado !== null): ?>
            <form method="post" action="/entrar" style="margin-bottom:16px">
                <input type="hidden" name="csrf" value="<?= e($token) ?>">
                <input type="hidden" name="reenviar" value="1">
                <input type="hidden" name="email" value="<?= e((string) $naoVerificado['email']) ?>">
                <button class="cl-btn cl-btn-yellow" type="submit">Reenviar e-mail de confirmação</button>
            </form>
        <?php endif; ?>

        <form class="cl-form" method="post" action="/entrar" novalidate>
            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <input type="hidden" name="destino" value="<?= e($destino) ?>">
            <div class="cl-form__grid">
                <label class="cl-campo cl-campo--full"><span>E-mail</span>
                    <input type="email" name="email" required autocomplete="email" autofocus></label>
                <label class="cl-campo cl-campo--full"><span>Senha</span>
                    <input type="password" name="senha" required autocomplete="current-password"></label>
            </div>
            <div class="cl-form__rodape">
                <button class="cl-btn cl-btn-primary cl-btn-lg" type="submit">Entrar</button>
            </div>
        </form>

        <?php if ($googleAtivo): ?>
            <div style="margin:18px 0;text-align:center;color:var(--cl-ink-dim)">ou</div>
            <a class="cl-btn-google" href="/entrar/google">
                <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.71-1.57 2.68-3.89 2.68-6.62z"/><path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.81.54-1.84.86-3.04.86-2.34 0-4.32-1.58-5.03-3.7H.96v2.33A9 9 0 0 0 9 18z"/><path fill="#FBBC05" d="M3.97 10.72a5.4 5.4 0 0 1 0-3.44V4.95H.96a9 9 0 0 0 0 8.1l3.01-2.33z"/><path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0A9 9 0 0 0 .96 4.95l3.01 2.33C4.68 5.16 6.66 3.58 9 3.58z"/></svg>
                Entrar com Google
            </a>
        <?php endif; ?>

        <p style="margin-top:18px">
            <a href="/recuperar-senha">Esqueci minha senha</a> · Não tem conta?
            <a href="/criar-conta">Criar conta</a>
        </p>
    </div>
</section>
