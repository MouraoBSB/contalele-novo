<?php
/**
 * Instalador web do banco de dados do site Conta Lelê.
 * Cria o schema, semeia configurações e o primeiro usuário admin.
 * Protegido por token. REMOVA este arquivo após o uso.
 *
 * Uso: https://contalele.com.br/instalar.php?token=SEU_TOKEN
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
echo '<title>Instalador — Conta Lelê</title>';
echo '<body style="font-family:sans-serif;max-width:640px;margin:40px auto;padding:0 16px">';
echo '<h1>Instalador do banco — Conta Lelê</h1>';

$pdo = bd($config['db']);

// 1. Não reinstala por cima de um banco já populado.
$jaInstalado = $pdo->query("SHOW TABLES LIKE 'usuarios_admin'")->fetch();
if ($jaInstalado) {
    $temUsuario = (int) $pdo->query('SELECT COUNT(*) FROM usuarios_admin')->fetchColumn();
    if ($temUsuario > 0) {
        echo '<p><strong>O banco já está instalado.</strong> Nada foi alterado.</p>';
        echo '<p>Remova este arquivo (<code>instalar.php</code>) do servidor.</p>';
        exit;
    }
}

// 2. Cria o schema.
$sql = file_get_contents(__DIR__ . '/sql/schema.sql');
$pdo->exec($sql);
echo '<p>Tabelas criadas.</p>';

// 3. Semeia as configurações padrão.
$padroes = [
    ['whatsapp',        '5561991938603',                'Número de WhatsApp (só dígitos, com DDI)'],
    ['email_contato',   'contato@contalele.com.br',     'E-mail exibido no site'],
    ['instagram',       'https://www.instagram.com/contalele/', 'URL do Instagram'],
    ['facebook',        'https://www.facebook.com/leticia.mourao.585', 'URL do Facebook'],
    ['youtube',         'https://www.youtube.com/c/ContaLel%C3%AA', 'URL do canal no YouTube'],
    ['smtp_host',       '',                             'Servidor SMTP'],
    ['smtp_porta',      '587',                          'Porta SMTP'],
    ['smtp_usuario',    '',                             'Usuário SMTP'],
    ['smtp_senha',      '',                             'Senha SMTP'],
    ['smtp_remetente',  'contato@contalele.com.br',     'E-mail remetente'],
    ['smtp_seguranca',  'tls',                          'Criptografia SMTP (tls/ssl/nenhuma)'],
];
$ins = $pdo->prepare(
    'INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES (?, ?, ?)'
);
foreach ($padroes as $linha) {
    $ins->execute($linha);
}
echo '<p>Configurações padrão semeadas.</p>';

// 4. Cria o primeiro usuário admin com senha provisória aleatória.
$senhaProvisoria = bin2hex(random_bytes(6)); // 12 caracteres
$hash = password_hash($senhaProvisoria, PASSWORD_DEFAULT);
$pdo->prepare(
    'INSERT INTO usuarios_admin (nome, email, senha_hash, precisa_trocar_senha)
     VALUES (?, ?, ?, 1)'
)->execute(['Administrador', $config['instalador']['admin_email'], $hash]);

echo '<h2>Usuário administrador criado</h2>';
echo '<p>E-mail: <code>' . e($config['instalador']['admin_email']) . '</code></p>';
echo '<p>Senha provisória: <code style="font-size:1.2em">' . e($senhaProvisoria) . '</code></p>';
echo '<p><strong>Anote esta senha agora.</strong> Ela só aparece uma vez '
   . 'e deverá ser trocada no primeiro acesso ao painel.</p>';
echo '<hr><p><strong>Importante:</strong> remova o arquivo '
   . '<code>instalar.php</code> do servidor agora.</p>';
