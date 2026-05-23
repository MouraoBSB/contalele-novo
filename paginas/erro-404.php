<?php
/**
 * Página de erro 404.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/girassol.php';
?>
<section class="cl-secao" style="text-align:center">
    <div class="cl-conteudo" style="max-width:680px">
        <div style="display:flex;justify-content:center;margin-bottom:20px">
            <?php girassol(['tamanho' => 140, 'gira' => true]); ?>
        </div>
        <p class="cl-eyebrow">Erro 404</p>
        <h1 class="cl-titulo-hero" style="font-size:clamp(28px,5vw,52px)">
            Essa página foi <span class="cl-em">contar histórias</span> em outro lugar.
        </h1>
        <p style="color:var(--cl-ink-dim);font-size:16px;margin-bottom:24px">Não encontramos o que você procura. Mas a Lelê tem muita coisa por aqui.</p>
        <p>
            <a class="cl-btn cl-btn-primary cl-btn-lg" href="/">Voltar ao início</a>
            <a class="cl-btn cl-btn-ghost cl-btn-lg" href="/contacao" style="margin-left:8px">Conhecer o trabalho</a>
        </p>
    </div>
</section>
