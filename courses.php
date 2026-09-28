<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";

/**
 * PUBLIC course list - anyone can see it, no login needed.
 * Students press "View Course", visitors are asked to login.
 */

$pageTitle = "Courses - LearnHub";
$extraCss = ["public.css"];

$is_logged_in = isset($_SESSION["user_id"]);
$user_role = $_SESSION["user_role"] ?? "";


/* Filters */

$search = trim($_GET["q"] ?? "");

$category = trim($_GET["category"] ?? "");

$page = max(1, intval($_GET["page"] ?? 1));

$per_page = 12;

$where = ["courses.deleted_at IS NULL"];
$params = [];

if ($search !== "") {

    $where[] = "(courses.title LIKE ?
                 OR courses.description LIKE ?
                 OR users.name LIKE ?)";

    $like = "%" . $search . "%";

    array_push($params, $like, $like, $like);
}

if ($category !== "") {

    $where[] = "courses.category = ?";
    $params[] = $category;
}

$where_sql = "WHERE " . implode(" AND ", $where);


/* Categories for the dropdown */

$categories = $pdo->query(
    "SELECT DISTINCT category
     FROM courses
     WHERE deleted_at IS NULL
     AND category <> ''
     ORDER BY category ASC"
)->fetchAll(PDO::FETCH_COLUMN);


/* Count + page */

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM courses
     LEFT JOIN users
        ON courses.teacher_id = users.id
     $where_sql"
);

$stmt->execute($params);

$total_courses = (int) $stmt->fetchColumn();

$total_pages = max(1, (int) ceil($total_courses / $per_page));

$page = min($page, $total_pages);

$offset = ($page - 1) * $per_page;


$stmt = $pdo->prepare(
    "SELECT
        courses.*,
        users.name AS teacher_name
     FROM courses
     LEFT JOIN users
        ON courses.teacher_id = users.id
     $where_sql
     ORDER BY courses.created_at DESC, courses.id DESC
     LIMIT $per_page OFFSET $offset"
);

$stmt->execute($params);

$courses = $stmt->fetchAll();

$keep = [];

if ($search !== "") {
    $keep["q"] = $search;
}

if ($category !== "") {
    $keep["category"] = $category;
}


/* Where does the button of a course card go? */

function public_course_link($course_id, $is_logged_in, $user_role)
{
    if (!$is_logged_in) {
        return ["auth/login.php", "Login to View"];
    }

    if ($user_role === "student") {
        return ["student/course-details.php?id=" . $course_id, "View Course"];
    }

    if ($user_role === "admin") {
        return ["admin/course-view.php?id=" . $course_id, "View Course"];
    }

    return ["teacher/dashboard.php", "Go to Dashboard"];
}

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
?>

