<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

$pageTitle = "Create Quiz - LearnHub";

$teacher_id = $_SESSION["user_id"];

$error = "";


/* Get teacher courses */

$stmt = $pdo->prepare(
    "SELECT id, title
     FROM courses
     WHERE teacher_id = ?
     AND deleted_at IS NULL
     ORDER BY title ASC"
);

$stmt->execute([$teacher_id]);

$courses = $stmt->fetchAll();


/* Create Quiz */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $course_id = intval($_POST["course_id"] ?? 0);

    $title = trim($_POST["title"] ?? "");


    if (!csrf_valid()) {

        $error = "Security check failed. Please try again.";

    } elseif ($course_id <= 0 || $title === "") {

        $error = "Please select a course and enter quiz title.";

    } else {

        /* Verify course belongs to teacher */

        $stmt = $pdo->prepare(
            "SELECT id
             FROM courses
             WHERE id = ?
             AND teacher_id = ?
             AND deleted_at IS NULL"
        );

        $stmt->execute([
            $course_id,
            $teacher_id
        ]);

        $course = $stmt->fetch();


        if (!$course) {

            $error = "Invalid course selected.";

        } else {

            /* Insert quiz */

            $stmt = $pdo->prepare(
                "INSERT INTO quizzes
                (course_id, title)
                VALUES (?, ?)"
            );

            $stmt->execute([
                $course_id,
                $title
            ]);

            $quiz_id = $pdo->lastInsertId();

            $_SESSION["success"] = "Quiz created successfully!";

            redirect("questions.php?quiz_id=" . $quiz_id);
        }
    }
}


require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <h2 class="fw-bold mb-4">
                        Create New Quiz
                    </h2>


                    <?php if ($error): ?>

                        <div class="alert alert-danger">

                            <?php echo htmlspecialchars($error); ?>

                        </div>

                    <?php endif; ?>


                    <?php if (count($courses) === 0): ?>

                        <div class="alert alert-warning">

                            You don't have any courses yet.

                            <a href="../courses/create.php">
                                Create a course first.
                            </a>

                        </div>

                    <?php else: ?>


                        <form method="POST">

                            <?php echo csrf_field(); ?>


                            <!-- Course -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Select Course
                                </label>

                                <select
                                    name="course_id"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        -- Select Course --
                                    </option>

                                    <?php foreach ($courses as $course): ?>

                                        <option
                                            value="<?php echo $course["id"]; ?>"
                                            <?php
                                            if (
                                                isset($_POST["course_id"]) &&
                                                $_POST["course_id"] == $course["id"]
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >

                                            <?php echo htmlspecialchars($course["title"]); ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- Quiz Title -->

                            <div class="mb-4">

                                <label class="form-label">
                                    Quiz Title
                                </label>

                                <input
                                    type="text"
                                    name="title"
                                    class="form-control"
                                    placeholder="e.g. HTML Basics Quiz"
                                    value="<?php echo htmlspecialchars($_POST["title"] ?? ""); ?>"
                                    required
                                >

                            </div>


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Create Quiz
                            </button>

                            <a
                                href="index.php"
                                class="btn btn-secondary"
                            >
                                Cancel
                            </a>

                        </form>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once "../../includes/footer.php"; ?>