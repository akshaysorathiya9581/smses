<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load PHPMailer
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

require __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get batch ID from URL
$batchId = $_GET['batch'] ?? null;

if (!$batchId) {
    die('Error: No batch ID provided');
}

// Get batch information
$batch = getBatchInfo($batchId);

if (!$batch) {
    die('Error: Batch not found');
}

// Handle AJAX requests for email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    if ($action === 'get_next_email') {
        // Get next pending email
        $emails = getPendingEmails($batchId);
        
        if (empty($emails)) {
            // No more emails, mark batch as completed
            updateBatchStatus($batchId, 'completed');
            echo json_encode(['done' => true]);
            exit();
        }
        
        $email = $emails[0];
        
        // Mark as processing
        updateEmailStatus($email['id'], 'processing');
        
        echo json_encode([
            'done' => false,
            'email' => $email
        ]);
        exit();
    }
    
    if ($action === 'send_email') {
        $emailId = $_POST['email_id'] ?? null;
        $recipientEmail = $_POST['recipient_email'] ?? null;
        
        if (!$emailId || !$recipientEmail) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            exit();
        }
        
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $batch['smtp_host'];
            $mail->Port       = $batch['smtp_port'];
            $mail->SMTPAuth   = (!empty($batch['smtp_username']) || !empty($batch['smtp_password']));
            $mail->Username   = $batch['smtp_username'];
            $mail->Password   = $batch['smtp_password'];
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 30;
            
            // Encryption handling
            $security = strtolower($batch['smtp_security']);
            if ($security === 'auto') {
                if ($batch['smtp_port'] === 465) {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($batch['smtp_port'] === 587 || $batch['smtp_port'] === 25) {
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
            $mail->setFrom($batch['from_email'], $batch['from_name']);
            $mail->addAddress($recipientEmail);
            $mail->addReplyTo($batch['from_email'], $batch['from_name']);
            
            // Content
            $mail->isHTML($batch['is_html']);
            
            // Append email ID to subject if the option is enabled
            $finalSubject = $batch['subject'];
            $includeEmailInSubject = isset($batch['include_email_in_subject']) && (int)$batch['include_email_in_subject'] === 1;
            if ($includeEmailInSubject && !empty($recipientEmail)) {
                $emailId = explode('@', $recipientEmail)[0]; // Get part before '@'
                $finalSubject = $batch['subject'] . ' ' . $emailId;
            }
            $mail->Subject = $finalSubject;
            
            if ($batch['is_html']) {
                $mail->Body = nl2br(htmlspecialchars($batch['message']));
                $mail->AltBody = strip_tags($batch['message']);
            } else {
                $mail->Body = $batch['message'];
            }
            
            // Debug output handling
            if ($batch['debug_mode']) {
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function ($str, $level) {
                    error_log($str);
                };
            }
            
            // Send the email
            $mail->send();
            
            // Update status to sent
            updateEmailStatus($emailId, 'sent');
            updateBatchCounts($batchId);
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
            
            // Update status to failed
            updateEmailStatus($emailId, 'failed', $errorMsg);
            updateBatchCounts($batchId);
            
            echo json_encode(['success' => false, 'error' => $errorMsg]);
        }
        
        exit();
    }
    
    if ($action === 'pause') {
        pauseBatch($batchId);
        echo json_encode(['success' => true]);
        exit();
    }
    
    if ($action === 'resume') {
        resumeBatch($batchId);
        echo json_encode(['success' => true]);
        exit();
    }
    
    if ($action === 'cancel') {
        cancelBatch($batchId);
        echo json_encode(['success' => true]);
        exit();
    }
    
    if ($action === 'get_progress') {
        $progress = getBatchProgress($batchId);
        echo json_encode($progress);
        exit();
    }
}

