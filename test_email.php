<?php

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    $mail->Username = 'ainafarzana521@gmail.com';
    $mail->Password = 'lhduceivbfqapvgt';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom(
        'ainafarzana521@gmail.com',
        'SA Design'
    );

    $mail->addAddress(
        'yonyonyona821@gmail.com'
    );

    $mail->isHTML(true);

    $mail->Subject = 'SA Design Test Email';

    $mail->Body = '
        <h2>SA Design</h2>
        <p>This is a test email.</p>
    ';

    $mail->send();

    echo "EMAIL SENT!";

} catch (Exception $e) {

    echo "EMAIL FAILED!<br><br>";
    echo "SMTP Error: " . $mail->ErrorInfo;

}
?>