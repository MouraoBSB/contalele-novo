<?php
/**
 * Marcação dos cursos: cartão grande (/cursos), cartão compacto (home),
 * comparação lado a lado (/cursos) e bloco "Outros cursos" (páginas de venda).
 * Tudo lê a lista única de includes/cursos.php, com a oferta em 'vigente'.
 *
 * Thiago Mourão — https://github.com/MouraoBSB — 06/11/2026
 */

declare(strict_types=1);

require_once __DIR__ . '/funcoes.php';
require_once __DIR__ . '/girassol.php';
require_once __DIR__ . '/cursos.php';

/** Preço em reais para HTML, sem quebra de linha entre "R$" e o número. */
function reais_html(int|float $valor): string
{
    return str_replace(' ', '&nbsp;', e(reais($valor)));
}

/** Ícone de linha do set da marca (viewBox 24, traço 1.7). */
function curso_icone(string $nome, int $tamanho = 18): string
{
    $desenhos = [
        'seta'  => '<path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.7" '
            . 'stroke-linecap="round" stroke-linejoin="round"/>',
        'check' => '<path d="M4 12.5l5 5 11-11" fill="none" stroke="currentColor" stroke-width="1.9" '
            . 'stroke-linecap="round" stroke-linejoin="round"/>',
        'mais'  => '<path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2" '
            . 'stroke-linecap="round"/>',
    ];
    return '<svg class="cl-ico" viewBox="0 0 24 24" width="' . $tamanho . '" height="' . $tamanho . '"'
        . ' aria-hidden="true" focusable="false">' . $desenhos[$nome] . '</svg>';
}

/** Foto do curso com dimensões reais e enquadramento do catálogo. */
function curso_foto(array $curso, string $classe, bool $preguicosa): string
{
    $f = $curso['foto'];
    return '<img' . ($classe !== '' ? ' class="' . e($classe) . '"' : '')
        . ' src="' . e($f['src']) . '" alt="' . e($f['alt']) . '"'
        . ' width="' . (int) $f['w'] . '" height="' . (int) $f['h'] . '"'
        . ' style="object-position:' . e($f['pos']) . '"'
        . ($preguicosa ? ' loading="lazy"' : '') . '>';
}

/**
 * Bloco de preço dos cartões grandes e do bloco "Outros cursos".
 * Com promoção: selo, preço riscado e parcela promocional; sem: preço e parcela normais.
 * O leitor de tela ouve a frase de .{prefixo}__sr; o visual fica com aria-hidden.
 */
function curso_preco_html(array $curso, string $prefixo): string
{
    $o = $curso['vigente'];
    $p = $o['promocao'];
    $px = e($prefixo);

    if ($p === null) {
        return '<div class="' . $px . '">'
            . '<p class="' . $px . '__valor"><strong>' . reais_html($o['preco']) . '</strong></p>'
            . '<p class="' . $px . '__parc">ou ' . (int) $o['parcelas'] . 'x de R$&nbsp;' . e($o['parcela'])
            . ' sem juros</p></div>';
    }

    return '<div class="' . $px . '">'
        . '<p class="' . $px . '__selo"><b>' . e($p['nome']) . '</b> · por tempo limitado</p>'
        . '<p class="' . $px . '__valor"><span class="' . $px . '__sr">Preço anterior: ' . e(reais($o['preco']))
        . '; preço de ' . e($p['nome']) . ': ' . e(reais($p['preco'])) . '</span>'
        . '<span aria-hidden="true"><span class="' . $px . '__de">de <del>' . reais_html($o['preco'])
        . '</del> por</span> <strong>' . reais_html($p['preco']) . '</strong></span></p>'
        . '<p class="' . $px . '__parc">' . reais_html($p['desconto']) . ' de desconto · ou '
        . (int) $o['parcelas'] . 'x de R$&nbsp;' . e($p['parcela']) . ' sem juros</p></div>';
}

/**
 * Cartão grande de /cursos. Um link só (o botão); o ::after dele cobre o
 * cartão inteiro, então o cartão todo é clicável sem link dentro de link.
 */
function curso_cartao(array $curso): void
{
    $id    = 'cl-curso-' . $curso['id'];
    $botao = in_array($curso['tom'], ['cafe', 'laranja'], true) ? 'cl-btn-yellow' : 'cl-btn-primary';
    ?>
    <article class="cl-curso cl-tom-<?= e($curso['tom']) ?>" aria-labelledby="<?= e($id) ?>">
        <figure class="cl-curso__foto">
            <?= curso_foto($curso, '', false) ?>
            <?php if ($curso['destaque'] !== ''): ?>
                <p class="cl-curso__inclui"><?= curso_icone('mais', 16) ?><?= e($curso['destaque']) ?></p>
            <?php endif; ?>
        </figure>
        <div class="cl-curso__corpo">
            <h3 class="cl-curso__nome" id="<?= e($id) ?>"><?= e($curso['nome']) ?></h3>
            <p class="cl-curso__resumo"><?= e($curso['resumo']) ?></p>
            <ul class="cl-curso__fatos">
                <?php foreach ($curso['fatos'] as $fato): ?>
                    <li><?= curso_icone('check') ?><?= e($fato) ?></li>
                <?php endforeach; ?>
            </ul>
            <?= curso_preco_html($curso, 'cl-preco') ?>
            <a class="cl-btn <?= $botao ?> cl-curso__link" href="<?= e($curso['url']) ?>">Conhecer o
                curso<?= curso_icone('seta') ?><span class="cl-sr"> <?= e($curso['nome']) ?></span></a>
        </div>
        <?php girassol_ref(['classe' => 'cl-curso__girassol']); ?>
    </article>
    <?php
}

