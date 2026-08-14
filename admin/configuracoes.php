<?php
/**
 * Edição das configurações do site (contatos, redes, SMTP, Google e curso).
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require __DIR__ . '/incluir/sessao.php';

ativar_tratamento_erros($config['site']['ambiente']);
exigir_login();

$pdo = bd();

// Campos por grupo: chave => [rótulo, tipo, ajuda].
// Tipos: text, email, password, url, textarea, bool, senha_hash.
$grupos = [
    'Contato e redes' => [
        'whatsapp'      => ['WhatsApp (só dígitos, com DDI — ex.: 5561999999999)', 'text', ''],
        'email_contato' => ['E-mail de contato exibido no site', 'email', ''],
        'instagram'     => ['URL do Instagram', 'text', ''],
        'facebook'      => ['URL do Facebook', 'text', ''],
        'youtube'       => ['URL do canal no YouTube', 'text', ''],
    ],
    'Envio de e-mail (SMTP)' => [
        'smtp_host'      => ['SMTP — servidor', 'text', ''],
        'smtp_porta'     => ['SMTP — porta (ex.: 587)', 'text', ''],
        'smtp_usuario'   => ['SMTP — usuário', 'text', ''],
        'smtp_senha'     => ['SMTP — senha', 'password', ''],
        'smtp_remetente' => ['SMTP — e-mail remetente', 'email', ''],
        'smtp_seguranca' => ['SMTP — criptografia (tls, ssl ou nenhuma)', 'text', ''],
    ],
    'Login com Google' => [
        'google_client_id'     => ['Google — Client ID', 'text', ''],
        'google_client_secret' => ['Google — Client Secret', 'password', ''],
        'google_oauth_ativo'   => ['Google — ativo (1 = ligado, 0 = desligado)', 'text', ''],
    ],
    'Curso Conte&Encante' => [
        'curso_publicado' => ['Página publicada', 'bool',
            'Desmarcada, a página /conte-e-encante só abre com a senha abaixo e não entra em buscadores.'],
        'curso_previa_hash' => ['Senha de pré-visualização', 'senha_hash',
            'Quem receber esta senha consegue ver a página antes de ela ir ao ar.'],
        'curso_checkout_url' => ['URL do checkout na Hotmart', 'url',
            'Vazio, a página usa https://pay.hotmart.com/B107134315C?off=87ea4lrn. Mantenha o '
            . '?off — ele fixa a oferta "Padrao sem juros". Sem esse trecho o link segue o preço '
            . 'base do momento, o que muda sozinho se alguém criar uma promoção.'],
        'curso_preco' => ['Preço à vista (só o número — ex.: 297)', 'text',
            'Vazio, a página usa 297.'],
        'curso_parcelamento' => ['Linha de parcelamento', 'text',
            'Vazio, a página usa "ou parcelado no cartão conforme as condições disponíveis no checkout."'],
        'curso_garantia_dias' => ['Garantia — número de dias', 'text',
            'Só o número. Vazio, a página usa 7. IMPORTANTE: confira se o prazo de garantia '
            . 'configurado no produto da Hotmart é exatamente este — a página e o checkout '
            . 'precisam dizer a mesma coisa.'],
        'curso_certificado' => ['Certificado', 'text',
            'Vazio, a página usa "Ao concluir o curso, você recebe um certificado." O texto '
            . 'aparece em dois lugares: na lista do que a aluna recebe e na resposta do FAQ.'],
        'curso_tempo_acesso' => ['Tempo de acesso', 'text',
            'Vazio, a página usa "Você terá acesso ao curso por 1 ano." Também aparece na '
            . 'lista e no FAQ.'],
        'curso_bonus' => ['Bônus', 'textarea',
            'O que entra além do curso. Enquanto estiver vazio, a página mostra o selo "a definir".'],
        'curso_encontros_ao_vivo' => ['Encontros ao vivo — quantidade e periodicidade', 'textarea',
            'Vazio, a página usa "São 5 encontros ao vivo, aos sábados pela manhã, pelo Zoom."'],
    ],
];

// Campos de segredo: em branco, preservam o valor já salvo.
$segredos = ['smtp_senha', 'google_client_secret', 'curso_previa_hash'];

$aviso = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } else {
        $st = $pdo->prepare(
            'INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        foreach ($grupos as $campos) {
            foreach ($campos as $chave => [$rotulo, $tipo, $ajuda]) {
                $enviado = trim((string) ($_POST[$chave] ?? ''));

                if ($tipo === 'bool') {
                    $st->execute([$chave, isset($_POST[$chave]) ? '1' : '0']);
                    continue;
                }
                if (in_array($chave, $segredos, true) && $enviado === '') {
                    continue; // mantém o valor atual
                }
                if ($tipo === 'senha_hash') {
                    $st->execute([$chave, password_hash($enviado, PASSWORD_DEFAULT)]);
                    continue;
                }
                $st->execute([$chave, $enviado]);
            }
        }
        $aviso = ['tipo' => 'ok', 'texto' => 'Configurações salvas.'];
    }
}

// Lê os valores atuais.
$valores = [];
foreach ($pdo->query('SELECT chave, valor FROM configuracoes') as $linha) {
    $valores[$linha['chave']] = (string) $linha['valor'];
}

$token = csrf_token();
$tituloPagina = 'Configurações';
require __DIR__ . '/incluir/topo.php';
?>
<h1>Configurações</h1>
<?php if ($aviso !== null): ?>
    <p class="adm-aviso adm-aviso--<?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></p>
<?php endif; ?>
<form method="post" action="/admin/configuracoes.php" class="adm-cartao">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">

    <?php foreach ($grupos as $titulo => $campos): ?>
        <fieldset class="adm-grupo">
            <legend><?= e($titulo) ?></legend>
            <?php foreach ($campos as $chave => [$rotulo, $tipo, $ajuda]): ?>
                <?php $atual = $valores[$chave] ?? ''; ?>

                <?php if ($tipo === 'bool'): ?>
                    <label class="adm-campo adm-campo--linha">
                        <input type="checkbox" name="<?= e($chave) ?>" value="1"
                               <?= $atual === '1' ? 'checked' : '' ?>>
                        <span><?= e($rotulo) ?></span>
                    </label>
                    <?php if ($ajuda !== ''): ?><small class="adm-ajuda"><?= e($ajuda) ?></small><?php endif; ?>

                <?php elseif ($tipo === 'textarea'): ?>
                    <label class="adm-campo"><span><?= e($rotulo) ?></span>
                        <textarea name="<?= e($chave) ?>" rows="3"><?= e($atual) ?></textarea>
                    </label>
                    <?php if ($ajuda !== ''): ?><small class="adm-ajuda"><?= e($ajuda) ?></small><?php endif; ?>

                <?php elseif ($tipo === 'password' || $tipo === 'senha_hash'): ?>
                    <label class="adm-campo"><span><?= e($rotulo) ?></span>
                        <input type="password" name="<?= e($chave) ?>" autocomplete="new-password"
                               placeholder="<?= $atual !== '' ? 'Valor salvo — preencha só para trocar' : '' ?>">
                    </label>
                    <?php if ($ajuda !== ''): ?><small class="adm-ajuda"><?= e($ajuda) ?></small><?php endif; ?>

                <?php else: ?>
                    <label class="adm-campo"><span><?= e($rotulo) ?></span>
                        <input type="<?= $tipo === 'url' ? 'url' : e($tipo) ?>" name="<?= e($chave) ?>"
                               value="<?= e($atual) ?>">
                    </label>
                    <?php if ($ajuda !== ''): ?><small class="adm-ajuda"><?= e($ajuda) ?></small><?php endif; ?>
                <?php endif; ?>

            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>

    <button class="adm-btn" type="submit">Salvar configurações</button>
</form>
<?php require __DIR__ . '/incluir/rodape.php'; ?>
