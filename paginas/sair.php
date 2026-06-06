<?php
/**
 * Logout do cursista.
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

require_once CL_RAIZ . '/includes/autenticacao_cursista.php';

deslogar_cursista();
header('Location: /');
exit;
