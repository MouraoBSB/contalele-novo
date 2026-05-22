<?php
/**
 * Runner de testes CLI do site Conta Lelê.
 * Uso: php tests/correr.php
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

// Inicia a sessão antes de qualquer saída — funções que dependem de sessão
// (ex.: CSRF) não emitem warning de "headers already sent" durante os testes.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$GLOBALS['placar'] = ['ok' => 0, 'falhas' => 0];

function afirmar(bool $condicao, string $descricao): void
{
    if ($condicao) {
        $GLOBALS['placar']['ok']++;
        echo "  ok    — {$descricao}\n";
    } else {
        $GLOBALS['placar']['falhas']++;
        echo "  FALHOU — {$descricao}\n";
    }
}

function afirmar_igual($esperado, $obtido, string $descricao): void
{
    $passou = $esperado === $obtido;
    $detalhe = $passou ? '' : ' (esperado ' . var_export($esperado, true)
        . ', obtido ' . var_export($obtido, true) . ')';
    afirmar($passou, $descricao . $detalhe);
}

foreach (glob(__DIR__ . '/*-teste.php') as $arquivo) {
    echo "\n[" . basename($arquivo) . "]\n";
    require $arquivo;
}

echo "\n----------\n";
echo "Total: {$GLOBALS['placar']['ok']} ok, {$GLOBALS['placar']['falhas']} falha(s)\n";
exit($GLOBALS['placar']['falhas'] > 0 ? 1 : 0);
