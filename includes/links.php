<?php
/**
 * Destinos da página /links, a do "link na bio" do Instagram.
 *
 * Thiago Mourão — https://github.com/MouraoBSB — 08/11/2026
 */

declare(strict_types=1);

/**
 * Marca o link com a origem Instagram. A página de vendas repassa o src ao
 * checkout, e a Hotmart mostra nas vendas quais vieram da bio.
 */
function link_com_origem(string $url): string
{
    return $url . (str_contains($url, '?') ? '&' : '?') . 'src=instagram';
}

/**
 * Botões abaixo dos cursos: canal, contratação pelo WhatsApp e livros grátis.
 * Canal ou número vazio no painel tira o botão, em vez de deixar link quebrado.
 *
 * @return list<array{icone:string,rotulo:string,detalhe:string,href:string,externo:bool}>
 */
function links_bio_botoes(string $youtube, string $whatsapp): array
{
    $youtube = trim($youtube);
    $numero  = preg_replace('/\D/', '', $whatsapp) ?? '';
    $botoes  = [];

    if ($youtube !== '') {
        $botoes[] = [
            'icone'   => 'youtube',
            'rotulo'  => 'Canal no YouTube',
            'detalhe' => 'Histórias contadas pela Lelê',
            'href'    => $youtube,
            'externo' => true,
        ];
    }
    if ($numero !== '') {
        $mensagem = 'Oi, Lelê! Vim pelo Instagram e quero saber sobre contação de histórias.';
        $botoes[] = [
            'icone'   => 'whatsapp',
            'rotulo'  => 'Contratar a Lelê',
            'detalhe' => 'Para escolas, eventos e famílias, pelo WhatsApp',
            'href'    => 'https://wa.me/' . $numero . '?text=' . rawurlencode($mensagem),
            'externo' => true,
        ];
    }
    $botoes[] = [
        'icone'   => 'livros',
        'rotulo'  => 'Livros grátis',
        'detalhe' => 'Histórias da Lelê para baixar em PDF',
        'href'    => '/livros',
        'externo' => false,
    ];

    return $botoes;
}
