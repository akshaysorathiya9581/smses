<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Clear bulk sending session data
unset($_SESSION['bulk_sending']);
unset($_SESSION['bulk_emails']);
unset($_SESSION['smtp_config']);

echo json_encode(['success' => true]);

