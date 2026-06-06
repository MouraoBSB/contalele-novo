<?php
/**
 * Acesso a dados dos cursistas (usuarios_cursistas) e regras de validação/bloqueio.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once __DIR__ . '/conexao.php';

const CURSISTA_MAX_TENTATIVAS = 5;
const CURSISTA_BLOQUEIO_MINUTOS = 15;

/** Senha mínima do projeto: 8 caracteres. */
function senha_forte(string $senha): bool
{
    return mb_strlen($senha) >= 8;
}

/** Normaliza um e-mail para comparação/armazenamento (apara e baixa a caixa). */
function normalizar_email(string $email): string
{
    return mb_strtolower(trim($email));
}

/**
 * Decide o próximo estado de tentativas a partir do total atual.
 * Pura (sem I/O) para ser testável.
 *
 * @return array{tentativas:int,bloquear:bool}
 */
function decisao_bloqueio(int $tentativasAtuais): array
{
    $t = $tentativasAtuais + 1;
    if ($t >= CURSISTA_MAX_TENTATIVAS) {
        return ['tentativas' => 0, 'bloquear' => true];
    }
    return ['tentativas' => $t, 'bloquear' => false];
}

function cursista_por_email(string $email): ?array
{
    $st = bd()->prepare('SELECT * FROM usuarios_cursistas WHERE email = ? LIMIT 1');
    $st->execute([normalizar_email($email)]);
    return $st->fetch() ?: null;
}

function cursista_por_id(int $id): ?array
{
    $st = bd()->prepare('SELECT * FROM usuarios_cursistas WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function cursista_por_google_id(string $googleId): ?array
{
    $st = bd()->prepare('SELECT * FROM usuarios_cursistas WHERE google_id = ? LIMIT 1');
    $st->execute([$googleId]);
    return $st->fetch() ?: null;
}

/**
 * Cria um cursista e devolve o id. Senha e google_id podem ser nulos.
 */
function criar_cursista(string $nome, string $email, ?string $senhaHash, ?string $googleId, bool $verificado): int
{
    bd()->prepare(
        'INSERT INTO usuarios_cursistas (nome, email, senha_hash, google_id, email_verificado, verificado_em)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $nome,
        normalizar_email($email),
        $senhaHash,
        $googleId,
        $verificado ? 1 : 0,
        $verificado ? date('Y-m-d H:i:s') : null,
    ]);
    return (int) bd()->lastInsertId();
}

function vincular_google_cursista(int $id, string $googleId): void
{
    bd()->prepare('UPDATE usuarios_cursistas SET google_id = ? WHERE id = ?')
        ->execute([$googleId, $id]);
}

function marcar_email_verificado(int $id): void
{
    bd()->prepare(
        'UPDATE usuarios_cursistas SET email_verificado = 1, verificado_em = NOW() WHERE id = ?'
    )->execute([$id]);
}

function atualizar_senha_cursista(int $id, string $senhaHash): void
{
    bd()->prepare('UPDATE usuarios_cursistas SET senha_hash = ? WHERE id = ?')
        ->execute([$senhaHash, $id]);
}

function atualizar_nome_cursista(int $id, string $nome): void
{
    bd()->prepare('UPDATE usuarios_cursistas SET nome = ? WHERE id = ?')
        ->execute([$nome, $id]);
}

function registrar_acesso_cursista(int $id): void
{
    bd()->prepare(
        'UPDATE usuarios_cursistas SET tentativas_login = 0, bloqueado_ate = NULL, ultimo_acesso = NOW() WHERE id = ?'
    )->execute([$id]);
}

function cursista_bloqueado(array $cursista): bool
{
    return $cursista['bloqueado_ate'] !== null
        && strtotime((string) $cursista['bloqueado_ate']) > time();
}

/**
 * Registra uma tentativa de login falha, aplicando o bloqueio quando atingir o limite.
 */
function registrar_tentativa_falha_cursista(array $cursista): void
{
    $d = decisao_bloqueio((int) $cursista['tentativas_login']);
    if ($d['bloquear']) {
        bd()->prepare(
            "UPDATE usuarios_cursistas SET tentativas_login = 0,
             bloqueado_ate = DATE_ADD(NOW(), INTERVAL " . CURSISTA_BLOQUEIO_MINUTOS . " MINUTE) WHERE id = ?"
        )->execute([(int) $cursista['id']]);
    } else {
        bd()->prepare('UPDATE usuarios_cursistas SET tentativas_login = ? WHERE id = ?')
            ->execute([$d['tentativas'], (int) $cursista['id']]);
    }
}
