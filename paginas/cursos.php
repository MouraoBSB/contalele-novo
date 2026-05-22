<?php
/**
 * Página "Cursos" — teaser. A plataforma de cursos vem na Fase 2.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$whats = configuracao('whatsapp', '5561991938603');

$seo['titulo']    = 'Cursos — em breve | Conta Lelê';
$seo['descricao'] = 'Cursos online da Lelê para professores e mediadores de '
    . 'leitura. Em breve. Avise-me quando abrir.';
?>
<section class="cl-secao">
  <div class="cl-conteudo">
    <div class="cl-faixa">
      <p class="cl-eyebrow" style="color:var(--cl-bg-3)">Novidade chegando</p>
      <h1 class="cl-secao__titulo">Cursos da Lelê para
        <span class="cl-em">professores</span></h1>
      <p style="max-width:560px;margin:12px auto">Estamos preparando cursos online
        para quem ensina, media leitura e quer encantar com histórias.
        Quer ser avisado quando abrir?</p>
      <a class="cl-btn cl-btn-yellow" href="https://wa.me/<?= e($whats) ?>?text=<?= rawurlencode('Oi! Quero saber dos cursos da Lelê.') ?>"
         target="_blank" rel="noopener">Quero ser avisado</a>
    </div>
  </div>
</section>
