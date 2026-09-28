<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


$pageTitle = "Manage Courses - LearnHub";


/* -----------------------------------------------------
   Filters  (?q=title, description or teacher   &category=)
----------------------------------------------------- */

$search = trim($_GET["q"] ?? "");
$filter_category = trim($_GET["category"] ?? "");

$where = ["courses.deleted_at IS NULL"];
$params = [];

if ($search !== "") {

    $where[] = "(courses.title LIKE ?
                 OR courses.description LIKE ?
                 OR users.name LIKE ?)";

    $like = "%" . $search . "%";

    array_push($params, $like, $like, $like);
}

if ($filter_category !== "") {

    $where[] = "courses.category = ?";
    $params[] = $filter_category;
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


/* Get All Courses */

$stmt = $pdo->prepare(
    "SELECT
        courses.id,
        courses.title,
        courses.description,
        courses.category,
        courses.thumbnail,
        courses.created_at,
        users.name AS teacher_name,
        (SELECT COUNT(*)
         FROM lessons
         WHERE lessons.course_id = courses.id
         AND lessons.deleted_at IS NULL) AS lesson_count,
        (SELECT COUNT(*)
         FROM quizzes
         WHERE quizzes.course_id = courses.id) AS quiz_count,
        (SELECT COUNT(*)
         FROM enrollments
         WHERE enrollments.course_id = courses.id) AS enrollment_count
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

if ($filter_category !== "") {
    $keep["category"] = $filter_category;
}


require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>


<div class="container py-5">


    <!-- Success Message -->

    <?php if (isset($_SESSION["success"])): ?>

        <div class="alert alert-success">

            <?php

            echo htmlspecialchars(
                $_SESSION["success"]
            );

            unset($_SESSION["success"]);

            ?>

        </div>

    <?php endif; ?>


    <!-- Error Message -->

    <?php if (isset($_SESSION["error"])): ?>

        <div class="alert alert-danger">

            <?php

            echo htmlspecialchars(
                $_SESSION["error"]
            );

            unset($_SESSION["error"]);

            ?>

        </div>

    <?php endif; ?>



    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                Manage Courses
            </h2>

            <p class="text-muted mb-0">

                View all courses available on LearnHub.

            </p>

        </div>


        <div class="d-flex gap-2">

            <a
                href="course-trash.php"
                class="btn btn-outline-danger"
            >
                🗑 Trash
            </a>

            <a
                href="dashboard.php"
                class="btn btn-outline-secondary"
            >
                ← Dashboard
            </a>

        </div>

    </div>



    <!-- Search / Filter -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form method="GET" class="row g-2">

                <div class="col-lg-6">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Search title, description or teacher..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>

                <div class="col-lg-3">

                    <select name="category" class="form-select">

                        <option value="">All Categories</option>

                        <?php foreach ($categories as $cat): ?>

                            <option
                                value="<?php echo htmlspecialchars($cat); ?>"
                                <?php echo $filter_category === $cat ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($cat); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-lg-3 d-flex gap-2">

                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>

                    <?php if ($keep): ?>

                        <a href="courses.php" class="btn btn-outline-secondary">
                            Clear
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </div>

    </div>



    <!-- Courses -->

    <?php if (count($courses) > 0): ?>


        <div class="row g-4">


            <?php foreach ($courses as $course): ?>


                <div class="col-md-6 col-lg-4">


                    <div class="card shadow-sm border-0 h-100">


                        <!-- Course Thumbnail -->

                        <?php if (!empty($course["thumbnail"])): ?>

                            <img
                                src="../uploads/courses/<?php echo htmlspecialchars($course["thumbnail"]); ?>"
                                class="card-img-top"
                                style="height: 200px; object-fit: cover;"
                                alt="Course Thumbnail"
                            >

                        <?php else: ?>

                            <div
                                class="bg-secondary text-white d-flex align-items-center justify-content-center"
                                style="height: 200px; font-size: 60px;"
                            >

                                📚

                            </div>

                        <?php endif; ?>



                        <!-- Card Body -->

                        <div
                            class="card-body d-flex flex-column"
                        >


                            <!-- Category + Status -->
                            <div
                                class="d-flex justify-content-between align-items-start mb-2"
                            >
                                <span class="badge bg-primary">
                                    <?php
                                    echo htmlspecialchars(
                                        $course["category"]
                                    );
                                    ?>
                                </span>
                                <span>
                                    <?php if ((int) $course["lesson_count"] === 0): ?>
                                        <span class="badge bg-warning text-dark">
                                            No lessons
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge bg-success">
                                        Active
                                    </span>
                                </span>
                            </div>



                            <!-- Title -->

                            <h5 class="fw-bold">

                                <?php

                                echo htmlspecialchars(
                                    $course["title"]
                                );

                                ?>

                            </h5>



                            <!-- Teacher -->

                            <p class="text-muted mb-2">

                                Teacher:

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $course["teacher_name"]
                                        ?? "Unknown"
                                    );

                                    ?>

                                </strong>

                            </p>



                            <!-- Description -->

                            <p class="card-text">

                                <?php

                                echo htmlspecialchars(
                                    substr(
                                        $course["description"],
                                        0,
                                        120
                                    )
                                );

                                if (
                                    strlen(
                                        $course["description"]
                                    ) > 120
                                ) {

                                    echo "...";

                                }

                                ?>

                            </p>



                            <!-- Lessons / Quizzes / Enrollments -->
                            <div class="d-flex text-center border rounded py-2 mb-3">
                                <div class="flex-fill">
                                    <strong><?php echo (int) $course["lesson_count"]; ?></strong>
                                    <br>
                                    <small class="text-muted">Lessons</small>
                                </div>
                                <div class="flex-fill border-start border-end">
                                    <strong><?php echo (int) $course["quiz_count"]; ?></strong>
                                    <br>
                                    <small class="text-muted">Quizzes</small>
                                </div>
                                <div class="flex-fill">
                                    <strong><?php echo (int) $course["enrollment_count"]; ?></strong>
                                    <br>
                                    <small class="text-muted">Enrolled</small>
                                </div>
                            </div>

                            <!-- Created Date -->

                            <small class="text-muted mb-3">

                                Created:

                                <?php

                                echo date(
                                    "M d, Y",
                                    strtotime(
                                        $course["created_at"]
                                    )
                                );

                                ?>

                            </small>



                            <!-- Actions -->

                            <div class="mt-auto d-flex gap-2">


                                <a
                                    href="../student/course-details.php?id=<?php echo $course["id"]; ?>"
                                    class="btn btn-outline-primary btn-sm"
                                >

                                    View

                                </a>
                                <a
                                    href="course-view.php?id=<?php echo $course["id"]; ?>#manage"
                                    class="btn btn-outline-secondary btn-sm"
                                >
                                    Manage
                                </a>


                                <a
                                    href="delete-course.php?id=<?php echo $course["id"]; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this course? All related lessons, quizzes and enrollments will also be deleted.');"
                                >

                                    Delete

                                </a>


                            </div>


                        </div>

                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="alert alert-info">

            <?php echo $keep ? "No courses match your search." : "No courses found."; ?>

        </div>


    <?php endif; ?>


</div>


<?php require_once "../includes/footer.php"; ?>