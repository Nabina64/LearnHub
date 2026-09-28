<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Student Only */

require_role("student", "../index.php");

$pageTitle = "My Courses - LearnHub";

$student_id = $_SESSION["user_id"];


/* Courses I am enrolled in */

$stmt = $pdo->prepare(
    "SELECT
        courses.id,
        courses.title,
        courses.description,
        courses.category,
        courses.thumbnail,
        enrollments.enrolled_at,
        users.name AS teacher_name,

        (SELECT COUNT(*)
         FROM lessons
         WHERE lessons.course_id = courses.id
         AND lessons.deleted_at IS NULL) AS lesson_count,

        (SELECT COUNT(*)
         FROM quizzes
         WHERE quizzes.course_id = courses.id) AS quiz_count

     FROM enrollments
     INNER JOIN courses
        ON enrollments.course_id = courses.id
     LEFT JOIN users
        ON courses.teacher_id = users.id
     WHERE enrollments.student_id = ?
     AND courses.deleted_at IS NULL
     ORDER BY enrollments.enrolled_at DESC, enrollments.id DESC"
);

$stmt->execute([$student_id]);

$courses = $stmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                My Courses
            </h2>

            <p class="text-muted mb-0">
                All the courses you are enrolled in.
            </p>

        </div>

        <a
            href="courses.php"
            class="btn btn-outline-primary"
        >
            Browse Courses
        </a>

    </div>


    <?php if (count($courses) > 0): ?>

        <div class="row g-4 mb-5">

            <?php foreach ($courses as $course): ?>

                <div class="col-md-6 col-lg-4">

                    <div class="card course-card shadow-sm h-100">

                        <?php if (!empty($course["thumbnail"])): ?>

                            <img
                                src="../uploads/courses/<?php echo htmlspecialchars($course["thumbnail"]); ?>"
                                class="card-img-top"
                                style="height: 180px; object-fit: cover;"
                                alt="Course Thumbnail"
                            >

                        <?php else: ?>

                            <div
                                class="bg-secondary text-white d-flex align-items-center justify-content-center"
                                style="height: 180px; font-size: 50px;"
                            >
                                📚
                            </div>

                        <?php endif; ?>


                        <div class="card-body d-flex flex-column">

                            <span class="badge bg-primary align-self-start mb-2">
                                <?php echo htmlspecialchars($course["category"]); ?>
                            </span>

                            <h5 class="card-title fw-bold">
                                <?php echo htmlspecialchars($course["title"]); ?>
                            </h5>

                            <p class="text-muted mb-2">
                                Teacher:
                                <?php echo htmlspecialchars($course["teacher_name"] ?? "Unknown"); ?>
                            </p>

                            <!-- Lessons / Quizzes -->

                            <div class="d-flex text-center border rounded py-2 mb-2">

                                <div class="flex-fill">
                                    <strong><?php echo (int) $course["lesson_count"]; ?></strong>
                                    <br>
                                    <small class="text-muted">Lessons</small>
                                </div>

                                <div class="flex-fill border-start">
                                    <strong><?php echo (int) $course["quiz_count"]; ?></strong>
                                    <br>
                                    <small class="text-muted">Quizzes</small>
                                </div>

                            </div>

                            <small class="text-muted mb-3">
                                Enrolled:
                                <?php
                                echo !empty($course["enrolled_at"])
                                    ? date("M d, Y", strtotime($course["enrolled_at"]))
                                    : "-";
                                ?>
                            </small>

                            <div class="mt-auto d-flex gap-2">

                                <a
                                    href="lesson.php?course_id=<?php echo $course["id"]; ?>"
                                    class="btn btn-success flex-fill"
                                >
                                    Continue Learning
                                </a>

                                <a
                                    href="course-details.php?id=<?php echo $course["id"]; ?>"
                                    class="btn btn-outline-primary"
                                >
                                    Details
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="alert alert-info">

            You haven't enrolled in any course yet.

            <a href="courses.php">
                Browse Courses
            </a>

        </div>

    <?php endif; ?>

</div>

<?php require_once "../includes/footer.php"; ?>
