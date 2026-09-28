<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

$pageTitle = "Quiz Questions - LearnHub";

$teacher_id = $_SESSION["user_id"];

$quiz_id = intval($_GET["quiz_id"] ?? 0);

if ($quiz_id <= 0) {
    redirect("index.php");
}


/* Get Quiz and verify ownership */

$stmt = $pdo->prepare(
    "SELECT
        quizzes.*,
        courses.title AS course_title
     FROM quizzes
     INNER JOIN courses
        ON quizzes.course_id = courses.id
     WHERE quizzes.id = ?
     AND courses.teacher_id = ?"
);

$stmt->execute([
    $quiz_id,
    $teacher_id
]);

$quiz = $stmt->fetch();

if (!$quiz) {
    redirect("index.php");
}


$error = "";


/* Add Question */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $question = trim($_POST["question"] ?? "");

    $option_a = trim($_POST["option_a"] ?? "");

    $option_b = trim($_POST["option_b"] ?? "");

    $option_c = trim($_POST["option_c"] ?? "");

    $option_d = trim($_POST["option_d"] ?? "");

    $correct_answer = strtoupper(
        trim($_POST["correct_answer"] ?? "")
    );


    if (!csrf_valid()) {

        $error = "Security check failed. Please try again.";

    } elseif (
        $question === "" ||
        $option_a === "" ||
        $option_b === "" ||
        $option_c === "" ||
        $option_d === ""
    ) {

        $error = "Please fill in all question and option fields.";

    } elseif (!in_array($correct_answer, ["A", "B", "C", "D"])) {

        $error = "Please select a valid correct answer.";

    } else {

        /* Insert Question */

        $stmt = $pdo->prepare(
            "INSERT INTO questions
            (
                quiz_id,
                question,
                option_a,
                option_b,
                option_c,
                option_d,
                correct_answer
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $quiz_id,
            $question,
            $option_a,
            $option_b,
            $option_c,
            $option_d,
            $correct_answer
        ]);

        $_SESSION["success"] = "Question added successfully!";

        redirect("questions.php?quiz_id=" . $quiz_id);
    }
}


/* Get existing questions */

$stmt = $pdo->prepare(
    "SELECT *
     FROM questions
     WHERE quiz_id = ?
     ORDER BY id ASC"
);

$stmt->execute([$quiz_id]);

$questions = $stmt->fetchAll();


require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
?>

<div class="container py-5">

    <!-- Quiz Header -->

    <div class="mb-4">

        <h2 class="fw-bold">

            <?php echo htmlspecialchars($quiz["title"]); ?>

        </h2>

        <p class="text-muted">

            Course:
            <?php echo htmlspecialchars($quiz["course_title"]); ?>

        </p>

    </div>


    <?php if (isset($_SESSION["success"])): ?>

        <div class="alert alert-success">

            <?php

            echo htmlspecialchars($_SESSION["success"]);

            unset($_SESSION["success"]);

            ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-danger">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <div class="row">

        <!-- Add Question Form -->

        <div class="col-md-6 mb-4">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <h4 class="fw-bold mb-4">
                        Add Question
                    </h4>


                    <form method="POST">

                        <?php echo csrf_field(); ?>

                        <!-- Question -->

                        <div class="mb-3">

                            <label class="form-label">
                                Question
                            </label>

                            <textarea
                                name="question"
                                class="form-control"
                                rows="3"
                                placeholder="Enter your question"
                                required
                            ><?php echo htmlspecialchars($_POST["question"] ?? ""); ?></textarea>

                        </div>


                        <!-- Option A -->

                        <div class="mb-3">

                            <label class="form-label">
                                Option A
                            </label>

                            <input
                                type="text"
                                name="option_a"
                                class="form-control"
                                placeholder="Enter option A"
                                value="<?php echo htmlspecialchars($_POST["option_a"] ?? ""); ?>"
                                required
                            >

                        </div>


                        <!-- Option B -->

                        <div class="mb-3">

                            <label class="form-label">
                                Option B
                            </label>

                            <input
                                type="text"
                                name="option_b"
                                class="form-control"
                                placeholder="Enter option B"
                                value="<?php echo htmlspecialchars($_POST["option_b"] ?? ""); ?>"
                                required
                            >

                        </div>


                        <!-- Option C -->

                        <div class="mb-3">

                            <label class="form-label">
                                Option C
                            </label>

                            <input
                                type="text"
                                name="option_c"
                                class="form-control"
                                placeholder="Enter option C"
                                value="<?php echo htmlspecialchars($_POST["option_c"] ?? ""); ?>"
                                required
                            >

                        </div>


                        <!-- Option D -->

                        <div class="mb-3">

                            <label class="form-label">
                                Option D
                            </label>

                            <input
                                type="text"
                                name="option_d"
                                class="form-control"
                                placeholder="Enter option D"
                                value="<?php echo htmlspecialchars($_POST["option_d"] ?? ""); ?>"
                                required
                            >

                        </div>


                        <!-- Correct Answer -->

                        <div class="mb-4">

                            <label class="form-label">
                                Correct Answer
                            </label>

                            <select
                                name="correct_answer"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    -- Select Correct Answer --
                                </option>

                                <option value="A">
                                    A
                                </option>

                                <option value="B">
                                    B
                                </option>

                                <option value="C">
                                    C
                                </option>

                                <option value="D">
                                    D
                                </option>

                            </select>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Add Question
                        </button>

                    </form>

                </div>

            </div>

        </div>


        <!-- Existing Questions -->

        <div class="col-md-6">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <h4 class="fw-bold mb-4">
                        Questions
                    </h4>


                    <?php if (count($questions) > 0): ?>

                        <?php foreach ($questions as $index => $q): ?>

                            <div class="border rounded p-3 mb-3">

                                <h6 class="fw-bold">

                                    <?php echo ($index + 1) . ". "; ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $q["question"]
                                    );
                                    ?>

                                </h6>


                                <p class="mb-1">
                                    <strong>A:</strong>
                                    <?php echo htmlspecialchars($q["option_a"]); ?>
                                </p>

                                <p class="mb-1">
                                    <strong>B:</strong>
                                    <?php echo htmlspecialchars($q["option_b"]); ?>
                                </p>

                                <p class="mb-1">
                                    <strong>C:</strong>
                                    <?php echo htmlspecialchars($q["option_c"]); ?>
                                </p>

                                <p class="mb-1">
                                    <strong>D:</strong>
                                    <?php echo htmlspecialchars($q["option_d"]); ?>
                                </p>


                                <span class="badge bg-success mt-2">

                                    Correct:
                                    <?php echo htmlspecialchars($q["correct_answer"]); ?>

                                </span>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p class="text-muted">
                            No questions added yet.
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>


    <div class="mt-4">

        <a
            href="index.php"
            class="btn btn-secondary"
        >
            ← Back to Quizzes
        </a>

    </div>

</div>

<?php require_once "../../includes/footer.php"; ?>