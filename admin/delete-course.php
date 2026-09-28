<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


/* POST + CSRF only - this changes data */

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valid()) {

    $_SESSION["error"] = "Invalid request.";

    redirect("courses.php");
}


/* Get Course ID */

$course_id = intval($_POST["id"] ?? 0);


/* Invalid ID */

if ($course_id <= 0) {

    $_SESSION["error"] = "Invalid course ID.";

    redirect("courses.php");

}


/* Get Course */

$stmt = $pdo->prepare(
    "SELECT
        id,
        title,
        thumbnail
     FROM courses
     WHERE id = ?
     AND deleted_at IS NULL"
);

$stmt->execute([$course_id]);

$course = $stmt->fetch();


/* Course Not Found */

if (!$course) {

    $_SESSION["error"] = "Course not found.";

    redirect("courses.php");

}


/* Move to trash (soft delete) */

$stmt = $pdo->prepare(
    "UPDATE courses
     SET deleted_at = NOW()
     WHERE id = ?"
);

$stmt->execute([$course_id]);

audit_log($pdo, $_SESSION["user_id"], "course_deleted", "Moved course \"" . $course["title"] . "\" (#$course_id) to trash.");


/* Success Message */

$_SESSION["success"] =
    "Course '"
    . $course["title"]
    . "' has been moved to trash. "
    . "You can restore it from the Trash page within 30 days.";


/* Redirect */

redirect("courses.php");
