<?php
/**
 * Migração idempotente do banco do site Conta Lelê — Fase 2 (contas de cursistas).
 * Cria tabelas/colunas novas e semeia configurações do Google sem afetar dados existentes.
 * Protegido pelo token do instalador. REMOVA este arquivo após o uso.
 *
 * Uso: https://contalele.com.br/migrar.php?token=SEU_TOKEN
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
header('Content-Type: text/html; charset=utf-8');

$token = $_GET['token'] ?? '';
if (!hash_equals($config['instalador']['token'], (string) $token)) {
    http_response_code(403);
    exit('Acesso negado.');
}

echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8">';
echo '<title>Migração — Conta Lelê</title>';
echo '<body style="font-family:sans-serif;max-width:640px;margin:40px auto;padding:0 16px">';
echo '<h1>Migração do banco — contas de cursistas</h1>';

$pdo = bd($config['db']);

// 1. Tabelas novas (idempotente via IF NOT EXISTS).
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS usuarios_cursistas (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        nome VARCHAR(120) NOT NULL,
        email VARCHAR(160) NOT NULL,
        senha_hash VARCHAR(255) NULL,
        google_id VARCHAR(40) NULL,
        email_verificado TINYINT(1) NOT NULL DEFAULT 0,
        verificado_em DATETIME NULL,
        tentativas_login TINYINT UNSIGNED NOT NULL DEFAULT 0,
        bloqueado_ate DATETIME NULL,
        ultimo_acesso DATETIME NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_usuarios_cursistas_email (email),
        UNIQUE KEY uq_usuarios_cursistas_google (google_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS tokens_autenticacao (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        escopo ENUM('cursista','admin') NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        finalidade ENUM('verificacao','recuperacao') NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expira_em DATETIME NOT NULL,
        usado_em DATETIME NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_token_hash (token_hash),
        KEY ix_token_lookup (escopo, usuario_id, finalidade)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo '<p>Tabelas usuarios_cursistas e tokens_autenticacao garantidas.</p>';

// 2. Coluna google_id em usuarios_admin (idempotente: confere antes).
$temColuna = $pdo->query("SHOW COLUMNS FROM usuarios_admin LIKE 'google_id'")->fetch();
if (!$temColuna) {
    $pdo->exec(
        "ALTER TABLE usuarios_admin
         ADD COLUMN google_id VARCHAR(40) NULL,
         ADD UNIQUE KEY uq_usuarios_admin_google (google_id)"
    );
    echo '<p>Coluna google_id adicionada em usuarios_admin.</p>';
} else {
    echo '<p>Coluna google_id já existe em usuarios_admin.</p>';
}

// 3. Tabela de limites de ação por IP (anti-abuso).
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS limites_acao (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        acao VARCHAR(40) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY ix_limites_lookup (acao, ip, criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo '<p>Tabela limites_acao garantida.</p>';

// 4. Configurações do Google (idempotente via INSERT IGNORE).
$ins = $pdo->prepare(
    'INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES (?, ?, ?)'
);
foreach ([
    ['google_client_id',     '',  'Google OAuth — Client ID'],
    ['google_client_secret', '',  'Google OAuth — Client Secret'],
    ['google_oauth_ativo',   '0', 'Google OAuth — ativo (1/0)'],
] as $linha) {
    $ins->execute($linha);
}
echo '<p>Configurações do Google semeadas.</p>';

echo '<hr><p><strong>Importante:</strong> remova o arquivo <code>migrar.php</code> do servidor agora.</p>';
