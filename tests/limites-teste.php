<?php
declare(strict_types=1);

require __DIR__ . '/../includes/limites.php';

$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
afirmar_igual('203.0.113.7', ip_requisicao(), 'ip_requisicao() devolve o REMOTE_ADDR');

unset($_SERVER['REMOTE_ADDR']);
afirmar_igual('0.0.0.0', ip_requisicao(), 'ip_requisicao() usa o padrão sem REMOTE_ADDR');
