<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

$pageTitle = "Lesson Trash - LearnHub";

$teacher_id = $_SESSION["user_id"];


/* Auto purge lessons trashed more than 30 days ago */

$stmt = $pdo->prepare(
    "DELETE lessons
     FROM lessons
     INNER JOIN courses
        ON lessons.course_id = courses.id
     WHERE courses.teacher_id = ?
     AND lessons.deleted_at IS NOT NULL
     AND lessons.deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
);

$stmt->execute([$teacher_id]);


/* Get trashed lessons */

$stmt = $pdo->prepare(
    "SELECT
        lessons.*,
        courses.title AS course_title
     FROM lessons
     INNER JOIN courses
        ON lessons.course_id = courses.id
     WHERE courses.teacher_id = ?
     AND lessons.deleted_at IS NOT NULL
     ORDER BY lessons.deleted_at DESC"
);

$stmt->execute([$teacher_id]);

$trashed = $stmt->fetchAll();

require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold">
                &#128465; Lesson Trash
            </h2>
            <p class="text-muted mb-0">
                Deleted lessons stay here for 30 days before being
                removed for good.
            </p>
        </div>

        <a
            href="index.php"
            class="btn btn-outline-secondary"
        >
            &larr; Back to Lessons
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


    <?php if (isset($_SESSION["error"])): ?>

        <div class="alert alert-danger">
            <?php
            echo htmlspecialchars($_SESSION["error"]);
            unset($_SESSION["error"]);
            ?>
        </div>

    <?php endif; ?>


    <?php if (count($trashed) > 0): ?>

        <div class="table-responsive">

            <table class="table table-bordered table-hover bg-white align-middle">

                <thead class="table-dark">

                    <tr>
                        <th>#</th>
                        <th>Lesson</th>
                        <th>Course</th>
                        <th>Deleted On</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($trashed as $index => $lesson): ?>

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
                                <?php
                                echo date(
                                    "M d, Y g:i A",
                                    strtotime($lesson["deleted_at"])
                                );
                                ?>
                            </td>

                            <td>

                                <form
                                    method="POST"
                                    action="restore.php"
                                    class="d-inline"
                                    onsubmit="return confirm('Restore this lesson?');"
                                >
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo $lesson["id"]; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        Restore
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="delete-forever.php"
                                    class="d-inline"
                                    onsubmit="return confirm('This will permanently delete the lesson. This cannot be undone. Continue?');"
                                >
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo $lesson["id"]; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Delete Forever
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
            Trash is empty.
        </div>

    <?php endif; ?>

</div>

<?php require_once "../../includes/footer.php"; ?>
