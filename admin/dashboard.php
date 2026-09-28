<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


$pageTitle = "Admin Dashboard - LearnHub";


/* Total Users */

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE deleted_at IS NULL"
);

$total_users = $stmt->fetch()["total"];


/* Total Students */

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'student'
     AND deleted_at IS NULL"
);

$total_students = $stmt->fetch()["total"];


/* Total Teachers */

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'teacher'
     AND deleted_at IS NULL"
);

$total_teachers = $stmt->fetch()["total"];


/* Total Courses */

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM courses
     WHERE deleted_at IS NULL"
);

$total_courses = $stmt->fetch()["total"];


/* Total Enrollments */

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM enrollments"
);

$total_enrollments = $stmt->fetch()["total"];


/* Pending Teacher Requests */

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'teacher'
     AND status = 'pending'
     AND deleted_at IS NULL"
);

$pending_teachers = $stmt->fetch()["total"];


require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>


<div class="container py-5">


    <!-- Welcome -->

    <div class="mb-4">

        <h2 class="fw-bold">

            Welcome Admin,
            <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
            👋

        </h2>

        <p class="text-muted">

            Manage users and courses from the admin panel.

        </p>

    </div>



    <!-- Statistics -->

    <div class="row g-4 mb-5">


        <!-- Users -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Users
                    </h6>

                    <h2 class="fw-bold text-primary">

                        <?php echo $total_users; ?>

                    </h2>

                </div>

            </div>

        </div>



        <!-- Students -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Students
                    </h6>

                    <h2 class="fw-bold text-success">

                        <?php echo $total_students; ?>

                    </h2>

                </div>

            </div>

        </div>



        <!-- Teachers -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                    <div class="card-body">

                    <h6 class="text-muted">
                        Teachers
                    </h6>

                    <h2 class="fw-bold text-warning">

                        <?php echo $total_teachers; ?>

                    </h2>

                </div>

            </div>

        </div>



        <!-- Courses -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Courses
                    </h6>

                    <h2 class="fw-bold text-danger">

                        <?php echo $total_courses; ?>

                    </h2>

                </div>

            </div>

        </div>

    </div>



    <!-- Additional Statistics -->

    <div class="row g-4 mb-5">


        <div class="col-md-6">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h5 class="fw-bold">
                        Total Enrollments
                    </h5>

                    <h2 class="text-primary">

                        <?php echo $total_enrollments; ?>

                    </h2>

                    <p class="text-muted mb-0">

                        Total student course enrollments.

                    </p>

                </div>

            </div>

        </div>


        <div class="col-md-6">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h5 class="fw-bold">
                        Pending Teacher Requests
                    </h5>

                    <h2 class="<?php echo $pending_teachers > 0 ? "text-warning" : "text-muted"; ?>">

                        <?php echo $pending_teachers; ?>

                    </h2>

                    <p class="text-muted">

                        Teachers waiting for your approval.

                    </p>

                    <a
                        href="teacher-requests.php"
                        class="btn btn-warning"
                    >
                        Review Requests
                    </a>

                </div>

            </div>

        </div>


        <div class="col-md-6">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h5 class="fw-bold">
                        Admin Panel
                    </h5>

                    <p class="text-muted">

                        Manage registered users and available courses.

                    </p>

                    <a
                        href="users.php"
                        class="btn btn-primary me-2"
                    >
                        Manage Users
                    </a>

                    <a
                        href="courses.php"
                        class="btn btn-outline-primary"
                    >
                        Manage Courses
                    </a>

                </div>

            </div>

        </div>

    </div>



    <!-- Quick Actions -->

    <h3 class="fw-bold mb-3">
        Quick Actions
    </h3>


    <div class="d-flex gap-2 flex-wrap">


        <a
            href="users.php"
            class="btn btn-primary"
        >
            👥 View Users
        </a>


        <a
            href="teacher-requests.php"
            class="btn btn-warning"
        >
            ✅ Teacher Requests

            <?php if ($pending_teachers > 0): ?>

                <span class="badge soft-dark-badge">
                    <?php echo $pending_teachers; ?>
                </span>

            <?php endif; ?>

        </a>


        <a
            href="courses.php"
            class="btn btn-success"
        >
            📚 View Courses
        </a>


        <a
            href="enrollments.php"
            class="btn btn-outline-primary"
        >
            🎓 Enrollments
        </a>

        <a
            href="reports.php"
            class="btn btn-outline-primary"
        >
            📊 Reports
        </a>

        <a
            href="settings.php"
            class="btn btn-outline-secondary"
        >
            ⚙️ Settings
        </a>

        <a
            href="../auth/logout.php"
            class="btn btn-danger"
        >
            Logout
        </a>


    </div>


</div>


<?php require_once "../includes/footer.php"; ?>