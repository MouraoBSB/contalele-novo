<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../admin/incluir/upload.php';

// nome_upload_seguro() é pura — gera um nome de arquivo seguro e aleatório.
$n = nome_upload_seguro('Foto Da Lelê!.JPG');
afirmar((bool) preg_match('/^[a-z0-9]+\.jpg$/', $n), 'nome_upload_seguro() gera nome aleatório com extensão minúscula');
afirmar(nome_upload_seguro('a.png') !== nome_upload_seguro('a.png'), 'nome_upload_seguro() gera nomes diferentes a cada chamada');
afirmar_igual('pdf', pathinfo(nome_upload_seguro('doc.PDF'), PATHINFO_EXTENSION), 'nome_upload_seguro() preserva a extensão pdf');

// extensao_permitida()
afirmar(extensao_permitida('foto.jpg', ['jpg', 'png']), 'extensao_permitida() aceita jpg na lista');
afirmar(!extensao_permitida('script.php', ['jpg', 'png']), 'extensao_permitida() rejeita php fora da lista');
afirmar(!extensao_permitida('arquivo', ['jpg']), 'extensao_permitida() rejeita arquivo sem extensão');
