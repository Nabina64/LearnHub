<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

/* POST + CSRF only */

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valid()) {

    $_SESSION["error"] = "Invalid request.";

    redirect("trash.php");
}

$teacher_id = $_SESSION["user_id"];

$course_id = intval($_POST["id"] ?? 0);

if ($course_id <= 0) {
    redirect("trash.php");
}


/* Verify course belongs to teacher and is trashed */

$stmt = $pdo->prepare(
    "SELECT id, title
     FROM courses
     WHERE id = ?
     AND teacher_id = ?
     AND deleted_at IS NOT NULL"
);

$stmt->execute([
    $course_id,
    $teacher_id
]);

$course = $stmt->fetch();

if (!$course) {

    $_SESSION["error"] = "Course not found in trash.";

    redirect("trash.php");
}


/* Restore */

$stmt = $pdo->prepare(
    "UPDATE courses
     SET deleted_at = NULL
     WHERE id = ?"
);

$stmt->execute([$course_id]);


$_SESSION["success"] =
    "Course '" . $course["title"] . "' restored successfully.";

redirect("trash.php");
