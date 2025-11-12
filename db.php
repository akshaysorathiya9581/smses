<?php
/**
 * Database Connection Configuration
 * Update these settings according to your database credentials
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'smses_send');
define('DB_USER', 'smses_senduser');
define('DB_PASS', 'user007');
// define('DB_USER', 'root');
// define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');



/**
 * Get database connection
 * @return mysqli Database connection object
 * @throws Exception If connection fails
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
 * Create a new email batch
 * @param array $config SMTP and email configuration
 * @return string Batch ID
 */
function createEmailBatch($config) {
    $mysqli = getDbConnection();
    $batchId = uniqid('batch_', true);
    
    // Check if include_email_in_subject column exists
    $includeEmailInSubject = isset($config['include_email_in_subject']) && $config['include_email_in_subject'] ? 1 : 0;
    $columnExists = false;
    
    try {
        $checkSql = "SHOW COLUMNS FROM email_batches LIKE 'include_email_in_subject'";
        $result = $mysqli->query($checkSql);
        $columnExists = $result && $result->num_rows > 0;
    } catch (Exception $e) {
        // Column doesn't exist, use old schema
        $columnExists = false;
    }
    
    if ($columnExists) {
        $sql = "INSERT INTO email_batches (
            batch_id, smtp_host, smtp_port, smtp_security, smtp_username, smtp_password,
            from_email, from_name, subject, message, is_html, debug_mode, email_delay, total_emails, 
            sent_count, failed_count, include_email_in_subject
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?)";
        
        $stmt = $mysqli->prepare($sql);
        $is_html = $config['is_html'] ? 1 : 0;
        $debug_mode = $config['debug'] ? 1 : 0;
        
        $stmt->bind_param('ssisssssssiiiii',
            $batchId,
            $config['host'],
            $config['port'],
            $config['security'],
            $config['username'],
            $config['password'],
            $config['from_email'],
            $config['from_name'],
            $config['subject'],
            $config['message'],
            $is_html,
            $debug_mode,
            $config['delay'],
            $config['total_emails'],
            $includeEmailInSubject
        );
    } else {
        // Fallback to old schema if column doesn't exist
        $sql = "INSERT INTO email_batches (
            batch_id, smtp_host, smtp_port, smtp_security, smtp_username, smtp_password,
            from_email, from_name, subject, message, is_html, debug_mode, email_delay, total_emails,
            sent_count, failed_count
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";
        
        $stmt = $mysqli->prepare($sql);
        $is_html = $config['is_html'] ? 1 : 0;
        $debug_mode = $config['debug'] ? 1 : 0;
        
        $stmt->bind_param('ssisssssssiiiii',
            $batchId,
            $config['host'],
            $config['port'],
            $config['security'],
            $config['username'],
            $config['password'],
            $config['from_email'],
            $config['from_name'],
            $config['subject'],
            $config['message'],
            $is_html,
            $debug_mode,
            $config['delay'],
            $config['total_emails']
        );
    }
    
    $stmt->execute();
    $stmt->close();
    
    return $batchId;
}

/**
 * Add email to queue
 * @param string $batchId Batch ID
 * @param string $email Recipient email address
 * @param array $config Email configuration
 */
function addEmailToQueue($batchId, $email, $config) {
    $mysqli = getDbConnection();
    
    $sql = "INSERT INTO email_queue (
        batch_id, recipient_email, from_email, from_name, subject, message, is_html
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    $is_html = $config['is_html'] ? 1 : 0;
    
    $stmt->bind_param('ssssssi',
        $batchId,
        $email,
        $config['from_email'],
        $config['from_name'],
        $config['subject'],
        $config['message'],
        $is_html
    );
    
    $stmt->execute();
    $stmt->close();
}

/**
 * Get batch information
 * @param string $batchId Batch ID
 * @return array|null Batch information
 */
function getBatchInfo($batchId) {
    $mysqli = getDbConnection();
    
    $sql = "SELECT * FROM email_batches WHERE batch_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $batchId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return $data;
}

/**
 * Reset emails stuck in processing state (older than 10 minutes)
 * @param string $batchId Batch ID
 */
function resetStuckProcessingEmails($batchId) {
    $mysqli = getDbConnection();
    
    // Reset emails that are in processing state, don't have sent_at, and were created more than 10 minutes ago
    // This catches emails that got stuck in processing state
    $sql = "UPDATE email_queue 
            SET status = 'pending',
                error_message = CONCAT(COALESCE(error_message, ''), ' [Reset from stuck processing state - retrying]')
            WHERE batch_id = ? 
            AND status = 'processing' 
            AND sent_at IS NULL
            AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $batchId);
    $stmt->execute();
    $resetCount = $stmt->affected_rows;
    $stmt->close();
    
    if ($resetCount > 0) {
        error_log("Reset $resetCount stuck processing emails for batch: $batchId");
    }
    
    return $resetCount;
}

/**
 * Get pending emails for a batch
 * @param string $batchId Batch ID
 * @return array List of pending emails
 */
function getPendingEmails($batchId) {
    $mysqli = getDbConnection();
    
    // First, reset any emails stuck in processing state
    resetStuckProcessingEmails($batchId);
    
    $sql = "SELECT * FROM email_queue 
            WHERE batch_id = ? AND status = 'pending' 
            ORDER BY id ASC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $batchId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $data;
}

/**
 * Update email status
 * @param int $emailId Email queue ID
 * @param string $status New status
 * @param string|null $errorMessage Error message if failed
 */
