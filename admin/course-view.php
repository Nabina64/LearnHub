<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Admin Only */

require_role("admin", "../index.php");

$pageTitle = "Course Details - LearnHub Admin";

$course_id = intval($_GET["id"] ?? 0);

if ($course_id <= 0) {
    redirect("courses.php");
}

/* Token used by the "remove student" form */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(16));
}


/* -----------------------------------------------------
   Course  (works for active AND trashed courses)
----------------------------------------------------- */

$stmt = $pdo->prepare(
    "SELECT
        courses.*,
        users.id     AS teacher_id_real,
        users.name   AS teacher_name,
        users.email  AS teacher_email,
        users.status AS teacher_status
     FROM courses
     LEFT JOIN users
        ON courses.teacher_id = users.id
     WHERE courses.id = ?"
);

$stmt->execute([$course_id]);

$course = $stmt->fetch();

if (!$course) {

    $_SESSION["error"] = "Course not found.";

    redirect("courses.php");
}

$is_trashed = $course["deleted_at"] !== null;


/* Lessons */

$stmt = $pdo->prepare(
    "SELECT id, title, content, video_url, created_at
     FROM lessons
     WHERE course_id = ?
     AND deleted_at IS NULL
     ORDER BY id ASC"
);

$stmt->execute([$course_id]);

$lessons = $stmt->fetchAll();


$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM lessons
     WHERE course_id = ?
     AND deleted_at IS NOT NULL"
);

$stmt->execute([$course_id]);

$trashed_lessons = (int) $stmt->fetchColumn();


/* Quizzes (+ questions, attempts, average score) */

$stmt = $pdo->prepare(
    "SELECT
        quizzes.id,
        quizzes.title,

        (SELECT COUNT(*)
         FROM questions
         WHERE questions.quiz_id = quizzes.id) AS question_count,

        (SELECT COUNT(*)
         FROM quiz_results
         WHERE quiz_results.quiz_id = quizzes.id) AS attempt_count,

        (SELECT AVG(quiz_results.score
                    / NULLIF(quiz_results.total_questions, 0)) * 100
         FROM quiz_results
         WHERE quiz_results.quiz_id = quizzes.id) AS average_percent

     FROM quizzes
     WHERE quizzes.course_id = ?
     ORDER BY quizzes.id ASC"
);

$stmt->execute([$course_id]);

$quizzes = $stmt->fetchAll();


/* Enrollments */

$stmt = $pdo->prepare(
    "SELECT
        enrollments.id AS enrollment_id,
        enrollments.enrolled_at,
        users.name,
        users.email,
        users.deleted_at AS user_deleted_at
     FROM enrollments
     LEFT JOIN users
        ON enrollments.student_id = users.id
     WHERE enrollments.course_id = ?
     ORDER BY enrollments.enrolled_at DESC, enrollments.id DESC"
);

$stmt->execute([$course_id]);

$enrollments = $stmt->fetchAll();


/* Views (analytics) */

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM course_views WHERE course_id = ?"
);

$stmt->execute([$course_id]);

$view_count = (int) $stmt->fetchColumn();


/* Thumbnail (only if the file is really on disk) */

$thumb_file = !empty($course["thumbnail"])
    ? basename($course["thumbnail"])
    : "";

$has_thumb = $thumb_file !== ""
    && is_file(__DIR__ . "/../uploads/courses/" . $thumb_file);


$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};

