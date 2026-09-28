<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


$pageTitle = "Teacher Requests - LearnHub";


/* Pending Teacher Requests */

$stmt = $pdo->query(
    "SELECT
        id,
        name,
        email,
        created_at
     FROM users
     WHERE role = 'teacher'
     AND status = 'pending'
     AND deleted_at IS NULL
     ORDER BY created_at ASC"
);

$pending = $stmt->fetchAll();


/* Already Handled Requests */

$stmt = $pdo->query(
    "SELECT
        id,
        name,
        email,
        status,
        approved_at
     FROM users
     WHERE role = 'teacher'
     AND status <> 'pending'
     AND deleted_at IS NULL
     ORDER BY approved_at DESC, id DESC
     LIMIT 20"
);

$handled = $stmt->fetchAll();


require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>


<div class="container py-5">


    <!-- Success Message -->

    <?php if (isset($_SESSION["success"])): ?>

        <div class="alert alert-success">

            <?php

            echo htmlspecialchars($_SESSION["success"]);

            unset($_SESSION["success"]);

            ?>

        </div>

    <?php endif; ?>



    <!-- Error Message -->

    <?php if (isset($_SESSION["error"])): ?>

        <div class="alert alert-danger">

            <?php

            echo htmlspecialchars($_SESSION["error"]);

            unset($_SESSION["error"]);

            ?>

        </div>

    <?php endif; ?>



    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                Teacher Requests
            </h2>

            <p class="text-muted mb-0">
                Approve or reject teacher registration requests.
            </p>

        </div>


        <a
            href="dashboard.php"
            class="btn btn-outline-secondary"
        >
            &larr; Dashboard
        </a>

    </div>



    <!-- Pending Requests -->

    <div class="card shadow-sm border-0 mb-5">

        <div class="card-body">

            <h5 class="fw-bold mb-3">

                Pending Requests

                <span class="badge bg-warning text-dark">
                    <?php echo count($pending); ?>
                </span>

            </h5>


            <?php if (count($pending) > 0): ?>


                <div class="table-responsive">

                    <table class="table table-bordered align-middle">

                        <thead class="table-dark">

                            <tr>

                                <th>ID</th>

                                <th>Name</th>

                                <th>Email</th>

                                <th>Requested On</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($pending as $teacher): ?>

                                <tr>

                                    <td>
                                        <?php echo $teacher["id"]; ?>
                                    </td>


                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $teacher["name"]
                                            );
                                            ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $teacher["email"]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo date(
                                            "M d, Y",
                                            strtotime(
                                                $teacher["created_at"]
                                            )
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <div class="d-flex gap-2">

                                            <a
                                                href="approve-teacher.php?id=<?php echo $teacher["id"]; ?>&action=approve"
                                                class="btn btn-sm btn-success"
                                                onclick="return confirm('Approve this teacher?');"
                                            >
                                                Approve
                                            </a>


                                            <a
                                                href="approve-teacher.php?id=<?php echo $teacher["id"]; ?>&action=reject"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Reject this teacher request?');"
                                            >
                                                Reject
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>

                <div class="alert alert-info mb-0">

                    No pending teacher requests right now.

                </div>

            <?php endif; ?>

        </div>

    </div>



    <!-- Handled Requests -->

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <h5 class="fw-bold mb-3">
                Recently Handled
            </h5>


            <?php if (count($handled) > 0): ?>

                <div class="table-responsive">

                    <table class="table table-bordered align-middle">

                        <thead class="table-light">

                            <tr>

                                <th>Name</th>

                                <th>Email</th>

                                <th>Status</th>

                                <th>Handled On</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($handled as $teacher): ?>

                                <tr>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $teacher["name"]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $teacher["email"]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php if ($teacher["status"] === "approved"): ?>

                                            <span class="badge bg-success">
                                                Approved
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-danger">
                                                Rejected
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo !empty($teacher["approved_at"])
                                            ? date(
                                                "M d, Y",
                                                strtotime(
                                                    $teacher["approved_at"]
                                                )
                                            )
                                            : "-";

                                        ?>

                                    </td>


                                    <td>

                                        <?php if ($teacher["status"] === "rejected"): ?>

                                            <a
                                                href="approve-teacher.php?id=<?php echo $teacher["id"]; ?>&action=approve"
                                                class="btn btn-sm btn-outline-success"
                                                onclick="return confirm('Approve this teacher now?');"
                                            >
                                                Approve
                                            </a>

                                        <?php else: ?>

                                            <a
                                                href="approve-teacher.php?id=<?php echo $teacher["id"]; ?>&action=reject"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Revoke access for this teacher?');"
                                            >
                                                Revoke
                                            </a>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>

                <div class="alert alert-info mb-0">

                    No handled requests yet.

                </div>

            <?php endif; ?>

        </div>

    </div>


</div>


<?php require_once "../includes/footer.php"; ?>
