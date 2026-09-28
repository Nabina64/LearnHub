<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

require_role("student", "../index.php");

$pageTitle = "Student Dashboard - LearnHub";

$student_id = $_SESSION["user_id"];


/* Total Enrolled Courses */

$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total
     FROM enrollments
     WHERE student_id = ?"
);

$stmt->execute([$student_id]);

$total_courses = $stmt->fetch()["total"];


/* Total Quiz Attempts */

$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total
     FROM quiz_results
     WHERE student_id = ?"
);

$stmt->execute([$student_id]);

$total_quizzes = $stmt->fetch()["total"];


/* Enrolled Courses */

$stmt = $pdo->prepare(
    "SELECT
        courses.id,
        courses.title,
        courses.description,
        courses.category,
        courses.thumbnail,
        users.name AS teacher_name
     FROM enrollments
     INNER JOIN courses
        ON enrollments.course_id = courses.id
     LEFT JOIN users
        ON courses.teacher_id = users.id
     WHERE enrollments.student_id = ?
     AND courses.deleted_at IS NULL
     ORDER BY enrollments.enrolled_at DESC"
);

$stmt->execute([$student_id]);

$enrolled_courses = $stmt->fetchAll();


/* Recent Quiz Results */

$stmt = $pdo->prepare(
    "SELECT
        quiz_results.id,
        quiz_results.score,
        quiz_results.total_questions,
        quiz_results.attempted_at,
        quizzes.title AS quiz_title,
        courses.title AS course_title
     FROM quiz_results
     INNER JOIN quizzes
        ON quiz_results.quiz_id = quizzes.id
     INNER JOIN courses
        ON quizzes.course_id = courses.id
     WHERE quiz_results.student_id = ?
     ORDER BY quiz_results.attempted_at DESC
     LIMIT 5"
);

$stmt->execute([$student_id]);

$quiz_results = $stmt->fetchAll();


require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <!-- Welcome -->

    <div class="mb-4">

        <h2 class="fw-bold">

            Welcome,
            <?php echo htmlspecialchars($_SESSION["user_name"]); ?> 👋

        </h2>

        <p class="text-muted">

            Continue learning and improve your skills.

        </p>

    </div>


    <!-- Statistics -->

    <div class="row g-4 mb-5">

        <div class="col-md-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h6 class="text-muted">
                        Enrolled Courses
                    </h6>

                    <h2 class="fw-bold text-primary">

                        <?php echo $total_courses; ?>

                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h6 class="text-muted">
                        Quiz Attempts
                    </h6>

                    <h2 class="fw-bold text-success">

                        <?php echo $total_quizzes; ?>

                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h6 class="text-muted">
                        Learning Status
                    </h6>

                    <h2 class="fw-bold">

                        <?php
                        echo $total_courses > 0
                            ? "Active"
                            : "Start";
                        ?>

                    </h2>

                </div>

            </div>

        </div>

    </div>


    <!-- My Learning -->

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h3 class="fw-bold">
                My Learning
            </h3>

            <p class="text-muted">
                Courses you are enrolled in.
            </p>

        </div>


        <a
            href="courses.php"
            class="btn btn-outline-primary"
        >
            Browse Courses
        </a>

    </div>


    <?php if (count($enrolled_courses) > 0): ?>

        <div class="row g-4 mb-5">

            <?php foreach ($enrolled_courses as $course): ?>

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

                                <?php
                                echo htmlspecialchars(
                                    $course["category"]
                                );
                                ?>

                            </span>


                            <h5 class="card-title fw-bold">

                                <?php
                                echo htmlspecialchars(
                                    $course["title"]
                                );
                                ?>

                            </h5>


                            <p class="text-muted">

                                Teacher:
                                <?php
                                echo htmlspecialchars(
                                    $course["teacher_name"] ?? "Unknown"
                                );
                                ?>

                            </p>


                            <p class="card-text">

                                <?php
                                echo htmlspecialchars(
                                    substr(
                                        $course["description"],
                                        0,
                                        100
                                    )
                                );

                                if (strlen($course["description"]) > 100) {
                                    echo "...";
                                }
                                ?>

                            </p>


                            <div class="mt-auto">

                                <a
                                    href="lesson.php?course_id=<?php echo $course["id"]; ?>"
                                    class="btn btn-success w-100"
                                >
                                    Continue Learning
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="alert alert-info mb-5">

            You haven't enrolled in any course yet.

            <a href="courses.php">
                Browse Courses
            </a>

        </div>

    <?php endif; ?>


    <!-- Quiz Results -->

    <div class="mb-3">

        <h3 class="fw-bold">
            Recent Quiz Results
        </h3>

        <p class="text-muted">
            Your latest quiz attempts.
        </p>

    </div>


    <?php if (count($quiz_results) > 0): ?>

        <div class="table-responsive mb-5">

            <table class="table table-bordered table-hover bg-white">

                <thead class="table-dark">

                    <tr>

                        <th>Quiz</th>

                        <th>Course</th>

                        <th>Score</th>

                        <th>Percentage</th>

                        <th>Date</th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($quiz_results as $result): ?>

                        <?php

                        $percentage = 0;

                        if ($result["total_questions"] > 0) {

                            $percentage =
                                ($result["score"] /
                                $result["total_questions"]) * 100;
                        }

                        ?>

                        <tr>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $result["quiz_title"]
                                );
                                ?>

                            </td>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $result["course_title"]
                                );
                                ?>

                            </td>

                            <td>

                                <?php
                                echo $result["score"]
                                    . " / "
                                    . $result["total_questions"];
                                ?>

                            </td>

                            <td>

                                <?php
                                echo number_format(
                                    $percentage,
                                    1
                                );
                                ?>%

                            </td>

                            <td>

                                <?php
                                echo date(
                                    "M d, Y",
                                    strtotime(
                                        $result["attempted_at"]
                                    )
                                );
                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="alert alert-secondary">

            You haven't attempted any quiz yet.

        </div>

    <?php endif; ?>


    <!-- Quick Actions -->

    <h3 class="fw-bold mb-3">
        Quick Actions
    </h3>


    <div class="d-flex gap-2 flex-wrap">

        <a
            href="courses.php"
            class="btn btn-primary"
        >
            📚 Browse Courses
        </a>

        <a
            href="my-courses.php"
            class="btn btn-success"
        >
            🎓 My Courses
        </a>

        <a
            href="quiz-results.php"
            class="btn btn-outline-primary"
        >
            📝 Quiz Results
        </a>

        <a
            href="settings.php"
            class="btn btn-outline-secondary"
        >
            Settings
        </a>

        <a
            href="../auth/logout.php"
            class="btn btn-danger"
        >
            Logout
        </a>

    </div>

</div>


<?php require_once "../includes/footer.php"; ?>