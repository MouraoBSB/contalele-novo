<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/links.php';

// link_com_origem() — a Hotmart registra de onde veio a venda pelo parâmetro src
afirmar_igual('/conte-e-encante?src=instagram', link_com_origem('/conte-e-encante'), 'origem acrescentada ao link do curso');
afirmar_igual('/cursos?a=x1&src=instagram', link_com_origem('/cursos?a=x1'), 'origem acrescentada a link que já tem parâmetros');

// links_bio_botoes() — ordem, destinos e abertura em nova aba
$botoes = links_bio_botoes('https://www.youtube.com/c/ContaLel%C3%AA', '+55 (61) 99193-8603');
afirmar_igual(['youtube', 'whatsapp', 'livros'], array_column($botoes, 'icone'), 'botões na ordem: YouTube, WhatsApp, livros');
afirmar_igual('https://www.youtube.com/c/ContaLel%C3%AA', $botoes[0]['href'], 'YouTube aponta para o canal configurado');
afirmar_igual(true, $botoes[0]['externo'], 'YouTube abre em nova aba');
afirmar(
    str_starts_with($botoes[1]['href'], 'https://wa.me/5561991938603?text='),
    'WhatsApp usa só os dígitos do número'
);
afirmar(
    str_contains(rawurldecode((string) parse_url($botoes[1]['href'], PHP_URL_QUERY)), 'Instagram'),
    'mensagem do WhatsApp diz que a pessoa veio do Instagram'
);
afirmar_igual(true, $botoes[1]['externo'], 'WhatsApp abre em nova aba');
afirmar_igual('/livros', $botoes[2]['href'], 'livros grátis apontam para a página Livros');
afirmar_igual(false, $botoes[2]['externo'], 'livros abrem na mesma aba');
foreach ($botoes as $b) {
    afirmar($b['rotulo'] !== '' && $b['detalhe'] !== '', "botão {$b['icone']} tem rótulo e detalhe");
}

// Canal ou número vazio no painel: o botão some, em vez de virar link quebrado
afirmar_igual(['livros'], array_column(links_bio_botoes('', ''), 'icone'), 'sem canal e sem número, só o botão dos livros');
afirmar_igual(['livros'], array_column(links_bio_botoes('   ', '( )'), 'icone'), 'canal em branco e número sem dígitos também somem');
