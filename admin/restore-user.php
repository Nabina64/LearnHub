<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


/* POST + CSRF only */

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valid()) {

    $_SESSION["error"] = "Invalid request.";

    redirect("user-trash.php");
}


$user_id = intval($_POST["id"] ?? 0);

if ($user_id <= 0) {

    redirect("user-trash.php");
}


/* Verify user is trashed */

$stmt = $pdo->prepare(
    "SELECT id, name
     FROM users
     WHERE id = ?
     AND deleted_at IS NOT NULL"
);

$stmt->execute([$user_id]);

$user = $stmt->fetch();

if (!$user) {

    $_SESSION["error"] = "User not found in trash.";

    redirect("user-trash.php");
}


/* Restore */

$stmt = $pdo->prepare(
    "UPDATE users
     SET deleted_at = NULL
     WHERE id = ?"
);

$stmt->execute([$user_id]);

audit_log($pdo, $_SESSION["user_id"], "user_restored", "Restored user \"" . $user["name"] . "\" (#$user_id) from trash.");


$_SESSION["success"] =
    "User '" . $user["name"] . "' restored successfully.";

redirect("user-trash.php");
