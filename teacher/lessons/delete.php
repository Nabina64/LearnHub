<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

/* POST + CSRF only - this changes data */

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valid()) {
    redirect("index.php");
}

$teacher_id = $_SESSION["user_id"];

$lesson_id = intval($_POST["id"] ?? 0);

if ($lesson_id <= 0) {
    redirect("index.php");
}


/* Verify lesson belongs to teacher */

$stmt = $pdo->prepare(
    "SELECT lessons.id
     FROM lessons
     INNER JOIN courses
        ON lessons.course_id = courses.id
     WHERE lessons.id = ?
     AND courses.teacher_id = ?
     AND lessons.deleted_at IS NULL"
);

$stmt->execute([
    $lesson_id,
    $teacher_id
]);

$lesson = $stmt->fetch();

if (!$lesson) {
    redirect("index.php");
}


/* Move to trash (soft delete) */

$stmt = $pdo->prepare(
    "UPDATE lessons
     SET deleted_at = NOW()
     WHERE id = ?"
);

$stmt->execute([$lesson_id]);


$_SESSION["success"] =
    "Lesson moved to trash. You can restore it from the "
    . "Trash page within 30 days.";

redirect("index.php");
