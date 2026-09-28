<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Teacher Only */

require_role("teacher", "../index.php");

$pageTitle = "My Profile - LearnHub";

$teacher_id = $_SESSION["user_id"];


/* Get Teacher Information */

$stmt = $pdo->prepare(
    "SELECT
        id,
        name,
        email,
        role,
        status,
        approved_at,
        created_at
     FROM users
     WHERE id = ?
     AND role = 'teacher'
     AND deleted_at IS NULL"
);

$stmt->execute([$teacher_id]);

$user = $stmt->fetch();

/* User Not Found */

if (!$user) {

    redirect("dashboard.php");
}


/* Teaching Summary */

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM courses
     WHERE teacher_id = ?
     AND deleted_at IS NULL"
);

$stmt->execute([$teacher_id]);

$total_courses = (int) $stmt->fetchColumn();


$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM lessons
     INNER JOIN courses
        ON lessons.course_id = courses.id
     WHERE courses.teacher_id = ?
     AND courses.deleted_at IS NULL
     AND lessons.deleted_at IS NULL"
);

$stmt->execute([$teacher_id]);

$total_lessons = (int) $stmt->fetchColumn();


$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT enrollments.student_id)
     FROM enrollments
     INNER JOIN courses
        ON enrollments.course_id = courses.id
     WHERE courses.teacher_id = ?
     AND courses.deleted_at IS NULL"
);

$stmt->execute([$teacher_id]);

$total_students = (int) $stmt->fetchColumn();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <!-- Page Header -->

    <div class="mb-4">

        <h2 class="fw-bold">
            My Profile
        </h2>

        <p class="text-muted">
            View your account information.
        </p>

    </div>


    <div class="row justify-content-center">

        <div class="col-md-7 col-lg-6">

            <div class="card shadow-sm border-0">

                <!-- Profile Header -->

                <div class="card-body text-center p-4">

                    <div
                        class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                        style="width: 90px; height: 90px; font-size: 40px;"
                    >
                        👨‍🏫
                    </div>

                    <h3 class="fw-bold">
                        <?php echo htmlspecialchars($user["name"]); ?>
                    </h3>

                    <span class="badge bg-warning text-dark">
                        Teacher
                    </span>

                    <!-- Teaching summary -->

                    <div class="d-flex text-center border rounded py-2 mt-4">

                        <div class="flex-fill">
                            <strong><?php echo $total_courses; ?></strong>
                            <br>
                            <small class="text-muted">Courses</small>
                        </div>

                        <div class="flex-fill border-start border-end">
                            <strong><?php echo $total_lessons; ?></strong>
                            <br>
                            <small class="text-muted">Lessons</small>
                        </div>

                        <div class="flex-fill">
                            <strong><?php echo $total_students; ?></strong>
                            <br>
                            <small class="text-muted">Students</small>
                        </div>

                    </div>

                </div>

                <hr class="m-0">

                <!-- Profile Information -->

                <div class="card-body p-4">

                    <!-- Name -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Full Name
                        </label>

                        <div class="form-control bg-light">
                            <?php echo htmlspecialchars($user["name"]); ?>
                        </div>

                    </div>


                    <!-- Email -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Email Address
                        </label>

                        <div class="form-control bg-light">
                            <?php echo htmlspecialchars($user["email"]); ?>
                        </div>

                    </div>


                    <!-- Role -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Account Role
                        </label>

                        <div class="form-control bg-light">
                            Teacher
                        </div>

                    </div>


                    <!-- Status -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Account Status
                        </label>

                        <div class="form-control bg-light">

                            <span class="badge bg-success">
                                <?php echo htmlspecialchars(ucfirst($user["status"])); ?>
                            </span>

                            <?php if (!empty($user["approved_at"])): ?>

                                <small class="text-muted ms-1">
                                    since
                                    <?php echo date("M d, Y", strtotime($user["approved_at"])); ?>
                                </small>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- Joined Date -->

                    <div class="mb-4">

                        <label class="form-label fw-bold">
                            Joined Date
                        </label>

                        <div class="form-control bg-light">
                            <?php echo date("M d, Y", strtotime($user["created_at"])); ?>
                        </div>

                    </div>


                    <!-- Actions -->

                    <div class="d-flex gap-2 flex-wrap">

                        <a
                            href="dashboard.php"
                            class="btn btn-primary"
                        >
                            &larr; Dashboard
                        </a>

                        <a
                            href="settings.php"
                            class="btn btn-outline-primary"
                        >
                            Edit in Settings
                        </a>

                        <a
                            href="../auth/logout.php"
                            class="btn btn-danger"
                        >
                            Logout
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
