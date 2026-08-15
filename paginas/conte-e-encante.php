<?php
/**
 * Página de vendas do curso Conte&Encante.
 *
 * Landing isolada: não usa o cabeçalho nem o rodapé do site, para não oferecer
 * saída a quem está decidindo a compra. Os dados da oferta que ainda dependem
 * da Lelê vêm de `configuracoes` e caem no padrão do mockup quando vazios.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/conexao.php';
require_once CL_RAIZ . '/includes/repositorio.php';
require_once CL_RAIZ . '/includes/girassol.php';
require_once CL_RAIZ . '/includes/previa.php';

// Enquanto não publicada, a página só abre com a senha de pré-visualização.
$publicada = exigir_previa('curso', 'Conte&Encante');

$layout   = 'landing';
$cssExtra = ['/assets/css/conte-e-encante.css'];
$jsExtra  = ['/assets/js/conte-e-encante.js'];

// ── Dados da oferta ──────────────────────────────────────────────────────
// O ?off fixa a oferta "Padrao sem juros". Sem ele o link segue o preço base
// vigente, o que quebra no dia em que existir uma promoção.
$urlCheckout   = trim(configuracao('curso_checkout_url',
    'https://pay.hotmart.com/B107134315C?off=87ea4lrn'));
$preco         = trim(configuracao('curso_preco', '297'));
$parcelamento  = trim(configuracao('curso_parcelamento',
    'ou 12x de R$ 24,75 sem juros · Pix ou cartão de crédito'));
$garantiaDias  = trim(configuracao('curso_garantia_dias', '7'));
$certificado   = trim(configuracao('curso_certificado',
    'Ao concluir o curso, você recebe um certificado.'));
$tempoAcesso   = trim(configuracao('curso_tempo_acesso',
    'Você terá acesso ao curso por 1 ano, tempo que cobre os 5 encontros ao vivo '
    . 'e a revisão das aulas quantas vezes precisar.'));
// Data em que os dois bônus entram na área de membros. Vazio, some o aviso da página.
$liberacaoBonus = trim(configuracao('curso_bonus_liberacao', '22 de agosto'));
$bonus         = trim(configuracao('curso_bonus',
    'Cante&Encante, com 6 aulas sobre música na contação, e Pequenos Objetos, '
    . 'Grandes Histórias, com histórias curtas para contar com objetos simples.'
    . ($liberacaoBonus !== '' ? ' Os dois ficam disponíveis em ' . $liberacaoBonus . '.' : '')));
$encontros     = trim(configuracao('curso_encontros_ao_vivo',
    'São 5 encontros ao vivo, aos sábados pela manhã, pelo Zoom.'));

// A Hotmart anexa o código do afiliado na URL desta página quando alguém chega
// por um link de divulgação. Sem repassar ao checkout, a comissão não é creditada.
if ($urlCheckout !== '') {
    $repasse = [];
    foreach (['a', 'src', 'sck', 'xcod'] as $parametro) {
        $valor = $_GET[$parametro] ?? null;
        if (is_string($valor) && $valor !== '') {
            $limpo = preg_replace('/[^A-Za-z0-9_.\-]/', '', $valor);
            if ($limpo !== '') {
                $repasse[$parametro] = substr($limpo, 0, 64);
            }
        }
    }
    if ($repasse !== []) {
        $urlCheckout .= (str_contains($urlCheckout, '?') ? '&' : '?')
            . http_build_query($repasse);
    }
}

// Sem checkout, os botões levam ao bloco da oferta em vez de a lugar nenhum.
$hrefCta = $urlCheckout !== '' ? $urlCheckout : '#lp-investimento';
$externo = $urlCheckout !== '';
$ctaTopo = ['href' => $hrefCta, 'texto' => 'Quero entrar', 'externo' => $externo];

/** Devolve o valor escapado, ou o selo de pendência quando ainda vazio. */
$ouPendente = static function (string $valor): string {
    return $valor !== '' ? e($valor) : '<span class="lp-pendente">a definir</span>';
};

/** Abre um botão de compra com o destino e o alvo corretos. */
$abreCta = static function () use ($hrefCta, $externo): string {
    return '<a class="lp-btn lp-btn--primario" href="' . e($hrefCta) . '"'
        . ($externo ? ' target="_blank" rel="noopener"' : '') . '>';
};

// ── SEO ──────────────────────────────────────────────────────────────────
$seo['titulo']    = 'Conte&Encante — curso de contação de histórias com Lelê';
$seo['descricao'] = 'Você não precisa ter dom para contar histórias. Precisa descobrir '
    . 'como contar do seu jeito. Curso de contação de histórias com Letícia Mourão, a Lelê.';
$seo['imagem']    = $seo['url_base'] . '/assets/img/curso/curso-og.jpg';

if (!$publicada) {
    // Rascunho não entra em buscador nem em pré-visualização de link.
    $seo['robots'] = 'noindex, nofollow';
}

$seo['json_ld'] = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Course',
    'name'        => 'Conte&Encante',
    'description' => $seo['descricao'],
    'url'         => $seo['url_base'] . '/conte-e-encante',
    'inLanguage'  => 'pt-BR',
    'provider'    => [
        '@type' => 'Person',
        'name'  => 'Letícia Rocha Mourão Marques',
        'url'   => $seo['url_base'],
    ],
    'hasCourseInstance' => [
        '@type'              => 'CourseInstance',
        'courseMode'         => 'online',
        'courseWorkload'     => 'PT10H',
    ],
    'offers' => [
        '@type'         => 'Offer',
        'price'         => preg_replace('/\D/', '', $preco) ?: '297',
        'priceCurrency' => 'BRL',
        'category'      => 'Paid',
        'url'           => $urlCheckout !== '' ? $urlCheckout : $seo['url_base'] . '/conte-e-encante',
    ],
];

/** Ícone de check usado nas listas. */
function lp_check(): string
{
    return '<span class="lp-ck" aria-hidden="true">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="#2b1300" stroke-width="2.4" '
        . 'stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l6 6L20 6"/></svg>'
        . '</span>';
}
?>

<?php if (!$publicada): ?>
<div class="lp-previa">
    <div class="lp-largura">
        <strong>Pré-visualização.</strong> Esta página ainda não está publicada e não aparece em buscadores.
    </div>
