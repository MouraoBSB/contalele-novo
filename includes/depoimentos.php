<?php
/**
 * Depoimentos: preparo do texto gravado pelo painel para exibição.
 *
 * Thiago Mourão — https://github.com/MouraoBSB — 10/11/2026
 */

declare(strict_types=1);

/**
 * Quebra o texto em parágrafos: cada quebra de linha digitada no painel
 * abre um parágrafo novo. Espaços repetidos e linhas vazias são descartados.
 *
 * @return string[]
 */
function texto_em_paragrafos(string $texto): array
{
    $linhas = preg_split('/\R+/u', $texto) ?: [];
    $paragrafos = [];
    foreach ($linhas as $linha) {
        $limpa = trim((string) preg_replace('/\s+/u', ' ', $linha));
        if ($limpa !== '') {
            $paragrafos[] = $limpa;
        }
    }
    return $paragrafos;
}

/** Texto numa linha só (para citações curtas, como o cartão do topo da home). */
function texto_corrido(string $texto): string
{
    return implode(' ', texto_em_paragrafos($texto));
}

/**
 * Trecho de até $limite caracteres (contando as reticências), cortado
 * entre palavras, sem pontuação solta antes das reticências.
 */
function texto_resumido(string $texto, int $limite): string
{
    $corrido = texto_corrido($texto);
    if (mb_strlen($corrido) <= $limite) {
        return $corrido;
    }
    $trecho = mb_substr($corrido, 0, $limite);
    $espaco = mb_strrpos($trecho, ' ');
    $trecho = $espaco === false ? mb_substr($corrido, 0, $limite - 1) : mb_substr($trecho, 0, $espaco);
    return preg_replace('/[\s,;:]+$/u', '', $trecho) . '…';
}

/**
 * Depoimentos em vídeo que têm arquivo de vídeo (um cadastro sem vídeo não aparece no site).
 *
 * @param array<int,array<string,mixed>> $linhas
 * @return array<int,array<string,mixed>>
 */
function depoimentos_com_video(array $linhas): array
{
    return array_values(array_filter(
        $linhas,
        static fn (array $d): bool => trim((string) ($d['video'] ?? '')) !== ''
    ));
}

/**
 * Caminho de uma página do próprio site (ex.: /conte-e-encante), ou vazio se o valor
 * digitado no painel não for um caminho interno simples.
 */
function link_interno(string $valor): string
{
    $valor = trim($valor);
    return preg_match('~^/[a-z0-9-]+(?:/[a-z0-9-]+)*/?(?:#[a-z0-9-]+)?$~', $valor) ? $valor : '';
}
