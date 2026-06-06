<?php
declare(strict_types=1);

require __DIR__ . '/../includes/tokens.php';

// gerar_token_cru()
$t1 = gerar_token_cru();
$t2 = gerar_token_cru();
afirmar(strlen($t1) === 64, 'gerar_token_cru() devolve 64 caracteres hex');
afirmar(ctype_xdigit($t1), 'gerar_token_cru() devolve apenas dígitos hexadecimais');
afirmar($t1 !== $t2, 'gerar_token_cru() gera valores diferentes a cada chamada');

// hash_token()
afirmar(strlen(hash_token($t1)) === 64, 'hash_token() devolve sha256 de 64 caracteres');
afirmar(hash_token($t1) === hash_token($t1), 'hash_token() é determinístico');
afirmar(hash_token($t1) !== $t1, 'hash_token() não devolve o token cru');
afirmar(hash_token($t1) !== hash_token($t2), 'hash_token() difere para tokens diferentes');

// TTLs definidos
afirmar(TOKEN_TTL_VERIFICACAO === 86400, 'TTL de verificação é 24h');
afirmar(TOKEN_TTL_RECUPERACAO === 3600, 'TTL de recuperação é 1h');
