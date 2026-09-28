<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Student Only - admin / teacher are sent to their own course pages */

require_role(
    "student",
    $_SESSION["user_role"] === "admin"
        ? "../admin/courses.php"
        : "../teacher/courses/index.php"
);

$pageTitle = "Explore Courses - LearnHub";


/* Filters */

$search = trim($_GET["q"] ?? "");
$category = trim($_GET["category"] ?? "");

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


// Get all courses with teacher name
$stmt = $pdo->prepare(
    "SELECT
        courses.*,
        users.name AS teacher_name
     FROM courses
     LEFT JOIN users
        ON courses.teacher_id = users.id
     $where_sql
     ORDER BY courses.created_at DESC"
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


require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>


<style>

/* =====================================================
   LEARNHUB COURSES PAGE
   All classes are page-specific.
   This will NOT affect homepage/dashboard.
===================================================== */

.lh-courses-page {
    background: #f7f9fc;
    min-height: 100vh;
}


/* ================= HERO ================= */

.lh-courses-hero {
    position: relative;

    padding: 55px 0 65px;

    background:
        radial-gradient(
            circle at 10% 20%,
            rgba(13, 110, 253, 0.10),
            transparent 35%
        ),
        radial-gradient(
            circle at 90% 80%,
            rgba(111, 66, 193, 0.08),
            transparent 35%
        ),
        #ffffff;

    border-bottom: 1px solid #e9edf3;
}


.lh-courses-hero-content {
    max-width: 850px;
    margin: auto;

    text-align: center;
}


.lh-courses-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 7px 13px;

    background: #edf4ff;

    color: #0d6efd;

    border-radius: 30px;

    font-size: 11px;

    font-weight: 800;

    letter-spacing: 1px;
}


.lh-courses-eyebrow::before {
    content: "";

    width: 6px;
    height: 6px;

    background: #0d6efd;

    border-radius: 50%;
}


.lh-courses-hero h1 {
    margin: 20px 0 12px;

    color: #111827;

    font-size: clamp(38px, 5vw, 54px);

    font-weight: 800;

    letter-spacing: -1.8px;

    line-height: 1.1;
}


.lh-courses-hero h1 span {
    color: #0d6efd;
}


.lh-courses-hero p {
    max-width: 650px;

    margin: auto;

    color: #667085;

    font-size: 16px;

    line-height: 1.7;
}


/* ================= MAIN ================= */

.lh-courses-main {
    padding: 48px 0 90px;
}


/* ================= FILTER / SEARCH BAR ================= */

.lh-filter-bar {
    margin-bottom: 28px;

    padding: 14px;

    background: #ffffff;

    border: 1px solid #e3e8ef;

    border-radius: 16px;

    box-shadow: 0 3px 12px rgba(16, 24, 40, 0.04);
}


/* ================= SECTION HEADER ================= */

.lh-courses-header {
    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    margin-bottom: 28px;
}


.lh-courses-heading small {
    display: block;

    margin-bottom: 6px;

    color: #0d6efd;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 1.5px;
}


.lh-courses-heading h2 {
    margin: 0;

    color: #172033;

    font-size: 29px;

    font-weight: 800;

    letter-spacing: -0.7px;
}


.lh-courses-count {
    display: inline-flex;

    align-items: center;

    padding: 9px 15px;

    background: #ffffff;

    border: 1px solid #e3e8ef;

    border-radius: 30px;

    color: #344054;

    font-size: 12px;

    font-weight: 700;

    box-shadow: 0 3px 12px rgba(16, 24, 40, 0.04);
}


/* ================= COURSE CARD ================= */

.lh-course-card {
    position: relative;

    height: 100%;

    display: flex;

    flex-direction: column;

    overflow: hidden;

    background: #ffffff;

    border: 1px solid #e5e9ef;

    border-radius: 18px;

    box-shadow:
        0 4px 15px rgba(16, 24, 40, 0.045);

    transition:
        transform 0.3s ease,
        box-shadow 0.3s ease,
        border-color 0.3s ease;
}


.lh-course-card:hover {
    transform: translateY(-7px);

    border-color: #d4e3fb;

    box-shadow:
        0 18px 40px rgba(16, 24, 40, 0.10);
}


/* ================= IMAGE ================= */

.lh-course-image {
    position: relative;

    height: 205px;

    overflow: hidden;

    background: #edf1f6;
}


.lh-course-image img {
    width: 100%;
    height: 100%;

    display: block;

    object-fit: cover;

    transition: transform 0.5s ease;
}


.lh-course-card:hover .lh-course-image img {
    transform: scale(1.06);
}


/* ================= IMAGE OVERLAY ================= */

.lh-course-image::after {
    content: "";

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            to bottom,
            rgba(0,0,0,0.02),
            rgba(0,0,0,0.18)
        );

    pointer-events: none;
}


/* ================= CATEGORY ================= */

.lh-course-category {
    position: absolute;

    z-index: 2;

    top: 14px;
    left: 14px;

    padding: 7px 11px;

    background: rgba(255,255,255,0.96);

    color: #0d6efd;

    border-radius: 8px;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 0.4px;

    box-shadow:
        0 4px 12px rgba(0,0,0,0.10);
}


/* ================= PLACEHOLDER ================= */

.lh-course-placeholder {
    width: 100%;
    height: 100%;

    display: flex;

    align-items: center;
    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #eaf2ff,
            #f7f9fc
        );
}


.lh-course-placeholder-icon {
    width: 65px;
    height: 65px;

    display: flex;

    align-items: center;
    justify-content: center;

    background: #ffffff;

    border-radius: 18px;

    font-size: 28px;

    box-shadow:
        0 10px 25px rgba(16,24,40,0.08);
}


