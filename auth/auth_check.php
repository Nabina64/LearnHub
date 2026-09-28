<?php

require_once __DIR__ . "/../includes/session.php";

if (!isset($_SESSION["user_id"])) {

    header("Location: /online-learning-platform/auth/login.php");
    exit;
}

/*
 * Force a password change (see the default-admin-account fix in
 * includes/schema.php / database/online_learning.sql) before this
 * user can reach any other page. settings.php is where the change
 * password form lives for every role, so it is the one page this
 * does not redirect away from.
 */
if (
    !empty($_SESSION["must_change_password"])
    && strpos($_SERVER["SCRIPT_NAME"] ?? "", "/settings.php") === false
) {

    $role = $_SESSION["user_role"] ?? "student";

    $target = "/online-learning-platform/" . $role . "/settings.php";

    header("Location: " . $target);
    exit;
}


/**
 * Centralized role-based access control.
 *
 * Every admin/teacher/student page used to repeat its own
 * "if ($_SESSION['user_role'] !== '...') { redirect(...); }" check.
 * That worked, but it meant the actual security decision was
 * copy-pasted in 50+ places - one typo or one forgotten check on a
 * new page would silently leave it unprotected.
 *
 * Call this ONE line instead, right after requiring auth_check.php:
 *
 *   require_role("admin");
 *   require_role("teacher", "../index.php");
 *   require_role(["admin", "teacher"]);
 *
 * - $allowed_roles: a role name or an array of role names allowed
 *   on this page.
 * - $redirect_to: where to send anyone who does NOT have one of
 *   those roles. Optional - defaults to the site home page.
 *
 * Every denied attempt is written to the audit log (see
 * includes/audit.php) when the database connection is available,
 * so repeated attempts to open pages a user has no business
 * opening are visible to the admin.
 */
function require_role($allowed_roles, $redirect_to = null)
{
    $allowed_roles = (array) $allowed_roles;

    $role = $_SESSION["user_role"] ?? null;

    if ($role !== null && in_array($role, $allowed_roles, true)) {
        return;
    }

    global $pdo;

    if (isset($pdo) && $pdo instanceof PDO && function_exists("audit_log")) {

        audit_log(
            $pdo,
            $_SESSION["user_id"] ?? null,
            "access_denied",
            "Tried to open a '" . implode("/", $allowed_roles)
                . "' page while logged in as '" . ($role ?? "guest") . "'."
        );
    }

    if ($redirect_to === null) {
        $redirect_to = "/online-learning-platform/index.php";
    }

    header("Location: " . $redirect_to);
    exit;
}