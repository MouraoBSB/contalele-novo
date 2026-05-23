<?php
/**
 * Rodapé compartilhado do site Conta Lelê.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/girassol.php';

$whatsRodape = function_exists('configuracao')
    ? configuracao('whatsapp', '5561991938603')
    : '5561991938603';
$emailRodape = function_exists('configuracao')
    ? configuracao('email_contato', 'contato@contalele.com.br')
    : 'contato@contalele.com.br';
?>
</main>

<!-- CTA final ------------------------------------------------------- -->
<section class="cl-cta-fim" aria-labelledby="cta-fim-titulo">
    <span class="cl-cta-fim__girassol-l" aria-hidden="true">
        <?php girassol(['tamanho' => 130, 'gira' => true]); ?>
    </span>
    <span class="cl-cta-fim__girassol-r" aria-hidden="true">
        <?php girassol(['tamanho' => 110, 'gira' => true]); ?>
    </span>
    <div class="cl-conteudo cl-cta-fim__interno">
        <h2 id="cta-fim-titulo">Sua próxima roda <span class="cl-em">começa aqui</span>.</h2>
        <p>Conte com a Lelê para escolas, eventos, famílias — ou venha escutar uma história no canal toda sexta.</p>
        <a class="cl-btn cl-btn-primary cl-btn-lg" href="/contato">Falar com a Lelê</a>
    </div>
</section>

<!-- Rodapé ----------------------------------------------------------- -->
<footer class="cl-rodape">
    <div class="cl-conteudo">
        <div class="cl-rodape__grid">
            <div class="cl-rodape__marca">
                <img src="/assets/img/logo.png" alt="Conta Lelê">
                <p>Contação de histórias, cordéis, livros e cursos para professores, mediadores e famílias.</p>
            </div>
            <div class="cl-rodape__col">
                <h4>Conta Lelê</h4>
                <ul>
                    <li><a href="/sobre">A Lelê</a></li>
                    <li><a href="/contacao">Contação de Histórias</a></li>
                    <li><a href="/cordeis">Cordéis</a></li>
                    <li><a href="/livros">Livros</a></li>
                    <li><a href="/festivais">Festivais &amp; Imprensa</a></li>
                </ul>
            </div>
            <div class="cl-rodape__col">
                <h4>Canais</h4>
                <ul>
                    <li><a href="https://www.youtube.com/c/ContaLel%C3%AA" target="_blank" rel="noopener">YouTube</a></li>
                    <li><a href="https://www.instagram.com/contalele/" target="_blank" rel="noopener">Instagram</a></li>
                    <li><a href="https://wa.me/<?= htmlspecialchars($whatsRodape, ENT_QUOTES) ?>" target="_blank" rel="noopener">WhatsApp</a></li>
                </ul>
            </div>
            <div class="cl-rodape__col">
                <h4>Fale com a gente</h4>
                <ul>
                    <li><a href="/contato">Contato</a></li>
                    <li><a href="mailto:<?= htmlspecialchars($emailRodape, ENT_QUOTES) ?>"><?= htmlspecialchars($emailRodape, ENT_QUOTES) ?></a></li>
                    <li>Planaltina-DF</li>
                </ul>
            </div>
        </div>
        <div class="cl-rodape__base">
            <span>&copy; <?= date('Y') ?> Conta Lelê. Todos os direitos reservados.</span>
            <span>Feito com histórias — Planaltina-DF.</span>
        </div>
    </div>
</footer>

<a class="cl-whats" href="https://wa.me/<?= htmlspecialchars($whatsRodape, ENT_QUOTES) ?>" target="_blank" rel="noopener"
   aria-label="Falar no WhatsApp">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 2a10 10 0 0 0-8.6 15l-1.4 5 5.2-1.4A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20zm4.4-6c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6.5 6.5 0 0 1-3.2-2.8c-.1-.2 0-.4.1-.5l.4-.5c.1-.1.1-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.5-.4h-.5a1 1 0 0 0-.7.3c-.7.7-1 1.6-.9 2.6.3 2.5 2.6 5.2 5.7 6.3 2 .7 2.8.5 3.4.4.7-.1 1.4-.7 1.6-1.3.2-.6.2-1.1.1-1.2l-.4-.1z"/>
    </svg>
</a>
<script src="/assets/js/main.js?v=<?= @filemtime(CL_RAIZ . '/assets/js/main.js') ?: time() ?>" defer></script>
</body>
</html>
