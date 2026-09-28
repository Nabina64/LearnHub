<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

// Only teacher can access
require_role("teacher", "../../student/dashboard.php");

$pageTitle = "My Courses - LearnHub";

$teacherId = $_SESSION["user_id"];

/* Search */

$search = trim($_GET["q"] ?? "");

$where = ["teacher_id = ?", "deleted_at IS NULL"];
$params = [$teacherId];

if ($search !== "") {

    $where[] = "(title LIKE ? OR description LIKE ? OR category LIKE ?)";

    $like = "%" . $search . "%";

    array_push($params, $like, $like, $like);
}

$where_sql = "WHERE " . implode(" AND ", $where);

// Get courses created by logged-in teacher
$stmt = $pdo->prepare(
    "SELECT
        courses.*,
        (
            SELECT COUNT(*)
            FROM course_views
            WHERE course_views.course_id = courses.id
        ) AS total_views,
        (
            SELECT COUNT(*)
            FROM enrollments
            WHERE enrollments.course_id = courses.id
        ) AS total_students
     FROM courses
     $where_sql
     ORDER BY created_at DESC"
);

$stmt->execute($params);

$courses = $stmt->fetchAll();

require_once "../../includes/header.php";
require_once "../../includes/navbar.php";

?>

<div class="container py-5">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="fw-bold">
                My Courses
            </h1>

            <p class="text-muted mb-0">
                Manage the courses you have created.
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
                + Create Course
            </a>

        </div>

    </div>


    <!-- Success Message -->

    <?php if (isset($_SESSION["success"])): ?>

        <div class="alert alert-success">

            <?php

            echo htmlspecialchars($_SESSION["success"]);

            unset($_SESSION["success"]);

            ?>

        </div>

    <?php endif; ?>


    <!-- Search -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form method="GET" class="row g-2">

                <div class="col-lg-9">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Search your courses by title, description or category..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>

                <div class="col-lg-3 d-flex gap-2">

                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>

                    <?php if ($search !== ""): ?>

                        <a href="index.php" class="btn btn-outline-secondary">
                            Clear
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </div>

    </div>


    <!-- Courses -->

    <?php if (empty($courses)): ?>

        <div class="card border-0 shadow-sm">

            <div class="card-body text-center p-5">

                <div class="fs-1">
                    📚
                </div>

                <h4 class="fw-bold mt-3">
                    <?php echo $search !== "" ? "No Courses Found" : "No Courses Yet"; ?>
                </h4>

                <p class="text-muted">
                    <?php echo $search !== ""
                        ? "Try a different search term."
                        : "You haven't created any course yet."; ?>
                </p>

                <a
                    href="<?php echo $search !== "" ? "index.php" : "create.php"; ?>"
                    class="btn btn-primary"
                >
                    <?php echo $search !== "" ? "Show All Courses" : "Create Your First Course"; ?>
                </a>

            </div>

        </div>

    <?php else: ?>

        <div class="row g-4">

            <?php foreach ($courses as $course): ?>

                <div class="col-md-6 col-lg-4">

                    <div class="card h-100 border-0 shadow-sm">


                        <!-- Thumbnail -->

                        <?php if (!empty($course["thumbnail"])): ?>

                            <img
                                src="../../uploads/courses/<?php echo htmlspecialchars($course["thumbnail"]); ?>"
                                class="card-img-top"
                                alt="Course Thumbnail"
                                style="height: 200px; object-fit: cover;"
                            >

                        <?php else: ?>

                            <div
                                class="bg-light d-flex align-items-center justify-content-center"
                                style="height: 200px;"
                            >

                                <span class="fs-1">
                                    📚
                                </span>

                            </div>

                        <?php endif; ?>


                        <!-- Course Details -->

                        <div class="card-body">

                            <span class="badge bg-primary mb-2">
                                <?php echo htmlspecialchars($course["category"]); ?>
                            </span>

                            <h5 class="card-title fw-bold">

                                <?php
                                echo htmlspecialchars($course["title"]);
                                ?>

                            </h5>

                            <p class="card-text text-muted">

                                <?php

                                $description =
                                    $course["description"];

                                if (strlen($description) > 100) {

                                    echo htmlspecialchars(
                                        substr($description, 0, 100)
                                    ) . "...";

                                } else {

                                    echo htmlspecialchars(
                                        $description
                                    );
                                }

                                ?>

                            </p>


                            <!-- Stats -->

                            <div class="d-flex gap-3 text-muted small">

                                <span>
                                    &#128065;
                                    <strong>
                                        <?php echo $course["total_views"]; ?>
                                    </strong>
                                    views
                                </span>

                                <span>
                                    &#128101;
                                    <strong>
                                        <?php echo $course["total_students"]; ?>
                                    </strong>
                                    students
                                </span>

                            </div>

                        </div>


                        <!-- Actions -->

                        <div class="card-footer bg-white border-0 p-3">

                            <div class="d-flex gap-2">

                                <a
                                    href="edit.php?id=<?php echo $course["id"]; ?>"
                                    class="btn btn-sm btn-outline-primary flex-fill"
                                >
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    action="delete.php"
                                    class="flex-fill"
                                    onsubmit="return confirm('Are you sure you want to delete this course?');"
                                >
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo $course["id"]; ?>">
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger w-100"
                                    >
                                        Delete
                                    </button>
                                </form>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>


<?php

require_once "../../includes/footer.php";

?>