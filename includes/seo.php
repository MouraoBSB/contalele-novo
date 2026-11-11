<?php
/**
 * Helpers de SEO do site Conta Lelê — meta tags, Open Graph e JSON-LD.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

/**
 * Devolve o conjunto padrão de dados de SEO.
 */
function seo_padrao(string $urlBase): array
{
    return [
        'titulo'    => 'Conta Lelê — Contação de histórias, cordéis e cursos',
        'descricao' => 'A Lelê conta e escreve histórias de verdade. '
            . 'Contação de histórias, cordéis, livros e cursos para professores e mediadores.',
        'url_base'  => rtrim($urlBase, '/'),
        'imagem'    => rtrim($urlBase, '/') . '/assets/img/og-padrao.jpg',
    ];
}

/**
 * Renderiza as meta tags de SEO no <head>.
 */
function seo_render(array $seo): void
{
    $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $url = $seo['url_base'] . $caminho;
    echo '<title>' . e($seo['titulo']) . '</title>' . "\n";
    echo '<meta name="description" content="' . e($seo['descricao']) . '">' . "\n";
    if (!empty($seo['robots'])) {
        echo '<meta name="robots" content="' . e($seo['robots']) . '">' . "\n";
    }
    echo '<link rel="canonical" href="' . e($url) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . e($seo['titulo']) . '">' . "\n";
    echo '<meta property="og:description" content="' . e($seo['descricao']) . '">' . "\n";
    echo '<meta property="og:url" content="' . e($url) . '">' . "\n";
    echo '<meta property="og:image" content="' . e($seo['imagem']) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}

/**
 * Renderiza um bloco JSON-LD de dados estruturados.
 */
function json_ld(array $dados): void
{
    echo '<script type="application/ld+json">'
        . json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . '</script>' . "\n";
}

/**
 * Dados estruturados schema.org Person da Lelê (usados na home e no Sobre).
 */
function json_ld_pessoa(string $urlBase): array
{
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'Person',
        'name'     => 'Letícia Rocha Mourão Marques',
        'alternateName' => 'Conta Lelê',
        'jobTitle' => 'Contadora de histórias, cordelista e escritora',
        'url'      => rtrim($urlBase, '/'),
        'address'  => ['@type' => 'PostalAddress', 'addressLocality' => 'Planaltina', 'addressRegion' => 'DF', 'addressCountry' => 'BR'],
        'sameAs'   => [
            'https://www.instagram.com/contalele/',
            'https://www.youtube.com/c/ContaLel%C3%AA',
        ],
    ];
}

/**
 * Nó schema.org VideoObject de um depoimento em vídeo (para o @graph da página).
 * Os caminhos já vêm prontos (com ?v=); sem capa não há miniatura e o nó não é gerado.
 */
function json_ld_depoimento_video(array $d, string $urlBase, string $caminhoVideo, string $caminhoCapa): ?array
{
    if ($caminhoVideo === '' || $caminhoCapa === '') {
        return null;
    }
    $base       = rtrim($urlBase, '/');
    $transcrito = trim((string) preg_replace('/\s+/u', ' ', (string) ($d['transcricao'] ?? '')));
    $destaque   = trim((string) ($d['destaque'] ?? ''));
    $no = [
        '@type'        => 'VideoObject',
        'name'         => 'Depoimento de ' . $d['autor'] . ' sobre o curso de contação de histórias',
        'description'  => $destaque !== '' ? $destaque : mb_strimwidth($transcrito, 0, 160, '…'),
        'thumbnailUrl' => $base . $caminhoCapa,
        'contentUrl'   => $base . $caminhoVideo,
        'inLanguage'   => 'pt-BR',
        'transcript'   => $transcrito,
    ];
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', (string) ($d['criado_em'] ?? ''), $m)) {
        $no['uploadDate'] = $m[0];
    }
    return $no;
}

/** Junta vários nós de dados estruturados num bloco só, com um @context na raiz. */
function json_ld_grafo(array $nos): array
{
    return [
        '@context' => 'https://schema.org',
        '@graph'   => array_map(static fn (array $no): array => array_diff_key($no, ['@context' => true]), $nos),
    ];
}
