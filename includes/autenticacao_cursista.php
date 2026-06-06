<?php
/**
 * Sessão e autenticação do cursista (área pública). Sessão isolada do admin
 * sob o cookie `cl_site`. É a sessão única do site público (também usada pelo CSRF).
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once __DIR__ . '/cursistas.php';

/**
 * Inicia a sessão pública (cl_site) com cookies endurecidos, uma única vez.
 */
function iniciar_sessao_site(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('cl_site');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => ($_SERVER['HTTPS'] ?? '') !== '',
    ]);
    session_start();
}

/**
 * Devolve os dados do cursista logado, ou null. NÃO inicia sessão para anônimo
 * (sem o cookie cl_site), preservando o cache de HTML para visitantes.
 */
function cursista_logado(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!isset($_COOKIE['cl_site'])) {
            return null;
        }
        iniciar_sessao_site();
    }
    return $_SESSION['cursista'] ?? null;
}

/**
 * Registra o cursista na sessão após login bem-sucedido.
 */
function logar_cursista(array $cursista): void
{
    iniciar_sessao_site();
    session_regenerate_id(true);
    $_SESSION['cursista'] = [
        'id'    => (int) $cursista['id'],
        'nome'  => (string) $cursista['nome'],
        'email' => (string) $cursista['email'],
    ];
}

/**
 * Encerra a sessão do cursista.
 */
function deslogar_cursista(): void
{
    iniciar_sessao_site();
    $_SESSION = [];
    session_destroy();
}

/**
 * Exige cursista logado. Redireciona para /entrar preservando o destino.
 */
function exigir_cursista(): void
{
    iniciar_sessao_site();
    if (($_SESSION['cursista'] ?? null) === null) {
        $destino = rawurlencode($_SERVER['REQUEST_URI'] ?? '/minha-conta');
        header('Location: /entrar?destino=' . $destino);
        exit;
    }
}

/**
 * Valida um destino de redirecionamento interno (evita open redirect).
 * Aceita só caminhos que começam com uma única barra.
 */
function destino_seguro(?string $destino, string $padrao = '/minha-conta'): string
{
    // Rejeita: vazio; não-absoluto; protocolo-relativo (//host); barra invertida
    // (o navegador normaliza \ para / e //host vira open redirect); e caracteres de controle.
    if (!is_string($destino) || $destino === '' || $destino[0] !== '/'
        || str_starts_with($destino, '//')
        || str_contains($destino, '\\')
        || preg_match('/[\x00-\x1f]/', $destino) === 1) {
        return $padrao;
    }
    return $destino;
}
