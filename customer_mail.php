<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/vendor/autoload.php';

function sendCustomerEmail(string $recipient, string $recipientName, string $subject, string $html): bool
{
    $config = require __DIR__ . '/mail_config.php';
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($config['username'], $config['from_name']);
        $mail->addAddress($recipient, $recipientName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $html)));
        $mail->send();
        return true;
    } catch (Exception $exception) {
        error_log('Customer email failed: ' . $exception->getMessage());
        return false;
    }
}

function customerEmailTemplate(string $title, string $message): string
{
    return '<div style="font-family:Arial,sans-serif;max-width:620px;padding:24px;color:#182033">'
        . '<h2 style="color:#102f91;margin-bottom:12px">SA Design</h2>'
        . '<h3 style="color:#f0208d">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>'
        . '<p style="line-height:1.7">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>'
        . '<p style="color:#64748b;font-size:12px">This is an automated notification from SA Design.</p></div>';
}