/* ================= CONTENT ================= */

.lh-course-content {
    flex: 1;

    padding: 21px 22px 18px;
}


.lh-course-title {
    margin: 0;

    color: #172033;

    font-size: 19px;

    font-weight: 750;

    line-height: 1.35;
}


.lh-course-description {
    min-height: 63px;

    margin: 10px 0 18px;

    color: #667085;

    font-size: 13px;

    line-height: 1.65;
}


/* ================= TEACHER ================= */

.lh-course-teacher {
    display: flex;

    align-items: center;

    gap: 10px;

    padding-top: 14px;

    border-top: 1px solid #edf0f4;
}


.lh-course-teacher-icon {
    width: 35px;
    height: 35px;

    flex-shrink: 0;

    display: flex;

    align-items: center;
    justify-content: center;

    background: #edf4ff;

    border-radius: 10px;

    font-size: 16px;
}


.lh-course-teacher-text small {
    display: block;

    margin-bottom: 2px;

    color: #98a2b3;

    font-size: 8px;

    font-weight: 800;

    letter-spacing: 0.9px;
}


.lh-course-teacher-text strong {
    display: block;

    color: #344054;

    font-size: 12px;

    font-weight: 700;
}


/* ================= FOOTER ================= */

.lh-course-footer {
    padding: 0 22px 22px;
}


.lh-course-button {
    width: 100%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 12px 14px;

    background: #0d6efd;

    color: #ffffff !important;

    text-decoration: none;

    border-radius: 10px;

    font-size: 13px;

    font-weight: 700;

    transition:
        background 0.2s ease,
        box-shadow 0.2s ease;
}


.lh-course-button:hover {
    background: #0b5ed7;

    box-shadow:
        0 7px 18px rgba(13, 110, 253, 0.22);
}


.lh-course-arrow {
    font-size: 17px;

    transition: transform 0.2s ease;
}


.lh-course-button:hover .lh-course-arrow {
    transform: translateX(4px);
}


/* ================= EMPTY STATE ================= */

.lh-courses-empty {
    max-width: 580px;

    margin: 30px auto;

    padding: 55px 30px;

    text-align: center;

    background: #ffffff;

    border: 1px solid #e5e9ef;

    border-radius: 18px;

    box-shadow:
        0 8px 25px rgba(16,24,40,0.05);
}


.lh-courses-empty-icon {
    width: 65px;
    height: 65px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin: auto;

    background: #edf4ff;

    border-radius: 17px;

    font-size: 27px;
}


.lh-courses-empty h3 {
    margin: 17px 0 7px;

    color: #172033;

    font-size: 21px;

    font-weight: 750;
}


.lh-courses-empty p {
    margin-bottom: 22px;

    color: #667085;

    font-size: 14px;
}


/* ================= RESPONSIVE ================= */

@media (max-width: 767px) {

    .lh-courses-hero {
        padding: 45px 0 50px;
    }

    .lh-courses-hero h1 {
        font-size: 39px;
    }

    .lh-courses-hero p {
        padding: 0 10px;

        font-size: 14px;
    }

    .lh-courses-main {
        padding: 40px 0 65px;
    }

    .lh-courses-header {
        align-items: flex-start;

        gap: 15px;
    }

    .lh-courses-heading h2 {
        font-size: 24px;
    }

    .lh-course-image {
        height: 200px;
    }

}

</style>


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
                    Explore practical courses, learn from structured
                    lessons and improve your skills at your own pace.
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
                        href="<?php echo $keep ? "courses.php" : "../index.php"; ?>"
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

                        <?php echo count($courses); ?>

                        <?php
                        echo count($courses) === 1
                            ? " Course"
                            : " Courses";
                        ?>

                    </div>

                </div>


                <!-- Course Grid -->

                <div class="row g-4">


                    <?php foreach ($courses as $course): ?>

                        <div class="col-md-6 col-lg-4">


                            <div class="lh-course-card">


                                <!-- Image -->

                                <div class="lh-course-image">

                                    <?php if (!empty($course["thumbnail"])): ?>

                                        <img
                                            src="../uploads/courses/<?php echo htmlspecialchars($course["thumbnail"]); ?>"
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

                                        <?php
                                        echo htmlspecialchars(
                                            $course["category"]
                                        );
                                        ?>

                                    </span>

                                </div>


                                <!-- Content -->

                                <div class="lh-course-content">


                                    <h3 class="lh-course-title">

                                        <?php
                                        echo htmlspecialchars(
                                            $course["title"]
                                        );
                                        ?>

                                    </h3>


                                    <p class="lh-course-description">

                                        <?php

                                        $description =
                                            $course["description"];

                                        if (strlen($description) > 120) {

                                            echo htmlspecialchars(
                                                substr(
                                                    $description,
                                                    0,
                                                    120
                                                )
                                            ) . "...";

                                        } else {

                                            echo htmlspecialchars(
                                                $description
                                            );

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

                                                <?php
                                                echo htmlspecialchars(
                                                    $course["teacher_name"]
                                                    ?? "Unknown"
                                                );
                                                ?>

                                            </strong>

                                        </div>

                                    </div>

                                </div>


                                <!-- Button -->

                                <div class="lh-course-footer">

                                    <a
                                        href="course-details.php?id=<?php echo $course["id"]; ?>"
                                        class="lh-course-button"
                                    >

                                        <span>
                                            View Course
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


            <?php endif; ?>


        </div>

    </section>

</div>


<?php

require_once "../includes/footer.php";

?>