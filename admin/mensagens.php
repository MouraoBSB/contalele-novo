<?php
/**
 * Caixa de entrada das mensagens do formulário de contato.
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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Sessão expirada.');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $acao = (string) ($_POST['acao'] ?? '');
    if ($acao === 'excluir') {
        $pdo->prepare('DELETE FROM mensagens_contato WHERE id = ?')->execute([$id]);
    } elseif ($acao === 'alternar_lida') {
        $pdo->prepare('UPDATE mensagens_contato SET lida = 1 - lida WHERE id = ?')->execute([$id]);
    }
    header('Location: /admin/mensagens.php');
    exit;
}

$mensagens = $pdo->query(
    'SELECT * FROM mensagens_contato ORDER BY criado_em DESC'
)->fetchAll();
$token = csrf_token();

$tituloPagina = 'Mensagens';
require __DIR__ . '/incluir/topo.php';
?>
<h1>Mensagens de contato</h1>
<?php if (!$mensagens): ?>
    <p>Nenhuma mensagem recebida ainda.</p>
<?php else: ?>
    <?php foreach ($mensagens as $m): ?>
        <article class="adm-cartao">
            <p style="margin:0 0 6px">
                <strong><?= e($m['nome']) ?></strong>
                &lt;<a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>&gt;
                <?php if (!$m['lida']): ?>
                    <span class="adm-tag adm-tag--sim">nova</span>
                <?php endif; ?>
            </p>
            <p style="margin:0 0 6px;color:var(--cl-ink-dim);font-size:13px">
                <?= e($m['criado_em']) ?>
                <?php if ($m['telefone']): ?> · Tel: <?= e($m['telefone']) ?><?php endif; ?>
                <?php if ($m['assunto']): ?> · Assunto: <?= e($m['assunto']) ?><?php endif; ?>
            </p>
            <p style="white-space:pre-wrap"><?= e($m['mensagem']) ?></p>
            <div class="adm-acoes">
                <form method="post" action="/admin/mensagens.php" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <button class="adm-btn adm-btn--mini adm-btn--claro" name="acao" value="alternar_lida">
                        <?= $m['lida'] ? 'Marcar como não lida' : 'Marcar como lida' ?></button>
                </form>
                <form method="post" action="/admin/mensagens.php" style="display:inline"
                      onsubmit="return confirm('Excluir esta mensagem?');">
                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <button class="adm-btn adm-btn--mini adm-btn--perigo" name="acao" value="excluir">Excluir</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/incluir/rodape.php'; ?>
