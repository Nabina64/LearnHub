<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Student Only - admin / teacher are sent to their own course pages */

require_role(
    "student",
    $_SESSION["user_role"] === "admin"
        ? "../admin/course-view.php?id=" . intval($_GET["id"] ?? 0)
        : "../teacher/courses/index.php"
);

$pageTitle = "Course Details - LearnHub";

$course_id = intval($_GET["id"] ?? 0);

if ($course_id <= 0) {
    redirect("courses.php");
}


/* Get Course Details */

$stmt = $pdo->prepare(
    "SELECT
        courses.*,
        users.name AS teacher_name
     FROM courses
     LEFT JOIN users
        ON courses.teacher_id = users.id
     WHERE courses.id = ?
     AND courses.deleted_at IS NULL"
);

$stmt->execute([$course_id]);

$course = $stmt->fetch();

if (!$course) {
    redirect("courses.php");
}


/* Record Course View */

record_course_view(
    $pdo,
    $course_id,
    $_SESSION["user_id"] ?? null
);


/* Check Enrollment */

$stmt = $pdo->prepare(
    "SELECT id
     FROM enrollments
     WHERE student_id = ?
     AND course_id = ?"
);

$stmt->execute([
    $_SESSION["user_id"],
    $course_id
]);

$is_enrolled = $stmt->fetch();


/* Count Lessons */

$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total_lessons
     FROM lessons
     WHERE course_id = ?
     AND deleted_at IS NULL"
);

$stmt->execute([$course_id]);

$lesson_data = $stmt->fetch();

$total_lessons = $lesson_data["total_lessons"];


/* Get Quizzes */

$stmt = $pdo->prepare(
    "SELECT
        quizzes.id,
        quizzes.title,
        (
            SELECT COUNT(*)
            FROM questions
            WHERE questions.quiz_id = quizzes.id
        ) AS question_count
     FROM quizzes
     WHERE quizzes.course_id = ?
     ORDER BY quizzes.id ASC"
);

$stmt->execute([$course_id]);

$quizzes = $stmt->fetchAll();


require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>

<div class="container py-5">

    <!-- Flash messages (enroll success / "please enroll first") -->

    <?php if (isset($_SESSION["success"])): ?>

        <div class="alert alert-success">

            <?php
            echo htmlspecialchars($_SESSION["success"]);
            unset($_SESSION["success"]);
            ?>

        </div>

    <?php endif; ?>


    <?php if (isset($_SESSION["error"])): ?>

        <div class="alert alert-warning">

            <?php
            echo htmlspecialchars($_SESSION["error"]);
            unset($_SESSION["error"]);
            ?>

        </div>

    <?php endif; ?>


    <div class="row">

        <!-- Course Image -->

        <div class="col-md-5 mb-4">

            <?php if (!empty($course["thumbnail"])): ?>

                <img
                    src="../uploads/courses/<?php echo htmlspecialchars($course["thumbnail"]); ?>"
                    class="img-fluid rounded shadow"
                    alt="Course Thumbnail"
                >

            <?php else: ?>

                <div
                    class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                    style="height: 300px; font-size: 70px;"
                >
                    📚
                </div>

            <?php endif; ?>

        </div>


        <!-- Course Information -->

        <div class="col-md-7">

            <span class="badge bg-primary mb-2">

                <?php echo htmlspecialchars($course["category"]); ?>

            </span>


            <h1 class="fw-bold">

                <?php echo htmlspecialchars($course["title"]); ?>

            </h1>


            <p class="text-muted">

                Teacher:

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $course["teacher_name"] ?? "Unknown"
                    );
                    ?>

                </strong>

            </p>


            <hr>


            <h5>
                About This Course
            </h5>


            <p>

                <?php
                echo nl2br(
                    htmlspecialchars(
                        $course["description"]
                    )
                );
                ?>

            </p>


            <p>

                <strong>
                    Lessons:
                </strong>

                <?php echo $total_lessons; ?>

            </p>


            <!-- Enrollment -->

            <?php if ($is_enrolled): ?>

                <div class="alert alert-success">

                    ✅ You are already enrolled in this course.

                </div>


                <?php if ($total_lessons > 0): ?>

                    <a
                        href="lesson.php?course_id=<?php echo $course_id; ?>"
                        class="btn btn-success"
                    >
                        Start Learning
                    </a>

                <?php endif; ?>


            <?php else: ?>

                <form
                    action="enroll.php"
                    method="POST"
                    class="mb-3"
                >

                    <?php echo csrf_field(); ?>

                    <input
                        type="hidden"
                        name="course_id"
                        value="<?php echo $course_id; ?>"
                    >


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Enroll Now
                    </button>

                </form>

            <?php endif; ?>


            <a
                href="courses.php"
                class="btn btn-outline-secondary"
            >
                ← Back to Courses
            </a>

        </div>

    </div>


    <!-- Quizzes -->

    <?php if ($is_enrolled && count($quizzes) > 0): ?>

        <div class="mt-5">

            <h3 class="fw-bold mb-3">
                Course Quizzes
            </h3>


            <div class="row">

                <?php foreach ($quizzes as $quiz): ?>

                    <div class="col-md-6 mb-3">

                        <div class="card shadow-sm h-100">

                            <div class="card-body">

                                <h5 class="fw-bold">

                                    <?php
                                    echo htmlspecialchars(
                                        $quiz["title"]
                                    );
                                    ?>

                                </h5>


                                <p class="text-muted mb-3">

                                    Questions:
                                    <?php echo $quiz["question_count"]; ?>

                                </p>


                                <?php if ($quiz["question_count"] > 0): ?>

                                    <a
                                        href="quiz.php?quiz_id=<?php echo $quiz["id"]; ?>"
                                        class="btn btn-primary"
                                    >
                                        Take Quiz
                                    </a>

                                <?php else: ?>

                                    <button
                                        class="btn btn-secondary"
                                        disabled
                                    >
                                        No Questions Yet
                                    </button>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

</div>


<?php require_once "../includes/footer.php"; ?>