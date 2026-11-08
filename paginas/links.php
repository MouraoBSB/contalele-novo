<?php
/**
 * Página /links: o "link na bio" do Instagram da Lelê.
 * Leve e feita para o celular: cursos (com o preço vigente), canal, contratação
 * e livros grátis. Topo e rodapé próprios, sem o menu do site.
 *
 * Thiago Mourão — https://github.com/MouraoBSB — 08/11/2026
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/girassol.php';
require_once CL_RAIZ . '/includes/cursos.php';
require_once CL_RAIZ . '/includes/cursos-cartoes.php';
require_once CL_RAIZ . '/includes/links.php';

$layout         = 'landing';
$landingPropria = true;
$cssExtra       = ['/assets/css/links.css'];

$cursos = cursos_publicados();
$botoes = links_bio_botoes(
    configuracao('youtube', 'https://www.youtube.com/c/ContaLel%C3%AA'),
    configuracao('whatsapp', '5561991938603')
);

$seo['titulo']    = 'Links da Lelê | Conta Lelê';
$seo['descricao'] = 'Cursos de contação de histórias com a Lelê, o canal no YouTube, livros grátis '
    . 'e contato para escolas e eventos.';
// Página de passagem do Instagram: quem busca no Google deve cair na home ou em /cursos.
$seo['robots'] = 'noindex, follow';

$icones = [
    'youtube'  => '<rect x="2.5" y="5" width="19" height="14" rx="4"/><path d="M10.2 9.4v5.2l4.4-2.6z"/>',
    'whatsapp' => '<path d="M12 3.6a8.4 8.4 0 0 0-7.3 12.6L3.6 20.4l4.3-1.1A8.4 8.4 0 1 0 12 3.6z"/>'
        . '<path d="M9 8.6c.2 2.9 2.6 5.5 5.6 6.1l1.2-1.4-1.9-.9-.8.8a4.5 4.5 0 0 1-2.2-2.2l.8-.8-.9-1.9z"/>',
    'livros'   => '<path d="M12 6.6C10.2 5.3 7.5 4.8 3.8 5v13c3.7-.2 6.4.3 8.2 1.6 1.8-1.3 4.5-1.8 8.2-1.6V5'
        . 'c-3.7-.2-6.4.3-8.2 1.6z"/><path d="M12 6.6v13"/>',
];
$seta = curso_icone('seta');
?>
<main id="conteudo" class="lk">
    <header class="lk-topo">
        <div class="lk-foto">
            <?php girassol_ref(['classe' => 'lk-foto__girassol', 'tamanho' => 150]); ?>
            <img src="/assets/img/links/lele-oi.jpg" alt="Lelê sorri e acena, com flores no cabelo, num fundo azul"
                width="360" height="360">
        </div>
        <h1 class="lk-marca"><img src="/assets/img/logo.png" alt="Conta Lelê" width="160" height="118"></h1>
        <p class="lk-oi">Oi, eu sou a <em>Lelê</em>!</p>
        <p class="lk-bio">Conto histórias há mais de 20 anos e ensino você a contar também.</p>
    </header>

    <?php if ($cursos !== []): ?>
        <section class="lk-bloco" aria-labelledby="lk-t-cursos">
            <h2 class="lk-olho" id="lk-t-cursos">Cursos</h2>
            <ul class="lk-lista">
                <?php foreach ($cursos as $curso): ?>
                    <?php $o = $curso['vigente']; $p = $o['promocao']; ?>
                    <li>
                        <a class="lk-curso lk-curso--<?= e($curso['tom']) ?>" href="<?= e(link_com_origem($curso['url'])) ?>">
                            <?= curso_foto($curso, 'lk-curso__foto', false) ?>
                            <span class="lk-curso__texto">
                                <span class="lk-curso__nome"><?= e($curso['nome']) ?></span>
                                <span class="lk-curso__linha"><?= e($curso['linha']) ?></span>
                                <span class="lk-curso__preco">
                                    <?php if ($p !== null): ?>
                                        <span class="lk-curso__selo"><?= e($p['nome']) ?></span>
                                        <span class="cl-sr">Preço anterior: <?= e(reais($o['preco'])) ?>; preço de
                                            <?= e($p['nome']) ?>: <?= e(reais($p['preco'])) ?>.</span>
                                        <span aria-hidden="true"><del><?= reais_html($o['preco']) ?></del>
                                            <strong><?= reais_html($p['preco']) ?></strong></span>
                                    <?php else: ?>
                                        <strong><?= reais_html($o['preco']) ?></strong>
                                        <span>ou <?= (int) $o['parcelas'] ?>x de R$&nbsp;<?= e($o['parcela']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </span>
                            <span class="lk-curso__seta" aria-hidden="true"><?= $seta ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="lk-bloco" aria-labelledby="lk-t-mais">
        <h2 class="lk-olho" id="lk-t-mais">Mais da Lelê</h2>
        <ul class="lk-lista">
            <?php foreach ($botoes as $b): ?>
                <li>
                    <a class="lk-botao" href="<?= e($b['href']) ?>"<?= $b['externo'] ? ' target="_blank" rel="noopener"' : '' ?>>
                        <span class="lk-botao__icone" aria-hidden="true"><svg viewBox="0 0 24 24" width="24" height="24"
                            fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                            stroke-linejoin="round" focusable="false"><?= $icones[$b['icone']] ?></svg></span>
                        <span class="lk-botao__texto">
                            <span class="lk-botao__rotulo"><?= e($b['rotulo']) ?></span>
                            <span class="lk-botao__detalhe"><?= e($b['detalhe']) ?></span>
                        </span>
                        <?php if ($b['externo']): ?><span class="cl-sr"> (abre em nova aba)</span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <footer class="lk-rodape">
        <a class="lk-site" href="/">Ver o site completo<?= $seta ?></a>
        <p>&copy; <?= date('Y') ?> Conta Lelê · Planaltina-DF</p>
    </footer>
</main>
