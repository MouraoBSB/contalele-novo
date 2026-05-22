<?php
/**
 * Motor de CRUD de conteúdo do painel — dirigido por admin/incluir/recursos.php.
 * Ações (via ?acao=): listar (padrão), novo, editar, salvar, excluir, publicar, mover.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';
require __DIR__ . '/incluir/sessao.php';
require __DIR__ . '/incluir/upload.php';

ativar_tratamento_erros($config['site']['ambiente']);
exigir_login();

$recursos = require __DIR__ . '/incluir/recursos.php';
$chave = (string) ($_GET['recurso'] ?? '');
if (!isset($recursos[$chave])) {
    http_response_code(404);
    exit('Recurso não encontrado.');
}
$rec    = $recursos[$chave];
$tabela = $rec['tabela'];
$pdo    = bd();
$acao   = (string) ($_GET['acao'] ?? 'listar');
$base   = '/admin/conteudo.php?recurso=' . rawurlencode($chave);

$IMG_EXT = ['jpg', 'jpeg', 'png', 'webp'];
$IMG_MIME = ['image/jpeg', 'image/png', 'image/webp'];
$PDF_EXT = ['pdf'];
$PDF_MIME = ['application/pdf'];

// ── Ações POST ───────────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Sessão expirada. Volte e recarregue a página.');
    }

    if ($acao === 'salvar') {
        $id = (int) ($_POST['id'] ?? 0);
        $dados = [];
        $erros = [];
        foreach ($rec['campos'] as $nome => $def) {
            if ($def['tipo'] === 'imagem' || $def['tipo'] === 'pdf') {
                try {
                    $ehImg = $def['tipo'] === 'imagem';
                    $arquivo = processar_upload(
                        $nome,
                        $ehImg ? $IMG_EXT : $PDF_EXT,
                        $ehImg ? $IMG_MIME : $PDF_MIME
                    );
                } catch (RuntimeException $e) {
                    $erros[] = $def['rotulo'] . ': ' . $e->getMessage();
                    $arquivo = null;
                }
                if ($arquivo !== null) {
                    $dados[$nome] = $arquivo;
                }
                continue;
            }
            $valor = trim((string) ($_POST[$nome] ?? ''));
            if ($def['tipo'] !== 'area') {
                $valor = limpar_texto($valor);
            }
            if (!empty($def['obrigatorio']) && $valor === '') {
                $erros[] = $def['rotulo'] . ' é obrigatório.';
            }
            if ($def['tipo'] === 'numero') {
                $dados[$nome] = $valor === '' ? null : (int) $valor;
            } elseif ($def['tipo'] === 'data') {
                $dados[$nome] = $valor === '' ? null : $valor;
            } else {
                $dados[$nome] = $valor;
            }
        }
        $dados['ordem']     = (int) ($_POST['ordem'] ?? 0);
        $dados['publicado'] = isset($_POST['publicado']) ? 1 : 0;
        if (!empty($rec['slug'])) {
            // Garante um slug único — acrescenta -2, -3... se já existir.
            $slugBase = gerar_slug((string) ($dados[$rec['rotulo']] ?? '')) ?: ('item-' . time());
            $slug = $slugBase;
            $n = 2;
            $chkSlug = $pdo->prepare("SELECT id FROM {$tabela} WHERE slug = ? AND id <> ? LIMIT 1");
            $chkSlug->execute([$slug, $id]);
            while ($chkSlug->fetch()) {
                $slug = $slugBase . '-' . $n++;
                $chkSlug->execute([$slug, $id]);
            }
            $dados['slug'] = $slug;
        }

        if ($erros) {
            $_SESSION['cl_flash'] = ['tipo' => 'erro', 'texto' => implode(' ', $erros)];
            header('Location: ' . $base . '&acao=' . ($id ? 'editar&id=' . $id : 'novo'));
            exit;
        }

        if ($id > 0) {
            $sets = implode(', ', array_map(static fn ($c) => "{$c} = ?", array_keys($dados)));
            $st = $pdo->prepare("UPDATE {$tabela} SET {$sets} WHERE id = ?");
            $st->execute([...array_values($dados), $id]);
        } else {
            $cols = implode(', ', array_keys($dados));
            $marc = implode(', ', array_fill(0, count($dados), '?'));
            $pdo->prepare("INSERT INTO {$tabela} ({$cols}) VALUES ({$marc})")
                ->execute(array_values($dados));
        }
        $_SESSION['cl_flash'] = ['tipo' => 'ok', 'texto' => 'Salvo com sucesso.'];
        header('Location: ' . $base);
        exit;
    }

    if ($acao === 'excluir') {
        $pdo->prepare("DELETE FROM {$tabela} WHERE id = ?")->execute([(int) ($_POST['id'] ?? 0)]);
        $_SESSION['cl_flash'] = ['tipo' => 'ok', 'texto' => 'Item excluído.'];
        header('Location: ' . $base);
        exit;
    }

    if ($acao === 'publicar') {
        $pdo->prepare("UPDATE {$tabela} SET publicado = 1 - publicado WHERE id = ?")
            ->execute([(int) ($_POST['id'] ?? 0)]);
        header('Location: ' . $base);
        exit;
    }

    if ($acao === 'mover') {
        $id = (int) ($_POST['id'] ?? 0);
        $dir = ($_POST['direcao'] ?? '') === 'subir' ? 'subir' : 'descer';
        $st = $pdo->prepare("SELECT id, ordem FROM {$tabela} WHERE id = ?");
        $st->execute([$id]);
        $atual = $st->fetch();
        if ($atual) {
            $comp = $dir === 'subir' ? '<' : '>';
            $ord  = $dir === 'subir' ? 'DESC' : 'ASC';
            $st2 = $pdo->prepare(
                "SELECT id, ordem FROM {$tabela} WHERE ordem {$comp} ? ORDER BY ordem {$ord} LIMIT 1"
            );
            $st2->execute([(int) $atual['ordem']]);
            $vizinho = $st2->fetch();
            if ($vizinho) {
                // Troca de ordem em transação — o swap é tudo ou nada.
                $pdo->beginTransaction();
                $up = $pdo->prepare("UPDATE {$tabela} SET ordem = ? WHERE id = ?");
                $up->execute([(int) $vizinho['ordem'], (int) $atual['id']]);
                $up->execute([(int) $atual['ordem'], (int) $vizinho['id']]);
                $pdo->commit();
            }
        }
        header('Location: ' . $base);
        exit;
    }

    http_response_code(400);
    exit('Ação inválida.');
}

// ── Formulário (novo / editar) ───────────────────────────────────────
$token = csrf_token();
$flash = $_SESSION['cl_flash'] ?? null;
unset($_SESSION['cl_flash']);

if ($acao === 'novo' || $acao === 'editar') {
    $registro = array_fill_keys(array_keys($rec['campos']), '');
    $registro['id'] = 0;
    $registro['ordem'] = 0;
    $registro['publicado'] = 1;
    if ($acao === 'editar') {
        $st = $pdo->prepare("SELECT * FROM {$tabela} WHERE id = ?");
        $st->execute([(int) ($_GET['id'] ?? 0)]);
        $registro = $st->fetch();
        if (!$registro) {
            http_response_code(404);
            exit('Item não encontrado.');
        }
    }
    $tituloPagina = $rec['singular'];
    require __DIR__ . '/incluir/topo.php';
    ?>
    <p><a href="<?= e($base) ?>">&larr; Voltar para <?= e($rec['plural']) ?></a></p>
    <h1><?= $registro['id'] ? 'Editar' : 'Novo' ?> <?= e($rec['singular']) ?></h1>
    <?php if ($flash): ?>
        <p class="adm-aviso adm-aviso--<?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></p>
    <?php endif; ?>
    <form method="post" action="<?= e($base) ?>&acao=salvar" enctype="multipart/form-data" class="adm-cartao">
        <input type="hidden" name="csrf" value="<?= e($token) ?>">
        <input type="hidden" name="id" value="<?= e((string) $registro['id']) ?>">
        <?php foreach ($rec['campos'] as $nome => $def): ?>
            <label class="adm-campo"><span><?= e($def['rotulo']) ?><?= !empty($def['obrigatorio']) ? ' *' : '' ?></span>
                <?php $valor = (string) ($registro[$nome] ?? ''); ?>
                <?php if ($def['tipo'] === 'area'): ?>
                    <textarea name="<?= e($nome) ?>"><?= e($valor) ?></textarea>
                <?php elseif ($def['tipo'] === 'imagem' || $def['tipo'] === 'pdf'): ?>
                    <?php if ($valor !== ''): ?>
                        <small>Atual: <?= e($valor) ?> — envie um arquivo só para substituir.</small>
                    <?php endif; ?>
                    <input type="file" name="<?= e($nome) ?>"
                           accept="<?= $def['tipo'] === 'imagem' ? 'image/*' : 'application/pdf' ?>">
                <?php elseif ($def['tipo'] === 'numero'): ?>
                    <input type="number" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
                <?php elseif ($def['tipo'] === 'data'): ?>
                    <input type="date" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
                <?php else: ?>
                    <input type="text" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
        <label class="adm-campo"><span>Ordem de exibição</span>
            <input type="number" name="ordem" value="<?= e((string) ($registro['ordem'] ?? 0)) ?>"></label>
        <label style="display:flex;gap:8px;align-items:center;margin-bottom:16px">
            <input type="checkbox" name="publicado" value="1" <?= !empty($registro['publicado']) ? 'checked' : '' ?>>
            <span>Publicado (visível no site)</span></label>
        <button class="adm-btn" type="submit">Salvar</button>
        <a class="adm-btn adm-btn--claro" href="<?= e($base) ?>">Cancelar</a>
    </form>
    <?php
    require __DIR__ . '/incluir/rodape.php';
    exit;
}

// ── Listagem (ação padrão) ───────────────────────────────────────────
$itens = $pdo->query("SELECT * FROM {$tabela} ORDER BY ordem, id")->fetchAll();
$tituloPagina = $rec['plural'];
require __DIR__ . '/incluir/topo.php';
?>
<h1><?= e($rec['plural']) ?></h1>
<?php if ($flash): ?>
    <p class="adm-aviso adm-aviso--<?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></p>
<?php endif; ?>
<p><a class="adm-btn" href="<?= e($base) ?>&acao=novo">+ Novo <?= e($rec['singular']) ?></a></p>
<?php if (!$itens): ?>
    <p>Nenhum item ainda.</p>
<?php else: ?>
<table class="adm-tabela">
    <thead><tr><th><?= e($rec['campos'][$rec['rotulo']]['rotulo']) ?></th>
        <th>Publicado</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($itens as $item): ?>
        <tr>
            <td><?= e((string) $item[$rec['rotulo']]) ?></td>
            <td><span class="adm-tag adm-tag--<?= $item['publicado'] ? 'sim' : 'nao' ?>">
                <?= $item['publicado'] ? 'Sim' : 'Não' ?></span></td>
            <td><div class="adm-acoes">
                <a class="adm-btn adm-btn--mini adm-btn--claro" href="<?= e($base) ?>&acao=editar&id=<?= (int) $item['id'] ?>">Editar</a>
                <form method="post" action="<?= e($base) ?>&acao=mover" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <button class="adm-btn adm-btn--mini adm-btn--claro" name="direcao" value="subir">&uarr;</button>
                    <button class="adm-btn adm-btn--mini adm-btn--claro" name="direcao" value="descer">&darr;</button>
                </form>
                <form method="post" action="<?= e($base) ?>&acao=publicar" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <button class="adm-btn adm-btn--mini adm-btn--claro" type="submit"><?= $item['publicado'] ? 'Despublicar' : 'Publicar' ?></button>
                </form>
                <form method="post" action="<?= e($base) ?>&acao=excluir" style="display:inline"
                      onsubmit="return confirm('Excluir este item? Esta ação não pode ser desfeita.');">
                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <button class="adm-btn adm-btn--mini adm-btn--perigo" type="submit">Excluir</button>
                </form>
            </div></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php require __DIR__ . '/incluir/rodape.php'; ?>
