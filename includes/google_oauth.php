<?php
/**
 * Cliente OAuth2/OpenID Connect do Google, com cURL. Agnóstico de escopo.
 * Credenciais vêm da tabela `configuracoes` (google_client_id/secret/ativo).
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once __DIR__ . '/repositorio.php';

/** True se o login com Google está configurado e ativo. */
function google_configurado(): bool
{
    return configuracao('google_oauth_ativo', '0') === '1'
        && configuracao('google_client_id') !== ''
        && configuracao('google_client_secret') !== '';
}

/** Monta a URL de autorização do Google. */
function google_url_autorizacao(string $redirectUri, string $state): string
{
    $params = [
        'client_id'     => configuracao('google_client_id'),
        'redirect_uri'  => $redirectUri,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/**
 * Troca o código de autorização por um access_token. Devolve o array de tokens ou null.
 */
function google_trocar_codigo(string $code, string $redirectUri): ?array
{
    $resp = google_post('https://oauth2.googleapis.com/token', [
        'code'          => $code,
        'client_id'     => configuracao('google_client_id'),
        'client_secret' => configuracao('google_client_secret'),
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code',
    ]);
    if ($resp === null || empty($resp['access_token'])) {
        return null;
    }
    return $resp;
}

/**
 * Busca o perfil do usuário (userinfo OIDC). Devolve sub/email/email_verified/name/picture ou null.
 */
function google_perfil(string $accessToken): ?array
{
    $ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $codigo !== 200) {
        registrar_log('Google userinfo falhou', ['http' => $codigo]);
        return null;
    }
    $dados = json_decode((string) $body, true);
    return is_array($dados) ? $dados : null;
}

/**
 * POST application/x-www-form-urlencoded que devolve JSON decodificado, ou null.
 */
function google_post(string $url, array $dados): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($dados),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $codigo !== 200) {
        registrar_log('Google token endpoint falhou', ['http' => $codigo]);
        return null;
    }
    $json = json_decode((string) $body, true);
    return is_array($json) ? $json : null;
}

/**
 * Gera um state anti-CSRF.
 */
function google_state(): string
{
    return bin2hex(random_bytes(16));
}