<div class="lh-courses-page">

    <!-- ================= HERO ================= -->

    <section class="lh-courses-hero">

        <div class="container">

            <div class="lh-courses-hero-content">

                <span class="lh-courses-eyebrow">
                    LEARNHUB COURSES
                </span>

                <h1>
                    Learn something.
                    <span>Build something.</span>
                </h1>

                <p>
                    Browse practical courses taught by real teachers.
                    <?php if (!$is_logged_in): ?>
                        Create a free account to enroll and start learning.
                    <?php endif; ?>
                </p>

            </div>

        </div>

    </section>


    <!-- ================= COURSES ================= -->

    <section class="lh-courses-main">

        <div class="container">

            <!-- Search -->

            <form method="GET" class="lh-filter-bar">

                <div class="row g-2">

                    <div class="col-lg-6">

                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            placeholder="Search course, topic or teacher..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                    </div>

                    <div class="col-lg-3">

                        <select
                            name="category"
                            class="form-select"
                        >

                            <option value="">All Categories</option>

                            <?php foreach ($categories as $cat): ?>

                                <option
                                    value="<?php echo htmlspecialchars($cat); ?>"
                                    <?php echo $category === $cat ? "selected" : ""; ?>
                                >
                                    <?php echo htmlspecialchars($cat); ?>
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
                                href="courses.php"
                                class="btn btn-outline-secondary"
                            >
                                Clear
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </form>


            <?php if (empty($courses)): ?>

                <!-- Empty -->

                <div class="lh-courses-empty">

                    <div class="lh-courses-empty-icon">
                        📚
                    </div>

                    <h3>
                        <?php echo $keep ? "No Courses Found" : "No Courses Available"; ?>
                    </h3>

                    <p>
                        <?php
                        echo $keep
                            ? "Try a different word or category."
                            : "Courses will appear here when teachers create them.";
                        ?>
                    </p>

                    <a
                        href="<?php echo $keep ? "courses.php" : "index.php"; ?>"
                        class="btn btn-primary px-4"
                    >
                        <?php echo $keep ? "Show All Courses" : "Back to Home"; ?>
                    </a>

                </div>

            <?php else: ?>

                <!-- Section Header -->

                <div class="lh-courses-header">

                    <div class="lh-courses-heading">

                        <small>
                            AVAILABLE COURSES
                        </small>

                        <h2>
                            Start learning today
                        </h2>

                    </div>

                    <div class="lh-courses-count">

                        <?php echo $total_courses; ?>
                        <?php echo $total_courses === 1 ? " Course" : " Courses"; ?>

                    </div>

                </div>


                <!-- Course Grid -->

                <div class="row g-4">

                    <?php foreach ($courses as $course): ?>

                        <?php
                        [$link, $label] = public_course_link(
                            $course["id"],
                            $is_logged_in,
                            $user_role
                        );
                        ?>

                        <div class="col-md-6 col-lg-4">

                            <div class="lh-course-card">

                                <!-- Image -->

                                <div class="lh-course-image">

                                    <?php if (!empty($course["thumbnail"]) && is_file(__DIR__ . "/uploads/courses/" . basename($course["thumbnail"]))): ?>

                                        <img
                                            src="uploads/courses/<?php echo htmlspecialchars($course["thumbnail"]); ?>"
                                            alt="<?php echo htmlspecialchars($course["title"]); ?>"
                                        >

                                    <?php else: ?>

                                        <div class="lh-course-placeholder">

                                            <div class="lh-course-placeholder-icon">
                                                📚
                                            </div>

                                        </div>

                                    <?php endif; ?>

                                    <span class="lh-course-category">
                                        <?php echo htmlspecialchars($course["category"]); ?>
                                    </span>

                                </div>


                                <!-- Content -->

                                <div class="lh-course-content">

                                    <h3 class="lh-course-title">
                                        <?php echo htmlspecialchars($course["title"]); ?>
                                    </h3>

                                    <p class="lh-course-description">

                                        <?php
                                        $description = $course["description"];

                                        if (strlen($description) > 120) {

                                            echo htmlspecialchars(
                                                substr($description, 0, 120)
                                            ) . "...";

                                        } else {

                                            echo htmlspecialchars($description);
                                        }
                                        ?>

                                    </p>

                                    <!-- Teacher -->

                                    <div class="lh-course-teacher">

                                        <div class="lh-course-teacher-icon">
                                            👨‍🏫
                                        </div>

                                        <div class="lh-course-teacher-text">

                                            <small>
                                                COURSE INSTRUCTOR
                                            </small>

                                            <strong>
                                                <?php echo htmlspecialchars($course["teacher_name"] ?? "Unknown"); ?>
                                            </strong>

                                        </div>

                                    </div>

                                </div>


                                <!-- Button -->

                                <div class="lh-course-footer">

                                    <a
                                        href="<?php echo htmlspecialchars($link); ?>"
                                        class="lh-course-button"
                                    >

                                        <span>
                                            <?php echo htmlspecialchars($label); ?>
                                        </span>

                                        <span class="lh-course-arrow">
                                            →
                                        </span>

                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>


                <div class="mt-5">
                    <?php render_pagination($page, $total_pages, $keep); ?>
                </div>

            <?php endif; ?>

        </div>

    </section>

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
