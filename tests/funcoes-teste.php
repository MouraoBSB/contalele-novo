<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/funcoes.php';

// e() — escape de HTML
afirmar_igual('&lt;b&gt;', e('<b>'), 'e() escapa tags HTML');
afirmar_igual('João &amp; Cia', e('João & Cia'), 'e() escapa & preservando acentos UTF-8');

// gerar_slug()
afirmar_igual('cordel-monstruoso', gerar_slug('Cordel Monstruoso'), 'gerar_slug() simples');
afirmar_igual('os-dois-cabritos', gerar_slug('Os Dois Cabritos'), 'gerar_slug() com artigos');
afirmar_igual('maria-nao-vai', gerar_slug('Maria não vai!'), 'gerar_slug() remove acento e pontuação');
afirmar_igual('a-lele', gerar_slug('  A   Lelê  '), 'gerar_slug() colapsa espaços');

// validar_email()
afirmar(validar_email('contato@contalele.com.br'), 'validar_email() aceita e-mail válido');
afirmar(!validar_email('invalido'), 'validar_email() rejeita texto sem @');
afirmar(!validar_email(''), 'validar_email() rejeita vazio');

// limpar_texto()
afirmar_igual('Oi Lelê', limpar_texto("  Oi   Lelê  "), 'limpar_texto() apara e colapsa espaços');
afirmar_igual('texto', limpar_texto('<script>texto</script>'), 'limpar_texto() remove tags');

// CSRF — geração e validação
$t = csrf_token();
afirmar(strlen($t) === 64, 'csrf_token() gera token de 64 caracteres');
afirmar(csrf_token() === $t, 'csrf_token() é estável na mesma sessão');
afirmar(csrf_validar($t), 'csrf_validar() aceita o token correto');
afirmar(!csrf_validar('errado'), 'csrf_validar() rejeita token errado');
afirmar(!csrf_validar(null), 'csrf_validar() rejeita null');

// url_imagem() — o painel salva em uploads/; as imagens do projeto ficam em assets/img/
$raizTeste = sys_get_temp_dir() . '/cl-url-imagem-' . bin2hex(random_bytes(4));
mkdir($raizTeste . '/uploads', 0777, true);
file_put_contents($raizTeste . '/uploads/a1b2c3.jpg', 'x');
afirmar_igual('/uploads/a1b2c3.jpg', url_imagem('a1b2c3.jpg', $raizTeste), 'url_imagem() acha a foto enviada pelo painel em uploads/');
afirmar_igual('/assets/img/Gisely.jpg', url_imagem('Gisely.jpg', $raizTeste), 'url_imagem() usa assets/img/ para imagens do projeto');
afirmar_igual('/assets/img/depoimentos/debora.jpg', url_imagem('depoimentos/debora.jpg', $raizTeste), 'url_imagem() aceita subpasta de assets/img/');
afirmar_igual('/assets/img/foto%20nova.jpg', url_imagem('foto nova.jpg', $raizTeste), 'url_imagem() codifica espaços no nome');
afirmar_igual('/assets/img/x.jpg', url_imagem('/x.jpg', $raizTeste), 'url_imagem() ignora barra inicial');
afirmar_igual('', url_imagem('', $raizTeste), 'url_imagem() devolve vazio sem arquivo');
afirmar_igual('', url_imagem('../config.php', $raizTeste), 'url_imagem() recusa caminho com ..');

// url_pdf() — mesmo critério para os e-books: uploads/ ou downloads/
file_put_contents($raizTeste . '/uploads/f9e8d7.pdf', 'x');
afirmar_igual('/uploads/f9e8d7.pdf', url_pdf('f9e8d7.pdf', $raizTeste), 'url_pdf() acha o PDF enviado pelo painel em uploads/');
afirmar_igual('/downloads/cordel.pdf', url_pdf('cordel.pdf', $raizTeste), 'url_pdf() usa downloads/ para PDFs do projeto');
afirmar_igual('', url_pdf('../x.pdf', $raizTeste), 'url_pdf() recusa caminho com ..');

// url_video() — vídeos enviados pelo painel ficam em uploads/; os do projeto, em assets/video/
file_put_contents($raizTeste . '/uploads/c4d5e6.mp4', 'x');
afirmar_igual('/uploads/c4d5e6.mp4', url_video('c4d5e6.mp4', $raizTeste), 'url_video() acha o vídeo enviado pelo painel em uploads/');
afirmar_igual('/assets/video/depoimento-manuela.mp4', url_video('depoimento-manuela.mp4', $raizTeste), 'url_video() usa assets/video/ para vídeos do projeto');
afirmar_igual('', url_video('../config.php', $raizTeste), 'url_video() recusa caminho com ..');

// com_versao() — acrescenta ?v=<data do arquivo> para o cache da Cloudflare pegar a troca
mkdir($raizTeste . '/assets/video', 0777, true);
file_put_contents($raizTeste . '/assets/video/foto nova.mp4', 'x');
touch($raizTeste . '/assets/video/foto nova.mp4', 1700000000);
afirmar_igual('/assets/video/foto%20nova.mp4?v=1700000000', com_versao('/assets/video/foto%20nova.mp4', $raizTeste), 'com_versao() usa a data do arquivo, mesmo com nome codificado');
afirmar_igual('/assets/video/sumiu.mp4', com_versao('/assets/video/sumiu.mp4', $raizTeste), 'com_versao() deixa o endereço como está se o arquivo não existe');
afirmar_igual('', com_versao('', $raizTeste), 'com_versao() devolve vazio para endereço vazio');
afirmar_igual('/assets/x/../../config.php', com_versao('/assets/x/../../config.php', $raizTeste), 'com_versao() não consulta caminho com ..');
unlink($raizTeste . '/assets/video/foto nova.mp4');
rmdir($raizTeste . '/assets/video');
rmdir($raizTeste . '/assets');
unlink($raizTeste . '/uploads/c4d5e6.mp4');
unlink($raizTeste . '/uploads/f9e8d7.pdf');
unlink($raizTeste . '/uploads/a1b2c3.jpg');
rmdir($raizTeste . '/uploads');
rmdir($raizTeste);
