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

$whats = configuracao('whatsapp', '5561991938603');
$emailContato = configuracao('email_contato', 'contato@contalele.com.br');

$aviso = null;        // ['tipo' => 'ok'|'erro', 'texto' => ...]
$valores = ['nome' => '', 'email' => '', 'telefone' => '', 'assunto' => '', 'mensagem' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $erros = [];

    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente de novo.';
    }
    // Honeypot: campo invisível que só bots preenchem.
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

            // Notificação por e-mail — best-effort: uma falha aqui NÃO derruba
            // o envio, pois a mensagem já está salva no banco.
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
<section class="cl-hero">
  <div class="cl-conteudo" style="padding:48px 16px">
    <p class="cl-eyebrow">Contato</p>
    <h1 class="cl-secao__titulo">Vamos <span class="cl-em">conversar</span>?</h1>
    <p style="max-width:680px;font-size:16px">Conte o que você imagina — a Lelê e a
      equipe vão ouvir, pode acreditar.</p>
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo" style="max-width:680px">
    <?php if ($aviso): ?>
      <p class="cl-aviso cl-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
    <?php endif; ?>

    <form method="post" action="/contato">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">
      <label class="cl-mel">Não preencha este campo
        <input type="text" name="site" tabindex="-1" autocomplete="off"></label>

      <label class="cl-campo"><span>Nome</span>
        <input type="text" name="nome" required value="<?= e($valores['nome']) ?>"></label>
      <label class="cl-campo"><span>E-mail</span>
        <input type="email" name="email" required value="<?= e($valores['email']) ?>"></label>
      <label class="cl-campo"><span>Telefone (opcional)</span>
        <input type="text" name="telefone" value="<?= e($valores['telefone']) ?>"></label>
      <label class="cl-campo"><span>Assunto</span>
        <input type="text" name="assunto" value="<?= e($valores['assunto']) ?>"></label>
      <label class="cl-campo"><span>Mensagem</span>
        <textarea name="mensagem" required><?= e($valores['mensagem']) ?></textarea></label>

      <button class="cl-btn cl-btn-primary" type="submit">Enviar mensagem</button>
      <a class="cl-btn cl-btn-yellow" href="https://wa.me/<?= e($whats) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
    </form>

    <p style="margin-top:32px;color:var(--cl-ink-dim)">
      Planaltina-DF · <a href="mailto:<?= e($emailContato) ?>"><?= e($emailContato) ?></a><br>
      <a href="<?= e(configuracao('instagram', '#')) ?>" target="_blank" rel="noopener">Instagram</a> ·
      <a href="<?= e(configuracao('youtube', '#')) ?>" target="_blank" rel="noopener">YouTube</a>
    </p>
  </div>
</section>