</div>
<?php endif; ?>

<!-- 1 · Hero ------------------------------------------------------------ -->
<section class="lp-secao lp-hero">
    <?php girassol_ref(['tamanho' => 320, 'classe' => 'lp-deco lp-gira']); ?>
    <div class="lp-largura">
        <div class="lp-hero__grade">
            <div>
                <p class="lp-olho lp-rv">Conte&amp;Encante · com Lelê</p>
                <h1 class="lp-rv">Você não precisa ter dom para contar histórias.
                    Precisa descobrir como contar <em>do seu jeito</em>.</h1>
                <p class="lp-lead lp-rv" style="margin-top:24px">Aprenda a transformar histórias em
                    experiências que despertam a imaginação, prendem a atenção das crianças e criam
                    memórias — usando sua voz, seu corpo, sua presença e até os objetos mais simples
                    que você já tem por perto.</p>
                <ul class="lp-chips lp-rv">
                    <li>Mesmo que a timidez trave você.</li>
                    <li>Mesmo que ache que não leva jeito.</li>
                    <li>Mesmo que sua turma seja agitada.</li>
                    <li>Mesmo que você tenha poucos recursos.</li>
                </ul>
                <p class="lp-rv" style="margin-top:32px">
                    <?= $abreCta() ?>Quero aprender a contar histórias</a>
                </p>
            </div>
            <div class="lp-hero__foto lp-rv">
                <img src="/assets/img/curso/curso-hero.jpg" width="900" height="900"
                     alt="Lelê sorrindo, de colete listrado colorido, segurando uma lâmpada azul">
            </div>
        </div>
    </div>
</section>

<!-- 2 · As dúvidas ------------------------------------------------------ -->
<section class="lp-secao lp-branco">
    <div class="lp-largura">
        <h2 class="lp-rv">Talvez você já tenha uma história nas mãos. O que falta é saber o que
            fazer com ela.</h2>
        <p class="lp-lead lp-rv" style="margin-top:24px">Você escolhe um livro lindo. Começa a contar.
            Mas, em poucos minutos, uma criança se distrai. Outra conversa. Outra levanta.
            E então aparecem aquelas dúvidas:</p>

        <div class="lp-tira3">
            <img class="lp-foto lp-foto--3x2 lp-foto--recorte lp-rv" loading="lazy"
                 src="/assets/img/curso/curso-duvida-1.jpg" width="760" height="507"
                 alt="Lelê com expressão de dúvida, apontando para o lado">
            <img class="lp-foto lp-foto--3x2 lp-foto--recorte lp-rv" loading="lazy"
                 src="/assets/img/curso/curso-duvida-2.jpg" width="760" height="507"
                 alt="Lelê dando de ombros, com ar de quem não sabe">
            <img class="lp-foto lp-foto--3x2 lp-foto--recorte lp-rv" loading="lazy"
                 src="/assets/img/curso/curso-duvida-3.jpg" width="760" height="507"
                 alt="Lelê com expressão preocupada, a mão junto ao rosto">
        </div>

        <div class="lp-mural">
            <?php
            $duvidas = [
                'Será que eu não tenho jeito para isso?',
                'Minha voz não é bonita.',
                'A timidez me trava.',
                'Não sei interpretar.',
                'Não consigo decorar histórias.',
                'Não tenho aqueles materiais maravilhosos que vejo na internet.',
                'Como algumas pessoas conseguem prender a atenção das crianças tão facilmente?',
            ];
            foreach ($duvidas as $duvida): ?>
                <figure class="lp-balao lp-rv">
                    <span class="lp-balao__aspas" aria-hidden="true">&ldquo;</span>
                    <blockquote style="margin:0"><p><?= e($duvida) ?></p></blockquote>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 3 · A virada -------------------------------------------------------- -->
<section class="lp-secao lp-cafe">
    <div class="lp-largura lp-estreito" style="text-align:center">
        <p class="lp-lead lp-rv">A resposta não está em ter um dom especial.</p>
        <p class="lp-lead lp-rv">E muito menos em imitar o jeito de outra pessoa contar.</p>
        <p class="lp-rv" style="font-size:24px;font-weight:900;line-height:1.2;margin:32px 0">
            Contar histórias é algo que pode ser aprendido.</p>
        <p class="lp-rv lp-dim">E, principalmente, pode ser aprendido sem apagar aquilo que você
            tem de mais importante:</p>
        <p class="lp-rv" style="font-family:var(--display);color:var(--bg-3);font-size:44px;
            line-height:1.1;margin:24px 0 0">a sua verdade.</p>
        <div class="lp-rv" style="display:flex;justify-content:center;margin-top:32px">
            <?php girassol_ref(['tamanho' => 72]); ?>
        </div>
    </div>
</section>

<!-- 4 · Imagine --------------------------------------------------------- -->
<section class="lp-secao lp-creme">
    <div class="lp-largura">
        <div class="lp-col2--imagine">
            <div>
                <h2 class="lp-rv">Imagine entrar diante das crianças sabendo exatamente
                    como começar…</h2>
                <ul class="lp-tracos lp-duas-col">
                    <li class="lp-rv">Saber criar expectativa antes mesmo de dizer &ldquo;Era uma vez&rdquo;.</li>
                    <li class="lp-rv">Perceber quando é hora de acelerar. Quando diminuir. Quando fazer
                        silêncio. Quando chamar as crianças para participar.</li>
                    <li class="lp-rv">Saber usar sua voz sem precisar fazer vinte vozes diferentes.</li>
                    <li class="lp-rv">Usar seu corpo com intenção, sem transformar a história numa
                        sequência exagerada de gestos.</li>
                    <li class="lp-rv">Olhar para um objeto simples e enxergar nele uma possibilidade
                        de história.</li>
                    <li class="lp-rv">Escolher um livro e conseguir ir além da leitura das palavras.</li>
                </ul>
                <p class="lp-destaque lp-rv">E, principalmente: olhar para as crianças e perceber
                    que elas estão com você.</p>
                <p class="lp-rv">O Conte&amp;Encante foi criado para ajudar você a construir esse caminho.</p>
            </div>
            <div class="lp-rv">
                <img class="lp-foto lp-foto--4x5" loading="lazy"
                     src="/assets/img/curso/curso-imagine.jpg" width="760" height="1140"
                     alt="Lelê de olhos fechados, com expressão de encantamento">
            </div>
        </div>
        <div class="lp-cta-centro lp-rv">
            <?= $abreCta() ?>Quero aprender com a Lelê</a>
        </div>
    </div>
