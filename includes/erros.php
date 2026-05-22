<?php
/**
 * Tratamento global de erros e logging estruturado do site Conta Lelê.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

if (!defined('CL_RAIZ')) {
    define('CL_RAIZ', dirname(__DIR__));
}

/**
 * Grava uma entrada estruturada no log do dia.
 */
function registrar_log(string $mensagem, array $contexto = [], ?string $dir = null): void
{
    $dir = $dir ?? CL_RAIZ . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $arquivo = $dir . '/erro-' . date('Y-m-d') . '.log';
    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $mensagem;
    if ($contexto !== []) {
        $linha .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    @file_put_contents($arquivo, $linha . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * Exibe uma página de erro amigável e encerra a execução.
 */
function pagina_erro(int $codigo = 500): void
{
    if (!headers_sent()) {
        http_response_code($codigo);
    }
    $titulo = $codigo === 404 ? 'Página não encontrada' : 'Algo deu errado';
    $arquivo = CL_RAIZ . '/paginas/erro-' . ($codigo === 404 ? '404' : '500') . '.php';
    if (is_file($arquivo)) {
        require $arquivo;
    } else {
        echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8">';
        echo '<title>' . $titulo . '</title><p>' . $titulo . '.</p>';
    }
    exit;
}

/**
 * Ativa os handlers globais de erro/exceção conforme o ambiente.
 */
function ativar_tratamento_erros(string $ambiente = 'producao'): void
{
    $producao = $ambiente !== 'desenvolvimento';

    ini_set('display_errors', $producao ? '0' : '1');
    error_reporting(E_ALL);

    set_exception_handler(static function (Throwable $e) use ($producao): void {
        registrar_log('Exceção não tratada: ' . $e->getMessage(), [
            'arquivo' => $e->getFile() . ':' . $e->getLine(),
            'uri'     => $_SERVER['REQUEST_URI'] ?? 'cli',
        ]);
        if ($producao) {
            pagina_erro(500);
        } else {
            echo '<pre>' . $e . '</pre>';
        }
    });

    set_error_handler(static function (int $nivel, string $msg, string $arq, int $linha): bool {
        if (!(error_reporting() & $nivel)) {
            return false;
        }
        throw new ErrorException($msg, 0, $nivel, $arq, $linha);
    });

    register_shutdown_function(static function () use ($producao): void {
        $erro = error_get_last();
        if ($erro !== null && in_array($erro['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            registrar_log('Erro fatal: ' . $erro['message'], [
                'arquivo' => $erro['file'] . ':' . $erro['line'],
            ]);
            if ($producao && !headers_sent()) {
                pagina_erro(500);
            }
        }
    });
}
