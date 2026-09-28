<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


/* POST + CSRF only - this changes an account's status */

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valid()) {

    $_SESSION["error"] = "Invalid request.";

    redirect("teacher-requests.php");
}


/* Get Inputs */

$user_id = intval($_POST["id"] ?? 0);

$action = $_POST["action"] ?? "";


if ($user_id <= 0 || !in_array($action, ["approve", "reject"])) {

    $_SESSION["error"] = "Invalid request.";

    redirect("teacher-requests.php");
}


/* Check Teacher Exists */

$stmt = $pdo->prepare(
    "SELECT id, name, role
     FROM users
     WHERE id = ?"
);

$stmt->execute([$user_id]);

$user = $stmt->fetch();


if (!$user) {

    $_SESSION["error"] = "User not found.";

    redirect("teacher-requests.php");
}


if ($user["role"] !== "teacher") {

    $_SESSION["error"] = "This user is not a teacher.";

    redirect("teacher-requests.php");
}


/* Update Status */

$status = ($action === "approve")
    ? "approved"
    : "rejected";

$stmt = $pdo->prepare(
    "UPDATE users
     SET status = ?,
         approved_at = NOW()
     WHERE id = ?"
);

$stmt->execute([$status, $user_id]);

audit_log(
    $pdo,
    $_SESSION["user_id"],
    "teacher_" . $status,
    "Teacher \"" . $user["name"] . "\" (#$user_id) was $status."
);

$_SESSION["success"] =
    "Teacher \"" . $user["name"] . "\" has been "
    . $status . ".";

redirect("teacher-requests.php");
