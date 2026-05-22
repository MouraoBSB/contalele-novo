<?php
/**
 * Funções utilitárias do site Conta Lelê.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Escapa um valor para saída segura em HTML (anti-XSS).
 */
function e(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Gera um slug amigável a partir de um texto (minúsculo, sem acento, com hífens).
 * Usa a extensão intl (Normalizer) para remover acentos de forma confiável,
 * com iconv como alternativa caso intl não esteja disponível.
 */
function gerar_slug(string $texto): string
{
    $texto = trim($texto);
    if (class_exists('Normalizer')) {
        $texto = Normalizer::normalize($texto, Normalizer::FORM_D) ?: $texto;
        $texto = preg_replace('/\p{Mn}/u', '', $texto); // remove marcas de acento
    } else {
        $transliterado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($transliterado !== false) {
            $texto = $transliterado;
        }
    }
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim($texto, '-');
}

/**
 * Valida um endereço de e-mail.
 */
function validar_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Limpa um texto de entrada: remove tags, apara e colapsa espaços.
 */
function limpar_texto(string $valor): string
{
    $valor = strip_tags($valor);
    $valor = preg_replace('/\s+/u', ' ', $valor);
    return trim($valor);
}

/**
 * Devolve o token CSRF da sessão, criando-o se necessário.
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Valida um token CSRF recebido contra o da sessão (comparação segura).
 */
function csrf_validar(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return !empty($_SESSION['csrf'])
        && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}