// Get initial progress
$progress = getBatchProgress($batchId);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Processing Bulk Emails - SMTP Sender</title>
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

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .content {
            padding: 40px;
        }

        .progress-section {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 24px;
        }

        .progress-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .progress-title {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            margin: 0 0 8px;
        }

        .progress-subtitle {
            color: #6b7280;
            font-size: 14px;
        }

        .progress-bar-container {
            background: #e5e7eb;
            border-radius: 12px;
            height: 40px;
            overflow: hidden;
            margin-bottom: 20px;
            position: relative;
        }

        .progress-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
        }

        .progress-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 16px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            text-align: center;
        }

        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-value.success {
            color: #10b981;
        }

        .stat-value.error {
            color: #ef4444;
        }

        .current-email {
            background: white;
            padding: 16px;
            border-radius: 8px;
            border: 2px solid #667eea;
            margin-bottom: 20px;
            text-align: center;
        }

        .current-email-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .current-email-value {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            word-break: break-all;
        }

        .progress-log {
            background: #1f2937;
            color: #10b981;
            padding: 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .progress-log-line {
            margin-bottom: 4px;
            padding: 4px 0;
            border-bottom: 1px solid #374151;
        }

        .progress-log-line.error {
            color: #ef4444;
        }

        .progress-log-line.success {
            color: #10b981;
        }

        .control-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
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

        .btn-pause {
            background: #f59e0b;
            color: white;
        }

        .btn-pause:hover {
            background: #d97706;
            transform: translateY(-2px);
        }

        .btn-resume {
            background: #10b981;
            color: white;
        }

        .btn-resume:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #ef4444;
            color: white;
        }

        .btn-cancel:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .btn-back {
            background: #6b7280;
            color: white;
        }

        .btn-back:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        .completion-message {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            font-size: 18px;
            display: none;
            margin-bottom: 20px;
        }

        .completion-message.active {
            display: block;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .status-badge.processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.paused {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .processing-indicator {
            animation: pulse 1.5s ease-in-out infinite;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <a href="test-mail.php" class="back-btn">← Back</a>
            <h1>📨 Processing Bulk Emails</h1>
            <p>Batch ID: <?= htmlspecialchars($batchId) ?></p>
        </div>

        <div class="content">
            <div class="progress-section">
                <div class="progress-header">
                    <div class="status-badge <?= strtolower($batch['status']) ?>" id="statusBadge">
                        <?= strtoupper($batch['status']) ?>
                    </div>
                    <h2 class="progress-title" id="progressTitle">
                        <?php if ($batch['status'] === 'completed'): ?>
                            ✅ Processing Complete
                        <?php elseif ($batch['status'] === 'paused'): ?>
                            ⏸️ Processing Paused
                        <?php elseif ($batch['status'] === 'cancelled'): ?>
                            ❌ Processing Cancelled
                        <?php else: ?>
                            <span class="processing-indicator">📧 Sending Emails...</span>
                        <?php endif; ?>
                    </h2>
                    <p class="progress-subtitle">Batch started at <?= date('Y-m-d H:i:s', strtotime($batch['created_at'])) ?></p>
                </div>

                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar">0%</div>
                </div>

                <div class="current-email" id="currentEmailDisplay">
                    <div class="current-email-label">Currently Sending To:</div>
                    <div class="current-email-value" id="currentEmail">-</div>
                </div>

                <div class="progress-stats">
                    <div class="stat-card">
                        <div class="stat-label">Progress</div>
                        <div class="stat-value" id="progressCount">0 / <?= $batch['total_emails'] ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Successful</div>
                        <div class="stat-value success" id="successCount"><?= $batch['sent_count'] ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Failed</div>
                        <div class="stat-value error" id="failedCount"><?= $batch['failed_count'] ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Pending</div>
                        <div class="stat-value" id="pendingCount"><?= $progress['pending_count'] ?></div>
                    </div>
                </div>

                <div class="progress-log" id="progressLog"></div>

                <div class="completion-message" id="completionMessage">
                    🎉 All emails have been processed successfully!
                </div>

                <div class="control-buttons">
                    <button class="btn-pause" id="pauseBtn" onclick="pauseProcessing()">⏸️ Pause</button>
                    <button class="btn-resume" id="resumeBtn" onclick="resumeProcessing()" style="display: none;">▶️ Resume</button>
                    <button class="btn-cancel" id="cancelBtn" onclick="cancelProcessing()">❌ Cancel</button>
                    <button class="btn-back" onclick="window.location.href='test-mail.php'">🏠 Back to Home</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const batchId = <?= json_encode($batchId) ?>;
        const totalEmails = <?= $batch['total_emails'] ?>;
        const emailDelay = <?= $batch['email_delay'] ?> * 1000; // Convert to milliseconds
        let isPaused = <?= $batch['status'] === 'paused' ? 'true' : 'false' ?>;
        let isCancelled = <?= $batch['status'] === 'cancelled' ? 'true' : 'false' ?>;
        let isCompleted = <?= $batch['status'] === 'completed' ? 'true' : 'false' ?>;
        let currentCount = <?= $batch['sent_count'] + $batch['failed_count'] ?>;

        // Initialize UI
        updateProgress();

        // Start processing if status is pending or processing
        if (<?= in_array($batch['status'], ['pending', 'processing']) ? 'true' : 'false' ?>) {
            startProcessing();
        } else if (isCompleted) {
            showCompletion();
        } else if (isCancelled) {
            addLogLine('❌ Batch processing was cancelled', 'error');
        } else if (isPaused) {
            addLogLine('⏸️ Batch processing is paused. Click Resume to continue.', '');
        }

        async function startProcessing() {
            isPaused = false;
            updateButtons();

            while (!isPaused && !isCancelled && !isCompleted) {
                try {
                    // Get next email
                    const nextResponse = await fetch('process.php?batch=' + batchId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=get_next_email'
                    });

                    const nextData = await nextResponse.json();

                    if (nextData.done) {
                        isCompleted = true;
                        showCompletion();
                        break;
                    }

                    const email = nextData.email;
                    document.getElementById('currentEmail').textContent = email.recipient_email;

                    // Send email
                    const sendResponse = await fetch('process.php?batch=' + batchId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=send_email&email_id=' + email.id + '&recipient_email=' + encodeURIComponent(email.recipient_email)
                    });

                    const sendData = await sendResponse.json();

                    if (sendData.success) {
                        addLogLine('✅ Sent to: ' + email.recipient_email, 'success');
                    } else {
                        addLogLine('❌ Failed: ' + email.recipient_email + ' - ' + sendData.error, 'error');
                    }

                    // Update progress
                    await updateProgress();

                    // Delay before next email
                    if (emailDelay > 0 && !isPaused && !isCancelled) {
                        await sleep(emailDelay);
                    }

                } catch (error) {
                    addLogLine('❌ Error: ' + error.message, 'error');
                    await sleep(2000); // Wait 2 seconds before retrying
                }
            }
        }

        async function updateProgress() {
            try {
                const response = await fetch('process.php?batch=' + batchId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_progress'
                });

                const progress = await response.json();

                const completed = progress.sent_count + progress.failed_count;
                const percentage = Math.round((completed / totalEmails) * 100);

                document.getElementById('progressBar').style.width = percentage + '%';
                document.getElementById('progressBar').textContent = percentage + '%';
                document.getElementById('progressCount').textContent = completed + ' / ' + totalEmails;
                document.getElementById('successCount').textContent = progress.sent_count;
                document.getElementById('failedCount').textContent = progress.failed_count;
                document.getElementById('pendingCount').textContent = progress.pending_count;

                currentCount = completed;

            } catch (error) {
                console.error('Error updating progress:', error);
            }
        }

        async function pauseProcessing() {
            isPaused = true;
            updateButtons();

            try {
                await fetch('process.php?batch=' + batchId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=pause'
                });

                addLogLine('⏸️ Processing paused by user', '');
                document.getElementById('statusBadge').className = 'status-badge paused';
                document.getElementById('statusBadge').textContent = 'PAUSED';
                document.getElementById('progressTitle').innerHTML = '⏸️ Processing Paused';

            } catch (error) {
                addLogLine('❌ Error pausing: ' + error.message, 'error');
            }
        }

        async function resumeProcessing() {
            try {
                await fetch('process.php?batch=' + batchId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=resume'
                });

                addLogLine('▶️ Processing resumed', 'success');
                document.getElementById('statusBadge').className = 'status-badge processing';
                document.getElementById('statusBadge').textContent = 'PROCESSING';
                document.getElementById('progressTitle').innerHTML = '<span class="processing-indicator">📧 Sending Emails...</span>';

                startProcessing();

            } catch (error) {
                addLogLine('❌ Error resuming: ' + error.message, 'error');
            }
        }

        async function cancelProcessing() {
            if (!confirm('Are you sure you want to cancel this batch? All pending emails will be marked as failed.')) {
                return;
            }

            isCancelled = true;
            isPaused = true;
            updateButtons();

            try {
                await fetch('process.php?batch=' + batchId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=cancel'
                });

                addLogLine('❌ Processing cancelled by user', 'error');
                document.getElementById('statusBadge').className = 'status-badge cancelled';
                document.getElementById('statusBadge').textContent = 'CANCELLED';
                document.getElementById('progressTitle').textContent = '❌ Processing Cancelled';
                document.getElementById('currentEmail').textContent = 'Cancelled';

                await updateProgress();

            } catch (error) {
                addLogLine('❌ Error cancelling: ' + error.message, 'error');
            }
        }

        function updateButtons() {
            const pauseBtn = document.getElementById('pauseBtn');
            const resumeBtn = document.getElementById('resumeBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            if (isPaused && !isCancelled && !isCompleted) {
                pauseBtn.style.display = 'none';
                resumeBtn.style.display = 'inline-block';
            } else {
                pauseBtn.style.display = 'inline-block';
                resumeBtn.style.display = 'none';
            }

            if (isCancelled || isCompleted) {
                pauseBtn.disabled = true;
                resumeBtn.disabled = true;
                cancelBtn.disabled = true;
            }
        }

        function showCompletion() {
            document.getElementById('completionMessage').classList.add('active');
            document.getElementById('currentEmail').textContent = 'Completed!';
            document.getElementById('statusBadge').className = 'status-badge completed';
            document.getElementById('statusBadge').textContent = 'COMPLETED';
            document.getElementById('progressTitle').textContent = '✅ Processing Complete';
            updateButtons();
            addLogLine('🎉 All emails have been processed!', 'success');
        }

        function addLogLine(message, type = '') {
            const progressLog = document.getElementById('progressLog');
            const line = document.createElement('div');
            line.className = 'progress-log-line ' + type;
            line.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            progressLog.appendChild(line);
            progressLog.scrollTop = progressLog.scrollHeight;
        }

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        // Update buttons on load
        updateButtons();
    </script>
</body>

</html>

