-- Email Queue System Database Schema
-- This schema supports pause/resume functionality for bulk email sending

CREATE TABLE IF NOT EXISTS `email_queue` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `batch_id` VARCHAR(50) NOT NULL,
  `recipient_email` VARCHAR(255) NOT NULL,
  `from_email` VARCHAR(255) NOT NULL,
  `from_name` VARCHAR(255) DEFAULT NULL,
  `subject` VARCHAR(500) NOT NULL,
  `message` TEXT NOT NULL,
  `is_html` TINYINT(1) DEFAULT 0,
  `status` ENUM('pending', 'processing', 'sent', 'failed', 'paused') DEFAULT 'pending',
  `error_message` TEXT DEFAULT NULL,
  `attempts` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `batch_id` (`batch_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `email_batches` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `batch_id` VARCHAR(50) NOT NULL UNIQUE,
  `smtp_host` VARCHAR(255) NOT NULL,
  `smtp_port` INT(11) NOT NULL,
  `smtp_security` VARCHAR(20) DEFAULT 'auto',
  `smtp_username` VARCHAR(255) DEFAULT NULL,
  `smtp_password` VARCHAR(255) DEFAULT NULL,
  `from_email` VARCHAR(255) NOT NULL,
  `from_name` VARCHAR(255) DEFAULT NULL,
  `subject` VARCHAR(500) NOT NULL,
  `message` TEXT NOT NULL,
  `is_html` TINYINT(1) DEFAULT 0,
  `debug_mode` TINYINT(1) DEFAULT 0,
  `email_delay` INT(11) DEFAULT 1,
  `total_emails` INT(11) DEFAULT 0,
  `sent_count` INT(11) DEFAULT 0,
  `failed_count` INT(11) DEFAULT 0,
  `status` ENUM('pending', 'processing', 'paused', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `batch_id` (`batch_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

