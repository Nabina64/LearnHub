<?php

/**
 * Self-healing database check for LearnHub.
 *
 * WHY THIS EXISTS
 * ---------------
 * The site needs a few columns/tables that were added later
 * (users.status, users.deleted_at, courses.deleted_at,
 *  lessons.deleted_at, course_views, site_settings,
 *  contact_messages ...).
 *
 * If database/feature_upgrade.sql was only half-applied, or was
 * never applied, every page that touches the missing column
 * crashes with a blank "500" page. Students were hit hardest:
 * they could login and open "Profile", but Dashboard, Courses,
 * Course Details and Lessons all died because they all read
 * courses.deleted_at.
 *
 * ensure_schema() looks at the real database on every request
 * and adds only what is missing. It never deletes or changes
 * existing data, and it never breaks the page if it cannot run
 * (for example when the MySQL user has no ALTER permission).
 */

function ensure_schema(PDO $pdo)
{
    static $already_checked = false;

    if ($already_checked) {
        return;
    }

    $already_checked = true;

    /* table => [ column => definition ] */

    $required = [

        "users" => [
            "status"      => "ENUM('pending','approved','rejected') "
                           . "NOT NULL DEFAULT 'approved'",
            "approved_at" => "DATETIME NULL DEFAULT NULL",
            "deleted_at"  => "DATETIME NULL DEFAULT NULL",
            "created_at"  => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",

            // Forces a password change on next login. Used to close
            // the well-known default admin account (see the seed
            // migration just below) - never set manually elsewhere.
            "must_change_password" => "TINYINT(1) NOT NULL DEFAULT 0",

            // --- Email verification ---
            "email_verified_at"    => "DATETIME NULL DEFAULT NULL",
            "verify_token_hash"    => "VARCHAR(255) NULL DEFAULT NULL",
            "verify_token_expires" => "DATETIME NULL DEFAULT NULL",

            // --- Forgot password ---
            "reset_token_hash"     => "VARCHAR(255) NULL DEFAULT NULL",
            "reset_token_expires"  => "DATETIME NULL DEFAULT NULL",
        ],

        "courses" => [
            "deleted_at"  => "DATETIME NULL DEFAULT NULL",
            "created_at"  => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        ],

        "lessons" => [
            "deleted_at"  => "DATETIME NULL DEFAULT NULL",
            "created_at"  => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        ],

        "enrollments" => [
            "enrolled_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        ],

        "quiz_results" => [
            "attempted_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        ],

        "contact_messages" => [
            "user_id"    => "INT NULL DEFAULT NULL",
            "reply"      => "TEXT NULL DEFAULT NULL",
            "replied_at" => "DATETIME NULL DEFAULT NULL",
        ],
    ];

    try {

        /* Which tables exist? */

        $tables = $pdo->query(
            "SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()"
        )->fetchAll(PDO::FETCH_COLUMN);

        $tables = array_map("strtolower", $tables);


        /* Which columns exist? */

        $rows = $pdo->query(
            "SELECT TABLE_NAME, COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()"
        )->fetchAll();

        $existing = [];

        foreach ($rows as $row) {

            $existing[
                strtolower($row["TABLE_NAME"])
            ][
                strtolower($row["COLUMN_NAME"])
            ] = true;
        }


        /* Add every missing column */

        $just_added_email_verified_at = false;

        foreach ($required as $table => $columns) {

            if (!in_array($table, $tables, true)) {
                continue;
            }

            foreach ($columns as $column => $definition) {

                if (isset($existing[$table][$column])) {
                    continue;
                }

                $pdo->exec(
                    "ALTER TABLE `$table`
                     ADD COLUMN `$column` $definition"
                );

                if ($table === "users" && $column === "email_verified_at") {
                    $just_added_email_verified_at = true;
                }
            }
        }

        /*
         * Grandfather in every account that existed before email
         * verification was added - there is no way to verify them
         * retroactively, and locking out the entire existing user
         * base on deploy would be far worse than the small amount of
         * unverified-address risk on old accounts. Only brand-new
         * registrations from this point on go through verification.
         * This runs exactly once, at the moment the column is added.
         */
        if ($just_added_email_verified_at) {

            $pdo->exec(
                "UPDATE users
                 SET email_verified_at = COALESCE(created_at, NOW())
                 WHERE email_verified_at IS NULL"
            );
        }


        /*
         * Close the well-known default admin account.
         *
         * The seed file (database/online_learning.sql) creates
         * admin@learnhub.com with a fixed, publicly-known password
         * hash. Any install that still has that exact hash has
         * never had its password changed, so flag it here - this
         * runs on every request but only ever matches (and only
         * ever has an effect) until an admin actually changes that
         * password, after which the hash no longer matches and this
         * becomes a permanent no-op for that account.
         */

        if (in_array("users", $tables, true)) {

            $pdo->prepare(
                "UPDATE users
                 SET must_change_password = 1
                 WHERE email = 'admin@learnhub.com'
                 AND password = '$2y$10$adcwZEJMDKBdBjEI0qlQpebQ41NvkBCRKjrhwk8IAdasBC/OVpl0.'
                 AND must_change_password = 0"
            )->execute();
        }


        /* Course views table (teacher analytics) */

        if (
            !in_array("course_views", $tables, true)
            && in_array("courses", $tables, true)
            && in_array("users", $tables, true)
        ) {

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS course_views (

                    id INT AUTO_INCREMENT PRIMARY KEY,

                    course_id INT NOT NULL,

                    user_id INT NULL,

                    viewed_at DATETIME NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    INDEX idx_course (course_id),
                    INDEX idx_user (user_id),
                    INDEX idx_viewed_at (viewed_at)

                ) ENGINE = InnoDB"
            );
        }

        /* Admin settings (registration on/off, contact info ...) */

        if (!in_array("site_settings", $tables, true)) {

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS site_settings (

                    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,

                    setting_value TEXT NULL,

                    updated_at TIMESTAMP NOT NULL
                        DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP

                ) ENGINE = InnoDB"
            );
        }


        /* Messages sent from the public Contact page */

        if (!in_array("contact_messages", $tables, true)) {

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS contact_messages (

                    id INT AUTO_INCREMENT PRIMARY KEY,

                    user_id INT NULL,

                    name VARCHAR(100) NOT NULL,

                    email VARCHAR(150) NOT NULL,

                    subject VARCHAR(200) NOT NULL,

                    message TEXT NOT NULL,

                    reply TEXT NULL,

                    replied_at DATETIME NULL,

                    is_read TINYINT(1) NOT NULL DEFAULT 0,

                    created_at TIMESTAMP NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    INDEX idx_created (created_at),
                    INDEX idx_user (user_id)

                ) ENGINE = InnoDB"
            );
        }

        /* Generic rate-limit counter (login throttling, password
           reset requests, verification email resends - see
           includes/security.php) */

        if (!in_array("rate_limit_hits", $tables, true)) {

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS rate_limit_hits (

                    id INT AUTO_INCREMENT PRIMARY KEY,

                    bucket VARCHAR(191) NOT NULL,

                    created_at DATETIME NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    INDEX idx_bucket_time (bucket, created_at)

                ) ENGINE = InnoDB"
            );
        }


        /* Audit log (see includes/audit.php + admin/audit-logs.php) */

        if (!in_array("audit_logs", $tables, true)) {

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS audit_logs (

                    id INT AUTO_INCREMENT PRIMARY KEY,

                    user_id INT NULL,

                    user_email VARCHAR(150) NULL,

                    action VARCHAR(60) NOT NULL,

                    description TEXT NULL,

                    ip_address VARCHAR(45) NULL,

                    created_at DATETIME NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    INDEX idx_created (created_at),
                    INDEX idx_action (action),
                    INDEX idx_user (user_id)

                ) ENGINE = InnoDB"
            );
        }

    } catch (Throwable $e) {

        // Never break the website because of the auto-fix.
        // Run database/feature_upgrade.sql by hand instead.
        error_log("LearnHub ensure_schema: " . $e->getMessage());
    }
}