$fmt_date = function ($value) {
    return !empty($value)
        ? date("M d, Y", strtotime($value))
        : "—";
};

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <!-- Success Message -->

    <?php if (isset($_SESSION["success"])): ?>

        <div class="alert alert-success">

            <?php
            echo $h($_SESSION["success"]);
            unset($_SESSION["success"]);
            ?>

        </div>

    <?php endif; ?>


    <!-- Error Message -->

    <?php if (isset($_SESSION["error"])): ?>

        <div class="alert alert-danger">

            <?php
            echo $h($_SESSION["error"]);
            unset($_SESSION["error"]);
            ?>

        </div>

    <?php endif; ?>


    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                Course Details
            </h2>

            <p class="text-muted mb-0">
                View and manage this course.
            </p>

        </div>

        <div class="d-flex gap-2">

            <a
                href="courses.php"
                class="btn btn-outline-secondary"
            >
                ← Courses
            </a>

        </div>

    </div>


    <?php if ($is_trashed): ?>

        <div class="alert alert-warning">

            This course is in the trash since
            <strong><?php echo $fmt_date($course["deleted_at"]); ?></strong>
            and is hidden from students. You can restore it from the
            <a href="#manage">Manage</a> section below.

        </div>

    <?php endif; ?>


    <!-- Course Information -->

    <div class="row mb-5">

        <!-- Course Image -->

        <div class="col-md-5 mb-4">

            <?php if ($has_thumb): ?>

                <img
                    src="../uploads/courses/<?php echo $h($thumb_file); ?>"
                    class="img-fluid rounded shadow"
                    alt="Course Thumbnail"
                >

            <?php else: ?>

                <div
                    class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                    style="height: 300px; font-size: 70px;"
                >
                    📚
                </div>

            <?php endif; ?>

        </div>


        <!-- Course Details -->

        <div class="col-md-7">

            <span class="badge bg-primary mb-2">
                <?php echo $h($course["category"]); ?>
            </span>

            <?php if ($is_trashed): ?>

                <span class="badge bg-danger mb-2">In Trash</span>

            <?php else: ?>

                <span class="badge bg-success mb-2">Active</span>

            <?php endif; ?>


            <h1 class="fw-bold">
                <?php echo $h($course["title"]); ?>
            </h1>


            <p class="text-muted">

                Teacher:

                <strong>
                    <?php echo $h($course["teacher_name"] ?? "Unknown"); ?>
                </strong>

                <?php if (!empty($course["teacher_email"])): ?>

                    (<?php echo $h($course["teacher_email"]); ?>)

                <?php endif; ?>

            </p>

            <p class="text-muted">

                Created:
                <strong><?php echo $fmt_date($course["created_at"] ?? null); ?></strong>

                &nbsp;•&nbsp;

                Page views:
                <strong><?php echo $view_count; ?></strong>

            </p>


            <hr>


            <h5>
                About This Course
            </h5>

            <p>

                <?php
                echo trim($course["description"] ?? "") !== ""
                    ? nl2br($h($course["description"]))
                    : '<span class="text-muted">No description.</span>';
                ?>

            </p>

        </div>

    </div>


    <!-- Statistics -->

    <div class="row g-4 mb-5">

        <div class="col-md-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h6 class="text-muted">
                        Lessons
                    </h6>

                    <h2 class="fw-bold text-primary">
                        <?php echo count($lessons); ?>
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h6 class="text-muted">
                        Quizzes
                    </h6>

                    <h2 class="fw-bold text-success">
                        <?php echo count($quizzes); ?>
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h6 class="text-muted">
                        Enrolled Students
                    </h6>

                    <h2 class="fw-bold">
                        <?php echo count($enrollments); ?>
                    </h2>

                </div>

            </div>

        </div>

    </div>


    <!-- Lessons -->

    <div class="mb-3" id="lessons">

        <h3 class="fw-bold">
            Lessons
        </h3>

        <p class="text-muted">

            Lessons added by the teacher.

            <?php if ($trashed_lessons > 0): ?>

                (<?php echo $trashed_lessons; ?> more in the teacher's trash)

            <?php endif; ?>

        </p>

    </div>

    <?php if (count($lessons) > 0): ?>

        <div class="card shadow-sm border-0 mb-5">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle mb-0">

                        <thead class="table-dark">

                            <tr>
                                <th>#</th>
                                <th>Lesson</th>
                                <th>Content</th>
                                <th>Video</th>
                                <th>Added</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($lessons as $index => $lesson): ?>

                                <?php
                                // Short preview (UTF-8 safe, so Nepali text is not cut in half)
                                $preview = trim($lesson["content"] ?? "");
                                $preview = preg_replace('/\s+/u', " ", $preview) ?? $preview;
                                $preview = preg_replace('/^(.{90}).+$/us', '$1...', $preview) ?? $preview;

                                $video = get_video_embed($lesson["video_url"] ?? "");
                                ?>

                                <tr>

                                    <td><?php echo $index + 1; ?></td>

                                    <td>
                                        <strong><?php echo $h($lesson["title"]); ?></strong>
                                    </td>

                                    <td class="text-muted">
                                        <?php echo $preview !== "" ? $h($preview) : "—"; ?>
                                    </td>

                                    <td>

                                        <?php if ($video): ?>

                                            <a
                                                href="<?php echo $h($video["original"]); ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="badge bg-success text-decoration-none"
                                            >
                                                Available
                                            </a>

                                        <?php else: ?>

                                            <span class="badge bg-secondary">
                                                No Video
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?php echo $fmt_date($lesson["created_at"] ?? null); ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    <?php else: ?>

        <div class="alert alert-secondary mb-5">
            This course has no lessons yet.
        </div>

    <?php endif; ?>


    <!-- Quizzes -->

    <div class="mb-3" id="quizzes">

        <h3 class="fw-bold">
            Quizzes
        </h3>

        <p class="text-muted">
            Quizzes created for this course.
        </p>

    </div>

    <?php if (count($quizzes) > 0): ?>

        <div class="card shadow-sm border-0 mb-5">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle mb-0">

                        <thead class="table-dark">

                            <tr>
                                <th>#</th>
                                <th>Quiz</th>
                                <th>Questions</th>
                                <th>Attempts</th>
                                <th>Average Score</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($quizzes as $index => $quiz): ?>

                                <tr>

                                    <td><?php echo $index + 1; ?></td>

                                    <td>
                                        <strong><?php echo $h($quiz["title"]); ?></strong>
                                    </td>

                                    <td><?php echo (int) $quiz["question_count"]; ?></td>

                                    <td><?php echo (int) $quiz["attempt_count"]; ?></td>

                                    <td>
                                        <?php
                                        echo $quiz["average_percent"] !== null
                                            ? number_format((float) $quiz["average_percent"], 1) . "%"
                                            : "—";
                                        ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    <?php else: ?>

        <div class="alert alert-secondary mb-5">
            This course has no quizzes yet.
        </div>

    <?php endif; ?>


    <!-- Enrollments -->

    <div class="mb-3" id="enrollments">

        <h3 class="fw-bold">
            Enrolled Students
        </h3>

        <p class="text-muted">
            Students who joined this course.
        </p>

    </div>

    <?php if (count($enrollments) > 0): ?>

        <div class="card shadow-sm border-0 mb-5">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle mb-0">

                        <thead class="table-dark">

                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Enrolled On</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($enrollments as $index => $enrollment): ?>

                                <tr>

                                    <td><?php echo $index + 1; ?></td>

                                    <td>

                                        <?php if ($enrollment["name"] === null): ?>

                                            <span class="text-muted">Deleted user</span>

                                        <?php else: ?>

                                            <strong><?php echo $h($enrollment["name"]); ?></strong>

                                            <?php if ($enrollment["user_deleted_at"] !== null): ?>

                                                <span class="badge bg-danger">In user trash</span>

                                            <?php endif; ?>

                                        <?php endif; ?>

                                    </td>

                                    <td><?php echo $h($enrollment["email"] ?? "—"); ?></td>

                                    <td>
                                        <?php echo $fmt_date($enrollment["enrolled_at"] ?? null); ?>
                                    </td>

                                    <td>

                                        <form
                                            method="POST"
                                            action="remove-enrollment.php"
                                            class="d-inline"
                                            onsubmit="return confirm('Remove this student from the course? Their account is not deleted.');"
                                        >

                                            <input type="hidden" name="csrf_token" value="<?php echo $h($_SESSION["csrf_token"]); ?>">
                                            <input type="hidden" name="enrollment_id" value="<?php echo (int) $enrollment["enrollment_id"]; ?>">
                                            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                            >
                                                Remove
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    <?php else: ?>

        <div class="alert alert-secondary mb-5">
            No student has enrolled in this course yet.
        </div>

    <?php endif; ?>


    <!-- Manage -->

    <div class="mb-3" id="manage">

        <h3 class="fw-bold">
            Manage Course
        </h3>

        <p class="text-muted">

            <?php if ($is_trashed): ?>

                Restore this course or delete it permanently.

            <?php else: ?>

                Moving a course to trash hides it from students. You can restore it any time.

            <?php endif; ?>

        </p>

    </div>

    <div class="d-flex gap-2 flex-wrap">

        <?php if ($is_trashed): ?>

            <a
                href="restore-course.php?id=<?php echo (int) $course["id"]; ?>"
                class="btn btn-success"
            >
                Restore
            </a>

            <a
                href="delete-course-forever.php?id=<?php echo (int) $course["id"]; ?>"
                class="btn btn-danger"
                onclick="return confirm('Delete this course FOREVER? Its lessons, quizzes, questions and enrollments will be removed and this cannot be undone.');"
            >
                Delete Forever
            </a>

        <?php else: ?>

            <a
                href="delete-course.php?id=<?php echo (int) $course["id"]; ?>"
                class="btn btn-danger"
                onclick="return confirm('Move this course to trash? You can restore it later from the Trash page.');"
            >
                Delete
            </a>

        <?php endif; ?>

        <a
            href="courses.php"
            class="btn btn-outline-secondary"
        >
            ← Back to Courses
        </a>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
