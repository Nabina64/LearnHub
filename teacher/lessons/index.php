<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

$pageTitle = "My Lessons - LearnHub";

$teacher_id = $_SESSION["user_id"];

/* Get teacher's lessons */
$stmt = $pdo->prepare(
    "SELECT
        lessons.*,
        courses.title AS course_title
     FROM lessons
     INNER JOIN courses
        ON lessons.course_id = courses.id
     WHERE courses.teacher_id = ?
     AND lessons.deleted_at IS NULL
     ORDER BY lessons.created_at DESC"
);

$stmt->execute([$teacher_id]);

$lessons = $stmt->fetchAll();

require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold">My Lessons</h2>
            <p class="text-muted">
                Manage lessons for your courses.
            </p>
        </div>

        <div class="d-flex gap-2">

            <a
                href="trash.php"
                class="btn btn-outline-danger"
            >
                &#128465; Trash
            </a>

            <a
                href="create.php"
                class="btn btn-primary"
            >
                + Add Lesson
            </a>

        </div>

    </div>


    <?php if (isset($_SESSION["success"])): ?>

        <div class="alert alert-success">
            <?php
            echo htmlspecialchars($_SESSION["success"]);
            unset($_SESSION["success"]);
            ?>
        </div>

    <?php endif; ?>


    <?php if (count($lessons) > 0): ?>

        <div class="table-responsive">

            <table class="table table-bordered table-hover bg-white">

                <thead class="table-dark">

                    <tr>
                        <th>#</th>
                        <th>Lesson</th>
                        <th>Course</th>
                        <th>Video</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($lessons as $index => $lesson): ?>

                        <tr>

                            <td>
                                <?php echo $index + 1; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($lesson["title"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($lesson["course_title"]); ?>
                            </td>

                            <td>

                                <?php if (!empty($lesson["video_url"])): ?>

                                    <span class="badge bg-success">
                                        Available
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        No Video
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <a
                                    href="edit.php?id=<?php echo $lesson["id"]; ?>"
                                    class="btn btn-sm btn-warning"
                                >
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    action="delete.php"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to delete this lesson?');"
                                >
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo $lesson["id"]; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Delete
                                    </button>
                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="alert alert-info">

            No lessons found.

            <a href="create.php">
                Add your first lesson.
            </a>

        </div>

    <?php endif; ?>

</div>

<?php require_once "../../includes/footer.php"; ?>