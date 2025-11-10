<?php
/**
 * Database Configuration Example
 * 
 * Copy this file to db.php and update with your actual credentials
 */

// Database configuration
define('DB_HOST', 'localhost');           // Your database host (usually 'localhost')
define('DB_NAME', 'email_sender');        // Your database name
define('DB_USER', 'root');                // Your database username
define('DB_PASS', '');                    // Your database password
define('DB_CHARSET', 'utf8mb4');          // Character set (don't change unless needed)

// Example configurations for different environments:

// WAMP/XAMPP (Local Development)
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'email_sender');
// define('DB_USER', 'root');
// define('DB_PASS', '');

// Production Server
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'your_production_db');
// define('DB_USER', 'your_db_user');
// define('DB_PASS', 'your_secure_password');

// Remote MySQL Server
// define('DB_HOST', '192.168.1.100');
// define('DB_NAME', 'email_sender');
// define('DB_USER', 'remote_user');
// define('DB_PASS', 'remote_password');

