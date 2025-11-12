<?php
session_start();

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type']) && $_POST['type'] == 1) {
    if(empty($_POST['username']) && empty($_POST['password'])){
        header("Location: login.php?status=0");
        exit();
    } else {
        if($_POST['username'] == 'admin' && $_POST['password'] == '7892'){
            $_SESSION['username'] = 'admin';
            // Continue to the page
        } else {
            header("Location: login.php?status=1");
            exit();
        }
    }
}

// Check if user is logged in
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * SMTP Email Test Tool - Professional Bulk Email Sender
 * Requires: PHPMailer library in vendor folder
 */

// Load PHPMailer directly from vendor folder
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize response variables
$sent = null;
$error = null;
$debugOutput = '';

/**
 * Safely retrieve and sanitize POST field values
 * @param string $name Field name
 * @param mixed $default Default value if field is not set
 * @return string Sanitized field value
 */
function field($name, $default = '') {
    return isset($_POST[$name]) ? trim($_POST[$name]) : $default;
}

/**
 * Validate email address format
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle AJAX requests for Process Manager
if (isset($_GET['action']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    require_once __DIR__ . '/db.php';
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_batches') {
        try {
            $batches = getAllBatches();
            echo json_encode(['success' => true, 'batches' => $batches]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }
}

// Handle POST request for deleting batch
if (isset($_GET['action']) && $_GET['action'] === 'delete_batch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/db.php';
    header('Content-Type: application/json');
    
    $batchId = $_POST['batch_id'] ?? null;
    
    if (!$batchId) {
        echo json_encode(['success' => false, 'error' => 'Batch ID is required']);
        exit();
    }
    
    try {
        deleteBatchById($batchId);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_send'])) {
    require __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/db.php';

    // Retrieve form data
    $host     = field('host');
    $port     = (int) field('port');
    $security = field('security', 'auto');
    $user     = field('username');
    $pass     = field('password');
    $from     = field('from_email');
    $fromName = field('from_name', 'SMTP Test');
    $to       = field('to_email');
    $subject  = field('subject', 'SMTP Test from PHP');
    $message  = field('message', "Hello,\n\nThis is a test email sent via SMTP.\n\nTime: " . date('Y-m-d H:i:s'));
    $isHtml   = isset($_POST['html_email']);
    $includeEmailInSubject = isset($_POST['include_email_in_subject']);

    // Enhanced validation
    $errors = [];
    
    if (empty($host)) {
        $errors[] = 'SMTP server is required.';
    }
    
    if ($port <= 0 || $port > 65535) {
        $errors[] = 'Port must be between 1 and 65535.';
    }
    
    if (empty($from)) {
        $errors[] = 'From email address is required.';
    } elseif (!isValidEmail($from)) {
        $errors[] = 'From email address is invalid.';
    }
    
    // Check if bulk email or single email
    $isBulkEmail = !empty($_FILES['email_file']['name']);
    
    if (!$isBulkEmail) {
        if (empty($to)) {
            $errors[] = 'To email address is required (or upload a file with email addresses).';
        } elseif (!isValidEmail($to)) {
            $errors[] = 'To email address is invalid.';
        }
    }
    
    if (empty($subject)) {
        $errors[] = 'Email subject is required.';
    }
    
    if (empty($message)) {
        $errors[] = 'Email message is required.';
    }

    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    } else {
        // If it's a single email, send it immediately
        if (!$isBulkEmail) {
            try {
                $mail = new PHPMailer(true);

                // Server settings
                $mail->isSMTP();
                $mail->Host       = $host;
                $mail->Port       = $port;
                $mail->SMTPAuth   = (!empty($user) || !empty($pass));
                $mail->Username   = $user;
                $mail->Password   = $pass;
                $mail->CharSet    = 'UTF-8';
                $mail->Timeout    = 30;

                // Encryption handling
                $security = strtolower($security);
                if ($security === 'auto') {
                    if ($port === 465) {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    } elseif ($port === 587 || $port === 25) {
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
                $mail->setFrom($from, $fromName);
                $mail->addAddress($to);
                $mail->addReplyTo($from, $fromName);

                // Content
                $mail->isHTML($isHtml);
                
                // Append email ID to subject if checkbox is checked
                $finalSubject = $subject;
                if ($includeEmailInSubject && !empty($to)) {
                    $emailId = explode('@', $to)[0]; // Get part before '@'
                    $finalSubject = $subject . ' ' . $emailId;
                }
                $mail->Subject = $finalSubject;
                
                if ($isHtml) {
                    $mail->Body = nl2br(htmlspecialchars($message));
                    $mail->AltBody = strip_tags($message);
                } else {
                    $mail->Body = $message;
                }

                // Debug output handling
                if (isset($_POST['debug'])) {
                    ob_start();
                    $mail->SMTPDebug = 2;
                    $mail->Debugoutput = function ($str, $level) {
                        echo htmlspecialchars($str) . "\n";
                    };
                }

                // Send the email
                $mail->send();
                $sent = true;
                
                // Capture debug output
                if (isset($_POST['debug'])) {
                    $debugOutput = ob_get_clean();
                }
                
            } catch (Exception $e) {
                if (isset($_POST['debug'])) {
                    $debugOutput = ob_get_clean();
                }
                
                $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
                $error = 'Failed to send email: ' . htmlspecialchars($errorMsg);
            }
        } else {
            // Process uploaded file
            $uploadedFile = $_FILES['email_file']['tmp_name'];
            $emails = file($uploadedFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            // Validate and clean emails
            $validEmails = [];
            foreach ($emails as $email) {
                $email = trim($email);
                if (isValidEmail($email)) {
                    $validEmails[] = $email;
                }
            }
            
            if (empty($validEmails)) {
                $error = 'No valid email addresses found in the uploaded file.';
            } else {
                try {
                    // Create batch configuration
                    $batchConfig = [
                        'host' => $host,
                        'port' => $port,
                        'security' => $security,
                        'username' => $user,
                        'password' => $pass,
                        'from_email' => $from,
                        'from_name' => $fromName,
                        'subject' => $subject,
                        'message' => $message,
                        'is_html' => $isHtml,
                        'debug' => isset($_POST['debug']),
                        'delay' => (int) field('email_delay', 1),
                        'total_emails' => count($validEmails),
                        'include_email_in_subject' => $includeEmailInSubject
                    ];
                    
                    // Create batch in database
                    $batchId = createEmailBatch($batchConfig);
                    
                    // Add all emails to queue
                    foreach ($validEmails as $email) {
                        addEmailToQueue($batchId, $email, $batchConfig);
                    }
                    
                    // Update batch status to processing
                    updateBatchStatus($batchId, 'processing');
                    
                    // Redirect to process.php
                    header("Location: process.php?batch=" . urlencode($batchId));
                    exit();
                    
                } catch (Exception $e) {
                    $error = 'Database error: ' . htmlspecialchars($e->getMessage());
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>SMTP Bulk Email Sender - Professional Testing Interface</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 40px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 32px;
            margin: 0 0 10px;
            font-weight: 700;
        }

        .header p {
            margin: 0;
            opacity: 0.95;
            font-size: 16px;
        }

        .header-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 12px;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .process-manager-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .process-manager-btn:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .content {
            padding: 40px;
        }

        .notice {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notice.success {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
        }

        .notice.error {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }

        .notice-icon {
            font-size: 24px;
        }

        .form-section {
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            margin: 0 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
        }

        label .required {
            color: #ef4444;
            margin-left: 2px;
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        input[type="password"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s ease;
            background: #ffffff;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="number"]:focus,
        input[type="password"]:focus,
        input[type="file"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        small {
            display: block;
            margin-top: 6px;
            color: #6b7280;
            font-size: 13px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 32px;
            flex-wrap: wrap;
        }

        button {
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        button[type="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
            min-width: 200px;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        button[type="submit"]:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        button[type="button"] {
            background: #f3f4f6;
            color: #374151;
        }

        button[type="button"]:hover {
            background: #e5e7eb;
        }

        .debug-output {
            background: #1f2937;
            color: #10b981;
            padding: 20px;
            border-radius: 8px;
            margin-top: 24px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 400px;
            overflow-y: auto;
            border: 2px solid #374151;
        }

        .debug-title {
            color: #10b981;
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .tips {
            background: #f0f9ff;
            border: 2px solid #3b82f6;
            border-radius: 10px;
            padding: 20px;
            margin-top: 32px;
        }

        .tips-title {
            font-weight: 700;
            color: #1e40af;
            margin: 0 0 12px;
            font-size: 16px;
        }

        .tips ul {
            margin: 0;
            padding-left: 20px;
            color: #1e40af;
        }

        .tips li {
            margin-bottom: 8px;
        }

        .bulk-email-toggle {
            background: #f0f9ff;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .toggle-label {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
            display: block;
        }

        @media (max-width: 768px) {
            .content {
                padding: 24px;
            }

            .header {
                padding: 24px;
            }

            .header h1 {
                font-size: 24px;
            }

            .logout-btn {
                position: static;
                display: block;
                margin-top: 16px;
                text-align: center;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            button[type="submit"] {
                width: 100%;
            }

            .header-buttons {
                position: static;
                margin-top: 16px;
                justify-content: center;
            }

            .logout-btn,
            .process-manager-btn {
                flex: 1;
                max-width: 200px;
                justify-content: center;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal.active {
            display: block;
        }

        .modal-content {
            background-color: #ffffff;
            margin: 3% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 1200px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px 30px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .close-modal {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            line-height: 1;
            padding: 0;
        }

        .close-modal:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .close-modal:active {
            transform: rotate(90deg) scale(0.95);
        }

        .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .batch-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .batch-table th {
            background: #f3f4f6;
            color: #374151;
            font-weight: 700;
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
            font-size: 14px;
            text-transform: uppercase;
        }

        .batch-table td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .batch-table tr:hover {
            background: #f9fafb;
        }

        .batch-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .batch-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .batch-status.processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .batch-status.paused {
            background: #fed7aa;
            color: #9a3412;
        }

        .batch-status.completed {
            background: #d1fae5;
            color: #065f46;
        }

        .batch-status.cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .batch-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .batch-actions a {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-view:active {
            transform: translateY(0);
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            border: 2px solid #dc2626;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .btn-delete:active {
            transform: translateY(0);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state-title {
            font-size: 20px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
        }

        .empty-state-text {
            font-size: 14px;
        }

        .batch-progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 4px;
        }

        .batch-progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #6b7280;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .filter-tab:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-buttons">
                <button onclick="openProcessManager()" class="process-manager-btn">📊 Process Manager</button>
                <a href="?logout=1" class="logout-btn">🚪 Logout</a>
            </div>
            <h1>📧 SMTP Bulk Email Sender</h1>
            <p>Professional SMTP server testing and bulk email delivery system</p>
        </div>

        <div class="content">
            <?php if ($sent): ?>
            <div class="notice success">
                <span class="notice-icon">✅</span>
                <div>
                    <strong>Success!</strong> Email sent successfully to <?= htmlspecialchars(field('to_email')) ?>
                </div>
            </div>
            <?php elseif ($error): ?>
            <div class="notice error">
                <span class="notice-icon">❌</span>
                <div>
                    <strong>Error:</strong> <?= $error ?>
                </div>
            </div>
            <?php endif; ?>

            <form method="post" autocomplete="off" enctype="multipart/form-data" id="emailForm">
                <!-- SMTP Server Configuration -->
                <div class="form-section">
                    <h2 class="section-title">🔧 SMTP Server Configuration</h2>
                    <div class="grid">
                        <div class="form-group">
                            <label for="host">SMTP Server <span class="required">*</span></label>
                            <input id="host" name="host" type="text" placeholder="smtp.example.com" 
                                   value="<?= htmlspecialchars(field('host')) ?>" required>
                            <small>Your SMTP server hostname or IP address</small>
                        </div>
                        <div class="form-group">
                            <label for="port">Port <span class="required">*</span></label>
                            <input id="port" name="port" type="number" placeholder="587" min="1" max="65535"
                                   value="<?= htmlspecialchars(field('port', '587')) ?>" required>
                            <small>Common: 25, 465 (SSL), 587 (TLS)</small>
                        </div>
                        <div class="form-group">
                            <label for="security">Security Protocol</label>
                            <select id="security" name="security">
                                <?php
                                $securityOptions = [
                                    'auto' => '🔄 Auto (Recommended)',
                                    'tls' => '🔒 STARTTLS (Port 587)',
                                    'ssl' => '🔐 SSL/TLS (Port 465)',
                                    'none' => '⚠️ None (Not Secure)'
                                ];
                                $selectedSecurity = field('security', 'auto');
                                foreach ($securityOptions as $value => $label) {
                                    $selected = $selectedSecurity === $value ? 'selected' : '';
                                    echo "<option value='$value' $selected>$label</option>";
                                }
                                ?>
                            </select>
                            <small>Auto detects based on port number</small>
                        </div>
                    </div>
                </div>

                <!-- Authentication -->
                <div class="form-section">
                    <h2 class="section-title">🔑 Authentication</h2>
                    <div class="grid">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input id="username" name="username" type="text" placeholder="user@example.com"
                                   value="<?= htmlspecialchars(field('username')) ?>">
                            <small>Leave blank if authentication is not required</small>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input id="password" name="password" type="password" placeholder="••••••••"
                                   value="<?= htmlspecialchars(field('password')) ?>">
                            <small>Your SMTP password or app-specific password</small>
                        </div>
                    </div>
                </div>

                <!-- Email Details -->
                <div class="form-section">
                    <h2 class="section-title">✉️ Email Details</h2>
                    <div class="grid">
                        <div class="form-group">
                            <label for="from_email">From Email <span class="required">*</span></label>
                            <input id="from_email" name="from_email" type="email" placeholder="sender@example.com"
                                   value="<?= htmlspecialchars(field('from_email')) ?>" required>
                            <small>Sender's email address</small>
                        </div>
                        <div class="form-group">
                            <label for="from_name">From Name</label>
                            <input id="from_name" name="from_name" type="text" placeholder="John Doe"
                                   value="<?= htmlspecialchars(field('from_name', 'SMTP Test')) ?>">
                            <small>Sender's display name</small>
                        </div>
                    </div>
                    
                    <!-- Recipient Options -->
                    <div class="bulk-email-toggle">
                        <label class="toggle-label">📬 Recipient Options</label>
                        <div class="checkbox-group">
                            <input type="radio" id="single_email" name="email_mode" value="single" checked onchange="toggleEmailMode()">
                            <label for="single_email">Single Email</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="radio" id="bulk_email" name="email_mode" value="bulk" onchange="toggleEmailMode()">
                            <label for="bulk_email">Bulk Email (Upload File)</label>
                        </div>
                    </div>

                    <div id="singleEmailSection">
                        <div class="form-group">
                            <label for="to_email">To Email <span class="required">*</span></label>
                            <input id="to_email" name="to_email" type="email" placeholder="recipient@example.com"
                                   value="<?= htmlspecialchars(field('to_email')) ?>">
                            <small>Recipient's email address</small>
                        </div>
                    </div>

                    <div id="bulkEmailSection" style="display: none;">
                        <div class="form-group">
                            <label for="email_file">Email List File <span class="required">*</span></label>
                            <input id="email_file" name="email_file" type="file" accept=".txt">
                            <small>Upload a text file with one email address per line</small>
                        </div>
                        <div class="form-group">
                            <label for="email_delay">Delay Between Emails (seconds)</label>
                            <input id="email_delay" name="email_delay" type="number" min="0" max="60" 
                                   value="<?= htmlspecialchars(field('email_delay', '1')) ?>" placeholder="1">
                            <small>Pause duration between each email (0-60 seconds)</small>
                        </div>
                    </div>

                    <div class="grid">
                        <div class="form-group">
                            <label for="subject">Subject <span class="required">*</span></label>
                            <input id="subject" name="subject" type="text" placeholder="Test Email"
                                   value="<?= htmlspecialchars(field('subject', 'SMTP Test from PHP')) ?>" required>
                            <small>Email subject line</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="message">Message <span class="required">*</span></label>
                        <textarea id="message" name="message" placeholder="Enter your test message here..." required><?= htmlspecialchars(field('message', "Hello,\n\nThis is a test email sent via SMTP.\n\nTime: " . date('Y-m-d H:i:s'))) ?></textarea>
                        <small>The content of your test email</small>
                    </div>
                </div>

                <!-- Options -->
                <div class="form-section">
                    <h2 class="section-title">⚙️ Options</h2>
                    <div class="checkbox-group">
                        <input type="checkbox" id="html_email" name="html_email" <?= isset($_POST['html_email']) ? 'checked' : '' ?>>
                        <label for="html_email">Send as HTML email</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="debug" name="debug" <?= isset($_POST['debug']) ? 'checked' : '' ?>>
                        <label for="debug">Show debug output (SMTP conversation)</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="include_email_in_subject" name="include_email_in_subject" <?= isset($_POST['include_email_in_subject']) ? 'checked' : '' ?>>
                        <label for="include_email_in_subject">Include recipient email ID in subject (e.g., "Subject santosh19")</label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="button-group">
                    <button type="submit" id="submitBtn">🚀 Send Email(s)</button>
                    <button type="button" onclick="resetForm()">🔄 Reset Form</button>
                </div>
            </form>

            <?php if (!empty($debugOutput)): ?>
            <div class="debug-output">
                <div class="debug-title">📊 Debug Output</div>
                <?= $debugOutput ?>
            </div>
            <?php endif; ?>

            <!-- Tips Section -->
            <div class="tips">
                <h3 class="tips-title">💡 Quick Tips</h3>
                <ul>
                    <li><strong>Gmail/Google Workspace:</strong> Use port 587 with STARTTLS. You'll need an <a href="https://support.google.com/accounts/answer/185833" target="_blank">App Password</a> instead of your regular password.</li>
                    <li><strong>Office 365/Outlook:</strong> Use smtp.office365.com on port 587 with STARTTLS.</li>
                    <li><strong>Bulk Sending:</strong> Upload a .txt file with one email per line. Set appropriate delays to avoid rate limiting.</li>
                    <li><strong>Common Ports:</strong> 25 (unencrypted), 465 (SSL/TLS), 587 (STARTTLS - recommended)</li>
                    <li><strong>Security:</strong> Always use encryption (SSL/TLS or STARTTLS) when possible.</li>
                    <li><strong>Troubleshooting:</strong> Enable debug output to see the full SMTP conversation and identify connection issues.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Process Manager Modal -->
    <div id="processManagerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📊 Process Manager</h2>
                <button class="close-modal" onclick="closeProcessManager()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterBatches('all')">All</button>
                    <button class="filter-tab" onclick="filterBatches('pending')">Pending</button>
                    <button class="filter-tab" onclick="filterBatches('processing')">Processing</button>
                    <button class="filter-tab" onclick="filterBatches('paused')">Paused</button>
                    <button class="filter-tab" onclick="filterBatches('completed')">Completed</button>
                    <button class="filter-tab" onclick="filterBatches('cancelled')">Cancelled</button>
                </div>
                <div id="batchListContainer">
                    <div class="empty-state">
                        <div class="empty-state-icon">⏳</div>
                        <div class="empty-state-title">Loading batches...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentFilter = 'all';
        let allBatches = [];

        function toggleEmailMode() {
            const isBulk = document.getElementById('bulk_email').checked;
            document.getElementById('singleEmailSection').style.display = isBulk ? 'none' : 'block';
            document.getElementById('bulkEmailSection').style.display = isBulk ? 'block' : 'none';
            
            // Update required attribute
            document.getElementById('to_email').required = !isBulk;
            document.getElementById('email_file').required = isBulk;
        }

        function resetForm() {
            document.getElementById('emailForm').reset();
            toggleEmailMode();
        }

        // Process Manager Functions
        function openProcessManager() {
            document.getElementById('processManagerModal').classList.add('active');
            loadBatches();
        }

        function closeProcessManager() {
            document.getElementById('processManagerModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('processManagerModal');
            if (event.target === modal) {
                closeProcessManager();
            }
        }

        async function loadBatches() {
            try {
                const response = await fetch('test-mail.php?action=get_batches', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                allBatches = data.batches || [];
                renderBatches();
            } catch (error) {
                console.error('Error loading batches:', error);
                document.getElementById('batchListContainer').innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">❌</div>
                        <div class="empty-state-title">Error Loading Batches</div>
                        <div class="empty-state-text">${error.message}</div>
                    </div>
                `;
            }
        }

        function filterBatches(status) {
            currentFilter = status;
            
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            renderBatches();
        }

        function renderBatches() {
            const container = document.getElementById('batchListContainer');
            
            // Filter batches based on current filter
            let filteredBatches = allBatches;
            if (currentFilter !== 'all') {
                filteredBatches = allBatches.filter(batch => batch.status === currentFilter);
            }
            
            if (filteredBatches.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <div class="empty-state-title">No Batches Found</div>
                        <div class="empty-state-text">No email batches match the current filter.</div>
                    </div>
                `;
                return;
            }
            
            let html = `
                <table class="batch-table">
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Total</th>
                            <th>Sent</th>
                            <th>Failed</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            filteredBatches.forEach(batch => {
                const totalEmails = parseInt(batch.total_emails) || 0;
                const sentCount = parseInt(batch.sent_count) || 0;
                const failedCount = parseInt(batch.failed_count) || 0;
                const progress = totalEmails > 0 
                    ? Math.min(100, Math.round(((sentCount + failedCount) / totalEmails) * 100))
                    : 0;
                
                const createdDate = new Date(batch.created_at).toLocaleString();
                
                html += `
                    <tr>
                        <td><code>${batch.batch_id}</code></td>
                        <td><span class="batch-status ${batch.status}">${batch.status}</span></td>
                        <td>
                            <div><strong>${progress}%</strong></div>
                            <div class="batch-progress-bar">
                                <div class="batch-progress-fill" style="width: ${progress}%"></div>
                            </div>
                        </td>
                        <td><strong>${totalEmails}</strong></td>
                        <td><span style="color: #10b981; font-weight: 600;">${sentCount}</span></td>
                        <td><span style="color: #ef4444; font-weight: 600;">${failedCount}</span></td>
                        <td>${createdDate}</td>
                        <td>
                            <div class="batch-actions">
                                <a href="process.php?batch=${encodeURIComponent(batch.batch_id)}" class="btn-view" target="_blank">
                                    👁 View
                                </a>
                                <a href="#" onclick="deleteBatch('${batch.batch_id}'); return false;" class="btn-delete">
                                    🗑 Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
            
            container.innerHTML = html;
        }

        async function deleteBatch(batchId) {
            if (!confirm('Are you sure you want to delete this batch? This action cannot be undone.')) {
                return;
            }
            
            try {
                const response = await fetch('test-mail.php?action=delete_batch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'batch_id=' + encodeURIComponent(batchId)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Reload batches
                    loadBatches();
                } else {
                    alert('Error deleting batch: ' + data.error);
                }
            } catch (error) {
                alert('Error deleting batch: ' + error.message);
            }
        }

        // Auto-refresh batches every 5 seconds when modal is open
        setInterval(() => {
            const modal = document.getElementById('processManagerModal');
            if (modal.classList.contains('active')) {
                loadBatches();
            }
        }, 5000);
    </script>
</body>

</html>
