<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";

// Only teacher can access this page
require_role("teacher", "../student/dashboard.php");

$pageTitle = "Teacher Dashboard - LearnHub";

$teacher_id = $_SESSION["user_id"];


/* Quick Stats */

$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total
     FROM courses
     WHERE teacher_id = ?
     AND deleted_at IS NULL"
);

$stmt->execute([$teacher_id]);

$my_courses_count = $stmt->fetch()["total"];


$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total
     FROM course_views
     INNER JOIN courses
        ON course_views.course_id = courses.id
     WHERE courses.teacher_id = ?"
);

$stmt->execute([$teacher_id]);

$my_views_count = $stmt->fetch()["total"];


$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT enrollments.student_id) AS total
     FROM enrollments
     INNER JOIN courses
        ON enrollments.course_id = courses.id
     WHERE courses.teacher_id = ?"
);

$stmt->execute([$teacher_id]);

$my_students_count = $stmt->fetch()["total"];


/* Most Viewed Course */

$stmt = $pdo->prepare(
    "SELECT
        courses.title,
        COUNT(course_views.id) AS total_views
     FROM courses
     LEFT JOIN course_views
        ON course_views.course_id = courses.id
     WHERE courses.teacher_id = ?
     GROUP BY courses.id, courses.title
     ORDER BY total_views DESC
     LIMIT 1"
);

$stmt->execute([$teacher_id]);

$top_course = $stmt->fetch();


require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>

<div class="container py-5">

    <!-- Welcome -->

    <div class="mb-5">

        <h1 class="fw-bold">
            Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?> 👋
        </h1>

        <p class="text-muted">
            Manage your courses and learning content from here.
        </p>

    </div>


    <!-- Quick Stats -->

    <div class="row g-4 mb-5">


        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        My Courses
                    </h6>

                    <h2 class="fw-bold text-primary">
                        <?php echo $my_courses_count; ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Course Views
                    </h6>

                    <h2 class="fw-bold text-success">
                        <?php echo $my_views_count; ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        My Students
                    </h6>

                    <h2 class="fw-bold text-danger">
                        <?php echo $my_students_count; ?>
                    </h2>

                </div>

            </div>

        </div>

    </div>


    <!-- Most Viewed Course -->

    <?php if ($top_course && $top_course["total_views"] > 0): ?>

        <div class="alert alert-primary d-flex justify-content-between align-items-center flex-wrap gap-2 mb-5">

            <div>

                <strong>
                    &#127942; Most viewed course:
                </strong>

                <?php echo htmlspecialchars($top_course["title"]); ?>

                &mdash;

                <?php echo $top_course["total_views"]; ?>
                views

            </div>

            <a
                href="analytics.php"
                class="btn btn-sm btn-primary"
            >
                View Analytics
            </a>

        </div>

    <?php endif; ?>


    <!-- Dashboard Cards -->

    <div class="row g-4">


        <!-- My Courses -->

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <div class="fs-1">
                        📚
                    </div>

                    <h4 class="fw-bold mt-3">
                        My Courses
                    </h4>

                    <p class="text-muted">
                        Create and manage your courses.
                    </p>

                    <a
                        href="courses/index.php"
                        class="btn btn-primary"
                    >
                        Manage Courses
                    </a>

                </div>

            </div>

        </div>


        <!-- Lessons -->

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <div class="fs-1">
                        🎥
                    </div>

                    <h4 class="fw-bold mt-3">
                        Lessons
                    </h4>

                    <p class="text-muted">
                        Add and manage lessons for your courses.
                    </p>

                    <a
                        href="lessons/index.php"
                        class="btn btn-outline-primary"
                    >
                        Manage Lessons
                    </a>

                </div>

            </div>

        </div>


        <!-- Quizzes -->

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <div class="fs-1">
                        📝
                    </div>

                    <h4 class="fw-bold mt-3">
                        Quizzes
                    </h4>

                    <p class="text-muted">
                        Create quizzes and questions for students.
                    </p>

                    <a
                        href="quizzes/index.php"
                        class="btn btn-outline-primary"
                    >
                        Manage Quizzes
                    </a>

                </div>

            </div>

        </div>


        <!-- Analytics -->

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <div class="fs-1">
                        &#128200;
                    </div>

                    <h4 class="fw-bold mt-3">
                        Analytics
                    </h4>

                    <p class="text-muted">
                        See how many views each of your courses got.
                    </p>

                    <a
                        href="analytics.php"
                        class="btn btn-outline-primary"
                    >
                        View Analytics
                    </a>

                </div>

            </div>

        </div>


        <!-- My Students -->

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <div class="fs-1">
                        &#128101;
                    </div>

                    <h4 class="fw-bold mt-3">
                        My Students
                    </h4>

                    <p class="text-muted">
                        Students enrolled in your courses.
                    </p>

                    <a
                        href="students.php"
                        class="btn btn-outline-primary"
                    >
                        View Students
                    </a>

                </div>

            </div>

        </div>

    </div>


    <!-- Quick Actions -->

    <div class="card border-0 shadow-sm mt-5">

        <div class="card-body p-4">

            <h4 class="fw-bold mb-4">
                Quick Actions
            </h4>

            <div class="d-flex flex-wrap gap-2">

                <a
                    href="courses/create.php"
                    class="btn btn-primary"
                >
                    + Create Course
                </a>

                <a
                    href="students.php"
                    class="btn btn-outline-primary"
                >
                    My Students
                </a>

                <a
                    href="profile.php"
                    class="btn btn-outline-secondary"
                >
                    Profile
                </a>

                <a
                    href="settings.php"
                    class="btn btn-outline-secondary"
                >
                    Settings
                </a>

                <a
                    href="../auth/logout.php"
                    class="btn btn-outline-danger"
                >
                    Logout
                </a>

            </div>

        </div>

    </div>

</div>


<?php

require_once "../includes/footer.php";

?>