</section>

<!-- 5 · O que é --------------------------------------------------------- -->
<section class="lp-secao lp-branco">
    <div class="lp-largura">
        <div class="lp-col2">
            <div>
                <h2 class="lp-rv">O que é o <em>Conte&amp;Encante</em>?</h2>
                <p class="lp-rv" style="margin-top:24px">O Conte&amp;Encante é um curso de contação
                    de histórias criado por Letícia Mourão — a Lelê — para quem deseja contar
                    histórias de maneira mais envolvente, criativa e intencional.</p>
                <p class="lp-rv">Aqui você não vai aprender uma fórmula para copiar o jeito da Lelê
                    contar. É justamente o contrário.</p>
                <p class="lp-rv">Você vai aprender princípios, técnicas e possibilidades para
                    descobrir <em class="lp-fk">o seu jeito de contar histórias</em>.</p>
                <p class="lp-rv">Ao longo do curso, vamos trabalhar elementos como verdade, intenção,
                    presença e encantamento.</p>
            </div>
            <div class="lp-capa lp-rv">
                <img src="/assets/img/curso/curso-capa.jpg" width="900" height="900" loading="lazy"
                     alt="Conte&amp;Encante — mais que contar, encantar. Curso com Lelê">
            </div>
        </div>
        <ul class="lp-nuvem lp-rv">
            <li>Voz</li><li>Corpo</li><li>Olhar</li><li>Ritmo</li><li>Pausa</li><li>Imaginação</li>
            <li>Objetos</li><li>Livros</li><li>Música</li><li>Participação</li><li>Repertório</li>
        </ul>
        <p class="lp-fecho lp-rv">E, principalmente, daquilo que nenhuma técnica substitui:
            a conexão entre quem conta e quem escuta.</p>
    </div>
</section>

<!-- 6 · Para quem é ----------------------------------------------------- -->
<section class="lp-secao lp-creme">
    <div class="lp-largura">
        <h2 class="lp-rv">Este curso é para você que…</h2>
        <div class="lp-grade">
            <?php
            $perfis = [
                'É professora ou professor da Educação Infantil ou dos anos iniciais e quer tornar seus momentos de história mais envolventes.',
                'Trabalha em biblioteca, sala de leitura ou mediação e quer ampliar seu repertório.',
                'Está começando na contação de histórias e não sabe por onde começar.',
                'Já conta histórias, mas sente que precisa compreender melhor voz, corpo, ritmo, presença e participação.',
                'Trabalha com crianças e procura maneiras criativas de despertar atenção e imaginação.',
                'É mãe, pai ou cuidador e deseja transformar a leitura em um momento especial de vínculo.',
                'Ou simplesmente ama histórias e deseja aprender a contá-las.',
            ];
            $tons = ['lp-tom-amarelo', 'lp-tom-laranja', 'lp-tom-cafe', 'lp-tom-creme'];
            foreach ($perfis as $i => $perfil): ?>
                <article class="lp-card-tom <?= $tons[$i % 4] ?> lp-rv">
                    <?php girassol_ref(['tamanho' => 110, 'classe' => 'lp-deco']); ?>
                    <p><span class="lp-card-tom__num"><?= sprintf('%02d', $i + 1) ?></span><?= e($perfil) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="lp-fecho lp-rv">Você não precisa chegar sabendo. O curso existe justamente para
            ensinar o caminho.</p>
    </div>
</section>

<!-- 7 · O método por trás ----------------------------------------------- -->
<section class="lp-secao lp-branco">
    <div class="lp-largura">
        <div class="lp-col2">
            <div>
                <p class="lp-olho lp-rv">O caminho</p>
                <h2 class="lp-rv">Você não vai aprender um monte de técnicas soltas.
                    Existe um caminho.</h2>
                <p class="lp-rv" style="margin-top:24px">Depois de mais de 20 anos contando histórias,
                    estudando, experimentando recursos e observando o que realmente acontece entre
                    quem conta e quem escuta, organizei parte dessa experiência em um caminho que
                    pudesse ser ensinado.</p>
                <div class="lp-visar lp-rv">
                    <?php girassol_ref(['tamanho' => 220, 'classe' => 'lp-deco lp-gira']); ?>
                    <strong>Método VISAR</strong>
                </div>
                <p class="lp-rv">Um método que ensina você a fazer escolhas conscientes ao contar uma
                    história, encontrando o seu próprio jeito de narrar — sem fórmulas engessadas e
                    sem precisar imitar ninguém.</p>
                <p class="lp-rv">Não é uma fórmula para transformar todo mundo no mesmo tipo de
                    contador de histórias. Muito pelo contrário. É um caminho para ajudar você a
                    encontrar a sua maneira de contar.</p>
                <p class="lp-rv">Ao longo do Conte&amp;Encante, você vai percorrer esse método nas
                    aulas, nos exercícios e nas experiências práticas.</p>
            </div>
            <div class="lp-rv">
                <img class="lp-foto lp-foto--3x2" loading="lazy"
                     src="/assets/img/curso/curso-metodo.jpg" width="900" height="600"
                     alt="Lelê apresentando com a mão aberta, sobre fundo azul">
            </div>
        </div>
        <p class="lp-painel lp-rv">E existe uma ideia fundamental por trás de tudo isso: não basta
            ter recursos. É preciso saber por que, quando e como utilizá-los.</p>
    </div>
</section>

