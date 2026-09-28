<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

$pageTitle = "Edit Lesson - LearnHub";

$teacher_id = $_SESSION["user_id"];

$lesson_id = intval($_GET["id"] ?? 0);

if ($lesson_id <= 0) {
    redirect("index.php");
}

/* Get lesson and verify teacher ownership */

$stmt = $pdo->prepare(
    "SELECT
        lessons.*,
        courses.title AS course_title
     FROM lessons
     INNER JOIN courses
        ON lessons.course_id = courses.id
     WHERE lessons.id = ?
     AND courses.teacher_id = ?
     AND lessons.deleted_at IS NULL"
);

$stmt->execute([
    $lesson_id,
    $teacher_id
]);

$lesson = $stmt->fetch();

if (!$lesson) {
    redirect("index.php");
}

$error = "";


/* Update Lesson */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");

    $content = trim($_POST["content"] ?? "");

    $video_url = trim($_POST["video_url"] ?? "");


    if (!csrf_valid()) {

        $error = "Security check failed. Please try again.";

    } elseif ($title === "") {

        $error = "Lesson title is required.";

    } else {

        $stmt = $pdo->prepare(
            "UPDATE lessons
             SET title = ?,
                 content = ?,
                 video_url = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $title,
            $content,
            $video_url,
            $lesson_id
        ]);

        $_SESSION["success"] = "Lesson updated successfully!";

        redirect("index.php");
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

                    <h2 class="fw-bold mb-2">
                        Edit Lesson
                    </h2>

                    <p class="text-muted mb-4">
                        Course:
                        <?php echo htmlspecialchars($lesson["course_title"]); ?>
                    </p>


                    <?php if ($error): ?>

                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>

                    <?php endif; ?>


                    <form method="POST">

                        <?php echo csrf_field(); ?>

                        <!-- Lesson Title -->

                        <div class="mb-3">

                            <label class="form-label">
                                Lesson Title
                            </label>

                            <input
                                type="text"
                                name="title"
                                class="form-control"
                                value="<?php echo htmlspecialchars($_POST["title"] ?? $lesson["title"]); ?>"
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
                                rows="8"
                            ><?php echo htmlspecialchars($_POST["content"] ?? $lesson["content"] ?? ""); ?></textarea>

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
                                value="<?php echo htmlspecialchars($_POST["video_url"] ?? $lesson["video_url"] ?? ""); ?>"
                            >

                            <small class="text-muted">
                                Optional.
                            </small>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Update Lesson
                        </button>

                        <a
                            href="index.php"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once "../../includes/footer.php"; ?>