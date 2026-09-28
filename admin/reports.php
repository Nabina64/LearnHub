<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Admin Only */

require_role("admin", "../index.php");

$pageTitle = "Reports - LearnHub";

$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};

$one = function ($sql) use ($pdo) {
    return $pdo->query($sql)->fetchColumn();
};


/* -----------------------------------------------------
   Overview numbers
----------------------------------------------------- */

$users_total = (int) $one("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL");
$users_new = (int) $one(
    "SELECT COUNT(*) FROM users
     WHERE deleted_at IS NULL
     AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
);

$courses_total = (int) $one("SELECT COUNT(*) FROM courses WHERE deleted_at IS NULL");
$courses_new = (int) $one(
    "SELECT COUNT(*) FROM courses
     WHERE deleted_at IS NULL
     AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
);

$enroll_total = (int) $one("SELECT COUNT(*) FROM enrollments");
$enroll_new = (int) $one(
    "SELECT COUNT(*) FROM enrollments
     WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
);

$attempts_total = (int) $one("SELECT COUNT(*) FROM quiz_results");
$attempts_avg = $one(
    "SELECT AVG(score / NULLIF(total_questions, 0)) * 100 FROM quiz_results"
);


/* Users by role / teacher status */

$roles = ["student" => 0, "teacher" => 0, "admin" => 0];

foreach ($pdo->query(
    "SELECT role, COUNT(*) AS total
     FROM users
     WHERE deleted_at IS NULL
     GROUP BY role"
) as $row) {

    $roles[$row["role"]] = (int) $row["total"];
}

$teacher_status = ["approved" => 0, "pending" => 0, "rejected" => 0];

foreach ($pdo->query(
    "SELECT status, COUNT(*) AS total
     FROM users
     WHERE role = 'teacher'
     AND deleted_at IS NULL
     GROUP BY status"
) as $row) {

    $teacher_status[$row["status"]] = (int) $row["total"];
}


/* Last 6 months: new users and enrollments */

$months = [];

for ($i = 5; $i >= 0; $i--) {

    $key = date("Y-m", strtotime("first day of -$i month"));

    $months[$key] = [
        "label" => date("M Y", strtotime($key . "-01")),
        "users" => 0,
        "enrollments" => 0,
    ];
}

$first_month = array_key_first($months) . "-01";

$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total
     FROM users
     WHERE deleted_at IS NULL
     AND created_at >= ?
     GROUP BY ym"
);

$stmt->execute([$first_month]);

foreach ($stmt->fetchAll() as $row) {

    if (isset($months[$row["ym"]])) {
        $months[$row["ym"]]["users"] = (int) $row["total"];
    }
}

$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(enrolled_at, '%Y-%m') AS ym, COUNT(*) AS total
     FROM enrollments
     WHERE enrolled_at >= ?
     GROUP BY ym"
);

$stmt->execute([$first_month]);

foreach ($stmt->fetchAll() as $row) {

    if (isset($months[$row["ym"]])) {
        $months[$row["ym"]]["enrollments"] = (int) $row["total"];
    }
}

$max_users = max(1, ...array_column($months, "users"));
$max_enroll = max(1, ...array_column($months, "enrollments"));


/* Top 5 courses by enrollments */

$top_courses = $pdo->query(
    "SELECT
        courses.id,
        courses.title,
        users.name AS teacher_name,
        (SELECT COUNT(*) FROM enrollments
         WHERE enrollments.course_id = courses.id) AS enrollments,
        (SELECT COUNT(*) FROM lessons
         WHERE lessons.course_id = courses.id
         AND lessons.deleted_at IS NULL) AS lessons,
        (SELECT COUNT(*) FROM quizzes
         WHERE quizzes.course_id = courses.id) AS quizzes
     FROM courses
     LEFT JOIN users ON courses.teacher_id = users.id
     WHERE courses.deleted_at IS NULL
     ORDER BY enrollments DESC, courses.id ASC
     LIMIT 5"
)->fetchAll();

$max_top = max(1, ...array_map(function ($c) {
    return (int) $c["enrollments"];
}, $top_courses ?: [["enrollments" => 1]]));


/* Teachers */

$teachers = $pdo->query(
    "SELECT
        users.id,
        users.name,
        users.email,
        (SELECT COUNT(*) FROM courses
         WHERE courses.teacher_id = users.id
         AND courses.deleted_at IS NULL) AS courses,
        (SELECT COUNT(*) FROM lessons
         INNER JOIN courses ON lessons.course_id = courses.id
         WHERE courses.teacher_id = users.id
         AND courses.deleted_at IS NULL
         AND lessons.deleted_at IS NULL) AS lessons,
        (SELECT COUNT(DISTINCT enrollments.student_id) FROM enrollments
         INNER JOIN courses ON enrollments.course_id = courses.id
         WHERE courses.teacher_id = users.id
         AND courses.deleted_at IS NULL) AS students
     FROM users
     WHERE users.role = 'teacher'
     AND users.status = 'approved'
     AND users.deleted_at IS NULL
     ORDER BY students DESC, courses DESC, users.id ASC
     LIMIT 10"
)->fetchAll();


