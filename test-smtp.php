<?php
require 'vendor/autoload.php'; // If using Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'milagrosluingtnp89@gmail.com';
    $mail->Password = '****************'; // Use an App Password if 2FA is enabled
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('noreply@shopwithpraise.com', 'Test');
    $mail->addAddress('braydenegan7@gmail.com');
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email from Gmail SMTP';

    $mail->send();
    echo 'Email sent successfully!';
} catch (Exception $e) {
    echo "Email failed: {$mail->ErrorInfo}";
}
