<?php
/**
 * Dashboard do painel administrativo.
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
$contar = static fn (string $sql): int => (int) $pdo->query($sql)->fetchColumn();

$cartoes = [
    ['Mensagens não lidas', $contar('SELECT COUNT(*) FROM mensagens_contato WHERE lida = 0'), '/admin/mensagens.php'],
    ['Cordéis',     $contar('SELECT COUNT(*) FROM cordeis'),     '/admin/conteudo.php?recurso=cordeis'],
    ['Livros',      $contar('SELECT COUNT(*) FROM ebooks'),      '/admin/conteudo.php?recurso=ebooks'],
    ['Notícias',    $contar('SELECT COUNT(*) FROM noticias'),    '/admin/conteudo.php?recurso=noticias'],
    ['Depoimentos', $contar('SELECT COUNT(*) FROM depoimentos'), '/admin/conteudo.php?recurso=depoimentos'],
    ['Festivais',   $contar('SELECT COUNT(*) FROM festivais'),   '/admin/conteudo.php?recurso=festivais'],
    ['Prêmios',     $contar('SELECT COUNT(*) FROM premios'),     '/admin/conteudo.php?recurso=premios'],
];

$tituloPagina = 'Painel';
require __DIR__ . '/incluir/topo.php';
?>
<h1>Olá, <?= e(usuario_logado()['nome']) ?>!</h1>
<p>Use o menu acima para gerenciar o conteúdo do site.</p>
<div class="adm-grade">
    <?php foreach ($cartoes as [$rotulo, $qtd, $url]): ?>
        <a class="adm-cartao" href="<?= e($url) ?>" style="text-decoration:none;color:inherit">
            <div style="font-size:32px;font-weight:900"><?= e((string) $qtd) ?></div>
            <div><?= e($rotulo) ?></div>
        </a>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/incluir/rodape.php'; ?>
