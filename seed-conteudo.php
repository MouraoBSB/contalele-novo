<?php
/**
 * Semeia o conteúdo dinâmico inicial do site Conta Lelê.
 * Protegido por token. REMOVA este arquivo após o uso.
 * Uso: https://contalele.com.br/seed-conteudo.php?token=SEU_TOKEN
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', __DIR__);
$config = require __DIR__ . '/config.php';
require __DIR__ . '/includes/erros.php';
require __DIR__ . '/includes/funcoes.php';
require __DIR__ . '/includes/conexao.php';

ativar_tratamento_erros($config['site']['ambiente']);
header('Content-Type: text/plain; charset=utf-8');

if (!hash_equals($config['instalador']['token'], (string) ($_GET['token'] ?? ''))) {
    http_response_code(403);
    exit('Acesso negado.');
}

$pdo = bd($config['db']);

// Só semeia se a tabela estiver vazia (idempotente).
function semear(PDO $pdo, string $tabela, array $colunas, array $linhas): void
{
    $qtd = (int) $pdo->query("SELECT COUNT(*) FROM {$tabela}")->fetchColumn();
    if ($qtd > 0) {
        echo "{$tabela}: já tem {$qtd} registro(s), pulando.\n";
        return;
    }
    $marc = '(' . implode(',', array_fill(0, count($colunas), '?')) . ')';
    $ins = $pdo->prepare(
        'INSERT INTO ' . $tabela . ' (' . implode(',', $colunas) . ') VALUES ' . $marc
    );
    foreach ($linhas as $linha) {
        $ins->execute($linha);
    }
    echo "{$tabela}: " . count($linhas) . " registro(s) inserido(s).\n";
}

// ── CORDÉIS (DOC-CONTEUDO §7) — [titulo, slug, sinopse, video_youtube, imagem_capa, ordem]
$cordeis = [
    [
        'Cordel Monstruoso',
        'cordel-monstruoso',
        'Esse é um cordel tenebroso! Conta a história do VENOBICHO, um monstro perigoso que pode estar em qualquer lugar. Mas ele gosta mesmo é de morar dentro das pessoas. Quando acha um hospedeiro, joga um veneno dentro do seu coração, fazendo a pessoa agir de forma muito cruel. Para impedir o monstro de nos invadir, é preciso agir diferente!',
        'https://www.youtube.com/watch?v=2s-SudIPNXs',
        'monstruoso.png',
        1,
    ],
    [
        'Os Dois Cabritos',
        'os-dois-cabritos',
        'Era uma vez dois cabritos que precisaram passar ao mesmo tempo por uma ponte muito estreita. Quem passaria primeiro? Quem iria ceder? Baseado na obra de Tatiana Belinky, criamos um cordel musicalizado trazendo o poder da tolerância e da gentileza.',
        'https://www.youtube.com/watch?v=uqfw2ARE6P4',
        'cabritos.png',
        2,
    ],
    [
        'Maria não vai Cazoutras',
        'maria-nao-vai-cazoutras',
        'Era uma vez uma ovelhinha que, para ser agradável, fazia tudo que as outras ovelhas faziam também. Até que um dia, cansada de apenas seguir, passou a caminhar como bem quisesse. Desta forma, nunca mais seguiu ninguém!!',
        'https://www.youtube.com/watch?v=n5ILN6jYCqE',
        'cazoutras.png',
        3,
    ],
    [
        'Sapa Cristina',
        'sapa-cristina',
        'Em um brejo bem distante / Morava uma linda sapinha / Danada e bem teimosa / Ela era danadinha / Um dia desobedeceu a mamãe / E ficou bem doentinha.',
        'https://www.youtube.com/watch?v=vYmu-8-Qu6M',
        'sapa.png',
        4,
    ],
    [
        'Cordel Amarelo',
        'cordel-amarelo',
        'Combater o suicídio é falar de vida. Utilizei-me da Literatura de Cordel e me enchi de cor para falar de forma leve e poética sobre algo tão polêmico e necessário. A vida vale mais e é um presente! Fica, você é importante pra muita gente!',
        'https://www.youtube.com/watch?v=_l-7XhVivlk',
        'amarelo.png',
        5,
    ],
    [
        'Cordel Feminino',
        'cordel-feminino',
        'Um cordel para homenagear o ser que é o capricho da Criação: o ser feminino!',
        'https://www.youtube.com/watch?v=bh0s9Kc9TP0',
        'feminino.png',
        6,
    ],
    [
        'Nada de Ziquizira',
        'nada-de-ziquizira',
        'Um cordel em homenagem aos 82 anos de Dona Alzira.',
        'https://www.youtube.com/watch?v=_BoFRqwjNWs',
        'ziquizira.png',
        7,
    ],
    [
        'Cordel da Poderosa',
        'cordel-da-poderosa',
        'Um cordel para homenagear os 60 anos de vida da minha mãe.',
        'https://www.youtube.com/watch?v=kIJHyJVj50I',
        'poderosa.png',
        8,
    ],
    [
        'Cordel do Chico',
        'cordel-do-chico',
        'Um cordel para homenagear um grande brasileiro.',
        'https://www.youtube.com/watch?v=CzmLcwBJIdo',
        'chico.png',
        9,
    ],
];
semear($pdo, 'cordeis', ['titulo', 'slug', 'sinopse', 'video_youtube', 'imagem_capa', 'ordem'], $cordeis);

// ── E-BOOKS (DOC-CONTEUDO §8) — [titulo, slug, sinopse, arquivo_pdf, imagem_capa, ordem]
$ebooks = [
    [
        'Os Dois Cabritos',
        'os-dois-cabritos',
        'Uma divertida empreitada onde dois cabritos precisam passar na ponte ao mesmo tempo. Alguém teria que ceder.',
        'Os_Dois_Cabritos.pdf',
        'cabritos.png',
        1,
    ],
    [
        'A História de um Coração',
        'a-historia-de-um-coracao',
        'História de um planeta onde moram partes de gente. Havia muitos corações, mas um era muito especial por amar incondicionalmente: o coração materno.',
        'A_Historia_De_Um_Coracao.pdf',
        null,
        2,
    ],
    [
        'A Festa Junina da Galinha Ruiva',
        'a-festa-junina-da-galinha-ruiva',
        'Você provavelmente conhece a história da Galinha Ruiva, que encontra um grão de milho e resolve plantá-lo. Pede ajuda aos amigos, mas ninguém quer ajudar — nem a plantar, nem a colher, nem a moer, nem a fazer um lindo e delicioso bolo. Mas quando o bolo fica pronto... todos querem um pedacinho. Desta vez a Galinha Ruiva, ao encontrar o milharal, decide fazer, além de bolo, pamonha, creme de milho, curau e suco. Então, decidiu juntar seus pintinhos e fazer uma deliciosa festa junina.',
        'A_Galinha_Ruiva.pdf',
        null,
        3,
    ],
    [
        'Fui Dormir em uma Oquinha',
        'fui-dormir-em-uma-oquinha',
        'História para homenagear os povos indígenas. Certa vez um menino foi dormir em uma oca e conheceu um indígena. Releitura da música "Fui morar em uma casinha".',
        'Fui_Dormir_Em_Uma_Oquinha.pdf',
        null,
        4,
    ],
    [
        'Cordel Monstruoso',
        'cordel-monstruoso',
        'História de um monstro malvado que entra dentro do homem. O Venobicho contamina com preconceito.',
        'Cordel_Monstruoso.pdf',
        'monstruoso.png',
        5,
    ],
];
semear($pdo, 'ebooks', ['titulo', 'slug', 'sinopse', 'arquivo_pdf', 'imagem_capa', 'ordem'], $ebooks);

// ── NOTÍCIAS (DOC-CONTEUDO §11) — [titulo, veiculo, url, imagem, data_publicacao, ordem]
$noticias = [
    [
        'Publicação no site da Editora Fergs ("Juca, o Cavalo-Marinho")',
        'Editora Fergs',
        'http://editora.fergs.org.br/juca-o-cavalo-marinho-contalele/',
        null,
        null,
        1,
    ],
    [
        'Publicação no site da Editora EME (contação de história)',
        'Editora EME',
        'https://editoraeme.com.br/blog/contacao-de-historia/',
        null,
        null,
        2,
    ],
    [
        'Publicação no site da Editora EME (tag Letícia Mourão)',
        'Editora EME',
        'https://editoraeme.com.br/blog/tag/leticia-mourao/',
        null,
        null,
        3,
    ],
    [
        'Publicação no Instagram da Editora Edebê',
        'Editora Edebê',
        'https://www.instagram.com/tv/CMEzAHBjJYm/',
        null,
        null,
        4,
    ],
];
semear($pdo, 'noticias', ['titulo', 'veiculo', 'url', 'imagem', 'data_publicacao', 'ordem'], $noticias);

// ── DEPOIMENTOS (DOC-CONTEUDO §13) — [autor, texto, foto, ordem]
$depoimentos = [
    [
        'Adeilson Sales',
        'Parabéns por todo seu trabalho. Esse resultado é fruto da sua competência e dedicação. Seja feliz!',
        'Adeilson.jpg',
        1,
    ],
    [
        'Gisely',
        'Hj canto, danço e me fantasio pras minhas crianças, pq um dia encontrei uma Lele. Vc é especial na minha história. Amo falar q te conheço a anos. Enobrece meu currículo 🤭🤭🤭',
        'Gisely.jpg',
        2,
    ],
    [
        'Marlene',
        'Acabei de assistir. Ficou maravilhoso 👏🏼👏🏼 você é um espetáculo 🤩💗👏🏼👏🏼',
        'Marlene.jpg',
        3,
    ],
    [
        'Iraneide',
        'Já perguntei pra Conta Lelê uma vez, qual é o valor do investimento??? Pq é isso gente!!! É um investimento que fazemos: ao contratarmos um cordel sobre a vida de alguém, uma história pra crianças, ou quaisquer outras formas de arte, é investir na emoção das pessoas, nas memórias, na história.',
        'Iraneide.jpg',
        4,
    ],
    [
        'Adairis',
        'Mulher, tem uma que vc come o papel, ela já assistiu mil vezes e não se cansa... E o legal é quando vc fala uma palavra que ela não conhece: ela volta, escuta novamente e vem me perguntar sobre essa palavra; quando ela já sabe o sentido, volta a história pra entender toda.',
        'Adairis.jpg',
        5,
    ],
];
semear($pdo, 'depoimentos', ['autor', 'texto', 'foto', 'ordem'], $depoimentos);

// ── FESTIVAIS (DOC-CONTEUDO §10) — [titulo, descricao, ano, videos, imagem, ordem]
$festivais = [
    [
        'Festival do Instituto Latinoamérica',
        'Festival promovido pelo Instituto Latinoamérica, em setembro de 2020. Participei entre os 15 contadores.',
        2020,
        "https://www.youtube.com/watch?v=vhe0a2W3Z4w\nhttps://www.youtube.com/watch?v=iglleMQZqJ4",
        'festival1.png',
        1,
    ],
    [
        'Feira da Pracinha do Museu',
        'Participação como contadora de histórias na Feira da Pracinha do Museu.',
        null,
        null,
        'festival2.png',
        2,
    ],
    [
        'Feira Cultural de Planaltina',
        'Participação na Feira Cultural de Planaltina. Feira promovida pelo Instituto Latinoamérica, em novembro de 2020.',
        2020,
        'https://www.youtube.com/watch?v=Gu61oWoOqP4',
        'festival3.png',
        3,
    ],
    [
        'Projeto de Extensão Universitária / CEPAE — Grupo GWAYA — UFG',
        'Participação no Projeto de Extensão Universitária/CEPAE-Grupo GWAYA-UFG.',
        null,
        "https://www.youtube.com/watch?v=MBADsfdnrig\nhttps://www.youtube.com/watch?v=39rMJ7eWKJw\nhttps://www.youtube.com/watch?v=PcdKx68wIQ8",
        'festival4.png',
        4,
    ],
];
semear($pdo, 'festivais', ['titulo', 'descricao', 'ano', 'videos', 'imagem', 'ordem'], $festivais);

// ── PRÊMIOS (DOC-CONTEUDO §10) — [titulo, ano, ordem]
$premios = [
    ['3º lugar no 3º Festival de Curtas das Escolas Públicas de Planaltina-DF', null, 1],
    ['Premiada no 1º Festival Amazônico de Contação de Histórias', null, 2],
    ['Premiada no 2º Festival Amazônico de Contação de Histórias', null, 3],
    ['Premiada no Festival Arte Fato de Brasília', null, 4],
    ['Premiada no Festival Feira da Pracinha do Museu', null, 5],
];
semear($pdo, 'premios', ['titulo', 'ano', 'ordem'], $premios);

echo "\nSeed concluído. Remova seed-conteudo.php do servidor.\n";
