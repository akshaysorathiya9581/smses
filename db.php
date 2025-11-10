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
    
    $sql = "INSERT INTO email_batches (
        batch_id, smtp_host, smtp_port, smtp_security, smtp_username, smtp_password,
        from_email, from_name, subject, message, is_html, debug_mode, email_delay, total_emails
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    $is_html = $config['is_html'] ? 1 : 0;
    $debug_mode = $config['debug'] ? 1 : 0;
    
    $stmt->bind_param('ssisssssssiiii',
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
 * Get pending emails for a batch
 * @param string $batchId Batch ID
 * @return array List of pending emails
 */
function getPendingEmails($batchId) {
    $mysqli = getDbConnection();
    
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
    
    // Get counts
    $sql = "SELECT 
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
            FROM email_queue 
            WHERE batch_id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $batchId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $counts = $result->fetch_assoc();
    $stmt->close();
    
    // Update batch
    $sql = "UPDATE email_batches 
            SET sent_count = ?, 
                failed_count = ? 
            WHERE batch_id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iis', $counts['sent_count'], $counts['failed_count'], $batchId);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get batch progress
 * @param string $batchId Batch ID
 * @return array Progress information
 */
function getBatchProgress($batchId) {
    $mysqli = getDbConnection();
    
    $sql = "SELECT 
                b.total_emails,
                b.sent_count,
                b.failed_count,
                b.status,
                (SELECT COUNT(*) FROM email_queue WHERE batch_id = b.batch_id AND status = 'pending') as pending_count,
                (SELECT COUNT(*) FROM email_queue WHERE batch_id = b.batch_id AND status = 'processing') as processing_count
            FROM email_batches b
            WHERE b.batch_id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $batchId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
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

