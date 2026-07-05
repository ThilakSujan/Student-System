-- ══════════════════════════════════════════════════════════════════════
--  EMAIL NOTIFICATION SYSTEM — Database Migration
--  Run this script ONCE against student1_db to set up email features.
-- ══════════════════════════════════════════════════════════════════════

-- 1. Add parent_email to students table
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS parent_email VARCHAR(100) DEFAULT NULL AFTER email,
    ADD COLUMN IF NOT EXISTS parent_name  VARCHAR(100) DEFAULT NULL AFTER parent_email;

-- 2. Add published flag + timestamp to marks table
ALTER TABLE marks
    ADD COLUMN IF NOT EXISTS published    TINYINT(1)  DEFAULT 0    AFTER status,
    ADD COLUMN IF NOT EXISTS published_at TIMESTAMP   NULL DEFAULT NULL AFTER published,
    ADD COLUMN IF NOT EXISTS published_by INT         DEFAULT NULL AFTER published_at;

-- 3. Email Logs table — records every email sending attempt
CREATE TABLE IF NOT EXISTS email_logs (
    id              INT           PRIMARY KEY AUTO_INCREMENT,
    recipient_email VARCHAR(255)  NOT NULL,
    subject         VARCHAR(500)  NOT NULL,
    email_type      VARCHAR(50)   NOT NULL COMMENT 'attendance|fee_invoice|marks_published|report_card|custom',
    status          ENUM('sent','failed') NOT NULL DEFAULT 'failed',
    sent_at         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    error_message   TEXT          DEFAULT NULL,
    related_id      INT           DEFAULT NULL    COMMENT 'e.g. student_id, payment_id',
    related_type    VARCHAR(50)   DEFAULT NULL    COMMENT 'student|payment|mark',
    created_by      INT           DEFAULT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ══════════════════════════════════════════════════════════════════════
--  Verification: Run these queries to confirm the migration worked.
-- ══════════════════════════════════════════════════════════════════════
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='students' AND COLUMN_NAME IN ('parent_email','parent_name');
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='marks' AND COLUMN_NAME IN ('published','published_at');
-- DESCRIBE email_logs;
