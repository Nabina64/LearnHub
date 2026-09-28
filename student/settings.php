<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/account.php";

/* Student Only */

require_role("student", "../index.php");

$pageTitle = "Settings - LearnHub";

/* Must run before any HTML is printed */

$account = handle_account_post($pdo, "student");

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                Settings
            </h2>

            <p class="text-muted mb-0">
                Manage your account information and password.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="btn btn-outline-secondary"
        >
            &larr; Dashboard
        </a>

    </div>

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <?php render_account_settings($account, "student"); ?>

        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
