<?php
/**
 * Página "Contação de Histórias" — serviços, públicos e diferenciais.
 * É a página comercial de contratação.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

$conteudo = require CL_RAIZ . '/includes/conteudo.php';
require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$whats = configuracao('whatsapp', '5561991938603');

$seo['titulo']    = 'Contação de Histórias — contrate a Lelê | Conta Lelê';
$seo['descricao'] = 'Contação de histórias para escolas, eventos, empresas e famílias. '
    . 'Conheça os serviços da Lelê e para quem a contação de histórias encanta.';
?>
<section class="cl-hero">
  <div class="cl-conteudo cl-hero__interno">
    <div>
      <p class="cl-eyebrow">Contação de Histórias</p>
      <h1 class="cl-secao__titulo">Torne o seu evento <span class="cl-em">encantador</span></h1>
      <p style="font-size:16px">A Lelê apresenta-se em escolas, festivais, livrarias,
        eventos e casas — de forma presencial ou online.</p>
      <p><a class="cl-btn cl-btn-primary" href="https://wa.me/<?= e($whats) ?>" target="_blank" rel="noopener">Pedir um orçamento</a></p>
    </div>
    <img class="cl-hero__foto" src="/assets/img/lele-contacao.jpg"
         alt="A Lelê contando histórias" width="640" height="800" loading="lazy">
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">O que a Lelê faz</p>
    <h2 class="cl-secao__titulo">Serviços</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach ($conteudo['servicos'] as $s): ?>
        <article class="cl-card"><h3><?= e($s['titulo']) ?></h3><p><?= e($s['texto']) ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="cl-secao cl-secao--alt">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Para quem</p>
    <h2 class="cl-secao__titulo">Para quem contar histórias</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach ($conteudo['publicos'] as $p): ?>
        <article class="cl-card"><h3><?= e($p['titulo']) ?></h3><p><?= e($p['texto']) ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Por que a Lelê</p>
    <h2 class="cl-secao__titulo">Diferenciais</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach ($conteudo['diferenciais'] as $d): ?>
        <article class="cl-card"><h3><?= e($d['titulo']) ?></h3><p><?= e($d['texto']) ?></p></article>
      <?php endforeach; ?>
    </div>
    <div class="cl-faixa" style="margin-top:32px">
      <h2 class="cl-secao__titulo">Vamos encantar o seu evento?</h2>
      <a class="cl-btn cl-btn-yellow" href="/contato">Falar com a Lelê</a>
    </div>
  </div>
</section>
