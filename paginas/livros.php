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
<section class="cl-hero">
  <div class="cl-conteudo" style="padding:48px 16px">
    <p class="cl-eyebrow">Livros digitais</p>
    <h1 class="cl-secao__titulo">Os <span class="cl-em">livros</span> da Lelê</h1>
    <p style="max-width:680px;font-size:16px">Histórias escritas pela Lelê,
      para ler e reler — disponíveis para download.</p>
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo">
    <?php if (!$ebooks): ?>
      <p>Em breve, novos livros por aqui.</p>
    <?php else: ?>
      <div class="cl-grade cl-grade--3">
        <?php foreach ($ebooks as $b): ?>
          <article class="cl-card cl-card-midia">
            <?php if ($b['imagem_capa']): ?>
              <img src="/assets/img/<?= e($b['imagem_capa']) ?>" alt="Capa de <?= e($b['titulo']) ?>" loading="lazy">
            <?php endif; ?>
            <div class="cl-card-midia__corpo">
              <h3><?= e($b['titulo']) ?></h3>
              <p><?= e($b['sinopse']) ?></p>
              <?php if ($b['arquivo_pdf']): ?>
                <p style="margin-top:12px">
                  <a class="cl-btn cl-btn-primary" href="/downloads/<?= e($b['arquivo_pdf']) ?>" download>Baixar PDF</a>
                </p>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
