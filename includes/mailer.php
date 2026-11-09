<?php
/**
 * Sends email through Gmail SMTP using PHPMailer.
 * Credentials live in config/mail.php (never hard-code them here).
 */

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

/** Sends an email. Returns null on success, or an error message on failure. */
function send_mail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): ?string
{
    $configFile = __DIR__ . '/../config/mail.php';
    if (!is_file($configFile)) {
        return 'Mail is not configured. Copy config/mail.example.php to config/mail.php and fill it in.';
    }

    $cfg = require $configFile;
    if (str_starts_with($cfg['username'], 'YOUR_') || str_starts_with($cfg['password'], 'YOUR_')) {
        return 'Mail is not configured yet. Enter your Gmail address and App Password in config/mail.php.';
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = str_replace(' ', '', $cfg['password']); // App Passwords are shown with spaces
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $cfg['port'];
        $mail->Timeout    = 15;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody;

        $mail->send();
        return null;
    } catch (MailException $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return 'The email could not be sent. Check your Gmail address, App Password and internet connection.';
    }
}

function send_otp_email(string $toEmail, string $toName, string $otp): ?string
{
    $name    = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
    $minutes = OTP_EXPIRY_MINUTES;
    $app     = APP_NAME;

    $html = <<<HTML
<div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:24px;color:#29232A">
  <h2 style="color:#B85C7A;margin:0 0 16px">{$app}</h2>
  <p>Hello {$name},</p>
  <p>Use this code to verify your email address:</p>
  <p style="font-size:32px;letter-spacing:8px;font-weight:bold;background:#FFF7FA;border:1px solid #F0E3E9;padding:16px;text-align:center;border-radius:8px">{$otp}</p>
  <p>The code expires in {$minutes} minutes. If you did not create an account, you can ignore this email.</p>
</div>
HTML;

    $text = "Hello {$toName},\n\nYour {$app} verification code is {$otp}.\nIt expires in {$minutes} minutes.\n";

    return send_mail($toEmail, $toName, "{$otp} is your {$app} verification code", $html, $text);
}
