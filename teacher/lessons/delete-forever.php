<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

/* POST + CSRF only - this permanently destroys data */

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valid()) {
    redirect("trash.php");
}

$teacher_id = $_SESSION["user_id"];

$lesson_id = intval($_POST["id"] ?? 0);

if ($lesson_id <= 0) {
    redirect("trash.php");
}


/* Verify lesson belongs to teacher and is trashed */

$stmt = $pdo->prepare(
    "SELECT lessons.id
     FROM lessons
     INNER JOIN courses
        ON lessons.course_id = courses.id
     WHERE lessons.id = ?
     AND courses.teacher_id = ?
     AND lessons.deleted_at IS NOT NULL"
);

$stmt->execute([
    $lesson_id,
    $teacher_id
]);

$lesson = $stmt->fetch();

if (!$lesson) {

    $_SESSION["error"] = "Lesson not found in trash.";

    redirect("trash.php");
}


/* Permanently delete */

$stmt = $pdo->prepare(
    "DELETE FROM lessons
     WHERE id = ?"
);

$stmt->execute([$lesson_id]);


$_SESSION["success"] = "Lesson permanently deleted.";

redirect("trash.php");
