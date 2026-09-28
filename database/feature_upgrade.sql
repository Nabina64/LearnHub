-- =====================================================
-- LearnHub - Feature Upgrade  (SAFE TO RUN MANY TIMES)
--
-- Adds everything the newer pages need:
--   users.status / approved_at / deleted_at
--   courses.deleted_at
--   lessons.deleted_at
--   course_views table
--   site_settings + contact_messages tables
--
-- Every step first checks if it already exists, so running
-- the file again never fails halfway. (The old version of
-- this file stopped at the first "duplicate column" error,
-- which left courses.deleted_at missing and broke all
-- student pages.)
--
-- Run it in phpMyAdmin: select the "online_learning"
-- database -> SQL tab -> paste -> Go.
--
-- NOTE: the website also repairs these columns by itself
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
-- 1. Teacher approval
-- -----------------------------------------------------

CALL learnhub_add_column('users', 'status',
    "ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved'");

CALL learnhub_add_column('users', 'approved_at',
    'DATETIME NULL DEFAULT NULL');

-- All old users stay usable
UPDATE users
SET status = 'approved'
WHERE status IS NULL OR status = '';


-- -----------------------------------------------------
-- 2. Soft delete (trash) for users, courses, lessons
-- -----------------------------------------------------

CALL learnhub_add_column('users', 'deleted_at',
    'DATETIME NULL DEFAULT NULL');

CALL learnhub_add_column('courses', 'deleted_at',
    'DATETIME NULL DEFAULT NULL');

CALL learnhub_add_column('lessons', 'deleted_at',
    'DATETIME NULL DEFAULT NULL');


-- -----------------------------------------------------
-- 3. Course views (teacher analytics)
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS course_views (

    id INT AUTO_INCREMENT PRIMARY KEY,

    course_id INT NOT NULL,

    user_id INT NULL,

    viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_course (course_id),
    INDEX idx_user (user_id),
    INDEX idx_viewed_at (viewed_at)

) ENGINE = InnoDB;


-- -----------------------------------------------------
-- 4. Admin settings + Contact page messages
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS site_settings (

    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,

    setting_value TEXT NULL,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP

) ENGINE = InnoDB;


CREATE TABLE IF NOT EXISTS contact_messages (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    email VARCHAR(150) NOT NULL,

    subject VARCHAR(200) NOT NULL,

    message TEXT NOT NULL,

    is_read TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_created (created_at)

) ENGINE = InnoDB;


DROP PROCEDURE IF EXISTS learnhub_add_column;
