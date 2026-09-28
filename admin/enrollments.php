<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Admin Only */

require_role("admin", "../index.php");

$pageTitle = "Manage Enrollments - LearnHub";


/* -----------------------------------------------------
   Filters  (?q=student or course   &course_id=   &page=)
----------------------------------------------------- */

$search = trim($_GET["q"] ?? "");

$filter_course = intval($_GET["course_id"] ?? 0);

$page = max(1, intval($_GET["page"] ?? 1));

$per_page = 20;

$where = [];
$params = [];

if ($search !== "") {

    $where[] = "(
        students.name LIKE ?
        OR students.email LIKE ?
        OR courses.title LIKE ?
    )";

    $like = "%" . $search . "%";

    array_push($params, $like, $like, $like);
}

if ($filter_course > 0) {

    $where[] = "courses.id = ?";
    $params[] = $filter_course;
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";


/* Courses for the filter dropdown */

$all_courses = $pdo->query(
    "SELECT id, title, deleted_at
     FROM courses
     ORDER BY title ASC"
)->fetchAll();


/* Summary numbers */

$total_enrollments = (int) $pdo->query(
    "SELECT COUNT(*) FROM enrollments"
)->fetchColumn();

$unique_students = (int) $pdo->query(
    "SELECT COUNT(DISTINCT student_id) FROM enrollments"
)->fetchColumn();

$recent_enrollments = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM enrollments
     WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
)->fetchColumn();


/* Count for paging */

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM enrollments
     INNER JOIN courses
        ON enrollments.course_id = courses.id
     LEFT JOIN users AS students
        ON enrollments.student_id = students.id
     $where_sql"
);

$stmt->execute($params);

$total_rows = (int) $stmt->fetchColumn();

$total_pages = max(1, (int) ceil($total_rows / $per_page));

$page = min($page, $total_pages);

$offset = ($page - 1) * $per_page;


/* Enrollments */

$stmt = $pdo->prepare(
    "SELECT
        enrollments.id AS enrollment_id,
        enrollments.enrolled_at,
        students.name AS student_name,
        students.email AS student_email,
        students.deleted_at AS student_deleted_at,
        courses.id AS course_id,
        courses.title AS course_title,
        courses.deleted_at AS course_deleted_at,
        teachers.name AS teacher_name
     FROM enrollments
     INNER JOIN courses
        ON enrollments.course_id = courses.id
     LEFT JOIN users AS students
        ON enrollments.student_id = students.id
     LEFT JOIN users AS teachers
        ON courses.teacher_id = teachers.id
     $where_sql
     ORDER BY enrollments.enrolled_at DESC, enrollments.id DESC
     LIMIT $per_page OFFSET $offset"
);

$stmt->execute($params);

$enrollments = $stmt->fetchAll();


/* Filters that must survive paging / removing */

$keep = [];

if ($search !== "") {
    $keep["q"] = $search;
}

if ($filter_course > 0) {
    $keep["course_id"] = $filter_course;
}

$return_query = $keep;

if ($page > 1) {
    $return_query["page"] = $page;
}