<!-- 8 · VIPE ------------------------------------------------------------ -->
<section class="lp-secao lp-creme lp-vipe">
    <div class="lp-largura">
        <p class="lp-olho lp-rv">O que você vai desenvolver</p>
        <h2 class="lp-rv">O que é o VIPE?</h2>
        <p class="lp-lead lp-rv" style="margin-top:24px">Se o VISAR é o método, o VIPE são os quatro
            requisitos que, para mim, todo contador de histórias precisa desenvolver:</p>

        <div class="lp-vipe__grade">
            <article class="lp-card-vipe lp-rv">
                <span class="lp-card-vipe__letra" aria-hidden="true">V</span>
                <img class="lp-foto lp-foto--3x2" loading="lazy"
                     src="/assets/img/curso/curso-vipe-verdade.jpg" width="760" height="507"
                     alt="Lelê com as mãos no peito e os olhos fechados">
                <h3>V — Verdade</h3>
                <p>Encontrar a sua própria maneira de contar. Não é copiar outro contador, forçar
                    uma voz ou interpretar um personagem o tempo inteiro. É existir de verdade
                    naquela história.</p>
            </article>

            <article class="lp-card-vipe lp-rv">
                <span class="lp-card-vipe__letra" aria-hidden="true">I</span>
                <img class="lp-foto lp-foto--3x2" loading="lazy"
                     src="/assets/img/curso/curso-vipe-intencao.jpg" width="760" height="507"
                     alt="Lelê com expressão pensativa, o dedo junto ao queixo">
                <h3>I — Intenção</h3>
                <p>Saber por que está fazendo cada escolha. Uma pausa, um gesto, uma mudança de voz,
                    um objeto, um silêncio: tudo precisa ter propósito.</p>
            </article>

            <article class="lp-card-vipe lp-rv">
                <span class="lp-card-vipe__letra" aria-hidden="true">P</span>
                <img class="lp-foto lp-foto--3x2" loading="lazy"
                     src="/assets/img/curso/curso-vipe-presenca.jpg" width="760" height="507"
                     alt="Lelê com a mão junto ao olho, olhando com atenção">
                <h3>P — Presença</h3>
                <p>Não basta saber a história. É preciso estar presente para quem está ouvindo:
                    olhar, perceber, escutar as reações, adaptar-se e criar conexão.</p>
            </article>

            <article class="lp-card-vipe lp-card-vipe--cafe lp-rv">
                <img class="lp-foto lp-foto--2x3" loading="lazy" style="border-radius:0"
                     src="/assets/img/curso/curso-vipe-encantamento.jpg" width="760" height="1140"
                     alt="Lelê com tecidos coloridos voando no ar">
                <div class="lp-vipe__txt">
                    <span class="lp-card-vipe__letra" aria-hidden="true">E</span>
                    <h3>E — Encantamento</h3>
                    <p>É transformar a narrativa em experiência. Fazer o outro imaginar, sentir,
                        participar e entrar naquele universo — mesmo que você tenha apenas uma
                        história, seu corpo e sua voz.</p>
                </div>
            </article>
        </div>

        <div class="lp-remate lp-rv">
            <p>Você não precisa ter dom para contar histórias.
                Mas precisa ter <span class="lp-fk">VIPE</span>.</p>
            <p class="lp-remate__quatro">Verdade · Intenção · Presença · Encantamento</p>
        </div>
    </div>
</section>

<!-- 9 · Você vai aprender a --------------------------------------------- -->
<section class="lp-secao lp-branco">
    <div class="lp-largura">
        <h2 class="lp-rv">Você não vai aprender apenas a contar. Vai aprender a encantar.</h2>
        <p class="lp-lead lp-rv" style="margin-top:24px">Ao longo do Conte&amp;Encante, você vai
            compreender como uma história deixa de ser apenas uma sequência de palavras e passa a
            se transformar em experiência.</p>
        <ul class="lp-checks lp-duas-col">
            <?php
            $aprendizados = [
                'encontrar histórias que façam sentido para você e para quem vai ouvi-las;',
                'preparar o ouvinte antes mesmo de começar a narrativa;',
                'criar convites para entrar no mundo da imaginação;',
                'desenvolver presença e conexão com o público;',
                'compreender a intenção por trás de cada escolha;',
                'explorar voz, ritmo, pausas, silêncio e musicalidade;',
                'usar corpo, olhar, gestos e movimento de maneira consciente;',
                'trabalhar com livros sem depender só da leitura;',
                'descobrir possibilidades narrativas em objetos simples;',
                'usar recursos sem deixar que eles se tornem mais importantes do que a história;',
                'lidar com turmas agitadas e diferentes respostas do público;',
                'estimular a participação sem perder o fio da narrativa;',
                'ampliar seu repertório;',
                'adaptar aquilo que aprende à sua realidade;',
                'e construir, pouco a pouco, a sua própria identidade como quem conta histórias.',
            ];
            foreach ($aprendizados as $item): ?>
                <li class="lp-rv"><?= lp_check() ?><span><?= e($item) ?></span></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- 10 · Recursos simples ----------------------------------------------- -->
<section class="lp-secao lp-creme">
    <div class="lp-largura">
        <h2 class="lp-rv">Recursos simples. Grandes possibilidades.</h2>
        <p class="lp-lead lp-rv" style="margin-top:24px">Talvez você já tenha visto contadores de
            histórias com cenários enormes, figurinos elaborados e materiais feitos especialmente
            para uma apresentação. Tudo isso pode ser lindo. Mas você não precisa começar por aí.</p>
        <p class="lp-rv">No Conte&amp;Encante, você vai descobrir que objetos comuns também podem
            abrir portas extraordinárias para a imaginação.</p>

        <div class="lp-escada">
            <span class="lp-rv">Uma mola vira lagarta.</span>
            <span class="lp-rv">Uma bola vira lua.</span>
            <span class="lp-rv">Um tecido vira céu.</span>
            <span class="lp-rv">Uma taça vira personagem.</span>
        </div>

        <div class="lp-tira4">
            <img class="lp-foto lp-foto--1x1 lp-rv" loading="lazy"
                 src="/assets/img/curso/curso-recurso-taca.jpg" width="640" height="851"
                 alt="Lelê segurando uma garrafinha verde com olhos e uma taça com chapéu de bruxa">
            <img class="lp-foto lp-foto--1x1 lp-rv" loading="lazy"
                 src="/assets/img/curso/curso-recurso-tecido.jpg" width="640" height="427"
                 alt="O rosto da Lelê aparecendo atrás de um tecido colorido">
            <img class="lp-foto lp-foto--1x1 lp-rv" loading="lazy"
                 src="/assets/img/curso/curso-recurso-barco.jpg" width="640" height="960"
                 alt="Lelê segurando um barco de papel sobre um tecido azul">
            <img class="lp-foto lp-foto--1x1 lp-rv" loading="lazy"
                 src="/assets/img/curso/curso-recurso-pandeiro.jpg" width="640" height="427"
                 alt="Lelê rindo enquanto toca um pandeiro de fitas coloridas">
        </div>

        <p class="lp-rv">O mais importante não é quanto custa o recurso.
            <em class="lp-fk">É o que você consegue fazer o público imaginar através dele.</em></p>
        <p class="lp-rv">Essa é uma das razões pelas quais o curso trabalha diferentes linguagens e
            possibilidades: livros, objetos, voz, corpo, música, movimento e brincadeira. Não para
            você usar tudo ao mesmo tempo. Mas para ter uma caixa de ferramentas e saber escolher o
            que cada história pede.</p>
    </div>
