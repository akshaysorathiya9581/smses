<?php
/**
 * Cronjob File for Sending Pending Emails
 * 
 * This is a standalone file that can be accessed via URL for Hostinger cronjob setup.
 * URL: https://yourdomain.com/send-pending-emails-cron.php
 * 
 * Cronjob command for Hostinger:
 * curl -s https://yourdomain.com/send-pending-emails-cron.php > /dev/null
 * OR
 * wget -q -O - https://yourdomain.com/send-pending-emails-cron.php > /dev/null
 */

// Set execution time limit (30 minutes)
set_time_limit(1800);
ini_set('max_execution_time', 1800);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Database configuration - UPDATE THESE VALUES FOR YOUR HOSTINGER SERVER
define('DB_HOST', 'localhost');
define('DB_NAME', 'smses_send');
define('DB_USER', 'smses_senduser');
define('DB_PASS', 'user007');
define('DB_CHARSET', 'utf8mb4');

// Path to PHPMailer - adjust if needed
$phpmailerPath = __DIR__ . '/vendor/phpmailer/phpmailer/src/';

// Load PHPMailer
if (file_exists($phpmailerPath . 'Exception.php')) {
    require_once $phpmailerPath . 'Exception.php';
    require_once $phpmailerPath . 'PHPMailer.php';
    require_once $phpmailerPath . 'SMTP.php';
} else {
    die("Error: PHPMailer not found. Please ensure vendor/phpmailer/phpmailer is installed.\n");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Get database connection
 */
function getDbConnection() {
    static $mysqli = null;
    
    if ($mysqli === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        try {
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $mysqli->set_charset(DB_CHARSET);
        } catch (Exception $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    return $mysqli;
}

/**
 * Get pending emails from all batches
 */
function getPendingEmails() {
    $mysqli = getDbConnection();
    
    // Try to include include_email_in_subject column if it exists
    $columnExists = false;
    try {
        $checkSql = "SHOW COLUMNS FROM email_batches LIKE 'include_email_in_subject'";
        $checkResult = $mysqli->query($checkSql);
        $columnExists = $checkResult && $checkResult->num_rows > 0;
    } catch (Exception $e) {
        $columnExists = false;
    }
    
    $includeEmailColumn = $columnExists ? ', eb.include_email_in_subject' : '';
    
    $sql = "SELECT eq.*, eb.smtp_host, eb.smtp_port, eb.smtp_security, eb.smtp_username, eb.smtp_password,
                   eb.from_email, eb.from_name, eb.subject, eb.message as batch_message, eb.is_html, eb.debug_mode" . $includeEmailColumn . "
            FROM email_queue eq
            INNER JOIN email_batches eb ON eq.batch_id = eb.batch_id
            WHERE eq.status = 'pending'
            AND eb.status IN ('pending', 'processing')
            ORDER BY eq.id ASC
            LIMIT 50";
    
    $result = $mysqli->query($sql);
    
    $emails = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $emails[] = $row;
        }
    }
    
    return $emails;
}

/**
 * Update email status
 */
function updateEmailStatus($emailId, $status, $errorMessage = null) {
    $mysqli = getDbConnection();
    
    $sql = "UPDATE email_queue 
            SET status = ?, 
                error_message = ?,
                attempts = attempts + 1";
    
    if ($status === 'sent') {
        $sql .= ", sent_at = NOW()";
    }
    
    $sql .= " WHERE id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssi', $status, $errorMessage, $emailId);
    $stmt->execute();
    $stmt->close();
}

/**
 * Update batch counts
 */
function updateBatchCounts($batchId) {
    $mysqli = getDbConnection();
    
    // Get counts - use COALESCE to convert NULL to 0
    // Count only 'sent' and 'failed' statuses, exclude 'processing' and 'pending'
    $sql = "SELECT 
                COALESCE(SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END), 0) as sent_count,
                COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as failed_count
            FROM email_queue 
            WHERE batch_id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $batchId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $counts = $result->fetch_assoc();
    $stmt->close();
    
    // Ensure counts are integers (not NULL)
    $sentCount = (int)($counts['sent_count'] ?? 0);
    $failedCount = (int)($counts['failed_count'] ?? 0);
    
    // Update batch - ensure we're updating even if counts are 0
    $sql = "UPDATE email_batches 
            SET sent_count = ?, 
                failed_count = ? 
            WHERE batch_id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iis', $sentCount, $failedCount, $batchId);
    $stmt->execute();
    
    // Check if update was successful
    if ($stmt->affected_rows === 0) {
        error_log("Warning: updateBatchCounts did not update any rows for batch_id: " . $batchId);
    }
    
    $stmt->close();
}

/**
 * Check if batch is completed
 */
