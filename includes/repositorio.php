<?php
/**
 * Camada de acesso a dados do site Conta Lelê.
 * Listagens de conteúdo publicado, lidas do banco.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Lê todos os registros publicados de uma tabela de conteúdo,
 * ordenados por `ordem` e `id`.
 *
 * @return array<int,array<string,mixed>>
 */
function listar_publicados(string $tabela): array
{
    $permitidas = ['cordeis', 'ebooks', 'noticias', 'depoimentos', 'festivais', 'premios'];
    if (!in_array($tabela, $permitidas, true)) {
        throw new InvalidArgumentException("Tabela não permitida: {$tabela}");
    }
    return bd()->query(
        "SELECT * FROM {$tabela} WHERE publicado = 1 ORDER BY ordem, id"
    )->fetchAll();
}

function listar_cordeis(): array      { return listar_publicados('cordeis'); }
function listar_ebooks(): array       { return listar_publicados('ebooks'); }
function listar_noticias(): array     { return listar_publicados('noticias'); }
function listar_depoimentos(): array  { return listar_publicados('depoimentos'); }
function listar_festivais(): array    { return listar_publicados('festivais'); }
function listar_premios(): array      { return listar_publicados('premios'); }

/**
 * Lê uma configuração da tabela `configuracoes` (com valor padrão).
 */
function configuracao(string $chave, string $padrao = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (bd()->query('SELECT chave, valor FROM configuracoes') as $linha) {
            $cache[$linha['chave']] = (string) $linha['valor'];
        }
    }
    return $cache[$chave] ?? $padrao;
}

/**
 * Extrai o ID de um vídeo a partir de uma URL do YouTube.
 * Aceita as formas watch?v=, youtu.be/ e embed/. Devolve '' se não achar.
 */
function youtube_id(string $url): string
{
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        return $m[1];
    }
    return '';
}
