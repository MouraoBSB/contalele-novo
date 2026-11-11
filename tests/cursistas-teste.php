<?php
declare(strict_types=1);

require __DIR__ . '/../includes/cursistas.php';

// senha_forte()
afirmar(senha_forte('12345678'), 'senha_forte() aceita 8 caracteres');
afirmar(!senha_forte('1234567'), 'senha_forte() rejeita 7 caracteres');
afirmar(!senha_forte(''), 'senha_forte() rejeita vazio');

// normalizar_email()
afirmar_igual('a@b.com', normalizar_email('  A@B.com '), 'normalizar_email() apara e baixa caixa');
afirmar_igual('joao@exemplo.com', normalizar_email('Joao@Exemplo.COM'), 'normalizar_email() baixa o domínio');

// decisao_bloqueio()
afirmar_igual(['tentativas' => 1, 'bloquear' => false], decisao_bloqueio(0), 'decisao_bloqueio() incrementa de 0 para 1');
afirmar_igual(['tentativas' => 4, 'bloquear' => false], decisao_bloqueio(3), 'decisao_bloqueio() incrementa sem bloquear no 4');
afirmar_igual(['tentativas' => 0, 'bloquear' => true], decisao_bloqueio(4), 'decisao_bloqueio() bloqueia na 5ª tentativa');

// entrada_visivel() — o botão "Entrar" só aparece quando ligado nas Configurações do painel
require_once __DIR__ . '/../includes/autenticacao_cursista.php';
afirmar(entrada_visivel(static fn (string $c, string $p): string => '1'), 'entrada_visivel() mostra o Entrar quando a configuração está ligada');
afirmar(!entrada_visivel(static fn (string $c, string $p): string => '0'), 'entrada_visivel() esconde o Entrar quando a configuração está desligada');
afirmar(!entrada_visivel(static fn (string $c, string $p): string => $p), 'entrada_visivel() esconde o Entrar enquanto a configuração não existe');
afirmar(!entrada_visivel(static function (string $c, string $p): string { throw new PDOException('banco fora'); }), 'entrada_visivel() esconde o Entrar se o banco falhar');
