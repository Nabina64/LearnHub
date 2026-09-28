<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


/* POST + CSRF only - this changes data, so it must never be a GET link */

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valid()) {

    $_SESSION["error"] = "Invalid request.";

    redirect("users.php");
}


/* Get User ID */

$user_id = intval($_POST["id"] ?? 0);


/* Invalid ID */

if ($user_id <= 0) {

    redirect("users.php");

}


/* Prevent Admin From Deleting Own Account */

if ($user_id == $_SESSION["user_id"]) {

    $_SESSION["error"] = "You cannot delete your own account.";

    redirect("users.php");

}


/* Check User Exists */

$stmt = $pdo->prepare(
    "SELECT id, name, role
     FROM users
     WHERE id = ?
     AND deleted_at IS NULL"
);

$stmt->execute([$user_id]);

$user = $stmt->fetch();


if (!$user) {

    $_SESSION["error"] = "User not found.";

    redirect("users.php");

}


/* Move to trash (soft delete) */

$stmt = $pdo->prepare(
    "UPDATE users
     SET deleted_at = NOW()
     WHERE id = ?"
);

$stmt->execute([$user_id]);

audit_log(
    $pdo,
    $_SESSION["user_id"],
    "user_deleted",
    "Moved user \"" . $user["name"] . "\" (#$user_id, role: " . $user["role"] . ") to trash."
);


/* Success Message */

$_SESSION["success"] =
    "User '" .
    $user["name"] .
    "' has been moved to trash. " .
    "You can restore it from the Trash page within 30 days.";


/* Redirect */

redirect("users.php");
