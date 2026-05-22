<?php
/**
 * Logout do painel administrativo.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

define('CL_RAIZ', dirname(__DIR__));
require __DIR__ . '/incluir/sessao.php';

deslogar();
header('Location: /admin/login.php');
exit;
