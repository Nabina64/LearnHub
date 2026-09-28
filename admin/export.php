<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Admin Only */

require_role("admin", "../index.php");

/**
 * CSV download for the admin.
 *
 *   export.php?type=users
 *   export.php?type=courses
 *   export.php?type=enrollments
 */

$type = $_GET["type"] ?? "";

/* Stops Excel from running a cell that starts with = + - @ */

$safe = function ($value) {

    $value = (string) $value;

    if ($value !== "" && strpos("=+-@\t\r", $value[0]) !== false) {
        return "'" . $value;
    }

    return $value;
};

switch ($type) {

    case "users":

        $headers = ["ID", "Name", "Email", "Role", "Status", "Registered"];

        $stmt = $pdo->query(
            "SELECT id, name, email, role, status, created_at
             FROM users
             WHERE deleted_at IS NULL
             ORDER BY id ASC"
        );

        break;


    case "courses":

        $headers = [
            "ID", "Title", "Category", "Teacher", "Lessons",
            "Quizzes", "Enrollments", "Status", "Created"
        ];

        $stmt = $pdo->query(
            "SELECT
                courses.id,
                courses.title,
                courses.category,
                users.name AS teacher,
                (SELECT COUNT(*) FROM lessons
                 WHERE lessons.course_id = courses.id
                 AND lessons.deleted_at IS NULL) AS lessons,
                (SELECT COUNT(*) FROM quizzes
                 WHERE quizzes.course_id = courses.id) AS quizzes,
                (SELECT COUNT(*) FROM enrollments
                 WHERE enrollments.course_id = courses.id) AS enrollments,
                CASE WHEN courses.deleted_at IS NULL
                     THEN 'Active' ELSE 'In Trash' END AS status,
                courses.created_at
             FROM courses
             LEFT JOIN users ON courses.teacher_id = users.id
             ORDER BY courses.id ASC"
        );

        break;


    case "enrollments":

        $headers = [
            "ID", "Student", "Student Email", "Course",
            "Teacher", "Enrolled On"
        ];

        $stmt = $pdo->query(
            "SELECT
                enrollments.id,
                students.name AS student,
                students.email AS student_email,
                courses.title AS course,
                teachers.name AS teacher,
                enrollments.enrolled_at
             FROM enrollments
             INNER JOIN courses
                ON enrollments.course_id = courses.id
             LEFT JOIN users AS students
                ON enrollments.student_id = students.id
             LEFT JOIN users AS teachers
                ON courses.teacher_id = teachers.id
             ORDER BY enrollments.id ASC"
        );

        break;


    default:

        $_SESSION["error"] = "Unknown export type.";

        redirect("reports.php");
}

$filename = "learnhub-" . $type . "-" . date("Y-m-d") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Pragma: no-cache");

$out = fopen("php://output", "w");

/* BOM so Excel shows Nepali / unicode text correctly */

fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, $headers);

while ($row = $stmt->fetch(PDO::FETCH_NUM)) {

    fputcsv($out, array_map($safe, $row));
}

fclose($out);

exit;
