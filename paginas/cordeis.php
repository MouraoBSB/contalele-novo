<?php
/**
 * Página "Cordéis" — galeria dos cordéis com vídeo do YouTube.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';

$cordeis = listar_cordeis();

$seo['titulo']    = 'Cordéis — literatura de cordel da Lelê | Conta Lelê';
$seo['descricao'] = 'Conheça os cordéis escritos e contados pela Lelê: histórias '
    . 'rimadas para crianças, com vídeos no YouTube.';
?>

<section class="cl-hero-simples">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Literatura de cordel</p>
        <h1>Os <span class="cl-em">cordéis</span> da Lelê</h1>
        <p>Histórias rimadas, com aquele tempero especial — escritas e contadas pela Lelê.</p>
    </div>
</section>

<section class="cl-secao" style="padding-top:48px">
    <div class="cl-conteudo">
        <?php if (!$cordeis): ?>
            <p>Em breve, novos cordéis por aqui.</p>
        <?php else: ?>
            <div class="cl-grade cl-stagger">
                <?php foreach ($cordeis as $c): $vid = youtube_id((string) $c['video_youtube']); ?>
                    <article class="cl-card">
                        <h3><?= e($c['titulo']) ?></h3>
                        <?php if ($vid): ?>
                            <div class="cl-video" style="margin:14px 0">
                                <iframe src="https://www.youtube-nocookie.com/embed/<?= e($vid) ?>"
                                        title="<?= e($c['titulo']) ?>" loading="lazy"
                                        allowfullscreen></iframe>
                            </div>
                        <?php elseif ($c['imagem_capa']): ?>
                            <img src="/assets/img/<?= e($c['imagem_capa']) ?>" alt="Capa de <?= e($c['titulo']) ?>"
                                 style="border-radius:14px;margin:14px 0" loading="lazy">
                        <?php endif; ?>
                        <p><?= e($c['sinopse']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
