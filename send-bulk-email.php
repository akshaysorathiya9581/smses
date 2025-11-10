<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if bulk sending is active
if (!isset($_SESSION['bulk_sending']) || !$_SESSION['bulk_sending']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No bulk sending session active']);
    exit();
}

// Load PHPMailer
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$index = $input['index'] ?? 0;

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'No email provided']);
    exit();
}

// Get SMTP configuration from session
$config = $_SESSION['smtp_config'] ?? [];

if (empty($config)) {
    echo json_encode(['success' => false, 'error' => 'SMTP configuration not found']);
    exit();
}

try {
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = $config['host'];
    $mail->Port       = $config['port'];
    $mail->SMTPAuth   = (!empty($config['username']) || !empty($config['password']));
    $mail->Username   = $config['username'];
    $mail->Password   = $config['password'];
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 30;

    // Encryption handling
    $security = strtolower($config['security']);
    if ($security === 'auto') {
        if ($config['port'] === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($config['port'] === 587 || $config['port'] === 25) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
    } elseif ($security === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($security === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;
    }

    // Email addresses
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($email);
    $mail->addReplyTo($config['from_email'], $config['from_name']);

    // Content
    $mail->isHTML($config['is_html']);
    $mail->Subject = $config['subject'];
    
    if ($config['is_html']) {
        $mail->Body = nl2br(htmlspecialchars($config['message']));
        $mail->AltBody = strip_tags($config['message']);
    } else {
        $mail->Body = $config['message'];
    }

    // Disable debug output for AJAX requests
    $mail->SMTPDebug = 0;

    // Send the email
    $mail->send();

    // Apply delay if specified (except for the last email)
    $totalEmails = count($_SESSION['bulk_emails'] ?? []);
    if ($index < $totalEmails - 1 && isset($config['delay']) && $config['delay'] > 0) {
        sleep($config['delay']);
    }

    echo json_encode([
        'success' => true,
        'email' => $email,
        'index' => $index
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'email' => $email,
        'index' => $index
    ]);
}

