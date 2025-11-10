<?php
/**
 * Installation Script for Email Batch Sending System
 * Run this file once to create the required database tables
 */

require_once 'db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Email System - Database Installation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #bee5eb;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .step {
            margin: 15px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 4px solid #4CAF50;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📧 Email Batch Sending System - Installation</h1>";

try {
    // Get database connection
    $mysqli = getDbConnection();
    
    echo "<div class='info'><strong>✓ Database Connection:</strong> Successfully connected to database <code>" . DB_NAME . "</code></div>";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/create_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: create_tables.sql");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL statements
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;[\r\n]+/', $sql)
        ),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    echo "<h2>Creating Tables...</h2>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        // Extract table name for display
        if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches)) {
            $tableName = $matches[1];
            
            try {
                $mysqli->query($statement);
                echo "<div class='step success'><strong>✓ Table Created:</strong> <code>{$tableName}</code></div>";
                $successCount++;
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo "<div class='step info'><strong>ℹ Table Exists:</strong> <code>{$tableName}</code> already exists (skipped)</div>";
                } else {
                    echo "<div class='step error'><strong>✗ Error creating table:</strong> <code>{$tableName}</code><br>{$e->getMessage()}</div>";
                    $errorCount++;
                }
            }
        }
    }
    
    echo "<hr style='margin: 30px 0;'>";
    
    if ($errorCount === 0) {
        echo "<div class='success'>
                <h3>✓ Installation Completed Successfully!</h3>
                <p><strong>Tables Created:</strong> {$successCount}</p>
                <p>Your email batch sending system is now ready to use.</p>
                <p><strong>Next Steps:</strong></p>
                <ul>
                    <li>Configure your SMTP settings in the email sending form</li>
                    <li>Start sending bulk emails</li>
                    <li>For security, consider deleting or restricting access to this install.php file</li>
                </ul>
              </div>";
    } else {
        echo "<div class='error'>
                <h3>⚠ Installation Completed with Errors</h3>
                <p><strong>Successful:</strong> {$successCount} | <strong>Failed:</strong> {$errorCount}</p>
                <p>Please review the errors above and fix them manually.</p>
              </div>";
    }
    
    // Display table information
    echo "<h2>Database Tables Information</h2>";
    
    $tables = ['email_batches', 'email_queue'];
    foreach ($tables as $table) {
        $result = $mysqli->query("SHOW TABLES LIKE '{$table}'");
        if ($result && $result->num_rows > 0) {
            $countResult = $mysqli->query("SELECT COUNT(*) as count FROM `{$table}`");
            $count = $countResult->fetch_assoc()['count'];
            
            echo "<div class='step'>
                    <strong>Table:</strong> <code>{$table}</code><br>
                    <strong>Records:</strong> {$count}
                  </div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>
            <h3>✗ Installation Failed</h3>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p>Please check your database configuration in <code>db.php</code></p>
          </div>";
}

echo "    </div>
</body>
</html>";
?>
