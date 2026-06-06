<?php
/**
 * Edição das configurações do site (contatos, redes e SMTP).
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

// Campos editáveis: chave => [rótulo, tipo input].
$campos = [
    'whatsapp'       => ['WhatsApp (só dígitos, com DDI — ex.: 5561999999999)', 'text'],
    'email_contato'  => ['E-mail de contato exibido no site', 'email'],
    'instagram'      => ['URL do Instagram', 'text'],
    'facebook'       => ['URL do Facebook', 'text'],
    'youtube'        => ['URL do canal no YouTube', 'text'],
    'smtp_host'      => ['SMTP — servidor', 'text'],
    'smtp_porta'     => ['SMTP — porta (ex.: 587)', 'text'],
    'smtp_usuario'   => ['SMTP — usuário', 'text'],
    'smtp_senha'     => ['SMTP — senha', 'password'],
    'smtp_remetente' => ['SMTP — e-mail remetente', 'email'],
    'smtp_seguranca' => ['SMTP — criptografia (tls, ssl ou nenhuma)', 'text'],
    'google_client_id'     => ['Google — Client ID', 'text'],
    'google_client_secret' => ['Google — Client Secret', 'password'],
    'google_oauth_ativo'   => ['Google — ativo (1 = ligado, 0 = desligado)', 'text'],
];

$aviso = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $aviso = ['tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página.'];
    } else {
        $st = $pdo->prepare(
            'INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        foreach (array_keys($campos) as $chave) {
            // Campo de segredo em branco: mantém o valor atual.
            if (in_array($chave, ['smtp_senha', 'google_client_secret'], true)
                && ($_POST[$chave] ?? '') === '') {
                continue;
            }
            $st->execute([$chave, trim((string) ($_POST[$chave] ?? ''))]);
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
    <?php foreach ($campos as $chave => [$rotulo, $tipo]): ?>
        <label class="adm-campo"><span><?= e($rotulo) ?></span>
            <?php if ($tipo === 'password'): ?>
                <input type="password" name="<?= e($chave) ?>"
                       placeholder="<?= ($valores[$chave] ?? '') !== '' ? 'Valor salvo — preencha só para trocar' : '' ?>">
            <?php else: ?>
                <input type="<?= e($tipo) ?>" name="<?= e($chave) ?>"
                       value="<?= e($valores[$chave] ?? '') ?>">
            <?php endif; ?>
        </label>
    <?php endforeach; ?>
    <button class="adm-btn" type="submit">Salvar configurações</button>
</form>
<?php require __DIR__ . '/incluir/rodape.php'; ?>
