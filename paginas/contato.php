<?php
/**
 * Página "Contato" — formulário, WhatsApp e redes.
 * Processa o POST do próprio formulário.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/email.php';
require_once CL_RAIZ . '/includes/girassol.php';
require_once CL_RAIZ . '/includes/autenticacao_cursista.php';

iniciar_sessao_site();

$whats = configuracao('whatsapp', '5561991938603');
$emailContato = configuracao('email_contato', 'contato@contalele.com.br');

$aviso = null;
$valores = ['nome' => '', 'email' => '', 'telefone' => '', 'assunto' => '', 'mensagem' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $erros = [];

    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente de novo.';
    }
    if (!empty($_POST['site'] ?? '')) {
        $erros[] = 'Envio bloqueado.';
    }

    $valores['nome']     = limpar_texto((string) ($_POST['nome'] ?? ''));
    $valores['email']    = limpar_texto((string) ($_POST['email'] ?? ''));
    $valores['telefone'] = limpar_texto((string) ($_POST['telefone'] ?? ''));
    $valores['assunto']  = limpar_texto((string) ($_POST['assunto'] ?? ''));
    $valores['mensagem'] = trim((string) ($_POST['mensagem'] ?? ''));

    if ($valores['nome'] === '')                 { $erros[] = 'Diga o seu nome.'; }
    if (!validar_email($valores['email']))       { $erros[] = 'Informe um e-mail válido.'; }
    if (mb_strlen($valores['mensagem']) < 10)    { $erros[] = 'Escreva uma mensagem um pouco maior.'; }

    if (!$erros) {
        try {
            bd()->prepare(
                'INSERT INTO mensagens_contato (nome, email, telefone, assunto, mensagem, ip)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $valores['nome'], $valores['email'], $valores['telefone'],
                $valores['assunto'], $valores['mensagem'],
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $corpoEmail = "Nome: {$valores['nome']}\nE-mail: {$valores['email']}\n"
                . "Telefone: {$valores['telefone']}\nAssunto: {$valores['assunto']}\n\n"
                . $valores['mensagem'];
            enviar_email(
                $emailContato,
                'Contato pelo site: ' . ($valores['assunto'] ?: 'sem assunto'),
                $corpoEmail,
                $valores['email']
            );

            $aviso = ['tipo' => 'ok', 'texto' => 'Mensagem enviada! A Lelê responde em breve.'];
            $valores = ['nome' => '', 'email' => '', 'telefone' => '', 'assunto' => '', 'mensagem' => ''];
        } catch (Throwable $e) {
            registrar_log('Falha ao gravar mensagem de contato', ['erro' => $e->getMessage()]);
            $aviso = ['tipo' => 'erro', 'texto' => 'Não foi possível enviar agora. Tente pelo WhatsApp.'];
        }
    } else {
        $aviso = ['tipo' => 'erro', 'texto' => implode(' ', $erros)];
    }
}

$token = csrf_token();

$seo['titulo']    = 'Contato — fale com a Lelê | Conta Lelê';
$seo['descricao'] = 'Entre em contato com a Conta Lelê para apresentações, '
    . 'cordéis personalizados e oficinas. WhatsApp e formulário.';
?>

<section class="cl-hero-simples">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Contato</p>
        <h1>Vamos <span class="cl-em">conversar</span>?</h1>
        <p>Conte o que você imagina — a Lelê e a equipe vão ouvir, pode acreditar.</p>
    </div>
</section>

<section class="cl-secao" style="padding-top:32px">
    <div class="cl-conteudo" style="max-width:760px">
        <?php if ($aviso): ?>
            <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
        <?php endif; ?>

        <form class="cl-form" method="post" action="/contato" novalidate>
            <span class="cl-form__girassol" aria-hidden="true">
                <?php girassol(['tamanho' => 130, 'gira' => true]); ?>
            </span>

            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <label class="cl-mel">Não preencha este campo
                <input type="text" name="site" tabindex="-1" autocomplete="off"></label>

            <div class="cl-form__grid">
                <label class="cl-campo">
                    <span>Nome</span>
                    <input type="text" name="nome" required value="<?= e($valores['nome']) ?>"
                           placeholder="Como podemos te chamar?" autocomplete="name">
                </label>
                <label class="cl-campo">
                    <span>E-mail</span>
                    <input type="email" name="email" required value="<?= e($valores['email']) ?>"
                           placeholder="voce@exemplo.com" autocomplete="email">
                </label>
                <label class="cl-campo">
                    <span>Telefone <small>(opcional)</small></span>
                    <input type="text" name="telefone" value="<?= e($valores['telefone']) ?>"
                           placeholder="(00) 00000-0000" autocomplete="tel">
                </label>
                <label class="cl-campo">
                    <span>Assunto</span>
                    <input type="text" name="assunto" value="<?= e($valores['assunto']) ?>"
                           placeholder="Contação na escola, cordel, curso…">
                </label>
                <label class="cl-campo cl-campo--full">
                    <span>Mensagem</span>
                    <textarea name="mensagem" required
                              placeholder="Conte um pouquinho do seu projeto, evento ou ideia."><?= e($valores['mensagem']) ?></textarea>
                </label>
            </div>

            <div class="cl-form__rodape">
                <button class="cl-btn cl-btn-primary cl-btn-lg" type="submit">Enviar mensagem</button>
                <a class="cl-btn cl-btn-yellow cl-btn-lg" href="https://wa.me/<?= e($whats) ?>"
                   target="_blank" rel="noopener">Falar no WhatsApp</a>
            </div>
        </form>

        <div class="cl-contato-info">
            <div class="cl-contato-card">
                <strong>WhatsApp</strong>
                <a href="https://wa.me/<?= e($whats) ?>" target="_blank" rel="noopener">(61) 99193-8603</a>
                <small style="color:var(--cl-ink-dim)">Resposta rápida em horário comercial</small>
            </div>
            <div class="cl-contato-card">
                <strong>E-mail</strong>
                <a href="mailto:<?= e($emailContato) ?>"><?= e($emailContato) ?></a>
                <small style="color:var(--cl-ink-dim)">Para propostas e parcerias</small>
            </div>
            <div class="cl-contato-card">
                <strong>Redes</strong>
                <a href="<?= e(configuracao('instagram', 'https://www.instagram.com/contalele/')) ?>" target="_blank" rel="noopener">Instagram</a>
                <a href="<?= e(configuracao('youtube', 'https://www.youtube.com/c/ContaLel%C3%AA')) ?>" target="_blank" rel="noopener">YouTube</a>
            </div>
        </div>
    </div>
</section>
