<?php
/**
 * Autodiagnóstico do ambiente do site Conta Lelê.
 * Protegido pelo token do instalador.
 * Uso: https://contalele.com.br/admin/diagnostico.php?token=SEU_TOKEN
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
$config = require CL_RAIZ . '/config.php';
require CL_RAIZ . '/includes/erros.php';
require CL_RAIZ . '/includes/funcoes.php';
require CL_RAIZ . '/includes/conexao.php';

ativar_tratamento_erros($config['site']['ambiente']);
header('Content-Type: text/html; charset=utf-8');

$token = $_GET['token'] ?? '';
if (!hash_equals($config['instalador']['token'], (string) $token)) {
    http_response_code(403);
    exit('Acesso negado.');
}

/** Lista de verificações: [rótulo, ok(bool), detalhe]. */
$checagens = [];

$checagens[] = ['Versão do PHP', version_compare(PHP_VERSION, '8.1', '>='), PHP_VERSION];

foreach (['pdo_mysql', 'mbstring', 'openssl', 'curl', 'fileinfo', 'gd'] as $ext) {
    $checagens[] = ['Extensão ' . $ext, extension_loaded($ext), extension_loaded($ext) ? 'carregada' : 'AUSENTE'];
}

foreach (['uploads', 'logs'] as $pasta) {
    $caminho = CL_RAIZ . '/' . $pasta;
    $checagens[] = ['Pasta ' . $pasta . ' gravável', is_writable($caminho), $caminho];
}

try {
    $pdo = bd($config['db']);
    $checagens[] = ['Conexão com o banco', true, 'conectado'];
    $tabelas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $esperadas = ['usuarios_admin', 'cordeis', 'ebooks', 'noticias', 'depoimentos',
        'festivais', 'premios', 'mensagens_contato', 'configuracoes'];
    $faltando = array_diff($esperadas, $tabelas);
    $checagens[] = ['Tabelas do banco', $faltando === [],
        $faltando === [] ? count($esperadas) . ' tabelas presentes' : 'faltando: ' . implode(', ', $faltando)];
} catch (Throwable $e) {
    $checagens[] = ['Conexão com o banco', false, $e->getMessage()];
}

echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8">';
echo '<title>Diagnóstico — Conta Lelê</title>';
echo '<body style="font-family:sans-serif;max-width:680px;margin:40px auto;padding:0 16px">';
echo '<h1>Diagnóstico do ambiente</h1><ul style="line-height:1.8;list-style:none;padding:0">';
$tudoOk = true;
foreach ($checagens as [$rotulo, $ok, $detalhe]) {
    $tudoOk = $tudoOk && $ok;
    $icone = $ok ? '&#9989;' : '&#10060;';
    echo '<li>' . $icone . ' <strong>' . e($rotulo) . '</strong> — ' . e($detalhe) . '</li>';
}
echo '</ul><p><strong>' . ($tudoOk ? 'Ambiente saudável.' : 'Há itens a corrigir.') . '</strong></p>';
