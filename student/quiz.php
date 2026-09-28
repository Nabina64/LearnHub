<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

require_role("student", "../index.php");

$pageTitle = "Take Quiz - LearnHub";

$student_id = $_SESSION["user_id"];

$quiz_id = intval($_GET["quiz_id"] ?? 0);

if ($quiz_id <= 0) {
    redirect("courses.php");
}


/* Get quiz */

$stmt = $pdo->prepare(
    "SELECT
        quizzes.*,
        courses.title AS course_title
     FROM quizzes
     INNER JOIN courses
        ON quizzes.course_id = courses.id
     WHERE quizzes.id = ?
     AND courses.deleted_at IS NULL"
);

$stmt->execute([$quiz_id]);

$quiz = $stmt->fetch();

if (!$quiz) {
    redirect("courses.php");
}


/* Check enrollment */

$stmt = $pdo->prepare(
    "SELECT enrollments.id
     FROM enrollments
     WHERE enrollments.student_id = ?
     AND enrollments.course_id = ?"
);

$stmt->execute([
    $student_id,
    $quiz["course_id"]
]);

$enrollment = $stmt->fetch();

if (!$enrollment) {

    redirect(
        "course-details.php?id=" . $quiz["course_id"]
    );
}


/* Get questions */

$stmt = $pdo->prepare(
    "SELECT *
     FROM questions
     WHERE quiz_id = ?
     ORDER BY id ASC"
);

$stmt->execute([$quiz_id]);

$questions = $stmt->fetchAll();


/* Submit Quiz */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_valid()) {

        redirect("courses.php");
    }

    $score = 0;

    $total_questions = count($questions);


    foreach ($questions as $question) {

        $question_id = $question["id"];

        $selected_answer = $_POST["answer_" . $question_id] ?? "";

        $selected_answer = strtoupper(
            trim($selected_answer)
        );


        if (
            $selected_answer !== "" &&
            $selected_answer === $question["correct_answer"]
        ) {

            $score++;
        }
    }


    /* Save Result */

    $stmt = $pdo->prepare(
        "INSERT INTO quiz_results
        (
            student_id,
            quiz_id,
            score,
            total_questions
        )
        VALUES (?, ?, ?, ?)"
    );

    $stmt->execute([
        $student_id,
        $quiz_id,
        $score,
        $total_questions
    ]);


    $result_id = $pdo->lastInsertId();


    redirect(
        "quiz-result.php?id=" . $result_id
    );
}


require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-9">

            <!-- Quiz Header -->

            <div class="mb-4">

                <span class="badge bg-primary">

                    <?php
                    echo htmlspecialchars(
                        $quiz["course_title"]
                    );
                    ?>

                </span>

                <h1 class="fw-bold mt-2">

                    <?php
                    echo htmlspecialchars(
                        $quiz["title"]
                    );
                    ?>

                </h1>

                <p class="text-muted">

                    Total Questions:
                    <?php echo count($questions); ?>

                </p>

            </div>


            <?php if (count($questions) === 0): ?>

                <div class="alert alert-info">

                    This quiz has no questions yet.

                </div>

                <a
                    href="course-details.php?id=<?php echo $quiz["course_id"]; ?>"
                    class="btn btn-secondary"
                >
                    ← Back to Course
                </a>

            <?php else: ?>


                <form
                    method="POST"
                    onsubmit="return confirm('Are you sure you want to submit the quiz?');"
                >

                    <?php echo csrf_field(); ?>


                    <?php foreach ($questions as $index => $question): ?>

                        <div class="card shadow-sm mb-4">

                            <div class="card-body p-4">

                                <h5 class="fw-bold">

                                    <?php echo ($index + 1) . ". "; ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $question["question"]
                                    );
                                    ?>

                                </h5>


                                <div class="mt-3">


                                    <!-- Option A -->

                                    <div class="form-check mb-2">

                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="answer_<?php echo $question["id"]; ?>"
                                            value="A"
                                            id="q<?php echo $question["id"]; ?>a"
                                            required
                                        >

                                        <label
                                            class="form-check-label"
                                            for="q<?php echo $question["id"]; ?>a"
                                        >

                                            <strong>A.</strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $question["option_a"]
                                            );
                                            ?>

                                        </label>

                                    </div>


                                    <!-- Option B -->

                                    <div class="form-check mb-2">

                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="answer_<?php echo $question["id"]; ?>"
                                            value="B"
                                            id="q<?php echo $question["id"]; ?>b"
                                            required
                                        >

                                        <label
                                            class="form-check-label"
                                            for="q<?php echo $question["id"]; ?>b"
                                        >

                                            <strong>B.</strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $question["option_b"]
                                            );
                                            ?>

                                        </label>

                                    </div>


                                    <!-- Option C -->

                                    <div class="form-check mb-2">

                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="answer_<?php echo $question["id"]; ?>"
                                            value="C"
                                            id="q<?php echo $question["id"]; ?>c"
                                            required
                                        >

                                        <label
                                            class="form-check-label"
                                            for="q<?php echo $question["id"]; ?>c"
                                        >

                                            <strong>C.</strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $question["option_c"]
                                            );
                                            ?>

                                        </label>

                                    </div>


                                    <!-- Option D -->

                                    <div class="form-check mb-2">

                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="answer_<?php echo $question["id"]; ?>"
                                            value="D"
                                            id="q<?php echo $question["id"]; ?>d"
                                            required
                                        >

                                        <label
                                            class="form-check-label"
                                            for="q<?php echo $question["id"]; ?>d"
                                        >

                                            <strong>D.</strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $question["option_d"]
                                            );
                                            ?>

                                        </label>

                                    </div>


                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>


                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-success btn-lg"
                        >
                            Submit Quiz
                        </button>

                        <a
                            href="course-details.php?id=<?php echo $quiz["course_id"]; ?>"
                            class="btn btn-secondary btn-lg"
                        >
                            Cancel
                        </a>

                    </div>

                </form>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>