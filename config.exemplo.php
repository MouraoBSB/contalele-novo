<?php
/**
 * Configuração do site Conta Lelê — MODELO.
 * Copie este arquivo para config.php e preencha com os valores reais.
 * config.php NÃO é versionado.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

return [
    'db' => [
        'host'    => 'localhost',
        'nome'    => 'NOME_DO_BANCO',
        'usuario' => 'USUARIO_DO_BANCO',
        'senha'   => 'SENHA_DO_BANCO',
        'charset' => 'utf8mb4',
    ],
    'site' => [
        'url'      => 'https://contalele.com.br',
        'ambiente' => 'producao', // 'producao' ou 'desenvolvimento'
    ],
    'instalador' => [
        // Token aleatório longo que protege instalar.php e admin/diagnostico.php.
        'token'       => 'TOKEN_ALEATORIO_LONGO',
        // E-mail do primeiro usuário do painel administrativo.
        'admin_email' => 'contato@contalele.com.br',
    ],
];
