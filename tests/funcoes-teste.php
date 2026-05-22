<?php
declare(strict_types=1);

require __DIR__ . '/../includes/funcoes.php';

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
