<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Admin Only */

require_role("admin", "../index.php");

/* POST only */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("courses.php");
}

$course_id = intval($_POST["course_id"] ?? 0);
$enrollment_id = intval($_POST["enrollment_id"] ?? 0);

$back = $course_id > 0
    ? "course-view.php?id=" . $course_id . "#enrollments"
    : "courses.php";

/* Came from the Enrollments page? Go back there (same filters/page) */

if (($_POST["return_to"] ?? "") === "enrollments") {

    parse_str((string) ($_POST["return_query"] ?? ""), $q);

    $keep = [];

    foreach (["q", "course_id", "page"] as $key) {

        if (isset($q[$key]) && is_scalar($q[$key]) && $q[$key] !== "") {
            $keep[$key] = (string) $q[$key];
        }
    }

    $back = "enrollments.php" . ($keep ? "?" . http_build_query($keep) : "");
}

/* CSRF check */

if (
    empty($_SESSION["csrf_token"])
    || !hash_equals(
        $_SESSION["csrf_token"],
        (string) ($_POST["csrf_token"] ?? "")
    )
) {

    $_SESSION["error"] = "Security check failed. Please try again.";

    redirect($back);
}

if ($course_id <= 0 || $enrollment_id <= 0) {

    $_SESSION["error"] = "Invalid request.";

    redirect($back);
}

/* Delete only the enrollment (the student account stays) */

$stmt = $pdo->prepare(
    "DELETE FROM enrollments
     WHERE id = ?
     AND course_id = ?"
);

$stmt->execute([$enrollment_id, $course_id]);

$_SESSION["success"] = $stmt->rowCount() > 0
    ? "Student removed from the course."
    : "Enrollment not found.";

redirect($back);
