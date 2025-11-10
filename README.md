# SMTP Bulk Email Sender with Database Queue System

A professional PHP-based bulk email sender with database-backed queue system for pause/resume functionality.

## Features

✅ **Database-Backed Queue System** - All emails are stored in database for reliability  
✅ **Pause/Resume Functionality** - Pause and resume bulk email sending at any time  
✅ **Real-time Progress Tracking** - Live progress updates with detailed statistics  
✅ **Single & Bulk Email Support** - Send to one recipient or thousands  
✅ **SMTP Configuration** - Support for all major SMTP providers  
✅ **Error Handling** - Detailed error logging and retry capability  
✅ **Secure Authentication** - Login system to protect access  
✅ **Modern UI** - Beautiful, responsive interface  

## Installation

### 1. Database Setup

First, create a MySQL database:

```sql
CREATE DATABASE email_sender CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Configure Database Connection

Edit `db.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'email_sender');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Run Installation

Navigate to `install.php` in your browser:

```
http://your-domain.com/send/install.php
```

This will create the necessary database tables:
- `email_queue` - Stores individual emails to be sent
- `email_batches` - Stores batch configuration and progress

### 4. Delete Installation File

After successful installation, delete `install.php` for security:

```bash
rm install.php
```

## Usage

### Login

Default credentials:
- **Username:** `admin`
- **Password:** `7892`

⚠️ **Important:** Change these credentials in `test-mail.php` (line 10) for production use!

### Sending Single Email

1. Log in to the system
2. Fill in SMTP server configuration
3. Enter authentication details
4. Fill in email details (from, to, subject, message)
5. Click "Send Email(s)"

### Sending Bulk Emails

1. Log in to the system
2. Fill in SMTP server configuration
3. Enter authentication details
4. Fill in email details (from, subject, message)
5. Select "Bulk Email (Upload File)" option
6. Upload a `.txt` file with one email address per line
7. Set delay between emails (optional)
8. Click "Send Email(s)"
9. You'll be redirected to `process.php` where you can monitor progress

### Pause/Resume Processing

On the processing page (`process.php`):
- Click **Pause** to temporarily stop sending
- Click **Resume** to continue from where you left off
- Click **Cancel** to stop and mark remaining emails as failed

## File Structure

```
send/
├── test-mail.php          # Main interface for configuring emails
├── process.php            # Email processing and monitoring page
├── db.php                 # Database connection and helper functions
├── schema.sql             # Database schema
├── install.php            # Installation script (delete after use)
├── login.php              # Login page
├── README.md              # This file
└── vendor/                # PHPMailer library
```

## Database Schema

### email_batches Table

Stores batch configuration and overall progress:

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| batch_id | VARCHAR(50) | Unique batch identifier |
| smtp_host | VARCHAR(255) | SMTP server hostname |
| smtp_port | INT | SMTP port |
| smtp_security | VARCHAR(20) | Security protocol (auto/tls/ssl/none) |
| smtp_username | VARCHAR(255) | SMTP username |
| smtp_password | VARCHAR(255) | SMTP password |
| from_email | VARCHAR(255) | Sender email |
| from_name | VARCHAR(255) | Sender name |
| subject | VARCHAR(500) | Email subject |
| message | TEXT | Email message |
| is_html | TINYINT | HTML email flag |
| debug_mode | TINYINT | Debug mode flag |
| email_delay | INT | Delay between emails (seconds) |
| total_emails | INT | Total emails in batch |
| sent_count | INT | Successfully sent count |
| failed_count | INT | Failed count |
| status | ENUM | Batch status (pending/processing/paused/completed/cancelled) |
| created_at | TIMESTAMP | Creation timestamp |
| started_at | TIMESTAMP | Start timestamp |
| completed_at | TIMESTAMP | Completion timestamp |

### email_queue Table

Stores individual emails:

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| batch_id | VARCHAR(50) | Reference to batch |
| recipient_email | VARCHAR(255) | Recipient email address |
| from_email | VARCHAR(255) | Sender email |
| from_name | VARCHAR(255) | Sender name |
| subject | VARCHAR(500) | Email subject |
| message | TEXT | Email message |
| is_html | TINYINT | HTML email flag |
| status | ENUM | Email status (pending/processing/sent/failed/paused) |
| error_message | TEXT | Error message if failed |
| attempts | INT | Number of send attempts |
| created_at | TIMESTAMP | Creation timestamp |
| sent_at | TIMESTAMP | Sent timestamp |

## SMTP Configuration Examples

### Gmail

```
SMTP Server: smtp.gmail.com
Port: 587
Security: STARTTLS
Username: your-email@gmail.com
Password: [App Password - not your regular password]
```

**Note:** You need to create an [App Password](https://support.google.com/accounts/answer/185833) for Gmail.

### Office 365 / Outlook

```
SMTP Server: smtp.office365.com
Port: 587
Security: STARTTLS
Username: your-email@outlook.com
Password: [Your password]
```

### Custom SMTP

```
SMTP Server: mail.yourdomain.com
Port: 587 (or 465 for SSL)
Security: Auto (recommended)
Username: your-email@yourdomain.com
Password: [Your password]
```

## API Functions (db.php)

Key functions available:

- `createEmailBatch($config)` - Create a new email batch
- `addEmailToQueue($batchId, $email, $config)` - Add email to queue
- `getBatchInfo($batchId)` - Get batch information
- `getPendingEmails($batchId)` - Get pending emails
- `updateEmailStatus($emailId, $status, $errorMessage)` - Update email status
- `updateBatchStatus($batchId, $status)` - Update batch status
- `pauseBatch($batchId)` - Pause batch processing
- `resumeBatch($batchId)` - Resume batch processing
- `cancelBatch($batchId)` - Cancel batch processing
- `getBatchProgress($batchId)` - Get batch progress statistics

## Troubleshooting

### Database Connection Error

- Check that your database exists
- Verify credentials in `db.php`
- Ensure MySQL/MariaDB is running

### SMTP Connection Error

- Enable debug mode to see detailed SMTP conversation
- Verify SMTP credentials
- Check firewall/port settings
- For Gmail, use App Password instead of regular password

### Emails Not Sending

- Check the `email_queue` table for error messages
- Review the progress log on `process.php`
- Verify SMTP settings are correct
- Check your email provider's sending limits

### Permission Denied

- Ensure PHP has write permissions to the directory
- Check file ownership and permissions

## Security Recommendations

1. **Change Default Password** - Update login credentials in `test-mail.php`
2. **Delete install.php** - Remove after installation
3. **Use HTTPS** - Always use SSL/TLS for the web interface
4. **Restrict Access** - Use `.htaccess` or server config to limit access
5. **Database Security** - Use strong database passwords
6. **Regular Backups** - Backup your database regularly

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- PHPMailer library (included in vendor/)
- PDO PHP extension
- OpenSSL PHP extension (for SMTP encryption)

## License

This project is open source and available for personal and commercial use.

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review error messages in the progress log
3. Enable debug mode for detailed SMTP information

## Changelog

### Version 2.0
- ✅ Added database-backed queue system
- ✅ Implemented pause/resume functionality
- ✅ Separated processing to dedicated page
- ✅ Enhanced error handling and logging
- ✅ Added batch management features

### Version 1.0
- Initial release with basic bulk email functionality

