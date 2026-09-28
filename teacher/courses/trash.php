<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

require_role("teacher", "../../student/dashboard.php");

$pageTitle = "Course Trash - LearnHub";

$teacher_id = $_SESSION["user_id"];


/* Auto purge courses trashed more than 30 days ago */

$stmt = $pdo->prepare(
    "SELECT id, thumbnail
     FROM courses
     WHERE teacher_id = ?
     AND deleted_at IS NOT NULL
     AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
);

$stmt->execute([$teacher_id]);

$to_purge = $stmt->fetchAll();

foreach ($to_purge as $old) {

    if (
        !empty($old["thumbnail"])
        && file_exists("../../uploads/courses/" . $old["thumbnail"])
    ) {
        unlink("../../uploads/courses/" . $old["thumbnail"]);
    }

    $del = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $del->execute([$old["id"]]);
}


/* Get trashed courses */

$stmt = $pdo->prepare(
    "SELECT id, title, category, deleted_at
     FROM courses
     WHERE teacher_id = ?
     AND deleted_at IS NOT NULL
     ORDER BY deleted_at DESC"
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
                &#128465; Course Trash
            </h2>
            <p class="text-muted mb-0">
                Deleted courses stay here for 30 days before being
                removed for good. Their lessons and quizzes stay
                attached and come back if you restore the course.
            </p>
        </div>

        <a
            href="index.php"
            class="btn btn-outline-secondary"
        >
            &larr; Back to Courses
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
                        <th>Course</th>
                        <th>Category</th>
                        <th>Deleted On</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($trashed as $index => $course): ?>

                        <tr>

                            <td>
                                <?php echo $index + 1; ?>
                            </td>

                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($course["title"]); ?>
                                </strong>
                            </td>

                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($course["category"] ?? "-"); ?>
                                </span>
                            </td>

                            <td>
                                <?php
                                echo date(
                                    "M d, Y g:i A",
                                    strtotime($course["deleted_at"])
                                );
                                ?>
                            </td>

                            <td>

                                <form
                                    method="POST"
                                    action="restore.php"
                                    class="d-inline"
                                    onsubmit="return confirm('Restore this course?');"
                                >
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo $course["id"]; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        Restore
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="delete-forever.php"
                                    class="d-inline"
                                    onsubmit="return confirm('This will permanently delete the course, its lessons and quizzes. This cannot be undone. Continue?');"
                                >
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo $course["id"]; ?>">
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