function checkAndUpdateBatchStatus($batchId) {
    $mysqli = getDbConnection();
    
    // Check if there are any pending emails for this batch
    $sql = "SELECT COUNT(*) as pending_count FROM email_queue 
            WHERE batch_id = ? AND status = 'pending'";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $batchId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    // If no pending emails, mark batch as completed
    if ($data['pending_count'] == 0) {
        $sql = "UPDATE email_batches 
                SET status = 'completed', completed_at = NOW() 
                WHERE batch_id = ? AND status IN ('pending', 'processing')";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $batchId);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Send email using PHPMailer
 */
function sendEmail($emailData) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $emailData['smtp_host'];
        $mail->Port       = $emailData['smtp_port'];
        $mail->SMTPAuth   = (!empty($emailData['smtp_username']) || !empty($emailData['smtp_password']));
        $mail->Username   = $emailData['smtp_username'];
        $mail->Password   = $emailData['smtp_password'];
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 30;
        
        // Encryption handling
        $security = strtolower($emailData['smtp_security']);
        if ($security === 'auto') {
            if ($emailData['smtp_port'] == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($emailData['smtp_port'] == 587 || $emailData['smtp_port'] == 25) {
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
        $mail->setFrom($emailData['from_email'], $emailData['from_name']);
        $mail->addAddress($emailData['recipient_email']);
        $mail->addReplyTo($emailData['from_email'], $emailData['from_name']);
        
        // Content
        $isHtml = (int)$emailData['is_html'] === 1;
        $mail->isHTML($isHtml);
        
        // Append email ID to subject if the option is enabled
        $finalSubject = $emailData['subject'];
        $includeEmailInSubject = isset($emailData['include_email_in_subject']) && (int)$emailData['include_email_in_subject'] === 1;
        if ($includeEmailInSubject && !empty($emailData['recipient_email'])) {
            $emailId = explode('@', $emailData['recipient_email'])[0]; // Get part before '@'
            $finalSubject = $emailData['subject'] . ' ' . $emailId;
        }
        $mail->Subject = $finalSubject;
        
        // Use message from email_queue if available, otherwise use batch message
        $message = !empty($emailData['message']) ? $emailData['message'] : $emailData['batch_message'];
        
        if ($isHtml) {
            $mail->Body = nl2br(htmlspecialchars($message));
            $mail->AltBody = strip_tags($message);
        } else {
            $mail->Body = $message;
        }
        
        // Debug output handling
        if (!empty($emailData['debug_mode']) && (int)$emailData['debug_mode'] === 1) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function ($str, $level) {
                error_log($str);
            };
        } else {
            $mail->SMTPDebug = 0;
        }
        
        // Send the email
        $mail->send();
        
        return ['success' => true, 'message' => 'Email sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}

/**
 * Main execution
 */
function processPendingEmails() {
    $results = [
        'processed' => 0,
        'sent' => 0,
        'failed' => 0,
        'errors' => []
    ];
    
    try {
        // Get pending emails
        $pendingEmails = getPendingEmails();
        
        if (empty($pendingEmails)) {
            return [
                'success' => true,
                'message' => 'No pending emails found.',
                'results' => $results
            ];
        }
        
        $results['processed'] = count($pendingEmails);
        $processedBatches = [];
        
        // Process each email
        foreach ($pendingEmails as $email) {
            // Mark as processing
            updateEmailStatus($email['id'], 'processing');
            
            // Send email
            $sendResult = sendEmail($email);
            
            if ($sendResult['success']) {
                // Update status to sent
                updateEmailStatus($email['id'], 'sent');
                $results['sent']++;
            } else {
                // Update status to failed
                updateEmailStatus($email['id'], 'failed', $sendResult['message']);
                $results['failed']++;
                $results['errors'][] = [
                    'email' => $email['recipient_email'],
                    'error' => $sendResult['message']
                ];
            }
            
            // Update batch counts immediately after each email status update
            updateBatchCounts($email['batch_id']);
            
            // Track batch for final status check
            if (!in_array($email['batch_id'], $processedBatches)) {
                $processedBatches[] = $email['batch_id'];
            }
            
            // Small delay to avoid overwhelming SMTP server
            usleep(200000); // 0.2 seconds
        }
        
        // Final batch status check for all processed batches
        foreach ($processedBatches as $batchId) {
            checkAndUpdateBatchStatus($batchId);
            // Ensure counts are up to date one final time
            updateBatchCounts($batchId);
        }
        
        return [
            'success' => true,
            'message' => 'Processing completed.',
            'results' => $results
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error processing emails: ' . $e->getMessage(),
            'results' => $results
        ];
    }
}

// Start processing
$startTime = microtime(true);
$output = [];

// Check if running from command line or web
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // Set content type for web access
    header('Content-Type: text/plain; charset=utf-8');
}

$output[] = "==========================================";
$output[] = "Pending Emails Cronjob - " . date('Y-m-d H:i:s');
$output[] = "==========================================";
$output[] = "";

try {
    $result = processPendingEmails();
    
    $output[] = "Status: " . ($result['success'] ? 'SUCCESS' : 'FAILED');
    $output[] = "Message: " . $result['message'];
    $output[] = "";
    $output[] = "Results:";
    $output[] = "  - Processed: " . $result['results']['processed'];
    $output[] = "  - Sent: " . $result['results']['sent'];
    $output[] = "  - Failed: " . $result['results']['failed'];
    
    if (!empty($result['results']['errors'])) {
        $output[] = "";
        $output[] = "Errors:";
        foreach ($result['results']['errors'] as $error) {
            $output[] = "  - " . $error['email'] . ": " . $error['error'];
        }
    }
    
} catch (Exception $e) {
    $output[] = "FATAL ERROR: " . $e->getMessage();
    error_log("Cronjob Error: " . $e->getMessage());
}

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

$output[] = "";
$output[] = "Execution Time: " . $executionTime . " seconds";
$output[] = "==========================================";

// Output results
echo implode("\n", $output);

// Close database connection
if (function_exists('getDbConnection')) {
    try {
        $mysqli = getDbConnection();
        $mysqli->close();
    } catch (Exception $e) {
        // Ignore connection close errors
    }
}

?>

