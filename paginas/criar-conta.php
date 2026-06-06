<?php
/**
 * Cadastro de cursista (double opt-in). Cria a conta e envia o e-mail de verificação.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';
require_once CL_RAIZ . '/includes/tokens.php';
require_once CL_RAIZ . '/includes/email.php';
require_once CL_RAIZ . '/includes/limites.php';

iniciar_sessao_site();
if (cursista_logado() !== null) {
    header('Location: /minha-conta');
    exit;
}

$aviso = null;
$enviado = false;
$valores = ['nome' => '', 'email' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $erros = [];
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente de novo.';
    }
    if (!empty($_POST['site'] ?? '')) {
        $erros[] = 'Envio bloqueado.';
    }

    $valores['nome']  = limpar_texto((string) ($_POST['nome'] ?? ''));
    $valores['email'] = limpar_texto((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $conf  = (string) ($_POST['confirmacao'] ?? '');

    if ($valores['nome'] === '')           { $erros[] = 'Diga o seu nome.'; }
    if (!validar_email($valores['email'])) { $erros[] = 'Informe um e-mail válido.'; }
    if (!senha_forte($senha))              { $erros[] = 'A senha precisa ter pelo menos 8 caracteres.'; }
    if ($senha !== $conf)                  { $erros[] = 'A confirmação não confere com a senha.'; }

    if (!$erros && acao_excedida('cadastro', 10, 3600)) {
        $enviado = true; // limite de envios por IP atingido — resposta neutra (anti-enumeração)
    } elseif (!$erros) {
        registrar_acao('cadastro');
        $base = rtrim($config['site']['url'], '/');
        $existente = cursista_por_email($valores['email']);
        if ($existente !== null) {
            // Anti-enumeração: não revela que a conta existe.
            enviar_email(
                $existente['email'],
                'Sobre a sua conta na Conta Lelê',
                "Recebemos um pedido de cadastro com este e-mail, mas você já tem uma conta.\n"
                    . "Para entrar: {$base}/entrar\nEsqueceu a senha? {$base}/recuperar-senha",
                null,
                template_email(
                    'Você já tem uma conta',
                    ['Recebemos um pedido de cadastro com este e-mail, mas você já tem uma conta na Conta Lelê.',
                     'Se foi você, é só entrar. Se esqueceu a senha, use o botão abaixo.'],
                    'Recuperar senha',
                    "{$base}/recuperar-senha"
                )
            );
        } else {
            try {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $id = criar_cursista($valores['nome'], $valores['email'], $hash, null, false);
                $tk = criar_token('cursista', $id, 'verificacao', TOKEN_TTL_VERIFICACAO);
                $url = "{$base}/verificar-email?token=" . $tk;
                enviar_email(
                    normalizar_email($valores['email']),
                    'Confirme o seu e-mail — Conta Lelê',
                    "Olá, {$valores['nome']}!\n\nConfirme o seu e-mail para ativar a conta:\n{$url}\n\n"
                        . 'O link vale por 24 horas.',
                    null,
                    template_email(
                        'Confirme o seu e-mail',
                        ["Olá, {$valores['nome']}! Falta só um passo para ativar a sua conta.",
                         'Clique no botão abaixo para confirmar o seu e-mail. O link vale por 24 horas.'],
                        'Confirmar e-mail',
                        $url
                    )
                );
            } catch (PDOException $e) {
                // Corrida de cadastro com o mesmo e-mail (violação de UNIQUE, SQLSTATE 23000):
                // trata como "conta já existe", mantendo a resposta neutra (anti-enumeração).
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }
        $enviado = true;
    } else {
        $aviso = ['tipo' => 'erro', 'texto' => implode(' ', $erros)];
    }
}

$token = csrf_token();
$seo['titulo']    = 'Criar conta | Conta Lelê';
$seo['descricao'] = 'Crie a sua conta na Conta Lelê para acessar os cursos da Lelê.';
$seo['robots']    = 'noindex, nofollow';
?>
<section class="cl-hero-simples">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Sua conta</p>
        <h1>Criar <span class="cl-em">conta</span></h1>
    </div>
</section>
<section class="cl-secao" style="padding-top:24px">
    <div class="cl-conteudo" style="max-width:520px">
        <?php if ($enviado): ?>
            <p class="cl-aviso cl-aviso--ok">Quase lá! Enviamos um e-mail de confirmação.
                Abra a sua caixa de entrada para ativar a conta.</p>
            <p>Não recebeu? Verifique o spam ou <a href="/criar-conta">tente de novo</a>.</p>
        <?php else: ?>
            <?php if ($aviso): ?>
                <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
            <?php endif; ?>
            <form class="cl-form" method="post" action="/criar-conta" novalidate>
                <input type="hidden" name="csrf" value="<?= e($token) ?>">
                <label class="cl-mel">Não preencha este campo
                    <input type="text" name="site" tabindex="-1" autocomplete="off"></label>
                <div class="cl-form__grid">
                    <label class="cl-campo cl-campo--full"><span>Nome</span>
                        <input type="text" name="nome" required value="<?= e($valores['nome']) ?>"
                               autocomplete="name"></label>
                    <label class="cl-campo cl-campo--full"><span>E-mail</span>
                        <input type="email" name="email" required value="<?= e($valores['email']) ?>"
                               autocomplete="email"></label>
                    <label class="cl-campo"><span>Senha (mínimo 8)</span>
                        <input type="password" name="senha" required minlength="8"
                               autocomplete="new-password"></label>
                    <label class="cl-campo"><span>Confirme a senha</span>
                        <input type="password" name="confirmacao" required minlength="8"
                               autocomplete="new-password"></label>
                </div>
                <div class="cl-form__rodape">
                    <button class="cl-btn cl-btn-primary cl-btn-lg" type="submit">Criar conta</button>
                </div>
            </form>
            <p style="margin-top:16px">Já tem conta? <a href="/entrar">Entrar</a></p>
        <?php endif; ?>
    </div>
</section>
