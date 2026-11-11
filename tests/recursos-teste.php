<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/incluir/upload.php';

// Coerência entre o painel (recursos.php) e o schema do banco: todo campo do formulário
// precisa de coluna na tabela, e todo tipo de campo precisa ser conhecido pelo motor.
$recursos = require __DIR__ . '/../admin/incluir/recursos.php';
$schema   = (string) file_get_contents(__DIR__ . '/../sql/schema.sql');

$colunasDoSchema = static function (string $sql, string $tabela): array {
    $padrao = '/CREATE TABLE IF NOT EXISTS ' . preg_quote($tabela, '/') . ' \((.*?)\n\) ENGINE/s';
    if (!preg_match($padrao, $sql, $m)) {
        return [];
    }
    preg_match_all('/^\s+([a-z_]+)\s+[A-Z]/m', $m[1], $c);
    return $c[1];
};

$tiposSimples = ['texto', 'area', 'numero', 'data'];
$tiposArquivo = array_keys(tipos_arquivo());

afirmar(isset($recursos['depoimentos_video']), 'o painel tem a área de depoimentos em vídeo');
afirmar_igual('video', $recursos['depoimentos_video']['campos']['video']['tipo'] ?? null, 'o campo do vídeo usa o tipo video');

foreach ($recursos as $chave => $rec) {
    $colunas  = $colunasDoSchema($schema, $rec['tabela']);
    $usadas   = array_merge(array_keys($rec['campos']), ['ordem', 'publicado']);
    $faltando = array_values(array_diff($usadas, $colunas));
    afirmar_igual([], $faltando, "schema.sql tem as colunas usadas pelo painel em {$rec['tabela']}");
    foreach ($rec['campos'] as $nome => $def) {
        $conhecido = in_array($def['tipo'], array_merge($tiposSimples, $tiposArquivo), true);
        afirmar($conhecido, "tipo do campo {$chave}.{$nome} é conhecido");
    }
}
