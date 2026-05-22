<?php
/**
 * Fábrica de conexão PDO com o banco de dados do site Conta Lelê.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Devolve a conexão PDO compartilhada, criando-a no primeiro uso.
 *
 * @param array|null $config Configuração de 'db' (opcional; usado em testes).
 */
function bd(?array $config = null): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if ($config === null) {
        $raiz = defined('CL_RAIZ') ? CL_RAIZ : dirname(__DIR__);
        $todo = require $raiz . '/config.php';
        $config = $todo['db'];
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['nome'],
        $config['charset']
    );

    try {
        $pdo = new PDO($dsn, $config['usuario'], $config['senha'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        if (function_exists('registrar_log')) {
            registrar_log('Falha ao conectar ao banco', ['erro' => $e->getMessage()]);
        }
        throw new RuntimeException('Não foi possível conectar ao banco de dados.', 0, $e);
    }

    return $pdo;
}
