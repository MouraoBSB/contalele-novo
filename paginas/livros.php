<?php
/**
 * Página "Livros" — e-books da Lelê com download em PDF.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$ebooks = listar_ebooks();

$seo['titulo']    = 'Livros — e-books da Lelê | Conta Lelê';
$seo['descricao'] = 'Baixe os livros digitais escritos pela Lelê: histórias '
    . 'para crianças, em PDF gratuito.';
?>

<section class="cl-hero-simples">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Livros digitais</p>
        <h1>Os <span class="cl-em">livros</span> da Lelê</h1>
        <p>Histórias escritas pela Lelê, para ler e reler — disponíveis para download.</p>
    </div>
</section>

<section class="cl-secao" style="padding-top:48px">
    <div class="cl-conteudo">
        <?php if (!$ebooks): ?>
            <p>Em breve, novos livros por aqui.</p>
        <?php else: ?>
            <div class="cl-grade cl-grade--3 cl-stagger">
                <?php foreach ($ebooks as $b): ?>
                    <article class="cl-card cl-card-midia">
                        <?php if ($b['imagem_capa']): ?>
                            <img src="/assets/img/<?= e($b['imagem_capa']) ?>" alt="Capa de <?= e($b['titulo']) ?>" loading="lazy">
                        <?php endif; ?>
                        <div class="cl-card-midia__corpo">
                            <h3><?= e($b['titulo']) ?></h3>
                            <p><?= e($b['sinopse']) ?></p>
                            <?php if ($b['arquivo_pdf']): ?>
                                <a class="cl-btn cl-btn-primary" href="/downloads/<?= e($b['arquivo_pdf']) ?>" download>
                                    <svg class="cl-icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="7 10 12 15 17 10"/>
                                        <line x1="12" y1="15" x2="12" y2="3"/>
                                    </svg>
                                    Baixar PDF
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
