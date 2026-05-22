<?php
declare(strict_types=1);

require __DIR__ . '/../includes/erros.php';

$dir = sys_get_temp_dir() . '/cl-logs-' . uniqid();
mkdir($dir);
registrar_log('Mensagem de teste', ['chave' => 'valor'], $dir);

$arquivos = glob($dir . '/erro-*.log');
afirmar(count($arquivos) === 1, 'registrar_log() cria um arquivo de log do dia');
$conteudo = file_get_contents($arquivos[0]);
afirmar(strpos($conteudo, 'Mensagem de teste') !== false, 'registrar_log() grava a mensagem');
afirmar(strpos($conteudo, 'valor') !== false, 'registrar_log() grava o contexto');
