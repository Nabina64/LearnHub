<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Student Only */

require_role("student", "../index.php");


$pageTitle = "My Profile - LearnHub";


/* Get Student Information */

$stmt = $pdo->prepare(
    "SELECT
        id,
        name,
        email,
        role,
        created_at
     FROM users
     WHERE id = ?
     AND role = 'student'"
);

$stmt->execute([
    $_SESSION["user_id"]
]);

$user = $stmt->fetch();


/* User Not Found */

if (!$user) {

    redirect("dashboard.php");

}


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

                        👤

                    </div>


                    <h3 class="fw-bold">

                        <?php

                        echo htmlspecialchars(
                            $user["name"]
                        );

                        ?>

                    </h3>


                    <span class="badge bg-primary">

                        Student

                    </span>


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

                            <?php

                            echo htmlspecialchars(
                                $user["name"]
                            );

                            ?>

                        </div>

                    </div>



                    <!-- Email -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Email Address
                        </label>

                        <div class="form-control bg-light">

                            <?php

                            echo htmlspecialchars(
                                $user["email"]
                            );

                            ?>

                        </div>

                    </div>



                    <!-- Role -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Account Role
                        </label>

                        <div class="form-control bg-light">

                            Student

                        </div>

                    </div>



                    <!-- Joined Date -->

                    <div class="mb-4">

                        <label class="form-label fw-bold">
                            Joined Date
                        </label>

                        <div class="form-control bg-light">

                            <?php

                            echo date(
                                "M d, Y",
                                strtotime(
                                    $user["created_at"]
                                )
                            );

                            ?>

                        </div>

                    </div>



                    <!-- Actions -->

                    <div class="d-flex gap-2 flex-wrap">


                        <a
                            href="dashboard.php"
                            class="btn btn-primary"
                        >
                            ← Dashboard
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