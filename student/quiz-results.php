<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Student Only */

require_role("student", "../index.php");

$pageTitle = "Quiz Results - LearnHub";

$student_id = $_SESSION["user_id"];


/* Optional filter: one course */

$filter_course = intval($_GET["course_id"] ?? 0);

$page = max(1, intval($_GET["page"] ?? 1));

$per_page = 15;


/* Courses in which I attempted a quiz (for the filter) */

$stmt = $pdo->prepare(
    "SELECT DISTINCT courses.id, courses.title
     FROM quiz_results
     INNER JOIN quizzes
        ON quiz_results.quiz_id = quizzes.id
     INNER JOIN courses
        ON quizzes.course_id = courses.id
     WHERE quiz_results.student_id = ?
     ORDER BY courses.title ASC"
);

$stmt->execute([$student_id]);

$filter_courses = $stmt->fetchAll();


/* Summary (all attempts, ignores the filter) */

$stmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS attempts,
        COUNT(DISTINCT quiz_results.quiz_id) AS quizzes_taken,
        AVG(quiz_results.score
            / NULLIF(quiz_results.total_questions, 0)) * 100 AS average,
        MAX(quiz_results.score
            / NULLIF(quiz_results.total_questions, 0)) * 100 AS best
     FROM quiz_results
     INNER JOIN quizzes
        ON quiz_results.quiz_id = quizzes.id
     WHERE quiz_results.student_id = ?"
);

$stmt->execute([$student_id]);

$summary = $stmt->fetch();


/* Results (with paging) */

$where = "WHERE quiz_results.student_id = ?";
$params = [$student_id];

if ($filter_course > 0) {

    $where .= " AND courses.id = ?";
    $params[] = $filter_course;
}

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM quiz_results
     INNER JOIN quizzes
        ON quiz_results.quiz_id = quizzes.id
     INNER JOIN courses
        ON quizzes.course_id = courses.id
     $where"
);

$stmt->execute($params);

$total_rows = (int) $stmt->fetchColumn();

$total_pages = max(1, (int) ceil($total_rows / $per_page));

$page = min($page, $total_pages);

$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare(
    "SELECT
        quiz_results.id,
        quiz_results.score,
        quiz_results.total_questions,
        quiz_results.attempted_at,
        quizzes.id AS quiz_id,
        quizzes.title AS quiz_title,
        courses.title AS course_title,
        courses.deleted_at AS course_deleted_at
     FROM quiz_results
     INNER JOIN quizzes
        ON quiz_results.quiz_id = quizzes.id
     INNER JOIN courses
        ON quizzes.course_id = courses.id
     $where
     ORDER BY quiz_results.attempted_at DESC, quiz_results.id DESC
     LIMIT $per_page OFFSET $offset"
);

$stmt->execute($params);

$results = $stmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                Quiz Results
            </h2>

            <p class="text-muted mb-0">
                Every quiz you have attempted.
            </p>

        </div>

        <a
            href="my-courses.php"
            class="btn btn-outline-primary"
        >
            My Courses
        </a>

    </div>


    <!-- Summary -->

    <div class="row g-4 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Attempts</h6>
                    <h2 class="fw-bold text-primary">
                        <?php echo (int) $summary["attempts"]; ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Quizzes Taken</h6>
                    <h2 class="fw-bold text-success">
                        <?php echo (int) $summary["quizzes_taken"]; ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Average Score</h6>
                    <h2 class="fw-bold text-warning">
                        <?php
                        echo $summary["average"] !== null
                            ? number_format((float) $summary["average"], 1) . "%"
                            : "-";
                        ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Best Score</h6>
                    <h2 class="fw-bold">
                        <?php
                        echo $summary["best"] !== null
                            ? number_format((float) $summary["best"], 1) . "%"
                            : "-";
                        ?>
                    </h2>
                </div>
            </div>
        </div>

    </div>


    <!-- Results -->

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <?php if (count($filter_courses) > 1 || $filter_course > 0): ?>

                <form method="GET" class="d-flex gap-2 mb-3">

                    <select
                        name="course_id"
                        class="form-select"
                        style="max-width: 320px;"
                    >

                        <option value="0">All Courses</option>

                        <?php foreach ($filter_courses as $course): ?>

                            <option
                                value="<?php echo $course["id"]; ?>"
                                <?php echo $filter_course == $course["id"] ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($course["title"]); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Go
                    </button>

                </form>

            <?php endif; ?>


            <?php if (count($results) > 0): ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-dark">

                            <tr>
                                <th>Quiz</th>
                                <th>Course</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($results as $result): ?>

                                <?php

                                $percentage = $result["total_questions"] > 0
                                    ? ($result["score"] / $result["total_questions"]) * 100
                                    : 0;

                                $badge = $percentage >= 70
                                    ? "bg-success"
                                    : ($percentage >= 40 ? "bg-warning text-dark" : "bg-danger");

                                ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php echo htmlspecialchars($result["quiz_title"]); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($result["course_title"]); ?>
                                    </td>

                                    <td>
                                        <?php echo $result["score"] . " / " . $result["total_questions"]; ?>
                                    </td>

                                    <td>
                                        <span class="badge <?php echo $badge; ?>">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </span>
                                    </td>

                                    <td>
                                        <?php echo date("M d, Y", strtotime($result["attempted_at"])); ?>
                                    </td>

                                    <td class="text-nowrap">

                                        <a
                                            href="quiz-result.php?id=<?php echo $result["id"]; ?>"
                                            class="btn btn-outline-primary btn-sm"
                                        >
                                            View
                                        </a>

                                        <?php if ($result["course_deleted_at"] === null): ?>

                                            <a
                                                href="quiz.php?quiz_id=<?php echo $result["quiz_id"]; ?>"
                                                class="btn btn-primary btn-sm"
                                            >
                                                Retake
                                            </a>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <?php
                render_pagination(
                    $page,
                    $total_pages,
                    $filter_course > 0 ? ["course_id" => $filter_course] : []
                );
                ?>

            <?php else: ?>

                <div class="text-center p-5">

                    <div class="fs-1">
                        📝
                    </div>

                    <h4 class="fw-bold mt-3">
                        No Quiz Results Yet
                    </h4>

                    <p class="text-muted mb-3">
                        Take a quiz from one of your courses and your results will appear here.
                    </p>

                    <a
                        href="my-courses.php"
                        class="btn btn-primary"
                    >
                        Go to My Courses
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
