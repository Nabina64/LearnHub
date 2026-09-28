<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Only students can enroll */
require_role("student", "../index.php");

/* Only POST request allowed */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    redirect("courses.php");
}

if (!csrf_valid()) {

    redirect("courses.php");
}

$student_id = $_SESSION["user_id"];

$course_id = intval($_POST["course_id"] ?? 0);

if ($course_id <= 0) {

    redirect("courses.php");
}


/* Check whether course exists */
$stmt = $pdo->prepare(
    "SELECT id
     FROM courses
     WHERE id = ?
     AND deleted_at IS NULL"
);

$stmt->execute([$course_id]);

$course = $stmt->fetch();

if (!$course) {

    redirect("courses.php");
}


/* Check existing enrollment */
$stmt = $pdo->prepare(
    "SELECT id
     FROM enrollments
     WHERE student_id = ?
     AND course_id = ?"
);

$stmt->execute([
    $student_id,
    $course_id
]);

$existing = $stmt->fetch();


if (!$existing) {

    /* Enroll Student */

    $stmt = $pdo->prepare(
        "INSERT INTO enrollments
        (student_id, course_id)
        VALUES (?, ?)"
    );

    $stmt->execute([
        $student_id,
        $course_id
    ]);

    $_SESSION["success"] = "Successfully enrolled in the course!";
}

redirect("course-details.php?id=" . $course_id);