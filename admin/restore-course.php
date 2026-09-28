<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


/* POST + CSRF only */

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valid()) {

    $_SESSION["error"] = "Invalid request.";

    redirect("course-trash.php");
}


$course_id = intval($_POST["id"] ?? 0);

if ($course_id <= 0) {

    redirect("course-trash.php");
}


/* Verify course is trashed */

$stmt = $pdo->prepare(
    "SELECT id, title
     FROM courses
     WHERE id = ?
     AND deleted_at IS NOT NULL"
);

$stmt->execute([$course_id]);

$course = $stmt->fetch();

if (!$course) {

    $_SESSION["error"] = "Course not found in trash.";

    redirect("course-trash.php");
}


/* Restore */

$stmt = $pdo->prepare(
    "UPDATE courses
     SET deleted_at = NULL
     WHERE id = ?"
);

$stmt->execute([$course_id]);

audit_log($pdo, $_SESSION["user_id"], "course_restored", "Restored course \"" . $course["title"] . "\" (#$course_id) from trash.");


$_SESSION["success"] =
    "Course '" . $course["title"] . "' restored successfully.";

redirect("course-trash.php");
