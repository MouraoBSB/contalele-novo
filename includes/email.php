<?php
/**
 * Envio de e-mail do site Conta Lelê via SMTP (PHPMailer).
 * As credenciais de SMTP vêm da tabela `configuracoes`.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once CL_RAIZ . '/lib/PHPMailer/Exception.php';
require_once CL_RAIZ . '/lib/PHPMailer/PHPMailer.php';
require_once CL_RAIZ . '/lib/PHPMailer/SMTP.php';

/**
 * Envia um e-mail de texto simples. Devolve true se enviou, false se não.
 * Não lança exceção: registra no log e devolve false em caso de falha.
 *
 * @param string|null $responder Endereço de Reply-To (opcional).
 */
function enviar_email(string $para, string $assunto, string $corpo, ?string $responder = null): bool
{
    $host = configuracao('smtp_host');
    if ($host === '') {
        registrar_log('E-mail não enviado: SMTP não configurado', ['assunto' => $assunto]);
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->Port       = (int) (configuracao('smtp_porta', '587'));
        $mail->SMTPAuth   = true;
        $mail->Username   = configuracao('smtp_usuario');
        $mail->Password   = configuracao('smtp_senha');
        $mail->CharSet    = 'UTF-8';
        $seguranca = configuracao('smtp_seguranca', 'tls');
        if ($seguranca === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($seguranca === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $remetente = configuracao('smtp_remetente', configuracao('smtp_usuario'));
        $mail->setFrom($remetente, 'Site Conta Lelê');
        $mail->addAddress($para);
        if ($responder !== null && validar_email($responder)) {
            $mail->addReplyTo($responder);
        }
        $mail->Subject = $assunto;
        $mail->Body    = $corpo;

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        registrar_log('Falha no envio de e-mail', ['erro' => $mail->ErrorInfo]);
        return false;
    }
}