</section>

<!-- 11 · E se a timidez me travar? -------------------------------------- -->
<section class="lp-secao lp-cafe">
    <div class="lp-largura lp-estreito">
        <div class="lp-litania">
            <?php
            $objecoes = [
                'E se a timidez me travar?',
                'E se eu não souber fazer vozes?',
                'E se eu não souber decorar?',
                'E se eu não tiver materiais?',
            ];
            foreach ($objecoes as $objecao): ?>
                <div class="lp-rv">
                    <p class="lp-litania__perg"><?= e($objecao) ?></p>
                    <p class="lp-litania__refrao">Você pode contar histórias.</p>
                </div>
            <?php endforeach; ?>
            <div class="lp-rv">
                <p class="lp-litania__perg">E se eu nunca tiver contado uma história na frente
                    de uma turma?</p>
                <p class="lp-litania__refrao">Então talvez este seja justamente o seu começo.</p>
            </div>
        </div>

        <p class="lp-rv lp-dim">Porque o Conte&amp;Encante não parte da ideia de que existe um
            talento mágico reservado a algumas pessoas.</p>
        <div class="lp-escada-cafe">
            <span class="lp-rv">Existe técnica.</span>
            <span class="lp-rv">Existe repertório.</span>
            <span class="lp-rv">Existe treino.</span>
            <span class="lp-rv">Existe intenção.</span>
            <span class="lp-rv">Existe experiência.</span>
        </div>
        <p class="lp-rv" style="margin-top:32px">E existe algo que ninguém pode ensinar você a copiar:
            <span class="lp-fk" style="color:var(--bg-3)">a sua verdade.</span></p>
        <div class="lp-cta-centro lp-rv">
            <a class="lp-btn lp-btn--amarelo" href="<?= e($hrefCta) ?>"<?= $externo ? ' target="_blank" rel="noopener"' : '' ?>>Quero começar</a>
        </div>
    </div>
</section>

<!-- 12 · Quem caminha com você ------------------------------------------ -->
<section class="lp-secao lp-branco">
    <div class="lp-largura">
        <div class="lp-col2 lp-col2--lele">
            <div class="lp-rv" style="position:relative">
                <img class="lp-foto lp-foto--2x3" loading="lazy"
                     src="/assets/img/curso/curso-lele.jpg" width="900" height="1350"
                     alt="Lelê segurando um girassol, sobre fundo azul">
                <span class="lp-selo" aria-hidden="true">+20 anos de palco</span>
            </div>
            <div>
                <p class="lp-olho lp-rv">Quem vai caminhar com você</p>
                <h2 class="lp-h-fredoka lp-rv">Oi, eu sou a Lelê.</h2>
                <p class="lp-rv" style="margin-top:24px">Meu nome é Letícia Mourão, mas muita gente
                    me conhece como <strong>Lelê</strong>, da Conta Lelê.</p>
                <p class="lp-rv">Sou contadora de histórias, atriz, cordelista, escritora e mediadora
                    de leitura e há <strong>mais de 20 anos conto histórias profissionalmente</strong>,
                    transformando palavras, livros, objetos, músicas e gestos em encontros com a
                    imaginação.</p>
                <p class="lp-rv">Ao longo dessas duas décadas, contei histórias para crianças,
                    famílias, educadores e públicos dos mais diferentes tamanhos, em escolas,
                    bibliotecas, teatros, eventos e festivais pelo Brasil.</p>
                <p class="lp-rv">Também sou formadora de educadores e contadores de histórias, e muito
                    do que você encontrará no Conte&amp;Encante nasceu não apenas dos meus estudos,
                    mas da experiência de quem passou mais de duas décadas diante do público,
                    contando, observando, experimentando e aprendendo.</p>
                <p class="lp-rv">O Conte&amp;Encante reúne aquilo que eu gostaria que alguém tivesse
                    me mostrado quando comecei.</p>
                <p class="lp-rv">Não quero ensinar você a contar histórias como eu.
                    <em class="lp-fk">Quero ajudar você a descobrir o seu jeito de contar.</em></p>
            </div>
        </div>
    </div>
</section>

<!-- 13 · Encontros ao vivo ---------------------------------------------- -->
<section class="lp-secao lp-amarelo-2">
    <?php girassol_ref(['tamanho' => 200, 'classe' => 'lp-deco lp-gira', 'estilo' => 'right:-60px;top:-50px;opacity:.35']); ?>
    <div class="lp-largura">
        <div class="lp-col2">
            <div>
                <h2 class="lp-rv">E você não vai fazer esse caminho sozinho</h2>
                <p class="lp-rv" style="margin-top:24px">Além das aulas gravadas, o Conte&amp;Encante
                    terá <strong>encontros ao vivo com a Lelê</strong>.</p>
                <p class="lp-rv">Serão momentos para estarmos juntos, conversar sobre o que você está
                    aprendendo, aprofundar conteúdos, compartilhar experiências e continuar
                    desenvolvendo sua prática.</p>
                <p class="lp-rv">Porque algumas coisas você aprende assistindo. Outras, experimentando.
                    E muitas delas acontecem justamente no encontro entre quem conta e quem escuta.</p>
                <p class="lp-nota lp-rv"><?= $encontros !== ''
                    ? e($encontros)
                    : 'Periodicidade e quantidade dos encontros ' . $ouPendente('') ?></p>
            </div>
            <div class="lp-rv">
                <img class="lp-foto lp-foto--3x2" loading="lazy"
                     src="/assets/img/curso/curso-encontros.jpg" width="900" height="600"
                     alt="Lelê rindo de braços abertos">
            </div>
        </div>
    </div>
</section>

