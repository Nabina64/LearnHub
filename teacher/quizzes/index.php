<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

$pageTitle = "My Quizzes - LearnHub";

$teacher_id = $_SESSION["user_id"];


/* Get teacher's quizzes */

$stmt = $pdo->prepare(
    "SELECT
        quizzes.*,
        courses.title AS course_title,
        (
            SELECT COUNT(*)
            FROM questions
            WHERE questions.quiz_id = quizzes.id
        ) AS question_count
     FROM quizzes
     INNER JOIN courses
        ON quizzes.course_id = courses.id
     WHERE courses.teacher_id = ?
     ORDER BY quizzes.created_at DESC"
);

$stmt->execute([$teacher_id]);

$quizzes = $stmt->fetchAll();


require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                My Quizzes
            </h2>

            <p class="text-muted">
                Create and manage quizzes for your courses.
            </p>

        </div>

        <a
            href="create.php"
            class="btn btn-primary"
        >
            + Create Quiz
        </a>

    </div>


    <?php if (isset($_SESSION["success"])): ?>

        <div class="alert alert-success">

            <?php
            echo htmlspecialchars($_SESSION["success"]);
            unset($_SESSION["success"]);
            ?>

        </div>

    <?php endif; ?>


    <?php if (count($quizzes) > 0): ?>

        <div class="table-responsive">

            <table class="table table-bordered table-hover bg-white">

                <thead class="table-dark">

                    <tr>

                        <th>#</th>

                        <th>Quiz Title</th>

                        <th>Course</th>

                        <th>Questions</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($quizzes as $index => $quiz): ?>

                        <tr>

                            <td>
                                <?php echo $index + 1; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($quiz["title"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($quiz["course_title"]); ?>
                            </td>

                            <td>

                                <span class="badge bg-info text-dark">

                                    <?php echo $quiz["question_count"]; ?>

                                </span>

                            </td>

                            <td>

                                <a
                                    href="questions.php?quiz_id=<?php echo $quiz["id"]; ?>"
                                    class="btn btn-sm btn-primary"
                                >
                                    Questions
                                </a>

                                <a
                                    href="delete.php?id=<?php echo $quiz["id"]; ?>"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this quiz?');"
                                >
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="alert alert-info">

            No quizzes found.

            <a href="create.php">
                Create your first quiz.
            </a>

        </div>

    <?php endif; ?>

</div>


<?php require_once "../../includes/footer.php"; ?>