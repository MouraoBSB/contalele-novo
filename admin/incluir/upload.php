<?php
/**
 * Processamento de uploads do painel (imagens, PDFs e vídeos).
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Tipos de campo de arquivo do painel: extensões, MIME reais aceitos, limite próprio
 * e o filtro do seletor de arquivos.
 *
 * @return array<string,array{ext:string[],mime:string[],max:int,accept:string}>
 */
function tipos_arquivo(): array
{
    return [
        'imagem' => [
            'ext'    => ['jpg', 'jpeg', 'png', 'webp'],
            'mime'   => ['image/jpeg', 'image/png', 'image/webp'],
            'max'    => 5 * 1048576,
            'accept' => 'image/*',
        ],
        'pdf' => [
            'ext'    => ['pdf'],
            'mime'   => ['application/pdf'],
            'max'    => 5 * 1048576,
            'accept' => 'application/pdf',
        ],
        // Vídeo já editado e comprimido (720p, cerca de 1 minuto, menos de 15 MB).
        'video' => [
            'ext'    => ['mp4'],
            'mime'   => ['video/mp4', 'application/mp4'],
            'max'    => 64 * 1048576,
            'accept' => 'video/mp4',
        ],
    ];
}

/**
 * Converte um limite do php.ini (ex.: "64M", "512K") em bytes. Zero ou negativo é "sem limite".
 */
function bytes_de_ini(string $valor): int
{
    $valor  = strtolower(trim($valor));
    $numero = (int) $valor;
    if ($numero <= 0) {
        return PHP_INT_MAX;
    }
    $multiplicador = ['k' => 1024, 'm' => 1048576, 'g' => 1073741824][substr($valor, -1)] ?? 1;
    return $numero * $multiplicador;
}

/** Maior arquivo que o PHP deste servidor aceita receber num formulário. */
function limite_servidor_bytes(): int
{
    return min(
        bytes_de_ini((string) ini_get('upload_max_filesize')),
        bytes_de_ini((string) ini_get('post_max_size'))
    );
}

/** Tamanho em MB para mensagens (ex.: "8 MB", "1,5 MB"). */
function formatar_mb(int $bytes): string
{
    $mb = round($bytes / 1048576, 1);
    $texto = $mb == floor($mb) ? (string) (int) $mb : str_replace('.', ',', (string) $mb);
    return $texto . ' MB';
}

/** Explica, em português, por que o PHP recusou o arquivo enviado. */
function mensagem_erro_upload(int $codigo, int $limite): string
{
    return match ($codigo) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Arquivo maior que o limite do servidor (' . formatar_mb($limite) . ').',
        UPLOAD_ERR_PARTIAL => 'O envio foi interrompido no meio. Tente de novo.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION =>
            'O servidor não conseguiu gravar o arquivo (código ' . $codigo . '). Confira o diagnóstico do painel.',
        default => 'Falha no envio do arquivo (código ' . $codigo . ').',
    };
}

/**
 * Verifica se o arquivo tem uma das extensões permitidas (minúsculas).
 */
function extensao_permitida(string $nomeArquivo, array $permitidas): bool
{
    $ext = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
    return $ext !== '' && in_array($ext, $permitidas, true);
}

/**
 * Gera um nome de arquivo seguro e aleatório, preservando a extensão.
 */
function nome_upload_seguro(string $nomeOriginal): string
{
    $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
    return bin2hex(random_bytes(8)) . ($ext !== '' ? '.' . $ext : '');
}

/**
 * Processa um arquivo enviado em $_FILES[$campo].
 * Valida tamanho, extensão e tipo MIME real; move para uploads/.
 * Devolve o nome do arquivo salvo, ou null se não houve envio,
 * ou lança RuntimeException se o arquivo for inválido.
 *
 * @param string[] $extensoes  Extensões permitidas (ex.: ['jpg','png','webp']).
 * @param string[] $mimes      Tipos MIME permitidos.
 */
function processar_upload(string $campo, array $extensoes, array $mimes, int $maxBytes = 5242880): ?string
{
    if (empty($_FILES[$campo]) || ($_FILES[$campo]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $arquivo = $_FILES[$campo];
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $limite = limite_servidor_bytes();
        if (function_exists('registrar_log')) {
            registrar_log('Upload recusado pelo PHP', [
                'campo'  => $campo,
                'codigo' => $arquivo['error'],
                'nome'   => $arquivo['name'] ?? '',
                'limite' => $limite,
            ]);
        }
        throw new RuntimeException(mensagem_erro_upload((int) $arquivo['error'], $limite));
    }
    if ($arquivo['size'] > $maxBytes) {
        throw new RuntimeException('Arquivo grande demais (máximo ' . formatar_mb($maxBytes) . ').');
    }
    if (!extensao_permitida($arquivo['name'], $extensoes)) {
        throw new RuntimeException('Tipo de arquivo não permitido.');
    }
    $mimeReal = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    if (!in_array($mimeReal, $mimes, true)) {
        throw new RuntimeException('O conteúdo do arquivo não confere com o tipo esperado.');
    }
    $nome = nome_upload_seguro($arquivo['name']);
    $destino = CL_RAIZ . '/uploads/' . $nome;
    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Não foi possível salvar o arquivo.');
    }
    return $nome;
}