<!-- 14 · Depoimentos ---------------------------------------------------- -->
<section class="lp-secao lp-branco">
    <div class="lp-largura">
        <p class="lp-olho lp-rv">O que muda depois</p>
        <h2 class="lp-rv">Quem já viveu uma história com a Lelê</h2>
        <p class="lp-nota lp-rv">Não queremos apenas mostrar que as pessoas &ldquo;gostaram&rdquo;.
            Queremos mostrar o que elas perceberam, aprenderam ou passaram a fazer diferente depois
            da experiência com a Lelê.</p>

        <div class="lp-dep-grade">
            <?php
            // Depoimentos autorizados pelas participantes. Para acrescentar, basta
            // uma entrada aqui e a foto em assets/img/curso/ (quadrada, 200px bastam).
            $depoimentos = [
                [
                    'texto' => 'Através do pouco que te acompanho fez a diferença nas contações '
                        . 'de histórias nas práticas do meu dia a dia.',
                    'autor' => 'Valdenira Agostinho',
                    'foto'  => 'curso-depo-valdenira.jpg',
                ],
                [
                    // Adaptado do áudio, com autorização dela.
                    'texto' => 'Eu tinha 4 anos quando ela chegava com a caixa mágica. Dentro, '
                        . 'um lápis de cor — e daquele lápis nasceu a história do Flix, do '
                        . 'Ziraldo. Comecei a ler Ziraldo por causa disso. Hoje tenho 30 anos e '
                        . 'ainda lembro.',
                    'autor' => 'Vitória Carolina',
                    'foto'  => 'curso-depo-vitoria.jpg',
                ],
                [
                    'texto' => 'Não sei como te agradecer!! Não sei… sem palavras por tanto '
                        . 'encantamento!! VOCÊ é Maravilhosa! Incrível e fez uma leitura lindaaaa!',
                    'autor' => 'Regina Melo',
                    'foto'  => 'curso-depo-regina.jpg',
                ],
            ];

            foreach ($depoimentos as $d): ?>
                <figure class="lp-card-dep lp-rv" style="margin:0">
                    <span class="lp-card-dep__aspas" aria-hidden="true">&ldquo;</span>
                    <blockquote style="margin:0"><p><?= e($d['texto']) ?></p></blockquote>
                    <figcaption class="lp-dep-pessoa">
                        <img class="lp-avatar" src="/assets/img/curso/<?= e($d['foto']) ?>"
                             width="46" height="46" loading="lazy" alt="<?= e($d['autor']) ?>">
                        <strong><?= e($d['autor']) ?></strong>
                    </figcaption>
                </figure>
            <?php endforeach; ?>

            <?php // Completa até três: o que ainda falta continua visível como pendência.
            for ($i = count($depoimentos); $i < 3; $i++): ?>
                <figure class="lp-card-dep lp-rv" style="margin:0">
                    <span class="lp-card-dep__aspas" aria-hidden="true">&ldquo;</span>
                    <blockquote style="margin:0"><p class="lp-dim">Depoimento
                        <span class="lp-pendente">a definir</span></p></blockquote>
                    <figcaption class="lp-dep-pessoa">
                        <span class="lp-avatar" aria-hidden="true"></span>
                        <strong class="lp-dim">Nome da participante</strong>
                    </figcaption>
                </figure>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- 15 · Tudo o que você recebe ----------------------------------------- -->
<section class="lp-secao lp-creme">
    <div class="lp-largura">
        <h2 class="lp-rv">Tudo o que você recebe</h2>
        <p class="lp-lead lp-rv" style="margin-top:24px">Ao entrar para o Conte&amp;Encante,
            você terá acesso a:</p>
        <ul class="lp-recebe">
            <?php
            $itens = [
                ['Curso completo Conte&Encante', 'Aulas gravadas para assistir no seu ritmo e rever sempre que precisar.'],
                ['Encontros ao vivo com a Lelê', 'Momentos de troca, aprofundamento e acompanhamento para levar o conteúdo das aulas para a prática.'],
                ['Exercícios e experiências práticas', 'Porque contação de histórias não se aprende apenas assistindo. É preciso experimentar.'],
                ['Materiais complementares', 'Conteúdos que ajudam você a estudar, organizar os aprendizados e aplicá-los.'],
                ['Recursos e repertório', 'Possibilidades para ampliar sua caixa de ferramentas de quem conta histórias.'],
                ['Histórias e ideias com objetos simples', 'Para descobrir que encantamento não depende de materiais caros.'],
            ];
            foreach ($itens as [$titulo, $texto]): ?>
                <li class="lp-rv"><?= lp_check() ?>
                    <span><b><?= e($titulo) ?></b><span class="lp-dim"><?= e($texto) ?></span></span>
                </li>
            <?php endforeach; ?>
            <li class="lp-rv"><?= lp_check() ?>
                <span><b>Bônus</b><span class="lp-dim"><?= $ouPendente($bonus) ?></span></span>
            </li>
            <li class="lp-rv"><?= lp_check() ?>
                <span><b>Certificado</b><span class="lp-dim"><?= $ouPendente($certificado) ?></span></span>
            </li>
            <li class="lp-rv"><?= lp_check() ?>
                <span><b>Tempo de acesso</b><span class="lp-dim"><?= $ouPendente($tempoAcesso) ?></span></span>
            </li>
        </ul>
    </div>
</section>

