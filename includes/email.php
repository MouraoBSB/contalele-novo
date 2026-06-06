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
function enviar_email(string $para, string $assunto, string $corpo, ?string $responder = null, ?string $html = null): bool
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
        if ($html !== null) {
            $mail->isHTML(true);
            $mail->Body    = $html;
            $mail->AltBody = $corpo;
        } else {
            $mail->Body = $corpo;
        }

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        registrar_log('Falha no envio de e-mail', ['erro' => $mail->ErrorInfo]);
        return false;
    }
}

/**
 * Monta um e-mail HTML simples na identidade da marca Conta Lelê.
 * Usa estilos inline (compatibilidade com clientes de e-mail) e a paleta da marca.
 *
 * @param array<int,string> $paragrafos Parágrafos do corpo (texto puro; serão escapados).
 */
function template_email(string $titulo, array $paragrafos, ?string $textoBotao = null, ?string $urlBotao = null): string
{
    $corpo = '';
    foreach ($paragrafos as $p) {
        $corpo .= '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#2b1300">'
            . e($p) . '</p>';
    }

    $botao = '';
    if ($textoBotao !== null && $urlBotao !== null) {
        $botao = '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 4px">'
            . '<tr><td style="border-radius:999px;background:#2b1300">'
            . '<a href="' . e($urlBotao) . '" '
            . 'style="display:inline-block;padding:14px 28px;border-radius:999px;'
            . 'background:#2b1300;color:#ffdc3a;font-weight:800;font-size:15px;'
            . 'text-decoration:none;font-family:Arial,Helvetica,sans-serif">'
            . e($textoBotao) . '</a></td></tr></table>';
    }

    return '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#fdf5d4;'
        . 'font-family:Arial,Helvetica,sans-serif">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
        . 'style="background:#fdf5d4;padding:24px 0"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
        . 'style="max-width:520px;background:#ffffff;border-radius:18px;overflow:hidden;'
        . 'box-shadow:0 8px 18px -14px rgba(43,19,0,0.3)">'
        . '<tr><td style="background:#ffdc3a;padding:18px 28px;font-weight:900;'
        . 'font-size:18px;color:#2b1300">Conta Lelê</td></tr>'
        . '<tr><td style="padding:28px">'
        . '<h1 style="margin:0 0 18px;font-size:22px;color:#2b1300">' . e($titulo) . '</h1>'
        . $corpo . $botao
        . '<p style="margin:24px 0 0;font-size:12px;color:rgba(43,19,0,0.58)">'
        . 'Se você não solicitou este e-mail, pode ignorá-lo com segurança.</p>'
        . '</td></tr></table></td></tr></table></body></html>';
}
