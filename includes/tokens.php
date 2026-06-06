<?php
/**
 * Tokens de uso único do site Conta Lelê (verificação de e-mail e recuperação de senha).
 * Agnóstico de escopo: serve cursista e admin. Grava apenas o hash do token.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

const TOKEN_TTL_VERIFICACAO = 86400; // 24 horas
const TOKEN_TTL_RECUPERACAO = 3600;  // 1 hora

/**
 * Gera um token cru de 256 bits (64 caracteres hexadecimais).
 */
function gerar_token_cru(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Devolve o hash sha256 (hex) do token cru. É o que se guarda no banco.
 */
function hash_token(string $tokenCru): string
{
    return hash('sha256', $tokenCru);
}

/**
 * Cria um token, grava o hash em tokens_autenticacao e devolve o token cru.
 */
function criar_token(string $escopo, int $usuarioId, string $finalidade, int $ttlSegundos): string
{
    // Housekeeping oportunístico: ~2% das criações limpam tokens expirados/usados antigos.
    if (random_int(1, 50) === 1) {
        limpar_tokens_expirados();
    }
    $tokenCru = gerar_token_cru();
    // Usa o relógio do banco (NOW()) também para a expiração, evitando divergência de fuso PHP×MySQL.
    $ttlSegundos = (int) $ttlSegundos;
    bd()->prepare(
        "INSERT INTO tokens_autenticacao (escopo, usuario_id, finalidade, token_hash, expira_em)
         VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL {$ttlSegundos} SECOND))"
    )->execute([$escopo, $usuarioId, $finalidade, hash_token($tokenCru)]);
    return $tokenCru;
}

/**
 * Valida e consome um token: confere finalidade, não-uso e validade; marca como usado.
 * Devolve a linha (com escopo e usuario_id) ou null se inválido.
 */
function consumir_token(string $tokenCru, string $finalidade): ?array
{
    if ($tokenCru === '') {
        return null;
    }
    $st = bd()->prepare(
        'SELECT * FROM tokens_autenticacao
         WHERE token_hash = ? AND finalidade = ? AND usado_em IS NULL AND expira_em > NOW()'
    );
    $st->execute([hash_token($tokenCru), $finalidade]);
    $linha = $st->fetch();
    if (!$linha) {
        return null;
    }
    bd()->prepare('UPDATE tokens_autenticacao SET usado_em = NOW() WHERE id = ?')
        ->execute([$linha['id']]);
    return $linha;
}

/**
 * Marca como usados todos os tokens pendentes de uma finalidade para um usuário.
 */
function invalidar_tokens(string $escopo, int $usuarioId, string $finalidade): void
{
    bd()->prepare(
        'UPDATE tokens_autenticacao SET usado_em = NOW()
         WHERE escopo = ? AND usuario_id = ? AND finalidade = ? AND usado_em IS NULL'
    )->execute([$escopo, $usuarioId, $finalidade]);
}

/**
 * Diz se houve um token criado há menos de $segundos (throttle de envio).
 */
function token_recente(string $escopo, int $usuarioId, string $finalidade, int $segundos): bool
{
    $segundos = (int) $segundos;
    $st = bd()->prepare(
        "SELECT 1 FROM tokens_autenticacao
         WHERE escopo = ? AND usuario_id = ? AND finalidade = ?
           AND criado_em > DATE_SUB(NOW(), INTERVAL {$segundos} SECOND) LIMIT 1"
    );
    $st->execute([$escopo, $usuarioId, $finalidade]);
    return (bool) $st->fetchColumn();
}

/**
 * Housekeeping: remove tokens expirados e os já usados há mais de 7 dias.
 */
function limpar_tokens_expirados(): void
{
    bd()->query(
        'DELETE FROM tokens_autenticacao
         WHERE expira_em < NOW()
            OR (usado_em IS NOT NULL AND usado_em < DATE_SUB(NOW(), INTERVAL 7 DAY))'
    );
}
