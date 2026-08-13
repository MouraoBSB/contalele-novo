<?php
/**
 * Pré-visualização protegida por senha para páginas ainda não publicadas.
 *
 * Enquanto a chave `<prefixo>_publicado` não for '1', a página só abre para
 * quem souber a senha guardada em `<prefixo>_previa_hash`. Serve para mostrar
 * a peça ao cliente antes de liberar ao público, sem indexação.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/repositorio.php';
require_once __DIR__ . '/funcoes.php';
require_once __DIR__ . '/limites.php';
require_once __DIR__ . '/autenticacao_cursista.php';

/**
 * Libera a página, ou interrompe a requisição pedindo a senha.
 * Devolve true quando a página está publicada (acesso público normal).
 */
function exigir_previa(string $prefixo, string $titulo): bool
{
    if (configuracao($prefixo . '_publicado', '0') === '1') {
        return true;
    }

    iniciar_sessao_site();
    $marca = 'previa_' . $prefixo;

    if (!empty($_SESSION[$marca])) {
        return false;
    }

    $hash  = configuracao($prefixo . '_previa_hash', '');
    $erro  = null;
    $bloqueado = acao_excedida('previa_senha', 10, 900);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if ($bloqueado) {
            $erro = 'Muitas tentativas. Espere alguns minutos e tente de novo.';
        } elseif (!csrf_validar($_POST['csrf'] ?? null)) {
            $erro = 'A página ficou aberta tempo demais. Recarregue e tente de novo.';
        } elseif ($hash === '') {
            $erro = 'A pré-visualização ainda não foi liberada.';
        } else {
            registrar_acao('previa_senha');
            if (password_verify((string) ($_POST['senha'] ?? ''), $hash)) {
                session_regenerate_id(true);
                $_SESSION[$marca] = true;
                // Redireciona para a própria URL: evita reenvio do formulário no F5.
                header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
                exit;
            }
            $erro = 'Senha incorreta.';
        }
    }

    previa_formulario($titulo, $erro, $hash === '');
    exit;
}

/**
 * Desenha a tela de senha. Encerra a resposta em seguida (o chamador dá exit).
 */
function previa_formulario(string $titulo, ?string $erro, bool $semSenha): void
{
    http_response_code($erro !== null ? 401 : 200);
    header('X-Robots-Tag: noindex, nofollow');
    $token = csrf_token();
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Pré-visualização — <?= e($titulo) ?></title>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Fredoka+One&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:grid;place-items:center;padding:24px;
  background:#fdf5d4;color:#2b1300;font-family:'Nunito','Segoe UI',system-ui,sans-serif}
.caixa{width:100%;max-width:420px;background:#fff;border:1px solid rgba(43,19,0,.08);
  border-radius:24px;padding:36px 30px;box-shadow:0 20px 40px -20px rgba(43,19,0,.3);text-align:center}
.marca{font-family:'Fredoka One',cursive;font-size:30px;line-height:1;margin-bottom:6px}
.marca span{color:#c4390e}
.olho{font-size:11px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;
  color:#c4390e;margin-bottom:22px}
p.ajuda{font-size:14.5px;color:rgba(43,19,0,.58);margin-bottom:22px;line-height:1.55}
label{display:block;text-align:left;font-size:13px;font-weight:800;margin-bottom:7px}
input{width:100%;padding:13px 15px;border:1.5px solid rgba(43,19,0,.18);border-radius:12px;
  font:inherit;font-size:16px;background:#fdf5d4;color:#2b1300}
input:focus{outline:3px solid #c4390e;outline-offset:2px;border-color:#2b1300}
button{width:100%;margin-top:16px;padding:15px 24px;border:0;border-radius:999px;
  background:#2b1300;color:#ffdc3a;font:inherit;font-weight:800;font-size:15px;cursor:pointer}
button:hover{filter:brightness(1.12)}
.erro{background:#fbe5db;color:#c4390e;border:1px solid #f3c8b6;border-radius:12px;
  padding:11px 14px;font-size:14px;font-weight:700;margin-bottom:18px}
</style>
</head>
<body>
<main class="caixa">
    <p class="marca">Conte<span>&amp;</span>Encante</p>
    <p class="olho">Pré-visualização</p>
    <?php if ($erro !== null): ?>
        <p class="erro"><?= e($erro) ?></p>
    <?php endif; ?>
    <?php if ($semSenha): ?>
        <p class="ajuda">Esta página ainda não foi liberada. Defina a senha de
            pré-visualização no painel, em Configurações.</p>
    <?php else: ?>
        <p class="ajuda">Esta página ainda não está publicada. Informe a senha para vê-la.</p>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e($token) ?>">
            <label for="senha">Senha</label>
            <input id="senha" type="password" name="senha" autocomplete="current-password"
                   autofocus required>
            <button type="submit">Ver a página</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
    <?php
}
