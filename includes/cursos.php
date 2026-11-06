<?php
/**
 * Lista única dos cursos da Lelê: textos, preço, promoção e publicação.
 *
 * Alimenta /cursos, a home, o bloco "Outros cursos" e as páginas de venda,
 * para que preço e promoção nunca fiquem diferentes entre elas.
 * Curso novo = item novo em cursos_catalogo().
 *
 * Thiago Mourão — https://github.com/MouraoBSB — 06/11/2026
 */

declare(strict_types=1);

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/repositorio.php';

/** Formata um valor em reais, com centavos só quando existem (R$ 397, R$ 33,09). */
function reais(int|float $valor): string
{
    $centavos = (int) round($valor * 100);
    $casas    = $centavos % 100 === 0 ? 0 : 2;
    return 'R$ ' . number_format($centavos / 100, $casas, ',', '.');
}

/** Parcela sem juros arredondada para cima no centavo, como a Hotmart mostra. */
function parcela_sem_juros(int|float $total, int $parcelas): string
{
    if ($parcelas < 1) {
        throw new InvalidArgumentException("Número de parcelas inválido: {$parcelas}");
    }
    $centavos = (int) round($total * 100);
    $parcela  = intdiv($centavos + $parcelas - 1, $parcelas);
    return number_format($parcela / 100, 2, ',', '.');
}

/**
 * Dados fixos dos cursos, na ordem em que aparecem no site.
 *
 * 'oferta'   preço e checkout normais (o ?off fixa a oferta na Hotmart);
 * 'painel'   chaves de `configuracoes` que, preenchidas, vencem o padrão;
 * 'promocao' Black Friday por tempo limitado, sem data de término: liga e
 *            desliga aqui. Os valores precisam bater com a oferta
 *            "Black Friday 2026" da Hotmart.
 *
 * @return array<string,array<string,mixed>>
 */
function cursos_catalogo(): array
{
    return [
        'conte-e-encante' => [
            'id'       => 'conte-e-encante',
            'nome'     => 'Conte&Encante',
            'url'      => '/conte-e-encante',
            'tom'      => 'cafe',
            'resumo'   => 'O curso completo de contação de histórias: técnica, presença e encantamento '
                . 'para você descobrir o seu jeito de contar.',
            'linha'    => 'O curso completo, com encontros ao vivo com a Lelê.',
            'foto'     => [
                'src' => '/assets/img/curso/curso-hero.jpg', 'w' => 900, 'h' => 900, 'pos' => '50% 28%',
                'alt' => 'Lelê sorri e segura uma lâmpada azul diante de um fundo amarelo',
            ],
            'destaque' => 'Inclui o Cante & Encante como bônus',
            'fatos'    => [
                '27 aulas em 6 módulos + 5 encontros ao vivo',
                '25 horas de carga horária',
                'Certificado de 25 horas',
            ],
            'comparar' => [
                'formato'     => '27 aulas gravadas em 6 módulos, com exercícios práticos',
                'duracao'     => '25 horas',
                'aoVivo'      => '5 encontros com a Lelê, aos sábados pela manhã, pelo Zoom',
                'bonus'       => 'Mapas mentais, fichas de exercício e 2 bônus: o Cante & Encante completo '
                    . 'e Pequenos Objetos, Grandes Histórias',
                'certificado' => '25 horas',
                'acesso'      => '1 ano',
            ],
            'oferta'   => [
                'preco'    => 397,
                'parcelas' => 12,
                'checkout' => 'https://pay.hotmart.com/B107134315C?off=87ea4lrn',
            ],
            'painel'   => [
                'publicado' => 'curso_publicado',
                'preco'     => 'curso_preco',
                'checkout'  => 'curso_checkout_url',
            ],
            'promocao' => [
                'ativa'    => true,
                'nome'     => 'Black Friday',
                'preco'    => 297,
                'checkout' => 'https://pay.hotmart.com/B107134315C?off=36l1f851',
            ],
            // Texto extra quando este curso aparece no bloco "Outros cursos" da página indicada.
            'no_bloco_outros' => [
                'cante-e-encante' => [
                    'nota' => 'O Conte&Encante já inclui o Cante & Encante como bônus. '
                        . 'Se começar por aqui, depois você paga só a diferença.',
                ],
            ],
            'nota_na_propria_pagina' => '',
        ],
        'cante-e-encante' => [
            'id'       => 'cante-e-encante',
            'nome'     => 'Cante & Encante',
            'url'      => '/cante-e-encante',
            'tom'      => 'amarelo',
            'resumo'   => 'Como usar a música para dar vida às histórias, mesmo sem tocar nenhum '
                . 'instrumento nem cantar bem.',
            'linha'    => 'Música nas histórias, com a Lelê e Kleyton Santos.',
            'foto'     => [
                'src' => '/assets/img/cante/aula1-lele-e-kleyton.jpg', 'w' => 1200, 'h' => 675, 'pos' => '58% 30%',
                'alt' => 'Lelê sorri no palco, ao lado do músico Kleyton Santos, sentado com o violão',
            ],
            'destaque' => '',
            'fatos'    => [
                '6 aulas gravadas num palco',
                'Cerca de 2h30 de aulas',
                'Certificado de 3 horas',
            ],
            'comparar' => [
                'formato'     => '6 aulas gravadas num palco, com Kleyton Santos ao violão',
                'duracao'     => 'Cerca de 2h30',
                'aoVivo'      => 'Não tem',
                'bonus'       => 'Letras com cifras, 2 mapas mentais e o e-book Circo Encantado',
                'certificado' => '3 horas',
                'acesso'      => '1 ano',
            ],
            'oferta'   => [
                'preco'    => 97,
                'parcelas' => 12,
                'checkout' => 'https://pay.hotmart.com/O108321613K?off=q5glzjoe',
            ],
            // Página pública, sem senha de prévia: não tem chave de publicação.
            'painel'   => [
                'checkout' => 'cante_checkout_url',
            ],
            'promocao' => [
                'ativa'    => true,
                'nome'     => 'Black Friday',
                'preco'    => 67,
                'checkout' => 'https://pay.hotmart.com/O108321613K?off=nn7nhybh',
            ],
            'no_bloco_outros' => [
                'conte-e-encante' => [
                    'etiqueta' => 'Já incluído aqui como bônus',
                    'nota'     => 'Quer só a parte da música? O Cante & Encante também é vendido separado.',
                ],
            ],
            'nota_na_propria_pagina' => 'Já é aluna ou aluno do Conte&Encante? O Cante & Encante já está '
                . 'na sua área de membros, como bônus.',
        ],
    ];
}

