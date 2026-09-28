<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

/* POST + CSRF only - this permanently destroys data */

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
    "SELECT id, title, thumbnail
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


/* Permanently delete the course.
   Lessons/quizzes/enrollments belonging to it are cleaned up too,
   since there is no FK cascade in this database. */

$pdo->prepare(
    "DELETE quizzes, questions FROM quizzes
     LEFT JOIN questions ON questions.quiz_id = quizzes.id
     WHERE quizzes.course_id = ?"
)->execute([$course_id]);

$pdo->prepare(
    "DELETE FROM lessons WHERE course_id = ?"
)->execute([$course_id]);

$pdo->prepare(
    "DELETE FROM enrollments WHERE course_id = ?"
)->execute([$course_id]);

$pdo->prepare(
    "DELETE FROM course_views WHERE course_id = ?"
)->execute([$course_id]);

$pdo->prepare(
    "DELETE FROM courses WHERE id = ?"
)->execute([$course_id]);


/* Delete thumbnail file */

if (
    !empty($course["thumbnail"])
    && file_exists(__DIR__ . "/../../uploads/courses/" . $course["thumbnail"])
) {
    unlink(__DIR__ . "/../../uploads/courses/" . $course["thumbnail"]);
}


$_SESSION["success"] =
    "Course '" . $course["title"] . "' permanently deleted.";

redirect("trash.php");
