<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

$pageTitle = "Add Lesson - LearnHub";

$teacher_id = $_SESSION["user_id"];

$error = "";

/* Get teacher's courses */
$stmt = $pdo->prepare(
    "SELECT id, title
     FROM courses
     WHERE teacher_id = ?
     AND deleted_at IS NULL
     ORDER BY title ASC"
);

$stmt->execute([$teacher_id]);

$courses = $stmt->fetchAll();


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $course_id = intval($_POST["course_id"] ?? 0);

    $title = trim($_POST["title"] ?? "");

    $content = trim($_POST["content"] ?? "");

    $video_url = trim($_POST["video_url"] ?? "");


    if (!csrf_valid()) {

        $error = "Security check failed. Please try again.";

    } elseif ($course_id <= 0 || $title === "") {

        $error = "Please select a course and enter lesson title.";

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

            /* Insert lesson */

            $stmt = $pdo->prepare(
                "INSERT INTO lessons
                (course_id, title, content, video_url)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                $course_id,
                $title,
                $content,
                $video_url
            ]);

            $_SESSION["success"] = "Lesson added successfully!";

            redirect("index.php");
        }
    }
}

require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-8">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <h2 class="fw-bold mb-4">
                        Add New Lesson
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


                            <!-- Lesson Title -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Lesson Title
                                </label>

                                <input
                                    type="text"
                                    name="title"
                                    class="form-control"
                                    placeholder="Enter lesson title"
                                    value="<?php echo htmlspecialchars($_POST["title"] ?? ""); ?>"
                                    required
                                >

                            </div>


                            <!-- Content -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Lesson Content
                                </label>

                                <textarea
                                    name="content"
                                    class="form-control"
                                    rows="7"
                                    placeholder="Write lesson content..."
                                ><?php echo htmlspecialchars($_POST["content"] ?? ""); ?></textarea>

                            </div>


                            <!-- Video URL -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Video URL
                                </label>

                                <input
                                    type="url"
                                    name="video_url"
                                    class="form-control"
                                    placeholder="https://www.youtube.com/watch?v=..."
                                    value="<?php echo htmlspecialchars($_POST["video_url"] ?? ""); ?>"
                                >

                                <small class="text-muted">
                                    Optional. You can add a YouTube or other video URL.
                                </small>

                            </div>


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Add Lesson
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