/** Converte o preço digitado no painel ("397", "397,00", "R$ 1.297") em número; null se não der. */
function preco_do_painel(string $texto): int|float|null
{
    $numero = str_replace(['R$', ' ', '.', ','], ['', '', '', '.'], trim($texto));
    if ($numero === '' || !is_numeric($numero) || (float) $numero <= 0) {
        return null;
    }
    $valor = (float) $numero;
    return floor($valor) === $valor ? (int) $valor : $valor;
}

/**
 * Oferta vigente do curso: normal, com o que o painel tiver preenchido,
 * e a promoção calculada sobre o preço normal (ou null, se desligada).
 *
 * @param array<string,string> $painel Valores de `configuracoes` (chave => valor).
 */
function curso_oferta(array $curso, array $painel): array
{
    $doPainel = static function (string $campo) use ($curso, $painel): string {
        $chave = $curso['painel'][$campo] ?? null;
        return $chave === null ? '' : trim((string) ($painel[$chave] ?? ''));
    };

    $preco    = preco_do_painel($doPainel('preco')) ?? $curso['oferta']['preco'];
    $parcelas = (int) $curso['oferta']['parcelas'];
    $checkout = $doPainel('checkout') ?: $curso['oferta']['checkout'];

    $promocao = null;
    if (!empty($curso['promocao']['ativa'])) {
        $p = $curso['promocao'];
        $promocao = [
            'nome'     => $p['nome'],
            'preco'    => $p['preco'],
            'desconto' => $preco - $p['preco'],
            'parcela'  => parcela_sem_juros($p['preco'], $parcelas),
            'checkout' => $p['checkout'],
        ];
    }

    return [
        'preco'    => $preco,
        'parcelas' => $parcelas,
        'parcela'  => parcela_sem_juros($preco, $parcelas),
        'checkout' => $checkout,
        'promocao' => $promocao,
    ];
}

/**
 * Textos da promoção no formato que as páginas de venda usam, ou null.
 *
 * @return array{nome:string,produto:string,checkout:string,de:string,por:string,desconto:string,parcela:string}|null
 */
function curso_promocao_pagina(array $curso, array $oferta): ?array
{
    $p = $oferta['promocao'];
    if ($p === null) {
        return null;
    }
    return [
        'nome'     => $p['nome'],
        'produto'  => $curso['nome'],
        'checkout' => $p['checkout'],
        'de'       => reais($oferta['preco']),
        'por'      => reais($p['preco']),
        'desconto' => reais($p['desconto']),
        'parcela'  => $oferta['parcelas'] . 'x de R$ ' . $p['parcela'],
    ];
}

/** O curso aparece para o público? Sem chave de publicação, sempre. */
function curso_publicado(array $curso, array $painel): bool
{
    $chave = $curso['painel']['publicado'] ?? null;
    return $chave === null || trim((string) ($painel[$chave] ?? '')) === '1';
}

/** Lê do painel só as chaves que o curso usa. */
function curso_valores_painel(array $curso): array
{
    $valores = [];
    foreach ($curso['painel'] ?? [] as $chave) {
        $valores[$chave] = configuracao($chave, '');
    }
    return $valores;
}

/** Curso do catálogo com a oferta vigente em 'vigente'. */
function curso(string $id): array
{
    $curso = cursos_catalogo()[$id] ?? null;
    if ($curso === null) {
        throw new InvalidArgumentException("Curso desconhecido: {$id}");
    }
    $curso['vigente'] = curso_oferta($curso, curso_valores_painel($curso));
    return $curso;
}

/**
 * Cursos visíveis ao público, na ordem do catálogo, já com a oferta vigente.
 *
 * @return list<array<string,mixed>>
 */
function cursos_publicados(): array
{
    $lista = [];
    foreach (cursos_catalogo() as $curso) {
        $painel = curso_valores_painel($curso);
        if (curso_publicado($curso, $painel)) {
            $curso['vigente'] = curso_oferta($curso, $painel);
            $lista[] = $curso;
        }
    }
    return $lista;
}