/* Quiz performance per course */

$quiz_stats = $pdo->query(
    "SELECT
        courses.id,
        courses.title,
        COUNT(DISTINCT quizzes.id) AS quizzes,
        COUNT(quiz_results.id) AS attempts,
        AVG(quiz_results.score
            / NULLIF(quiz_results.total_questions, 0)) * 100 AS average
     FROM courses
     INNER JOIN quizzes ON quizzes.course_id = courses.id
     LEFT JOIN quiz_results ON quiz_results.quiz_id = quizzes.id
     WHERE courses.deleted_at IS NULL
     GROUP BY courses.id, courses.title
     ORDER BY attempts DESC, courses.id ASC
     LIMIT 10"
)->fetchAll();


require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <!-- Success / Error -->

    <?php if (isset($_SESSION["success"])): ?>

        <div class="alert alert-success">

            <?php
            echo $h($_SESSION["success"]);
            unset($_SESSION["success"]);
            ?>

        </div>

    <?php endif; ?>

    <?php if (isset($_SESSION["error"])): ?>

        <div class="alert alert-danger">

            <?php
            echo $h($_SESSION["error"]);
            unset($_SESSION["error"]);
            ?>

        </div>

    <?php endif; ?>


    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                Reports
            </h2>

            <p class="text-muted mb-0">
                A summary of what is happening on LearnHub.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="btn btn-outline-secondary"
        >
            &larr; Dashboard
        </a>

    </div>


    <!-- Overview -->

    <div class="row g-4 mb-5">

        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Users</h6>
                    <h2 class="fw-bold text-primary"><?php echo $users_total; ?></h2>
                    <small class="text-muted">+<?php echo $users_new; ?> in last 30 days</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Courses</h6>
                    <h2 class="fw-bold text-success"><?php echo $courses_total; ?></h2>
                    <small class="text-muted">+<?php echo $courses_new; ?> in last 30 days</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Enrollments</h6>
                    <h2 class="fw-bold text-warning"><?php echo $enroll_total; ?></h2>
                    <small class="text-muted">+<?php echo $enroll_new; ?> in last 30 days</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Quiz Attempts</h6>
                    <h2 class="fw-bold text-danger"><?php echo $attempts_total; ?></h2>
                    <small class="text-muted">
                        Average score:
                        <?php
                        echo $attempts_avg !== null
                            ? number_format((float) $attempts_avg, 1) . "%"
                            : "-";
                        ?>
                    </small>
                </div>
            </div>
        </div>

    </div>


    <!-- Growth + Users -->

    <div class="row g-4 mb-5">

        <div class="col-lg-8">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h5 class="fw-bold">
                        Last 6 Months
                    </h5>

                    <p class="text-muted">
                        New users and new enrollments each month.
                    </p>

                    <div class="table-responsive">

                        <table class="table align-middle mb-0">

                            <thead>
                                <tr>
                                    <th style="width: 110px;">Month</th>
                                    <th>New Users</th>
                                    <th>Enrollments</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($months as $month): ?>

                                    <tr>

                                        <td class="fw-semibold">
                                            <?php echo $h($month["label"]); ?>
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 10px;">
                                                    <div
                                                        class="progress-bar bg-primary"
                                                        style="width: <?php echo round($month["users"] / $max_users * 100); ?>%"
                                                    ></div>
                                                </div>
                                                <strong style="min-width: 28px;">
                                                    <?php echo $month["users"]; ?>
                                                </strong>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 10px;">
                                                    <div
                                                        class="progress-bar bg-success"
                                                        style="width: <?php echo round($month["enrollments"] / $max_enroll * 100); ?>%"
                                                    ></div>
                                                </div>
                                                <strong style="min-width: 28px;">
                                                    <?php echo $month["enrollments"]; ?>
                                                </strong>
                                            </div>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-lg-4">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h5 class="fw-bold">
                        Users By Role
                    </h5>

                    <p class="text-muted">
                        Active accounts.
                    </p>

                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Students</span>
                        <span class="badge bg-primary"><?php echo $roles["student"]; ?></span>
                    </div>

                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Teachers</span>
                        <span class="badge bg-warning text-dark"><?php echo $roles["teacher"]; ?></span>
                    </div>

                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Admins</span>
                        <span class="badge bg-danger"><?php echo $roles["admin"]; ?></span>
                    </div>

                    <h6 class="fw-bold mt-4">Teacher Accounts</h6>

                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Approved</span>
                        <span class="badge bg-success"><?php echo $teacher_status["approved"]; ?></span>
                    </div>

                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Pending</span>
                        <a href="teacher-requests.php" class="badge bg-warning text-dark text-decoration-none">
                            <?php echo $teacher_status["pending"]; ?>
                        </a>
                    </div>

                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Rejected</span>
                        <span class="badge bg-secondary"><?php echo $teacher_status["rejected"]; ?></span>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Top Courses -->

    <div class="mb-3">

        <h3 class="fw-bold">
            Top Courses
        </h3>

        <p class="text-muted">
            Courses with the most enrollments.
        </p>

    </div>

    <div class="card shadow-sm border-0 mb-5">

        <div class="card-body">

            <?php if (count($top_courses) > 0): ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Course</th>
                                <th>Teacher</th>
                                <th style="min-width: 180px;">Enrollments</th>
                                <th>Lessons</th>
                                <th>Quizzes</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($top_courses as $index => $course): ?>

                                <tr>

                                    <td><?php echo $index + 1; ?></td>

                                    <td>
                                        <a
                                            href="course-view.php?id=<?php echo (int) $course["id"]; ?>"
                                            class="fw-bold text-decoration-none"
                                        >
                                            <?php echo $h($course["title"]); ?>
                                        </a>
                                    </td>

                                    <td><?php echo $h($course["teacher_name"] ?? "Unknown"); ?></td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 10px;">
                                                <div
                                                    class="progress-bar bg-warning"
                                                    style="width: <?php echo round($course["enrollments"] / $max_top * 100); ?>%"
                                                ></div>
                                            </div>
                                            <strong style="min-width: 28px;">
                                                <?php echo (int) $course["enrollments"]; ?>
                                            </strong>
                                        </div>
                                    </td>

                                    <td><?php echo (int) $course["lessons"]; ?></td>

                                    <td><?php echo (int) $course["quizzes"]; ?></td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p class="text-muted mb-0">No courses yet.</p>

            <?php endif; ?>

        </div>

    </div>


    <!-- Teachers -->

    <div class="mb-3">

        <h3 class="fw-bold">
            Teachers
        </h3>

        <p class="text-muted">
            Approved teachers and what they have created.
        </p>

    </div>

    <div class="card shadow-sm border-0 mb-5">

        <div class="card-body">

            <?php if (count($teachers) > 0): ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-dark">
                            <tr>
                                <th>Teacher</th>
                                <th>Email</th>
                                <th>Courses</th>
                                <th>Lessons</th>
                                <th>Students</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($teachers as $teacher): ?>

                                <tr>
                                    <td><strong><?php echo $h($teacher["name"]); ?></strong></td>
                                    <td><?php echo $h($teacher["email"]); ?></td>
                                    <td><?php echo (int) $teacher["courses"]; ?></td>
                                    <td><?php echo (int) $teacher["lessons"]; ?></td>
                                    <td><?php echo (int) $teacher["students"]; ?></td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p class="text-muted mb-0">No approved teachers yet.</p>

            <?php endif; ?>

        </div>

    </div>


    <!-- Quiz performance -->

    <div class="mb-3">

        <h3 class="fw-bold">
            Quiz Performance
        </h3>

        <p class="text-muted">
            How students are doing in each course quiz.
        </p>

    </div>

    <div class="card shadow-sm border-0 mb-5">

        <div class="card-body">

            <?php if (count($quiz_stats) > 0): ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-dark">
                            <tr>
                                <th>Course</th>
                                <th>Quizzes</th>
                                <th>Attempts</th>
                                <th>Average Score</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($quiz_stats as $row): ?>

                                <tr>

                                    <td>
                                        <a
                                            href="course-view.php?id=<?php echo (int) $row["id"]; ?>"
                                            class="fw-bold text-decoration-none"
                                        >
                                            <?php echo $h($row["title"]); ?>
                                        </a>
                                    </td>

                                    <td><?php echo (int) $row["quizzes"]; ?></td>

                                    <td><?php echo (int) $row["attempts"]; ?></td>

                                    <td>
                                        <?php
                                        echo $row["average"] !== null
                                            ? number_format((float) $row["average"], 1) . "%"
                                            : "-";
                                        ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p class="text-muted mb-0">No quizzes have been created yet.</p>

            <?php endif; ?>

        </div>

    </div>


    <!-- Export -->

    <div class="mb-3">

        <h3 class="fw-bold">
            Export Data
        </h3>

        <p class="text-muted">
            Download a CSV file that opens in Excel or Google Sheets.
        </p>

    </div>

    <div class="d-flex gap-2 flex-wrap">

        <a
            href="export.php?type=users"
            class="btn btn-outline-primary"
        >
            Users CSV
        </a>

        <a
            href="export.php?type=courses"
            class="btn btn-outline-primary"
        >
            Courses CSV
        </a>

        <a
            href="export.php?type=enrollments"
            class="btn btn-outline-primary"
        >
            Enrollments CSV
        </a>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
