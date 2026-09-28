<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Only teacher can access */

require_role("teacher", "../student/dashboard.php");


$pageTitle = "My Students - LearnHub";

$teacher_id = $_SESSION["user_id"];


/* Filters (optional) */

$filter_course = intval($_GET["course_id"] ?? 0);
$search = trim($_GET["q"] ?? "");


/* Teacher's Own Courses - used for the filter dropdown */

$stmt = $pdo->prepare(
    "SELECT id, title
     FROM courses
     WHERE teacher_id = ?
     AND deleted_at IS NULL
     ORDER BY title ASC"
);

$stmt->execute([$teacher_id]);

$my_courses = $stmt->fetchAll();


/* Students Enrolled In Teacher's Courses */

$sql =
    "SELECT
        users.id AS student_id,
        users.name AS student_name,
        users.email AS student_email,
        courses.id AS course_id,
        courses.title AS course_title,
        enrollments.enrolled_at
     FROM enrollments
     INNER JOIN courses
        ON enrollments.course_id = courses.id
     INNER JOIN users
        ON enrollments.student_id = users.id
     WHERE courses.teacher_id = ?";

$params = [$teacher_id];

if ($filter_course > 0) {

    $sql .= " AND courses.id = ?";

    $params[] = $filter_course;
}

if ($search !== "") {

    $sql .= " AND (users.name LIKE ? OR users.email LIKE ?)";

    $like = "%" . $search . "%";

    array_push($params, $like, $like);
}

$sql .= " ORDER BY enrollments.enrolled_at DESC";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$enrollments = $stmt->fetchAll();


/* Unique Student Count */

$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT enrollments.student_id) AS total
     FROM enrollments
     INNER JOIN courses
        ON enrollments.course_id = courses.id
     WHERE courses.teacher_id = ?"
);

$stmt->execute([$teacher_id]);

$unique_students = $stmt->fetch()["total"];


require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>


<div class="container py-5">


    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="fw-bold">
                My Students
            </h1>

            <p class="text-muted mb-0">
                Students enrolled in the courses you created.
            </p>

        </div>


        <a
            href="dashboard.php"
            class="btn btn-outline-secondary"
        >
            &larr; Dashboard
        </a>

    </div>



    <!-- Summary -->

    <div class="row g-4 mb-4">

        <div class="col-md-6 col-lg-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Unique Students
                    </h6>

                    <h2 class="fw-bold text-primary">
                        <?php echo $unique_students; ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Enrollments
                    </h6>

                    <h2 class="fw-bold text-success">
                        <?php echo count($enrollments); ?>
                    </h2>

                </div>

            </div>

        </div>


        <!-- Search + Course Filter -->

        <div class="col-lg-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <h6 class="text-muted mb-2">
                        Search / Filter
                    </h6>

                    <form method="GET">

                        <div class="d-flex flex-column gap-2">

                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                placeholder="Search student name or email..."
                                value="<?php echo htmlspecialchars($search); ?>"
                            >

                            <div class="d-flex gap-2">

                                <select
                                    name="course_id"
                                    class="form-select"
                                >

                                    <option value="0">
                                        All Courses
                                    </option>

                                    <?php foreach ($my_courses as $course): ?>

                                        <option
                                            value="<?php echo $course["id"]; ?>"
                                            <?php
                                            echo ($filter_course == $course["id"])
                                                ? "selected"
                                                : "";
                                            ?>
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $course["title"]
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>


                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    Go
                                </button>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>



    <!-- Students Table -->

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

                                <th>Enrolled On</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php $count = 1; ?>

                            <?php foreach ($enrollments as $row): ?>

                                <tr>

                                    <td>
                                        <?php echo $count; ?>
                                    </td>


                                    <td>

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $row["student_name"]
                                            );
                                            ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["student_email"]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span class="badge bg-primary">

                                            <?php
                                            echo htmlspecialchars(
                                                $row["course_title"]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php

                                        echo !empty($row["enrolled_at"])
                                            ? date(
                                                "M d, Y",
                                                strtotime(
                                                    $row["enrolled_at"]
                                                )
                                            )
                                            : "-";

                                        ?>

                                    </td>

                                </tr>

                                <?php $count++; ?>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="text-center p-5">

                    <div class="fs-1">
                        &#128101;
                    </div>

                    <h4 class="fw-bold mt-3">
                        <?php echo ($search !== "" || $filter_course > 0) ? "No Students Found" : "No Students Yet"; ?>
                    </h4>

                    <p class="text-muted mb-0">
                        <?php
                        echo ($search !== "" || $filter_course > 0)
                            ? "Try a different name, email or course."
                            : "No student has enrolled in your courses yet.";
                        ?>
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>


</div>


<?php require_once "../includes/footer.php"; ?>
