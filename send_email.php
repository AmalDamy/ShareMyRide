<?php
// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer Library
// Ensure these paths match your folder structure
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

function sendResetEmail($toEmail, $resetLink) {
    $mail = new PHPMailer(true);
    $debugOutput = '';

    try {
        // Capture Debug Output
        $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
            $debugOutput .= "$level: $str\n";
        };
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
        $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
            $debugOutput .= "$level: $str\n";
        };

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        $mail->Username   = 'sharemyride17@gmail.com';
        $mail->Password   = 'rvuwfmxicvzdnaik';
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // FIX FOR XAMPP: Disable SSL Certificate Verification
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Gmail requires the From address to match the authenticated user
        $mail->setFrom('sharemyride17@gmail.com', 'ShareMyRide');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password';
        $mail->Body    = "Click to reset: $resetLink";

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo, 'debug' => $debugOutput];
    }
}
?>
