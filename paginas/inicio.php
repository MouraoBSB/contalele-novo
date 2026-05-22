<?php
/**
 * Página inicial do site Conta Lelê.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

$conteudo = require CL_RAIZ . '/includes/conteudo.php';
require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$cordeisDestaque = array_slice(listar_cordeis(), 0, 3);
$depoimentos     = listar_depoimentos();
$whats           = configuracao('whatsapp', '5561991938603');

$seo['titulo']    = 'Conta Lelê — Contação de histórias, cordéis e cursos';
$seo['descricao'] = 'A Lelê conta e escreve histórias de verdade para escolas, '
    . 'eventos e famílias. Conheça o trabalho, os cordéis e os livros.';
$seo['json_ld']   = json_ld_pessoa($seo['url_base']);
?>
<section class="cl-hero">
  <div class="cl-conteudo cl-hero__interno">
    <div>
      <p class="cl-eyebrow">Antes do livro vem o som</p>
      <h1 class="cl-secao__titulo">Eu sou a <span class="cl-em">Lelê</span>!
        Conto e escrevo histórias pra você.</h1>
      <p style="font-size:18px;color:var(--cl-ink-dim)"><?= e($conteudo['hero_sub']) ?></p>
      <p>
        <a class="cl-btn cl-btn-primary" href="/contacao">Conhecer o trabalho</a>
        <a class="cl-btn cl-btn-yellow" href="https://wa.me/<?= e($whats) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
      </p>
    </div>
    <img class="cl-hero__foto" src="/assets/img/lele-hero.jpg"
         alt="A Lelê, contadora de histórias" width="640" height="800">
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Quem é a Lelê</p>
    <h2 class="cl-secao__titulo">Histórias que <span class="cl-em">transformam</span></h2>
    <p style="max-width:680px;font-size:16px"><?= e($conteudo['proposito']) ?></p>
    <p><a class="cl-btn cl-btn-ghost" href="/sobre">Conhecer a Lelê</a></p>
  </div>
</section>

<section class="cl-secao cl-secao--alt">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">O que a Lelê faz</p>
    <h2 class="cl-secao__titulo">Conheça os serviços</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach (array_slice($conteudo['servicos'], 0, 6) as $s): ?>
        <article class="cl-card">
          <h3><?= e($s['titulo']) ?></h3>
          <p><?= e($s['texto']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:24px"><a class="cl-btn cl-btn-primary" href="/contacao">Ver tudo sobre contação</a></p>
  </div>
</section>

<?php if ($cordeisDestaque): ?>
<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Literatura de cordel</p>
    <h2 class="cl-secao__titulo">Cordéis da Lelê</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach ($cordeisDestaque as $c): ?>
        <article class="cl-card cl-card-midia">
          <?php if ($c['imagem_capa']): ?>
            <img src="/assets/img/<?= e($c['imagem_capa']) ?>" alt="Capa de <?= e($c['titulo']) ?>" loading="lazy">
          <?php endif; ?>
          <div class="cl-card-midia__corpo">
            <h3><?= e($c['titulo']) ?></h3>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:24px"><a class="cl-btn cl-btn-ghost" href="/cordeis">Ver todos os cordéis</a></p>
  </div>
</section>
<?php endif; ?>

<?php if ($depoimentos): ?>
<section class="cl-secao cl-secao--alt">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">O que dizem</p>
    <h2 class="cl-secao__titulo">Quem já viveu uma história</h2>
    <div class="cl-grade">
      <?php foreach ($depoimentos as $d): ?>
        <figure class="cl-depo" style="margin:0">
          <blockquote style="margin:0;font-size:15px">"<?= e($d['texto']) ?>"</blockquote>
          <figcaption class="cl-depo__autor">
            <?php if ($d['foto']): ?>
              <img src="/assets/img/<?= e($d['foto']) ?>" alt="<?= e($d['autor']) ?>" loading="lazy">
            <?php endif; ?>
            <strong><?= e($d['autor']) ?></strong>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="cl-secao">
  <div class="cl-conteudo">
    <div class="cl-faixa">
      <p class="cl-eyebrow" style="color:var(--cl-bg-3)">Canal no YouTube</p>
      <h2 class="cl-secao__titulo">Vídeo novo toda <span class="cl-em">sexta às 16h</span></h2>
      <p>Histórias para crianças e famílias, sempre com a Lelê.</p>
      <a class="cl-btn cl-btn-yellow" href="https://www.youtube.com/c/ContaLel%C3%AA" target="_blank" rel="noopener">Inscrever-se no canal</a>
    </div>
  </div>
</section>
