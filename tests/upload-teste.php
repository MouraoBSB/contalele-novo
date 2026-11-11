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

// bytes_de_ini() — converte os limites do php.ini (64M, 512K, 2G) em bytes
afirmar_igual(67108864, bytes_de_ini('64M'), 'bytes_de_ini() entende megabytes');
afirmar_igual(524288, bytes_de_ini('512K'), 'bytes_de_ini() entende kilobytes');
afirmar_igual(2147483648, bytes_de_ini('2G'), 'bytes_de_ini() entende gigabytes');
afirmar_igual(8388608, bytes_de_ini(' 8m '), 'bytes_de_ini() aceita minúscula e espaços');
afirmar_igual(1048576, bytes_de_ini('1048576'), 'bytes_de_ini() aceita número puro');
afirmar_igual(PHP_INT_MAX, bytes_de_ini('-1'), 'bytes_de_ini() trata -1 como sem limite');
afirmar_igual(PHP_INT_MAX, bytes_de_ini('0'), 'bytes_de_ini() trata 0 como sem limite');

// formatar_mb() — limite legível para a mensagem do painel
afirmar_igual('8 MB', formatar_mb(8388608), 'formatar_mb() mostra MB inteiros sem casa decimal');
afirmar_igual('1,5 MB', formatar_mb(1572864), 'formatar_mb() usa vírgula na casa decimal');

// tipos_arquivo() — cada tipo de campo de arquivo do painel com extensões, MIME e limite próprios
$tipos = tipos_arquivo();
afirmar_igual(['mp4'], $tipos['video']['ext'], 'tipos_arquivo() aceita só MP4 para vídeo');
afirmar(in_array('video/mp4', $tipos['video']['mime'], true), 'tipos_arquivo() reconhece o MIME video/mp4');
afirmar($tipos['video']['max'] > $tipos['imagem']['max'], 'tipos_arquivo() dá ao vídeo um limite maior que o da imagem');
afirmar_igual(['jpg', 'jpeg', 'png', 'webp'], $tipos['imagem']['ext'], 'tipos_arquivo() mantém as extensões de imagem');
afirmar_igual(['pdf'], $tipos['pdf']['ext'], 'tipos_arquivo() mantém o PDF');

// mensagem_erro_upload() — explica o motivo em vez de "falha no envio"
afirmar(str_contains(mensagem_erro_upload(UPLOAD_ERR_INI_SIZE, 8388608), '8 MB'), 'mensagem_erro_upload() cita o limite do servidor quando o arquivo passa dele');
afirmar(str_contains(mensagem_erro_upload(UPLOAD_ERR_PARTIAL, 8388608), 'interrompido'), 'mensagem_erro_upload() avisa envio interrompido');
afirmar(mensagem_erro_upload(UPLOAD_ERR_NO_TMP_DIR, 8388608) !== '', 'mensagem_erro_upload() tem texto para erro de servidor');
