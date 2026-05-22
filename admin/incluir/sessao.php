<?php
/**
 * Sessão e autenticação do painel administrativo.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Inicia a sessão do painel com cookies endurecidos (uma única vez).
 */
function iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('cl_admin');
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
 * Devolve os dados do administrador logado, ou null.
 */
function usuario_logado(): ?array
{
    return $_SESSION['admin'] ?? null;
}

/**
 * Registra o administrador na sessão após login bem-sucedido.
 */
function logar(array $usuario): void
{
    iniciar_sessao();
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id'                   => (int) $usuario['id'],
        'nome'                 => (string) $usuario['nome'],
        'email'                => (string) $usuario['email'],
        'precisa_trocar_senha' => (int) $usuario['precisa_trocar_senha'],
    ];
}

/**
 * Encerra a sessão do administrador.
 */
function deslogar(): void
{
    iniciar_sessao();
    $_SESSION = [];
    session_destroy();
}

/**
 * Exige login. Redireciona para o login se não houver; força a troca
 * de senha no primeiro acesso. Chame no topo de toda página protegida.
 */
function exigir_login(): void
{
    iniciar_sessao();
    if (usuario_logado() === null) {
        header('Location: /admin/login.php');
        exit;
    }
    $u = usuario_logado();
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!empty($u['precisa_trocar_senha']) && $script !== 'trocar-senha.php' && $script !== 'sair.php') {
        header('Location: /admin/trocar-senha.php');
        exit;
    }
}
