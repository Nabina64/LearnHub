<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

// Only teacher can access
require_role("teacher", "../../student/dashboard.php");

/* POST + CSRF only - this changes data */

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valid()) {

    $_SESSION["error"] = "Invalid request.";

    redirect("index.php");
}

$teacherId = $_SESSION["user_id"];

$courseId = isset($_POST["id"])
    ? (int) $_POST["id"]
    : 0;


// Get course belonging to logged-in teacher
$stmt = $pdo->prepare(
    "SELECT id
     FROM courses
     WHERE id = ? AND teacher_id = ?
     AND deleted_at IS NULL"
);

$stmt->execute([
    $courseId,
    $teacherId
]);

$course = $stmt->fetch();


// Course not found
if (!$course) {

    $_SESSION["error"] =
        "Course not found or you do not have permission to delete it.";

    redirect("index.php");
}


// Move to trash (soft delete) - thumbnail is kept in case of restore
$stmt = $pdo->prepare(
    "UPDATE courses
     SET deleted_at = NOW()
     WHERE id = ? AND teacher_id = ?"
);

$stmt->execute([
    $courseId,
    $teacherId
]);


$_SESSION["success"] =
    "Course moved to trash. You can restore it from the "
    . "Trash page within 30 days.";

redirect("index.php");
