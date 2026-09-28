<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Only teacher can access */

require_role("teacher", "../student/dashboard.php");


$pageTitle = "Course Analytics - LearnHub";

$teacher_id = $_SESSION["user_id"];


/* Views + Students For Each Course */

$stmt = $pdo->prepare(
    "SELECT
        courses.id,
        courses.title,
        courses.category,
        courses.thumbnail,
        courses.created_at,

        (
            SELECT COUNT(*)
            FROM course_views
            WHERE course_views.course_id = courses.id
        ) AS total_views,

        (
            SELECT COUNT(DISTINCT user_id)
            FROM course_views
            WHERE course_views.course_id = courses.id
            AND user_id IS NOT NULL
        ) AS unique_viewers,

        (
            SELECT COUNT(*)
            FROM course_views
            WHERE course_views.course_id = courses.id
            AND viewed_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ) AS views_this_week,

        (
            SELECT COUNT(*)
            FROM enrollments
            WHERE enrollments.course_id = courses.id
        ) AS total_students

     FROM courses
     WHERE courses.teacher_id = ?
     ORDER BY total_views DESC, courses.created_at DESC"
);

$stmt->execute([$teacher_id]);

$courses = $stmt->fetchAll();


/* Totals */

$total_views = 0;
$total_students = 0;
$views_this_week = 0;

foreach ($courses as $course) {

    $total_views += $course["total_views"];
    $total_students += $course["total_students"];
    $views_this_week += $course["views_this_week"];
}


/* Most Viewed Course (list is already sorted by views) */

$top_course = null;

if (count($courses) > 0 && $courses[0]["total_views"] > 0) {

    $top_course = $courses[0];
}


/* Highest View Number - used for progress bar width */

$max_views = $top_course
    ? $top_course["total_views"]
    : 0;


require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>


<div class="container py-5">


    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="fw-bold">
                Course Analytics
            </h1>

            <p class="text-muted mb-0">
                See how many people viewed your courses.
            </p>

        </div>


        <a
            href="dashboard.php"
            class="btn btn-outline-secondary"
        >
            &larr; Dashboard
        </a>

    </div>



    <?php if (count($courses) === 0): ?>


        <div class="card border-0 shadow-sm">

            <div class="card-body text-center p-5">

                <div class="fs-1">
                    &#128200;
                </div>

                <h4 class="fw-bold mt-3">
                    No Data Yet
                </h4>

                <p class="text-muted">
                    Create a course first, then analytics will
                    show up here.
                </p>

                <a
                    href="courses/create.php"
                    class="btn btn-primary"
                >
                    Create Your First Course
                </a>

            </div>

        </div>


    <?php else: ?>


        <!-- Summary Cards -->

        <div class="row g-4 mb-5">


            <div class="col-md-6 col-lg-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Total Courses
                        </h6>

                        <h2 class="fw-bold text-primary">
                            <?php echo count($courses); ?>
                        </h2>

                    </div>

                </div>

            </div>


            <div class="col-md-6 col-lg-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Total Views
                        </h6>

                        <h2 class="fw-bold text-success">
                            <?php echo $total_views; ?>
                        </h2>

                    </div>

                </div>

            </div>


            <div class="col-md-6 col-lg-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Views This Week
                        </h6>

                        <h2 class="fw-bold text-warning">
                            <?php echo $views_this_week; ?>
                        </h2>

                    </div>

                </div>

            </div>


            <div class="col-md-6 col-lg-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Total Students
                        </h6>

                        <h2 class="fw-bold text-danger">
                            <?php echo $total_students; ?>
                        </h2>

                    </div>

                </div>

            </div>

        </div>



        <!-- Most Viewed Course -->

        <?php if ($top_course): ?>

            <div class="card border-0 shadow-sm mb-5 bg-primary text-white">

                <div class="card-body p-4">

                    <h6 class="text-uppercase mb-2">
                        &#127942; Most Viewed Course
                    </h6>

                    <h3 class="fw-bold">

                        <?php
                        echo htmlspecialchars($top_course["title"]);
                        ?>

                    </h3>

                    <p class="mb-0">

                        <?php echo $top_course["total_views"]; ?>
                        total views &middot;

                        <?php echo $top_course["unique_viewers"]; ?>
                        unique viewers &middot;

                        <?php echo $top_course["total_students"]; ?>
                        enrolled students

                    </p>

                </div>

            </div>

        <?php else: ?>

            <div class="alert alert-info">

                Your courses have not received any views yet.

            </div>

        <?php endif; ?>



        <!-- Views Table -->

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <h5 class="fw-bold mb-3">
                    Views By Course
                </h5>


                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-dark">

                            <tr>

                                <th>#</th>

                                <th>Course</th>

                                <th>Total Views</th>

                                <th>Unique Viewers</th>

                                <th>This Week</th>

                                <th>Students</th>

                                <th style="min-width: 160px;">
                                    Popularity
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php $rank = 1; ?>

                            <?php foreach ($courses as $course): ?>

                                <tr>

                                    <td>
                                        <?php echo $rank; ?>
                                    </td>


                                    <td>

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $course["title"]
                                            );
                                            ?>

                                        </strong>

                                        <br>

                                        <span class="badge bg-secondary">

                                            <?php
                                            echo htmlspecialchars(
                                                $course["category"]
                                            );
                                            ?>

                                        </span>

                                        <?php if ($rank === 1 && $course["total_views"] > 0): ?>

                                            <span class="badge bg-success">
                                                Top
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <strong class="fs-5">
                                            <?php echo $course["total_views"]; ?>
                                        </strong>

                                    </td>


                                    <td>
                                        <?php echo $course["unique_viewers"]; ?>
                                    </td>


                                    <td>
                                        <?php echo $course["views_this_week"]; ?>
                                    </td>


                                    <td>
                                        <?php echo $course["total_students"]; ?>
                                    </td>


                                    <td>

                                        <?php

                                        $percent = ($max_views > 0)
                                            ? round(
                                                ($course["total_views"]
                                                    / $max_views) * 100
                                            )
                                            : 0;

                                        ?>

                                        <div class="progress" style="height: 10px;">

                                            <div
                                                class="progress-bar bg-primary"
                                                style="width: <?php echo $percent; ?>%;"
                                            ></div>

                                        </div>

                                    </td>

                                </tr>

                                <?php $rank++; ?>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


    <?php endif; ?>


</div>


<?php require_once "../includes/footer.php"; ?>
