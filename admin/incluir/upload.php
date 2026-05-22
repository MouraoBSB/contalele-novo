<?php
/**
 * Processamento de uploads do painel (imagens e PDFs).
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

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
        throw new RuntimeException('Falha no envio do arquivo.');
    }
    if ($arquivo['size'] > $maxBytes) {
        throw new RuntimeException('Arquivo grande demais (máximo ' . (int) ($maxBytes / 1048576) . ' MB).');
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
