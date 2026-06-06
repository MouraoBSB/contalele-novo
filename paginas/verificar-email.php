<?php
/**
 * Verificação de e-mail do cursista via token. Ativa a conta e faz login.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/tokens.php';

iniciar_sessao_site();

$tokenCru = (string) ($_GET['token'] ?? '');
$linha = consumir_token($tokenCru, 'verificacao');

$ok = false;
if ($linha !== null && $linha['escopo'] === 'cursista') {
    $cursista = cursista_por_id((int) $linha['usuario_id']);
    if ($cursista !== null && (int) $cursista['ativo'] === 1) {
        marcar_email_verificado((int) $cursista['id']);
        logar_cursista($cursista);
        header('Location: /minha-conta');
        exit;
    }
}

$seo['titulo'] = 'Verificação de e-mail | Conta Lelê';
$seo['robots'] = 'noindex, nofollow';
?>
<section class="cl-secao" style="padding-top:48px">
    <div class="cl-conteudo" style="max-width:520px">
        <h1>Link inválido ou expirado</h1>
        <p class="cl-aviso cl-aviso--erro">Não foi possível confirmar o seu e-mail.
            O link pode ter expirado ou já ter sido usado.</p>
        <p>Crie a conta de novo para receber um novo link de confirmação:
            <a href="/criar-conta">Criar conta</a>.</p>
    </div>
</section>