$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
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
                Manage Enrollments
            </h2>

            <p class="text-muted mb-0">
                See which student joined which course.
            </p>

        </div>

        <div class="d-flex gap-2">

            <a
                href="export.php?type=enrollments"
                class="btn btn-outline-success"
            >
                Export CSV
            </a>

            <a
                href="dashboard.php"
                class="btn btn-outline-secondary"
            >
                &larr; Dashboard
            </a>

        </div>

    </div>


    <!-- Summary -->

    <div class="row g-4 mb-4">

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Total Enrollments</h6>
                    <h2 class="fw-bold text-primary">
                        <?php echo $total_enrollments; ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Students Enrolled</h6>
                    <h2 class="fw-bold text-success">
                        <?php echo $unique_students; ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Last 30 Days</h6>
                    <h2 class="fw-bold text-warning">
                        <?php echo $recent_enrollments; ?>
                    </h2>
                </div>
            </div>
        </div>

    </div>


    <!-- Filter -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form method="GET" class="row g-2">

                <div class="col-lg-5">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Search student name, email or course..."
                        value="<?php echo $h($search); ?>"
                    >

                </div>

                <div class="col-lg-4">

                    <select
                        name="course_id"
                        class="form-select"
                    >

                        <option value="0">All Courses</option>

                        <?php foreach ($all_courses as $course): ?>

                            <option
                                value="<?php echo (int) $course["id"]; ?>"
                                <?php echo $filter_course === (int) $course["id"] ? "selected" : ""; ?>
                            >
                                <?php
                                echo $h($course["title"]);
                                echo $course["deleted_at"] !== null ? " (in trash)" : "";
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-lg-3 d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Search
                    </button>

                    <?php if ($keep): ?>

                        <a
                            href="enrollments.php"
                            class="btn btn-outline-secondary"
                        >
                            Clear
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </div>

    </div>


    <!-- Enrollments Table -->

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <?php if (count($enrollments) > 0): ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-dark">

                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Teacher</th>
                                <th>Enrolled On</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($enrollments as $index => $row): ?>

                                <tr>

                                    <td><?php echo $offset + $index + 1; ?></td>

                                    <td>

                                        <?php if ($row["student_name"] === null): ?>

                                            <span class="text-muted">Deleted user</span>

                                        <?php else: ?>

                                            <strong><?php echo $h($row["student_name"]); ?></strong>

                                            <?php if ($row["student_deleted_at"] !== null): ?>

                                                <span class="badge bg-danger">In user trash</span>

                                            <?php endif; ?>

                                        <?php endif; ?>

                                    </td>

                                    <td><?php echo $h($row["student_email"] ?? "-"); ?></td>

                                    <td>

                                        <a
                                            href="course-view.php?id=<?php echo (int) $row["course_id"]; ?>"
                                            class="badge bg-primary text-decoration-none"
                                        >
                                            <?php echo $h($row["course_title"]); ?>
                                        </a>

                                        <?php if ($row["course_deleted_at"] !== null): ?>

                                            <span class="badge bg-danger">In Trash</span>

                                        <?php endif; ?>

                                    </td>

                                    <td><?php echo $h($row["teacher_name"] ?? "Unknown"); ?></td>

                                    <td>
                                        <?php
                                        echo !empty($row["enrolled_at"])
                                            ? date("M d, Y", strtotime($row["enrolled_at"]))
                                            : "-";
                                        ?>
                                    </td>

                                    <td>

                                        <form
                                            method="POST"
                                            action="remove-enrollment.php"
                                            class="d-inline"
                                            onsubmit="return confirm('Remove this student from the course? Their account is not deleted.');"
                                        >

                                            <?php echo csrf_field(); ?>

                                            <input type="hidden" name="enrollment_id" value="<?php echo (int) $row["enrollment_id"]; ?>">
                                            <input type="hidden" name="course_id" value="<?php echo (int) $row["course_id"]; ?>">
                                            <input type="hidden" name="return_to" value="enrollments">
                                            <input type="hidden" name="return_query" value="<?php echo $h(http_build_query($return_query)); ?>">

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

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">

                    <small class="text-muted">
                        Showing <?php echo $offset + 1; ?>-<?php echo $offset + count($enrollments); ?>
                        of <?php echo $total_rows; ?>
                    </small>

                    <?php render_pagination($page, $total_pages, $keep); ?>

                </div>

            <?php else: ?>

                <div class="text-center p-5">

                    <div class="fs-1">
                        &#128101;
                    </div>

                    <h4 class="fw-bold mt-3">
                        No Enrollments Found
                    </h4>

                    <p class="text-muted mb-0">

                        <?php echo $keep ? "Nothing matches your search." : "No student has enrolled in any course yet."; ?>

                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