function updateEmailStatus($emailId, $status, $errorMessage = null) {
    $mysqli = getDbConnection();
    
    // For 'sent' status, always update regardless of current status
    if ($status === 'sent') {
        // Cast emailId to int to ensure proper type matching
        $emailId = (int)$emailId;
        
        // Update without status condition to ensure it always updates
        $sql = "UPDATE email_queue 
                SET status = 'sent', 
                    sent_at = NOW(),
                    error_message = ?,
                    attempts = attempts + 1
                WHERE id = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $errorMessage, $emailId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        // Log if update failed for debugging
        if ($affectedRows === 0) {
            error_log("ERROR: updateEmailStatus failed to update email_id: " . $emailId . " to 'sent' status. Email may not exist in database.");
        } else {
            error_log("SUCCESS: updateEmailStatus updated email_id: " . $emailId . " to 'sent' status");
        }
    } else {
        // For other statuses (failed, etc.)
        // Cast emailId to int to ensure proper type matching
        $emailId = (int)$emailId;
        
        $sql = "UPDATE email_queue 
                SET status = ?, 
                    error_message = ?,
                    attempts = attempts + 1
                WHERE id = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssi', $status, $errorMessage, $emailId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        // Check if update was successful
        if ($affectedRows === 0) {
            error_log("ERROR: updateEmailStatus did not update any rows for email_id: " . $emailId . " with status: " . $status);
        } else {
            error_log("SUCCESS: updateEmailStatus updated email_id: " . $emailId . " to status: " . $status);
        }
    }
}

/**
 * Update batch status
 * @param string $batchId Batch ID
 * @param string $status New status
 */
function updateBatchStatus($batchId, $status) {
    $mysqli = getDbConnection();
    
    $sql = "UPDATE email_batches SET status = ?";
    
    if ($status === 'processing') {
        $sql .= ", started_at = NOW()";
    } elseif ($status === 'completed' || $status === 'cancelled') {
        $sql .= ", completed_at = NOW()";
    }
    
    $sql .= " WHERE batch_id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ss', $status, $batchId);
    $stmt->execute();
    $stmt->close();
}

/**
 * Update batch counts
 * @param string $batchId Batch ID
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
 * Get batch progress
 * @param string $batchId Batch ID
 * @return array Progress information
 */
function getBatchProgress($batchId) {
    $mysqli = getDbConnection();
    
    // Get counts directly from email_queue for accuracy, then update batch table
    $sql = "SELECT 
                b.total_emails,
                b.status,
                COALESCE(SUM(CASE WHEN eq.status = 'sent' THEN 1 ELSE 0 END), 0) as sent_count,
                COALESCE(SUM(CASE WHEN eq.status = 'failed' THEN 1 ELSE 0 END), 0) as failed_count,
                COALESCE(SUM(CASE WHEN eq.status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
                COALESCE(SUM(CASE WHEN eq.status = 'processing' THEN 1 ELSE 0 END), 0) as processing_count
            FROM email_batches b
            LEFT JOIN email_queue eq ON b.batch_id = eq.batch_id
            WHERE b.batch_id = ?
            GROUP BY b.batch_id";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $batchId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    // Ensure counts are integers
    if ($data) {
        $data['sent_count'] = (int)($data['sent_count'] ?? 0);
        $data['failed_count'] = (int)($data['failed_count'] ?? 0);
        $data['pending_count'] = (int)($data['pending_count'] ?? 0);
        $data['processing_count'] = (int)($data['processing_count'] ?? 0);
        
        // Update the batch table with the accurate counts
        $updateSql = "UPDATE email_batches 
                     SET sent_count = ?, 
                         failed_count = ? 
                     WHERE batch_id = ?";
        $updateStmt = $mysqli->prepare($updateSql);
        $updateStmt->bind_param('iis', $data['sent_count'], $data['failed_count'], $batchId);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    return $data;
}

/**
 * Pause batch processing
 * @param string $batchId Batch ID
 */
function pauseBatch($batchId) {
    updateBatchStatus($batchId, 'paused');
    
    // Set processing emails back to pending
    $mysqli = getDbConnection();
    $sql = "UPDATE email_queue SET status = 'pending' 
            WHERE batch_id = ? AND status = 'processing'";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $batchId);
    $stmt->execute();
    $stmt->close();
}

/**
 * Resume batch processing
 * @param string $batchId Batch ID
 */
function resumeBatch($batchId) {
    updateBatchStatus($batchId, 'processing');
}

/**
 * Cancel batch processing
 * @param string $batchId Batch ID
 */
function cancelBatch($batchId) {
    updateBatchStatus($batchId, 'cancelled');
    
    // Set pending and processing emails to failed
    $mysqli = getDbConnection();
    $sql = "UPDATE email_queue 
            SET status = 'failed', error_message = 'Batch cancelled by user' 
            WHERE batch_id = ? AND status IN ('pending', 'processing')";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $batchId);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get all email batches
 * @return array List of all batches
 */
function getAllBatches() {
    $mysqli = getDbConnection();
    
    $sql = "SELECT * FROM email_batches 
            ORDER BY created_at DESC";
    $result = $mysqli->query($sql);
    
    $batches = [];
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row;
    }
    
    return $batches;
}

/**
 * Delete batch and all associated emails
 * @param string $batchId Batch ID
 */
function deleteBatchById($batchId) {
    $mysqli = getDbConnection();
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Delete all emails in the queue for this batch
        $sql = "DELETE FROM email_queue WHERE batch_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $batchId);
        $stmt->execute();
        $stmt->close();
        
        // Delete the batch
        $sql = "DELETE FROM email_batches WHERE batch_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $batchId);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $mysqli->commit();
    } catch (Exception $e) {
        // Rollback on error
        $mysqli->rollback();
        throw $e;
    }
}