<!-- 15b · Bônus --------------------------------------------------------- -->
<section class="lp-secao lp-branco">
    <div class="lp-largura">
        <p class="lp-olho lp-rv">Os bônus</p>
        <h2 class="lp-rv">E você ainda leva dois bônus</h2>

        <article class="lp-bonus lp-rv">
            <div class="lp-bonus__cabeca">
                <span class="lp-bonus__num" aria-hidden="true">Bônus 1</span>
                <span class="lp-bonus__data">Liberado em <?= e($liberacaoBonus) ?></span>
                <h3 class="lp-bonus__nome">Cante<em>&amp;</em>Encante</h3>
                <p class="lp-bonus__sub">Como usar a música para dar vida às histórias</p>
                <p>A proposta não é ensinar musicalização nem formar professoras de música. É
                    mostrar, de maneira simples e muito prática, como usar a música como recurso
                    dentro da contação de histórias, sempre com a história como protagonista.</p>
                <p class="lp-bonus__frase">A música não precisa ser protagonista. Ela caminha ao
                    lado da história para ampliar a participação, a emoção e o encantamento.</p>
                <figure class="lp-bonus__foto">
                    <img class="lp-foto lp-foto--3x2" loading="lazy"
                         src="/assets/img/curso/curso-bonus-musica.jpg" width="760" height="507"
                         alt="Lelê com almofadas coloridas e Klayton Santos com o violão">
                    <figcaption>Lelê e <strong>Klayton Santos</strong>, que assina a criação
                        musical do bônus.</figcaption>
                </figure>
            </div>
            <div class="lp-bonus__corpo">
                <p class="lp-bonus__rotulo">São 6 aulas</p>
                <ol class="lp-aulas">
                    <li><b>A música como aliada da narrativa</b> — com Os Entalados, mostrando
                        diferentes funções da música: criar ritual, apresentar personagens,
                        estimular participação, reforçar acontecimentos e marcar o encerramento.</li>
                    <li><b>Rituais de início e fim</b> — músicas para preparar a escuta e sinalizar
                        que a história começou ou terminou.</li>
                    <li><b>Cantigas de roda e paródias</b> — como aproveitar o repertório cultural
                        que as crianças já conhecem, adaptar letras e &ldquo;pescar&rdquo; novamente
                        a atenção da turma.</li>
                    <li><b>Criando músicas para histórias</b> — começa com A Lagarta Comilona e
                        mostra, com o tio Klayton, que uma composição pode nascer de uma frase, de
                        versos, rimas e repetições da própria narrativa.</li>
                    <li><b>Música sem complicação</b> — mostra que não é preciso tocar violão, ler
                        partitura ou ser profissional. A própria voz, um cantarolar, instrumentos
                        simples e até livros que já trazem suas músicas podem ser suficientes.</li>
                    <li><b>Oficina musical</b> — uma aula para brincar e experimentar, usando os
                        materiais Circo Encantado e Adivinhas dos Animais, que você recebe como
                        recursos para utilizar.</li>
                </ol>
                <p class="lp-bonus__fecho">Você não sai apenas com músicas para copiar. Aprende como
                    pensar musicalmente uma história, para conseguir adaptar essas ideias ao seu
                    próprio repertório.</p>
            </div>
        </article>

        <article class="lp-bonus lp-rv">
            <div class="lp-bonus__cabeca">
                <span class="lp-bonus__num" aria-hidden="true">Bônus 2</span>
                <span class="lp-bonus__data">Liberado em <?= e($liberacaoBonus) ?></span>
                <h3 class="lp-bonus__nome">Pequenos Objetos,<br>Grandes Histórias</h3>
                <p>Um bônus prático com histórias curtas criadas pela Lelê para serem contadas com
                    objetos simples e acessíveis.</p>
                <p class="lp-bonus__frase">Você não precisa de recursos caros ou elaborados para
                    encantar. Com pequenos objetos, criatividade e uma boa história, é possível
                    criar grandes experiências com as crianças.</p>
            </div>
            <div class="lp-bonus__corpo">
                <p class="lp-bonus__rotulo">Em cada proposta, você recebe</p>
                <ul class="lp-checks">
                    <li><?= lp_check() ?><span>a história escrita, pronta para conhecer e praticar;</span></li>
                    <li><?= lp_check() ?><span>o vídeo da contação da Lelê, para ver como ela dá vida à história;</span></li>
                    <li><?= lp_check() ?><span>os objetos e recursos utilizados;</span></li>
                    <li><?= lp_check() ?><span>e orientações de uso, com dicas para você adaptar e levar a proposta para a sua realidade.</span></li>
                </ul>
            </div>
        </article>
    </div>
</section>

<!-- 16 · Investimento --------------------------------------------------- -->
<section class="lp-secao lp-amarelo lp-preco-secao" id="lp-investimento">
    <?php girassol_ref(['tamanho' => 260, 'classe' => 'lp-deco lp-esq']); ?>
    <?php girassol_ref(['tamanho' => 260, 'classe' => 'lp-deco lp-dir']); ?>
    <div class="lp-largura">
        <h2 class="lp-rv" style="text-align:center">Quanto vale conseguir transformar uma história
            em um momento que uma criança vai lembrar?</h2>
        <p class="lp-fecho lp-rv" style="font-weight:400;font-size:17px">Você não está comprando um
            conjunto de histórias para decorar. Está aprendendo princípios e ferramentas que poderá
            usar em <em class="lp-fk">uma história, dez histórias, cem histórias</em>.</p>
        <p class="lp-fecho lp-rv" style="font-weight:400;font-size:17px;margin-top:16px">São
            conhecimentos construídos ao longo de mais de 20 anos de experiência com a contação de
            histórias, agora organizados em um caminho para ajudar você a desenvolver a sua própria
            forma de contar.</p>

        <div class="lp-cartao-preco lp-rv">
            <p class="lp-cartao-preco__rotulo">Entre para o Conte&amp;Encante por</p>
            <p class="lp-preco">R$&nbsp;<?= e($preco) ?></p>
            <p class="lp-dim" style="margin:0"><?= e($parcelamento) ?></p>
            <?= $abreCta() ?>Quero entrar no Conte&amp;Encante</a>
        </div>

        <div class="lp-garantia lp-rv">
            <?php if ($garantiaDias !== ''): ?>
                <span class="lp-selo-garantia" aria-hidden="true">Garantia de<br><?= e($garantiaDias) ?> dias</span>
                <h3 class="lp-garantia__titulo">Experimente o Conte&amp;Encante
                    por <?= e($garantiaDias) ?> dias</h3>
                <div class="lp-garantia__texto">
                    <p>Você terá <?= e($garantiaDias) ?> dias para entrar no curso, conhecer a
                        metodologia, assistir às primeiras aulas e perceber se o Conte&amp;Encante
                        faz sentido para você.</p>
                    <p>Se, dentro desse período, você entender que o curso não é para você, poderá
                        solicitar o cancelamento conforme as regras da plataforma e receber o
                        reembolso.</p>
                    <p>Sem precisar continuar com algo que não fez sentido para você.</p>
                </div>
                <p class="lp-garantia__risco">O risco não precisa ser seu.
                    Entre, conheça e experimente.</p>
            <?php else: ?>
                <span class="lp-selo-garantia" aria-hidden="true">Garantia</span>
                <p style="margin:0">Prazo de garantia <?= $ouPendente('') ?>, conforme as condições
                    da oferta. Entre, conheça o curso, assista às aulas e veja se o Conte&amp;Encante
                    faz sentido para você.</p>
            <?php endif; ?>
        </div>

        <p class="lp-fecho lp-rv" style="font-weight:400;font-size:17px;margin-top:32px">E tem uma
            diferença importante: você não precisa esperar terminar o curso inteiro para começar a
            experimentar. A proposta é que você assista, experimente, conte, observe o que aconteceu
            e volte para aprender mais. Porque ninguém se torna contador de histórias apenas
            estudando contação. <em class="lp-fk">A gente aprende a contar… contando.</em></p>
    </div>
