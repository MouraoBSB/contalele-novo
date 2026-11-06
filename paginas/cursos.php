<?php
/**
 * Página "Cursos": os cursos da Lelê lado a lado, para escolher por onde começar.
 * Cartões, comparação e preços vêm da lista única de includes/cursos.php.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/girassol.php';
require_once CL_RAIZ . '/includes/cursos.php';
require_once CL_RAIZ . '/includes/cursos-cartoes.php';

$cssExtra = ['/assets/css/cursos.css'];
$cursos   = cursos_publicados();
$whats    = configuracao('whatsapp', '5561991938603');

$seo['titulo']    = 'Cursos de contação de histórias com a Lelê | Conta Lelê';
$seo['descricao'] = 'Cursos online de contação de histórias com a Lelê: Conte&Encante e Cante & Encante. '
    . 'Veja o que cada um ensina e escolha por onde começar.';
// Lista de cursos: cada item aponta para a página do curso, que tem os dados completos (Course).
$seo['json_ld'] = [
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'itemListElement' => array_map(
        static fn(int $i, array $c): array => [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'url'      => $seo['url_base'] . $c['url'],
        ],
        array_keys($cursos),
        $cursos
    ),
];

// Faixa acima do rodapé: aqui a dúvida é qual curso escolher, e a conversa vai para o WhatsApp.
$faixaFim = [
    'antes'    => 'Ficou com dúvida sobre qual escolher? ',
    'destaque' => 'Fale com a Lelê.',
    'depois'   => '',
    'texto'    => 'Conte o que você precisa e a gente indica o curso certo.',
    'botao'    => 'Chamar no WhatsApp',
    'href'     => 'https://wa.me/' . $whats . '?text=' . rawurlencode('Oi, Lelê! Quero ajuda para escolher um curso.'),
    'externo'  => true,
];

$seta = curso_icone('seta');
?>
<?php girassol_sprite(); ?>

<section class="cl-cursos-topo">
    <?php girassol_ref(['classe' => 'cl-cursos-topo__girassol cl-girassol--gira']); ?>
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Cursos da Lelê</p>
        <h1>Aprenda a contar histórias com a <span class="cl-em">Lelê</span></h1>
        <p>Cursos online para quem conta histórias na escola, na biblioteca ou em casa. Veja o que cada um
            ensina e escolha por onde começar.</p>
        <h2 class="cl-sr">Os cursos</h2>
        <div class="cl-cursos-grade">
            <?php foreach ($cursos as $curso) { curso_cartao($curso); } ?>
        </div>
    </div>
</section>

<section class="cl-secao cl-secao--escolher" aria-labelledby="t-escolher">
    <div class="cl-conteudo">
        <div class="cl-escolha-caixa">
            <p class="cl-eyebrow">Qual escolher?</p>
            <h2 class="cl-secao__titulo" id="t-escolher">Por onde <span class="cl-em">começar</span></h2>
            <ul class="cl-escolha">
                <li>
                    <span class="cl-escolha__num" aria-hidden="true">1</span>
                    <p class="cl-escolha__se">Quer aprender a contar histórias do começo ao fim, com encontros ao vivo com a Lelê?</p>
                    <p class="cl-escolha__entao">Escolha o <strong>Conte&amp;Encante</strong>. O Cante &amp; Encante já vem junto, como bônus.</p>
                    <a href="/conte-e-encante">Ver o Conte&amp;Encante<?= $seta ?></a>
                </li>
                <li>
                    <span class="cl-escolha__num" aria-hidden="true">2</span>
                    <p class="cl-escolha__se">Já conta histórias e quer colocar música nelas?</p>
                    <p class="cl-escolha__entao">Comece pelo <strong>Cante &amp; Encante</strong>. Se depois quiser o curso completo, você paga só a diferença.</p>
                    <a href="/cante-e-encante">Ver o Cante &amp; Encante<?= $seta ?></a>
                </li>
                <li>
                    <span class="cl-escolha__num" aria-hidden="true">3</span>
                    <p class="cl-escolha__se">Já sabe que quer os dois?</p>
                    <p class="cl-escolha__entao">Fique com o <strong>Conte&amp;Encante</strong>: ele já inclui o Cante &amp; Encante inteiro.</p>
                    <a href="/conte-e-encante">Ver o Conte&amp;Encante<?= $seta ?></a>
                </li>
            </ul>
            <?php if (count($cursos) > 1): ?>
                <div class="cl-comparar-wrap">
                    <h3>Lado a lado</h3>
                    <?php cursos_comparacao($cursos); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="cl-secao cl-secao--amarela" aria-labelledby="t-quem">
    <div class="cl-conteudo cl-quem">
        <div class="cl-quem__wrap">
            <figure class="cl-quem__foto"><img src="/assets/img/lele-sobre.jpg"
                alt="Lelê sorri e aponta para o lado, com flores coloridas no cabelo" width="1600" height="1066"
                loading="lazy"></figure>
            <?php girassol_ref(['classe' => 'cl-quem__girassol']); ?>
        </div>
        <div>
            <p class="cl-eyebrow">Quem ensina</p>
            <h2 class="cl-secao__titulo" id="t-quem">Oi, eu sou a <span class="cl-em">Lelê</span></h2>
            <p><strong>Letícia Mourão, a Lelê,</strong> é contadora de histórias, atriz, cordelista, escritora,
                mediadora de leitura e agente cultural do DF. Conta histórias há mais de 20 anos.</p>
            <p>No Cante &amp; Encante, ela divide o palco com <strong>Kleyton Santos</strong>, músico e parceiro
                da Lelê há muitos anos, ao violão.</p>
            <a class="cl-btn cl-btn-ghost cl-quem__btn" href="/sobre">Conhecer a Lelê</a>
        </div>
    </div>
</section>

<section class="cl-secao" aria-labelledby="t-duvidas">
    <div class="cl-conteudo">
        <p class="cl-eyebrow">Dúvidas rápidas</p>
        <h2 class="cl-secao__titulo" id="t-duvidas">Antes de escolher</h2>
        <div class="cl-faq">
            <details>
                <summary>Já comprei o Conte&amp;Encante. Preciso comprar o Cante &amp; Encante?</summary>
                <p><strong>Não.</strong> Ele já está na sua área de membros, como bônus.</p>
            </details>
            <details>
                <summary>Comprei o Cante &amp; Encante e agora quero o Conte&amp;Encante. Pago tudo de novo?</summary>
                <p><strong>Não.</strong> Passados os 7 dias de garantia, aparece na sua área de membros um link para
                    você passar ao Conte&amp;Encante pagando só a diferença.</p>
            </details>
            <details>
                <summary>Os cursos têm certificado?</summary>
                <p><strong>Sim:</strong> 25 horas no Conte&amp;Encante e 3 horas no Cante &amp; Encante.</p>
            </details>
            <details>
                <summary>Por quanto tempo tenho acesso?</summary>
                <p>1 ano, nos dois cursos.</p>
            </details>
            <details>
                <summary>Como pago?</summary>
                <p>Pix ou cartão de crédito, em até 12x sem juros, pela Hotmart.</p>
            </details>
            <details>
                <summary>E se eu não gostar?</summary>
                <p>Você tem 7 dias de garantia e pede o reembolso pela própria plataforma.</p>
            </details>
        </div>
    </div>
</section>
