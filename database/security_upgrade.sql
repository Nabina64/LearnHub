-- =====================================================
-- LearnHub - Security Upgrade  (SAFE TO RUN MANY TIMES)
--
-- Adds everything the new security features need:
--   users.email_verified_at / verify_token_hash / verify_token_expires
--   users.reset_token_hash / reset_token_expires
--   rate_limit_hits table   (login throttling, password reset, resend)
--   audit_logs table        (admin > Audit Logs)
--
-- Run it in phpMyAdmin: select the "online_learning"
-- database -> SQL tab -> paste -> Go.
--
-- NOTE: the website also repairs these columns/tables by itself
-- (includes/schema.php), so this file is only a backup.
-- =====================================================


DROP PROCEDURE IF EXISTS learnhub_add_column;

DELIMITER $$

CREATE PROCEDURE learnhub_add_column(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN

    IF EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = p_table
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = p_table
        AND COLUMN_NAME = p_column
    ) THEN

        SET @learnhub_sql = CONCAT(
            'ALTER TABLE `', p_table,
            '` ADD COLUMN `', p_column, '` ', p_definition
        );

        PREPARE learnhub_stmt FROM @learnhub_sql;
        EXECUTE learnhub_stmt;
        DEALLOCATE PREPARE learnhub_stmt;

    END IF;

END$$

DELIMITER ;


-- -----------------------------------------------------
-- 1. Email verification
-- -----------------------------------------------------

CALL learnhub_add_column('users', 'email_verified_at',
    'DATETIME NULL DEFAULT NULL');

CALL learnhub_add_column('users', 'verify_token_hash',
    'VARCHAR(255) NULL DEFAULT NULL');

CALL learnhub_add_column('users', 'verify_token_expires',
    'DATETIME NULL DEFAULT NULL');

-- Grandfather in every account that already existed before this
-- upgrade - there is no way to verify them retroactively, and
-- locking out the whole existing user base would be far worse than
-- the small amount of risk left on old accounts.
UPDATE users
SET email_verified_at = COALESCE(created_at, NOW())
WHERE email_verified_at IS NULL;


-- -----------------------------------------------------
-- 2. Forgot password
-- -----------------------------------------------------

CALL learnhub_add_column('users', 'reset_token_hash',
    'VARCHAR(255) NULL DEFAULT NULL');

CALL learnhub_add_column('users', 'reset_token_expires',
    'DATETIME NULL DEFAULT NULL');


-- -----------------------------------------------------
-- 3. Generic rate limiting (login throttling, password reset
--    requests, verification email resends)
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS rate_limit_hits (

    id INT AUTO_INCREMENT PRIMARY KEY,

    bucket VARCHAR(191) NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_bucket_time (bucket, created_at)

) ENGINE = InnoDB;


-- -----------------------------------------------------
-- 4. Audit log
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS audit_logs (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    user_email VARCHAR(150) NULL,

    action VARCHAR(60) NOT NULL,

    description TEXT NULL,

    ip_address VARCHAR(45) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_created (created_at),
    INDEX idx_action (action),
    INDEX idx_user (user_id)

) ENGINE = InnoDB;


DROP PROCEDURE IF EXISTS learnhub_add_column;
