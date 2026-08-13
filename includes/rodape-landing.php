<?php
/**
 * Rodapé das landings. Mínimo de propósito: sem links para o resto do site,
 * para não oferecer saída a quem está decidindo a compra.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 *
 * @var array $jsExtra Scripts específicos da página.
 */

declare(strict_types=1);

$jsExtra = $jsExtra ?? [];

$igRodape    = configuracao('instagram', 'https://www.instagram.com/contalele/');
$ytRodape    = configuracao('youtube', 'https://www.youtube.com/c/ContaLel%C3%AA');
$emailRodape = configuracao('email_contato', 'contato@contalele.com.br');
?>
</main>

<footer class="lp-rodape">
    <div class="lp-largura lp-rodape__interno">
        <img class="lp-rodape__logo" src="/assets/img/logo.png" alt="Conta Lelê" width="150" height="44">
        <nav class="lp-rodape__links" aria-label="Canais da Conta Lelê">
            <a href="<?= e($igRodape) ?>" target="_blank" rel="noopener">Instagram</a>
            <a href="<?= e($ytRodape) ?>" target="_blank" rel="noopener">YouTube</a>
            <a href="mailto:<?= e($emailRodape) ?>"><?= e($emailRodape) ?></a>
        </nav>
        <p class="lp-rodape__base">&copy; <?= date('Y') ?> Conta Lelê · Planaltina-DF</p>
    </div>
</footer>

<?php foreach ($jsExtra as $js): ?>
    <script src="<?= e($js) ?>?v=<?= @filemtime(CL_RAIZ . $js) ?: time() ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
