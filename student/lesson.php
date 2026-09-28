<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

require_role("student", "../index.php");

$pageTitle = "Learn - LearnHub";

$student_id = $_SESSION["user_id"];

$course_id = intval($_GET["course_id"] ?? 0);

if ($course_id <= 0) {
    redirect("courses.php");
}


/* Check course */

$stmt = $pdo->prepare(
    "SELECT
        courses.*,
        users.name AS teacher_name
     FROM courses
     LEFT JOIN users
        ON courses.teacher_id = users.id
     WHERE courses.id = ?
     AND courses.deleted_at IS NULL"
);

$stmt->execute([$course_id]);

$course = $stmt->fetch();

if (!$course) {
    redirect("courses.php");
}


/* Check enrollment */

$stmt = $pdo->prepare(
    "SELECT id
     FROM enrollments
     WHERE student_id = ?
     AND course_id = ?"
);

$stmt->execute([
    $student_id,
    $course_id
]);

$enrollment = $stmt->fetch();

if (!$enrollment) {

    $_SESSION["error"] = "Please enroll in this course first.";

    redirect("course-details.php?id=" . $course_id);
}


/* Get lessons */

$stmt = $pdo->prepare(
    "SELECT *
     FROM lessons
     WHERE course_id = ?
     AND deleted_at IS NULL
     ORDER BY id ASC"
);

$stmt->execute([$course_id]);

$lessons = $stmt->fetchAll();


/* Pick the lesson to show (from the list we already loaded) */

$lesson_id = intval($_GET["lesson_id"] ?? 0);

$selected_lesson = null;
$selected_index = 0;

foreach ($lessons as $index => $lesson) {

    if ((int) $lesson["id"] === $lesson_id) {

        $selected_lesson = $lesson;
        $selected_index = $index;
        break;
    }
}

// No lesson_id (or a wrong one) -> open the first lesson
if (!$selected_lesson && count($lessons) > 0) {

    $selected_lesson = $lessons[0];
    $selected_index = 0;
}

$prev_lesson = $lessons[$selected_index - 1] ?? null;
$next_lesson = $lessons[$selected_index + 1] ?? null;

$video = $selected_lesson
    ? get_video_embed($selected_lesson["video_url"] ?? "")
    : null;


require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <!-- Course Header -->

    <div class="mb-4">

        <span class="badge bg-primary">
            <?php echo htmlspecialchars($course["category"]); ?>
        </span>

        <h1 class="fw-bold mt-2">
            <?php echo htmlspecialchars($course["title"]); ?>
        </h1>

        <p class="text-muted">
            Teacher:
            <?php echo htmlspecialchars($course["teacher_name"] ?? "Unknown"); ?>
        </p>

    </div>


    <div class="row">

        <!-- Lessons List -->

        <div class="col-md-4 mb-4">

            <div class="card shadow-sm">

                <div class="card-header lesson-card-header">
                    <strong>Course Lessons</strong>
                </div>

                <div class="list-group list-group-flush">

                    <?php if (count($lessons) > 0): ?>

                        <?php foreach ($lessons as $index => $lesson): ?>

                            <?php
                            $is_active = $selected_lesson
                                && (int) $lesson["id"] === (int) $selected_lesson["id"];
                            ?>

                            <a
                                href="lesson.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $lesson["id"]; ?>"
                                class="list-group-item list-group-item-action<?php echo $is_active ? " active" : ""; ?>"
                            >

                                <strong>
                                    <?php echo $index + 1; ?>.
                                </strong>

                                <?php echo htmlspecialchars($lesson["title"]); ?>

                            </a>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="p-3 text-muted">
                            No lessons available yet.
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <!-- Lesson Content -->

        <div class="col-md-8">

            <?php if ($selected_lesson): ?>

                <div class="card shadow-sm">

                    <div class="card-body p-4">

                        <small class="text-muted">
                            Lesson <?php echo $selected_index + 1; ?>
                            of <?php echo count($lessons); ?>
                        </small>

                        <h2 class="fw-bold">
                            <?php echo htmlspecialchars($selected_lesson["title"]); ?>
                        </h2>

                        <hr>


                        <!-- Video -->

                        <?php if ($video): ?>

                            <?php if ($video["type"] === "iframe"): ?>

                                <div class="ratio ratio-16x9 mb-3">

                                    <iframe
                                        src="<?php echo htmlspecialchars($video["src"]); ?>"
                                        title="Lesson Video"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        allowfullscreen
                                    ></iframe>

                                </div>

                            <?php elseif ($video["type"] === "video"): ?>

                                <video
                                    class="w-100 rounded mb-3"
                                    controls
                                    preload="metadata"
                                >
                                    <source src="<?php echo htmlspecialchars($video["src"]); ?>">
                                    Your browser does not support video.
                                </video>

                            <?php endif; ?>

                            <div class="mb-4">

                                <a
                                    href="<?php echo htmlspecialchars($video["original"]); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    <?php
                                    echo $video["type"] === "link"
                                        ? "▶ Watch Video"
                                        : "Open Video in New Tab";
                                    ?>
                                </a>

                            </div>

                        <?php endif; ?>


                        <!-- Content -->

                        <h5>
                            Lesson Content
                        </h5>

                        <?php if (trim($selected_lesson["content"] ?? "") !== ""): ?>

                            <p>
                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $selected_lesson["content"]
                                    )
                                );
                                ?>
                            </p>

                        <?php else: ?>

                            <p class="text-muted">
                                No written content for this lesson.
                            </p>

                        <?php endif; ?>


                        <!-- Previous / Next -->

                        <hr class="my-4">

                        <div class="d-flex justify-content-between gap-2">

                            <?php if ($prev_lesson): ?>

                                <a
                                    href="lesson.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $prev_lesson["id"]; ?>"
                                    class="btn btn-outline-secondary"
                                >
                                    ← Previous
                                </a>

                            <?php else: ?>

                                <span></span>

                            <?php endif; ?>


                            <?php if ($next_lesson): ?>

                                <a
                                    href="lesson.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $next_lesson["id"]; ?>"
                                    class="btn btn-primary"
                                >
                                    Next Lesson →
                                </a>

                            <?php else: ?>

                                <a
                                    href="course-details.php?id=<?php echo $course_id; ?>"
                                    class="btn btn-success"
                                >
                                    Finish ✓
                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php else: ?>

                <div class="alert alert-info">

                    No lesson is available for this course yet.

                </div>

            <?php endif; ?>

        </div>

    </div>


    <div class="mt-4">

        <a
            href="course-details.php?id=<?php echo $course_id; ?>"
            class="btn btn-outline-secondary"
        >
            ← Back to Course
        </a>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
