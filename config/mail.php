<?php
// Configurazione mail ANED
define('MAIL_HOST_SMTP', 'smtps.aruba.it');
define('MAIL_HOST_POP3', 'pop3s.aruba.it');
define('MAIL_PORT_SMTP', 465);
define('MAIL_USERNAME', 'roma@aned.it');
define('MAIL_PASSWORD', 'Anchiave25!');
define('MAIL_FROM', 'roma@aned.it');
define('MAIL_FROM_NAME', 'ANED Roma');
define('MAIL_ENCRYPTION', 'ssl');

// Funzione invio mail via PHPMailer (incluso nel progetto)
function sendMail($to, $subject, $htmlBody, $textBody = '') {
    require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';
    require_once __DIR__ . '/../libs/PHPMailer/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST_SMTP;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = MAIL_PORT_SMTP;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Errore invio mail: ' . $mail->ErrorInfo);
        return false;
    }
}

function mailTemplate($title, $content) {
    return '<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}
  .wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
  .header{background:#1a1a2e;padding:24px;text-align:center}
  .logo-text{font-size:32px;font-weight:900;color:#fff;letter-spacing:2px}
  .triangle{width:0;height:0;border-left:30px solid transparent;border-right:30px solid transparent;border-top:52px solid #c0392b;margin:8px auto 0}
  .it-label{margin-top:-42px;color:#fff;font-weight:700;font-size:14px;text-align:center}
  .body{padding:32px}
  .body h2{color:#1a1a2e;margin-top:0}
  .btn{display:inline-block;background:#c0392b;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:bold;margin-top:16px}
  .footer{background:#f4f4f4;padding:16px;text-align:center;font-size:12px;color:#888}
</style></head>
<body>
<div class="wrap">
  <div class="header">
    <div class="logo-text">ANED</div>
    <div class="triangle"></div>
    <div class="it-label">IT</div>
  </div>
  <div class="body">
    <h2>' . htmlspecialchars($title) . '</h2>
    ' . $content . '
  </div>
  <div class="footer">ANED - Associazione Nazionale Ex Deportati &bull; roma@aned.it</div>
</div>
</body></html>';
}