</section>

<!-- 17 · FAQ ------------------------------------------------------------ -->
<section class="lp-secao lp-branco">
    <div class="lp-largura">
        <h2 class="lp-rv" style="text-align:center">Dúvidas frequentes</h2>
        <div class="lp-faq">
            <?php
            $faq = [
                ['Nunca contei histórias. O curso serve para mim?',
                 'Sim. Você não precisa ter experiência anterior. O Conte&Encante foi pensado também para quem deseja começar.'],
                ['A timidez me trava. Vou conseguir?',
                 'Sim. Você não precisa se transformar numa pessoa extrovertida para contar histórias. Vamos trabalhar presença, intenção e possibilidades para que você encontre o seu jeito de contar.'],
                ['Preciso saber interpretar ou fazer vozes?',
                 'Não. Voz é uma ferramenta da contação, mas contar histórias é muito maior do que criar vozes diferentes para personagens.'],
                ['Preciso comprar materiais?',
                 'Não. Um dos princípios do curso é justamente ampliar seu olhar para recursos simples e para aquilo que já existe ao seu redor.'],
                ['Preciso decorar as histórias?',
                 'Não. Você vai aprender caminhos para compreender e se apropriar da narrativa sem depender simplesmente da memorização palavra por palavra.'],
                ['As aulas são ao vivo?',
                 'As aulas principais são gravadas e você poderá assistir no seu ritmo. Além delas, o curso terá encontros ao vivo com a Lelê.'],
                ['Posso assistir pelo celular?',
                 'Sim. O acesso ao curso é realizado pela plataforma Hotmart e pode ser feito pelos dispositivos compatíveis com a plataforma.'],
            ];

            // A garantia só entra no FAQ quando o prazo está definido.
            if ($garantiaDias !== '') {
                $faq[] = ['Como funciona a garantia?',
                    'Você terá ' . $garantiaDias . ' dias para entrar no curso, conhecer a '
                    . 'metodologia, assistir às primeiras aulas e perceber se o Conte&Encante faz '
                    . 'sentido para você. Se, dentro desse período, você entender que o curso não é '
                    . 'para você, poderá solicitar o cancelamento conforme as regras da plataforma '
                    . 'e receber o reembolso.'];
            }

            // Estas duas dependem do painel e podem vir vazias: entram no mesmo array
            // para que a numeração dos id continue única.
            $faq[] = ['Por quanto tempo terei acesso?', $tempoAcesso];
            $faq[] = ['Tem certificado?', $certificado];

            $n = 0;
            foreach ($faq as [$pergunta, $resposta]):
                $n++;
                $aberta = $n === 1;
            ?>
                <div class="lp-faq__item">
                    <h3>
                        <button class="lp-faq__btn" type="button" id="lp-faq-b<?= $n ?>"
                                aria-expanded="<?= $aberta ? 'true' : 'false' ?>"
                                aria-controls="lp-faq-r<?= $n ?>">
                            <?= e($pergunta) ?>
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#2b1300"
                                 stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
                                 aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                    </h3>
                    <div class="lp-faq__resp<?= $aberta ? ' lp-faq__resp--on' : '' ?>"
                         id="lp-faq-r<?= $n ?>" role="region" aria-labelledby="lp-faq-b<?= $n ?>">
                        <p><?= $resposta !== '' ? e($resposta) : $ouPendente('') ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 18 · Fechamento ----------------------------------------------------- -->
<section class="lp-secao lp-creme lp-fim">
    <?php girassol_ref(['tamanho' => 200, 'classe' => 'lp-deco lp-esq lp-gira']); ?>
    <?php girassol_ref(['tamanho' => 170, 'classe' => 'lp-deco lp-dir lp-gira']); ?>
    <div class="lp-largura">
        <div class="lp-poema">
            <h2 class="lp-rv">Talvez você tenha chegado até aqui pensando que não nasceu para
                contar histórias.</h2>
            <p class="lp-rv" style="margin-top:24px">Talvez a timidez trave você. Talvez ache sua voz comum.
                Talvez não tenha cenário. Não tenha figurino. Não tenha uma mala cheia de recursos.</p>
            <p class="lp-rv">Mas você tem algo que nenhum material pode substituir:
                <em class="lp-fk">a possibilidade de criar conexão.</em></p>
            <p class="lp-rv">Uma história pode caber em um livro. Em uma música. Em um pedaço de
                tecido. Em uma mola. Nas suas mãos. Ou simplesmente na sua voz.</p>
            <p class="lp-rv">E quando alguém se dispõe a contar e outra pessoa aceita imaginar…
                alguma coisa acontece entre as duas.</p>
            <p class="lp-rv">É esse encontro que eu quero ensinar você a construir.</p>
        </div>

        <div class="lp-foto-fim lp-rv">
            <img class="lp-foto lp-foto--3x2 lp-foto--recorte" loading="lazy"
                 src="/assets/img/curso/curso-fechamento.jpg" width="900" height="599"
                 alt="Lelê fazendo um coração com as mãos">
        </div>

        <p class="lp-dupla lp-rv">Você não precisa começar sem medo.<br>Só precisa começar.</p>
        <p class="lp-vem lp-rv">Vem comigo?</p>
        <p class="lp-assinatura lp-rv">Conte&amp;Encante — com Lelê</p>
        <div class="lp-cta-centro lp-rv">
            <?= $abreCta() ?>Quero contar e encantar</a>
        </div>
    </div>
</section>
