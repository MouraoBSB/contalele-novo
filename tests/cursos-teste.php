<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/cursos.php';

// reais() — formato de preço usado em todo o site
afirmar_igual('R$ 397', reais(397), 'reais() sem centavos quando o valor é inteiro');
afirmar_igual('R$ 33,09', reais(33.09), 'reais() com centavos e vírgula');
afirmar_igual('R$ 1.234', reais(1234), 'reais() com separador de milhar');

// parcela_sem_juros() — tem de bater com as parcelas que a Hotmart mostra hoje
afirmar_igual('33,09', parcela_sem_juros(397, 12), 'parcela de R$ 397 em 12x');
afirmar_igual('24,75', parcela_sem_juros(297, 12), 'parcela de R$ 297 em 12x');
afirmar_igual('8,09', parcela_sem_juros(97, 12), 'parcela de R$ 97 em 12x');
afirmar_igual('5,59', parcela_sem_juros(67, 12), 'parcela de R$ 67 em 12x');

// Catálogo — campos de que os cartões dependem
$catalogo = cursos_catalogo();
afirmar_igual(['conte-e-encante', 'cante-e-encante'], array_keys($catalogo), 'catálogo tem os dois cursos, nesta ordem');
foreach ($catalogo as $id => $c) {
    $faltando = array_diff(
        ['id', 'nome', 'url', 'tom', 'resumo', 'linha', 'foto', 'destaque', 'fatos', 'comparar', 'oferta', 'promocao'],
        array_keys($c)
    );
    afirmar_igual([], array_values($faltando), "{$id}: tem todos os campos");
    afirmar_igual($id, $c['id'], "{$id}: id igual à chave");
    afirmar(in_array($c['tom'], ['cafe', 'amarelo', 'laranja', 'creme'], true), "{$id}: tom da paleta restrita");
    afirmar_igual([], array_values(array_diff(['src', 'w', 'h', 'pos', 'alt'], array_keys($c['foto']))), "{$id}: foto completa");
    afirmar(is_file(__DIR__ . '/..' . $c['foto']['src']), "{$id}: a foto existe no site");
    afirmar_igual(
        [],
        array_values(array_diff(['formato', 'duracao', 'aoVivo', 'bonus', 'certificado', 'acesso'], array_keys($c['comparar']))),
        "{$id}: comparação completa"
    );
}
afirmar(str_contains(implode(' ', $catalogo['conte-e-encante']['fatos']), '6 módulos'), 'Conte: fatos com os 6 módulos atuais');

// Oferta normal — padrão do código e troca pelo painel
$conte = $catalogo['conte-e-encante'];
$conte['promocao']['ativa'] = false;
$o = curso_oferta($conte, []);
afirmar_igual(397, $o['preco'], 'Conte: preço padrão sem painel');
afirmar_igual('33,09', $o['parcela'], 'Conte: parcela padrão');
afirmar_igual('https://pay.hotmart.com/B107134315C?off=87ea4lrn', $o['checkout'], 'Conte: checkout padrão');
afirmar_igual(null, $o['promocao'], 'promoção desligada some da oferta');

$o = curso_oferta($conte, ['curso_preco' => '450', 'curso_checkout_url' => 'https://pay.hotmart.com/X?off=abc']);
afirmar_igual(450, $o['preco'], 'Conte: preço vem do painel');
afirmar_igual('37,50', $o['parcela'], 'Conte: parcela acompanha o preço do painel');
afirmar_igual('https://pay.hotmart.com/X?off=abc', $o['checkout'], 'Conte: checkout vem do painel');

$o = curso_oferta($conte, ['curso_preco' => '  ', 'curso_checkout_url' => '']);
afirmar_igual(397, $o['preco'], 'Conte: painel em branco volta ao preço padrão');
afirmar_igual('https://pay.hotmart.com/B107134315C?off=87ea4lrn', $o['checkout'], 'Conte: painel em branco volta ao checkout padrão');

// Promoção ligada
$o = curso_oferta($catalogo['conte-e-encante'], []);
afirmar_igual(297, $o['promocao']['preco'], 'Conte: preço de Black Friday');
afirmar_igual(100, $o['promocao']['desconto'], 'Conte: desconto calculado sobre o preço normal');
afirmar_igual('24,75', $o['promocao']['parcela'], 'Conte: parcela de Black Friday');
afirmar_igual('https://pay.hotmart.com/B107134315C?off=36l1f851', $o['promocao']['checkout'], 'Conte: checkout de Black Friday');

$o = curso_oferta($catalogo['cante-e-encante'], []);
afirmar_igual(97, $o['preco'], 'Cante: preço normal');
afirmar_igual('https://pay.hotmart.com/O108321613K?off=q5glzjoe', $o['checkout'], 'Cante: checkout normal');
afirmar_igual(30, $o['promocao']['desconto'], 'Cante: desconto de Black Friday');

// Textos da promoção para as páginas de venda: os mesmos de antes da lista única
afirmar_igual(
    [
        'nome'     => 'Black Friday',
        'produto'  => 'Conte&Encante',
        'checkout' => 'https://pay.hotmart.com/B107134315C?off=36l1f851',
        'de'       => 'R$ 397',
        'por'      => 'R$ 297',
        'desconto' => 'R$ 100',
        'parcela'  => '12x de R$ 24,75',
    ],
    curso_promocao_pagina($catalogo['conte-e-encante'], curso_oferta($catalogo['conte-e-encante'], [])),
    'Conte: textos da promoção iguais aos da página'
);
afirmar_igual(
    [
        'nome'     => 'Black Friday',
        'produto'  => 'Cante & Encante',
        'checkout' => 'https://pay.hotmart.com/O108321613K?off=nn7nhybh',
        'de'       => 'R$ 97',
        'por'      => 'R$ 67',
        'desconto' => 'R$ 30',
        'parcela'  => '12x de R$ 5,59',
    ],
    curso_promocao_pagina($catalogo['cante-e-encante'], curso_oferta($catalogo['cante-e-encante'], [])),
    'Cante: textos da promoção iguais aos da página'
);
afirmar_igual(null, curso_promocao_pagina($conte, curso_oferta($conte, [])), 'sem promoção, a página recebe null');

// Publicação — o Conte segue a chave do painel; o Cante é sempre público
afirmar(curso_publicado($catalogo['conte-e-encante'], ['curso_publicado' => '1']), 'Conte: publicado com a chave em 1');
afirmar(!curso_publicado($catalogo['conte-e-encante'], ['curso_publicado' => '0']), 'Conte: escondido com a chave em 0');
afirmar(!curso_publicado($catalogo['conte-e-encante'], []), 'Conte: escondido sem a chave');
afirmar(curso_publicado($catalogo['cante-e-encante'], []), 'Cante: sempre publicado');
