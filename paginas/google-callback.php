<?php
/**
 * Callback do login com Google do cursista: criar-ou-vincular e logar.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/google_oauth.php';

iniciar_sessao_site();

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
    $redirectUri = $base . '/entrar/google/callback';
    $tokens = google_trocar_codigo($code, $redirectUri);
    $perfil = $tokens !== null ? google_perfil($tokens['access_token']) : null;

    if ($perfil === null || empty($perfil['sub']) || empty($perfil['email'])
        || ($perfil['email_verified'] ?? false) !== true) {
        $erro = 'Não foi possível confirmar a sua conta Google.';
    } else {
        $sub   = (string) $perfil['sub'];
        $email = normalizar_email((string) $perfil['email']);
        $nome  = limpar_texto((string) ($perfil['name'] ?? 'Cursista'));

        $c = cursista_por_google_id($sub);
        if ($c === null) {
            $porEmail = cursista_por_email($email);
            if ($porEmail !== null) {
                vincular_google_cursista((int) $porEmail['id'], $sub);
                if ((int) $porEmail['email_verificado'] !== 1) {
                    marcar_email_verificado((int) $porEmail['id']);
                }
                $c = cursista_por_id((int) $porEmail['id']);
            } else {
                $id = criar_cursista($nome, $email, null, $sub, true);
                $c = cursista_por_id($id);
            }
        }

        if ($c !== null && (int) $c['ativo'] === 1) {
            registrar_acesso_cursista((int) $c['id']);
            logar_cursista($c);
            header('Location: /minha-conta');
            exit;
        }
        $erro = 'A sua conta está inativa. Fale com a gente.';
    }
}

$seo['titulo'] = 'Entrar com Google | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-secao" style="padding-top:48px">
    <div class="cl-conteudo" style="max-width:460px">
        <h1>Não deu certo</h1>
        <p class="cl-aviso cl-aviso--erro"><?= e($erro ?? 'Erro inesperado.') ?></p>
        <p><a href="/entrar">Voltar para entrar</a></p>
    </div>
</section>
