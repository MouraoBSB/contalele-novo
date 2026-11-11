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

/**
 * Acrescenta ao link do checkout os parâmetros de afiliado que a Hotmart anexa
 * na URL da página de vendas (a, src, sck, xcod). Sem esse repasse, a comissão
 * de quem chegou por um link de divulgação não é creditada.
 * Lista fechada de parâmetros e valores limpos: nada da URL entra cru no link.
 */
function checkout_com_afiliado(string $urlCheckout, array $parametros): string
{
    if ($urlCheckout === '') {
        return '';
    }
    $repasse = [];
    foreach (['a', 'src', 'sck', 'xcod'] as $nome) {
        $valor = $parametros[$nome] ?? null;
        if (is_string($valor) && $valor !== '') {
            $limpo = preg_replace('/[^A-Za-z0-9_.\-]/', '', $valor);
            if ($limpo !== '') {
                $repasse[$nome] = substr($limpo, 0, 64);
            }
        }
    }
    if ($repasse === []) {
        return $urlCheckout;
    }
    return $urlCheckout . (str_contains($urlCheckout, '?') ? '&' : '?') . http_build_query($repasse);
}

/**
 * Endereço público de um arquivo gravado no banco.
 * O painel salva os envios em uploads/; os arquivos que vêm com o projeto
 * ficam na pasta própria do tipo. Procura primeiro em uploads/.
 */
function url_arquivo(string $arquivo, string $pastaProjeto, ?string $raiz = null): string
{
    $arquivo = ltrim(str_replace('\\', '/', $arquivo), '/');
    if ($arquivo === '' || in_array('..', explode('/', $arquivo), true)) {
        return '';
    }
    $codificado = implode('/', array_map('rawurlencode', explode('/', $arquivo)));
    $raiz ??= CL_RAIZ;
    return is_file($raiz . '/uploads/' . $arquivo)
        ? '/uploads/' . $codificado
        : '/' . $pastaProjeto . '/' . $codificado;
}

/** Imagem gravada no banco: uploads/ ou assets/img/. */
function url_imagem(string $arquivo, ?string $raiz = null): string
{
    return url_arquivo($arquivo, 'assets/img', $raiz);
}

/** PDF gravado no banco: uploads/ ou downloads/. */
function url_pdf(string $arquivo, ?string $raiz = null): string
{
    return url_arquivo($arquivo, 'downloads', $raiz);
}

/** Vídeo gravado no banco: uploads/ ou assets/video/. */
function url_video(string $arquivo, ?string $raiz = null): string
{
    return url_arquivo($arquivo, 'assets/video', $raiz);
}

/**
 * Acrescenta ?v=<data do arquivo> a um endereço do site, para a Cloudflare e o navegador
 * buscarem o arquivo novo quando ele for trocado com o mesmo nome.
 */
function com_versao(string $url, ?string $raiz = null): string
{
    $caminho = rawurldecode($url);
    if ($url === '' || in_array('..', explode('/', $caminho), true)) {
        return $url;
    }
    $arquivo = ($raiz ?? CL_RAIZ) . $caminho;
    return is_file($arquivo) ? $url . '?v=' . (int) filemtime($arquivo) : $url;
}