/** Cartão compacto do topo da home. O link do nome cobre o cartão inteiro. */
function curso_mini(array $curso): void
{
    $o = $curso['vigente'];
    $p = $o['promocao'];
    ?>
    <article class="cl-mini cl-tom-<?= e($curso['tom']) ?>">
        <?= curso_foto($curso, 'cl-mini__foto', false) ?>
        <div class="cl-mini__corpo">
            <h3 class="cl-mini__nome"><a href="<?= e($curso['url']) ?>"><?= e($curso['nome']) ?></a></h3>
            <p class="cl-mini__linha"><?= e($curso['linha']) ?></p>
            <p class="cl-mini__preco">
                <?php if ($p !== null): ?>
                    <span class="cl-sr">Preço anterior: <?= e(reais($o['preco'])) ?>; preço de
                        <?= e($p['nome']) ?>: <?= e(reais($p['preco'])) ?>.</span>
                    <span aria-hidden="true"><del><?= reais_html($o['preco']) ?></del>
                        <strong><?= reais_html($p['preco']) ?></strong></span>
                    <span class="cl-mini__selo"><?= e($p['nome']) ?></span>
                <?php else: ?>
                    <strong><?= reais_html($o['preco']) ?></strong>
                    <span class="cl-mini__parc">ou <?= (int) $o['parcelas'] ?>x de R$&nbsp;<?= e($o['parcela']) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <span class="cl-mini__seta" aria-hidden="true"><?= curso_icone('seta') ?></span>
    </article>
    <?php
}

/** Tabela "Lado a lado" de /cursos, com uma coluna por curso. */
function cursos_comparacao(array $cursos): void
{
    $linhas = [
        'formato'     => 'Formato',
        'duracao'     => 'Duração',
        'aoVivo'      => 'Encontros ao vivo',
        'bonus'       => 'Materiais e bônus',
        'certificado' => 'Certificado',
        'acesso'      => 'Acesso',
    ];
    ?>
    <table class="cl-comparar" style="--cl-n:<?= count($cursos) ?>">
        <caption class="cl-sr">Comparação entre os cursos</caption>
        <thead>
            <tr>
                <td></td>
                <?php foreach ($cursos as $c): ?>
                    <th scope="col" class="cl-tom-<?= e($c['tom']) ?>"><?= e($c['nome']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($linhas as $campo => $rotulo): ?>
                <tr>
                    <th scope="row"><?= e($rotulo) ?></th>
                    <?php foreach ($cursos as $c): ?>
                        <td data-curso="<?= e($c['nome']) ?>"><?= e($c['comparar'][$campo]) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th scope="row">Preço</th>
                <?php foreach ($cursos as $c): ?>
                    <?php $o = $c['vigente']; $p = $o['promocao']; ?>
                    <td data-curso="<?= e($c['nome']) ?>">
                        <?php if ($p !== null): ?>
                            <span class="cl-sr">Preço anterior: <?= e(reais($o['preco'])) ?>; preço de
                                <?= e($p['nome']) ?>:</span>
                            <span aria-hidden="true"><del><?= reais_html($o['preco']) ?></del></span>
                            <b><?= reais_html($p['preco']) ?></b>
                            <small>ou <?= (int) $o['parcelas'] ?>x de R$&nbsp;<?= e($p['parcela']) ?></small>
                        <?php else: ?>
                            <b><?= reais_html($o['preco']) ?></b>
                            <small>ou <?= (int) $o['parcelas'] ?>x de R$&nbsp;<?= e($o['parcela']) ?></small>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>
    <?php
}

/**
 * Bloco "Outros cursos da Lelê", no fim da página de venda do curso $atualId.
 * Mostra os outros cursos publicados; sem nenhum, não imprime nada.
 */
function cursos_bloco_outros(string $atualId): void
{
    $outros = array_values(array_filter(
        cursos_publicados(),
        static fn(array $c): bool => $c['id'] !== $atualId
    ));
    if ($outros === []) {
        return;
    }
    $aviso = (string) (cursos_catalogo()[$atualId]['nota_na_propria_pagina'] ?? '');
    ?>
    <section class="cl-outros" aria-labelledby="cl-outros-titulo">
        <div class="cl-outros-wrap">
            <h2 class="cl-outros-olho" id="cl-outros-titulo">Outros cursos da Lelê</h2>
            <div class="cl-outros-lista">
                <?php foreach ($outros as $c): ?>
                    <?php $rel = $c['no_bloco_outros'][$atualId] ?? []; ?>
                    <article class="cl-outros-card">
                        <?= curso_foto($c, 'cl-outros-foto', true) ?>
                        <div class="cl-outros-corpo">
                            <?php if (!empty($rel['etiqueta'])): ?>
                                <p class="cl-outros-etiqueta"><?= curso_icone('check', 16) ?><?= e($rel['etiqueta']) ?></p>
                            <?php endif; ?>
                            <h3 class="cl-outros-nome"><?= e($c['nome']) ?></h3>
                            <p class="cl-outros-resumo"><?= e($c['resumo']) ?></p>
                            <?= curso_preco_html($c, 'cl-outros-preco') ?>
                            <?php if (!empty($rel['nota'])): ?>
                                <p class="cl-outros-nota"><?= e($rel['nota']) ?></p>
                            <?php endif; ?>
                            <a class="cl-outros-link" href="<?= e($c['url']) ?>">Conhecer o curso<span
                                class="cl-outros-sr"> <?= e($c['nome']) ?></span><?= curso_icone('seta') ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ($aviso !== ''): ?>
                <p class="cl-outros-aviso"><?= e($aviso) ?></p>
            <?php endif; ?>
            <p class="cl-outros-todos"><a href="/cursos">Ver todos os cursos<?= curso_icone('seta') ?></a></p>
        </div>
    </section>
    <?php
}
