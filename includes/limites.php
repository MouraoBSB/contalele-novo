<?php
/**
 * Limites de ação por IP (anti-abuso) — contagem em janela deslizante.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once __DIR__ . '/conexao.php';

/** IP da requisição atual (com padrão seguro). */
function ip_requisicao(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/** Registra uma ocorrência da ação para o IP. */
function registrar_acao(string $acao, ?string $ip = null): void
{
    // Housekeeping oportunístico: ~1% dos registros limpam ocorrências antigas.
    if (random_int(1, 100) === 1) {
        limpar_limites_antigos();
    }
    bd()->prepare('INSERT INTO limites_acao (acao, ip) VALUES (?, ?)')
        ->execute([$acao, $ip ?? ip_requisicao()]);
}

/** True se o IP já atingiu $max ocorrências de $acao na janela de $janelaSegundos. */
function acao_excedida(string $acao, int $max, int $janelaSegundos, ?string $ip = null): bool
{
    $janelaSegundos = (int) $janelaSegundos;
    $st = bd()->prepare(
        "SELECT COUNT(*) FROM limites_acao
         WHERE acao = ? AND ip = ? AND criado_em > DATE_SUB(NOW(), INTERVAL {$janelaSegundos} SECOND)"
    );
    $st->execute([$acao, $ip ?? ip_requisicao()]);
    return (int) $st->fetchColumn() >= $max;
}

/** Housekeeping: remove registros com mais de 1 dia. */
function limpar_limites_antigos(): void
{
    bd()->query('DELETE FROM limites_acao WHERE criado_em < DATE_SUB(NOW(), INTERVAL 1 DAY)');
}
