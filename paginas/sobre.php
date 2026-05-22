<?php
/**
 * Página "A Lelê" — biografia, propósito, prêmios e equipe.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

$conteudo = require CL_RAIZ . '/includes/conteudo.php';
require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$premios = listar_premios();

$seo['titulo']    = 'A Lelê — quem conta as histórias | Conta Lelê';
$seo['descricao'] = 'Conheça Letícia Rocha Mourão, a Lelê: contadora de histórias, '
    . 'cordelista e escritora de Planaltina-DF, com mais de 20 anos de palco.';
$seo['json_ld']   = json_ld_pessoa($seo['url_base']);
?>
<section class="cl-hero">
  <div class="cl-conteudo cl-hero__interno">
    <div>
      <p class="cl-eyebrow">A Lelê</p>
      <h1 class="cl-secao__titulo">Eu sou a <span class="cl-em">Lelê</span>,
        e vou contar uma história pra você!</h1>
      <p style="font-size:16px"><?= e($conteudo['bio']) ?></p>
    </div>
    <img class="cl-hero__foto" src="/assets/img/lele-sobre.jpg"
         alt="A Lelê" width="640" height="800" loading="lazy">
  </div>
</section>

<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Por que contar histórias?</p>
    <h2 class="cl-secao__titulo">O <span class="cl-em">propósito</span></h2>
    <p style="max-width:680px;font-size:16px"><?= e($conteudo['proposito']) ?></p>
  </div>
</section>

<?php if ($premios): ?>
<section class="cl-secao cl-secao--alt">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Reconhecimento</p>
    <h2 class="cl-secao__titulo">Prêmios</h2>
    <ul class="cl-lista">
      <?php foreach ($premios as $p): ?>
        <li class="cl-card"><strong><?= e($p['titulo']) ?></strong><?php
          if (!empty($p['ano'])) { echo ' — ' . e((string) $p['ano']); } ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<section class="cl-secao">
  <div class="cl-conteudo">
    <p class="cl-eyebrow">Quem faz acontecer</p>
    <h2 class="cl-secao__titulo">A equipe</h2>
    <div class="cl-grade cl-grade--3">
      <?php foreach ($conteudo['equipe'] as $m): ?>
        <article class="cl-card cl-card-midia">
          <img src="/assets/img/<?= e($m['foto']) ?>" alt="<?= e($m['nome']) ?>" loading="lazy">
          <div class="cl-card-midia__corpo">
            <h3><?= e($m['nome']) ?></h3>
            <p><?= e($m['funcao']) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
