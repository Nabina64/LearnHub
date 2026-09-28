<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


$pageTitle = "Course Trash - LearnHub";


/* Auto purge courses trashed more than 30 days ago */

$stmt = $pdo->query(
    "SELECT id, thumbnail
     FROM courses
     WHERE deleted_at IS NOT NULL
     AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
);

$to_purge = $stmt->fetchAll();

foreach ($to_purge as $old) {

    if (
        !empty($old["thumbnail"])
        && file_exists("../uploads/courses/" . $old["thumbnail"])
    ) {
        unlink("../uploads/courses/" . $old["thumbnail"]);
    }

    $pdo->prepare("DELETE FROM lessons WHERE course_id = ?")
        ->execute([$old["id"]]);

    $pdo->prepare(
        "DELETE quizzes, questions FROM quizzes
         LEFT JOIN questions ON questions.quiz_id = quizzes.id
         WHERE quizzes.course_id = ?"
    )->execute([$old["id"]]);

    $pdo->prepare("DELETE FROM enrollments WHERE course_id = ?")
        ->execute([$old["id"]]);

    $pdo->prepare("DELETE FROM course_views WHERE course_id = ?")
        ->execute([$old["id"]]);

    $pdo->prepare("DELETE FROM courses WHERE id = ?")
        ->execute([$old["id"]]);
}


/* Get trashed courses */

$stmt = $pdo->query(
    "SELECT
        courses.id,
        courses.title,
        courses.category,
        courses.deleted_at,
        users.name AS teacher_name
     FROM courses
     LEFT JOIN users
        ON courses.teacher_id = users.id
     WHERE courses.deleted_at IS NOT NULL
     ORDER BY courses.deleted_at DESC"
);

$trashed = $stmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>


<div class="container py-5">


    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                🗑 Course Trash
            </h2>

            <p class="text-muted mb-0">
                Deleted courses stay here for 30 days before being
                removed for good.
            </p>

        </div>


        <a
            href="courses.php"
            class="btn btn-outline-secondary"
        >
            ← Back to Courses
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
                        <th>Teacher</th>
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
                                <?php echo htmlspecialchars($course["teacher_name"] ?? "-"); ?>
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

                                <a
                                    href="restore-course.php?id=<?php echo $course["id"]; ?>"
                                    class="btn btn-sm btn-success"
                                    onclick="return confirm('Restore this course?');"
                                >
                                    Restore
                                </a>

                                <a
                                    href="delete-course-forever.php?id=<?php echo $course["id"]; ?>"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('This will permanently delete the course, its lessons and quizzes. This cannot be undone. Continue?');"
                                >
                                    Delete Forever
                                </a>

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


<?php require_once "../includes/footer.php"; ?>
