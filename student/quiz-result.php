<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Student Only */

require_role("student", "../index.php");


$pageTitle = "Quiz Result - LearnHub";


/* Get Result ID */

$result_id = intval($_GET["id"] ?? 0);


if ($result_id <= 0) {

    redirect("dashboard.php");

}


$student_id = $_SESSION["user_id"];


/* Get Quiz Result */

$stmt = $pdo->prepare(
    "SELECT
        quiz_results.id,
        quiz_results.score,
        quiz_results.total_questions,
        quiz_results.attempted_at,
        quizzes.id AS quiz_id,
        quizzes.title AS quiz_title,
        courses.id AS course_id,
        courses.title AS course_title
     FROM quiz_results
     INNER JOIN quizzes
        ON quiz_results.quiz_id = quizzes.id
     INNER JOIN courses
        ON quizzes.course_id = courses.id
     WHERE quiz_results.id = ?
     AND quiz_results.student_id = ?"
);

$stmt->execute([
    $result_id,
    $student_id
]);

$result = $stmt->fetch();


/* Result Not Found */

if (!$result) {

    redirect("dashboard.php");

}


/* Calculate Percentage */

$percentage = 0;


if ($result["total_questions"] > 0) {

    $percentage =
        ($result["score"] /
        $result["total_questions"]) * 100;

}


require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>


<div class="container py-5">


    <!-- Result Card -->

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="card shadow-sm border-0">


                <div class="card-body text-center p-5">


                    <!-- Title -->

                    <h2 class="fw-bold mb-3">

                        Quiz Completed! 🎉

                    </h2>


                    <p class="text-muted">

                        <?php

                        echo htmlspecialchars(
                            $result["quiz_title"]
                        );

                        ?>

                    </p>


                    <hr>


                    <!-- Score -->

                    <h5 class="text-muted">

                        Your Score

                    </h5>


                    <h1 class="display-4 fw-bold text-primary">

                        <?php

                        echo $result["score"];

                        ?>

                        /

                        <?php

                        echo $result["total_questions"];

                        ?>

                    </h1>


                    <!-- Percentage -->

                    <h4 class="text-success mb-4">

                        <?php

                        echo number_format(
                            $percentage,
                            1
                        );

                        ?>%

                    </h4>


                    <!-- Course -->

                    <p>

                        <strong>
                            Course:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $result["course_title"]
                        );

                        ?>

                    </p>


                    <!-- Attempt Date -->

                    <p class="text-muted">

                        Attempted on:

                        <?php

                        echo date(
                            "M d, Y h:i A",
                            strtotime(
                                $result["attempted_at"]
                            )
                        );

                        ?>

                    </p>


                    <hr class="my-4">


                    <!-- Action Buttons -->

                    <div
                        class="d-flex justify-content-center gap-2 flex-wrap"
                    >


                        <!-- Retake Quiz -->

                        <a
                            href="quiz.php?quiz_id=<?php echo $result["quiz_id"]; ?>"
                            class="btn btn-primary"
                        >

                            🔄 Retake Quiz

                        </a>


                        <!-- Course Details -->

                        <a
                            href="course-details.php?id=<?php echo $result["course_id"]; ?>"
                            class="btn btn-outline-primary"
                        >

                            📚 Course

                        </a>


                        <!-- Dashboard -->

                        <a
                            href="dashboard.php"
                            class="btn btn-outline-secondary"
                        >

                            Dashboard

                        </a>


                    </div>


                </div>

            </div>

        </div>

    </div>


</div>


<?php require_once "../includes/footer.php"; ?>