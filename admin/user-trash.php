<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


$pageTitle = "User Trash - LearnHub";


/* Auto purge users trashed more than 30 days ago */

$stmt = $pdo->query(
    "DELETE FROM users
     WHERE deleted_at IS NOT NULL
     AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
);


/* Get trashed users */

$stmt = $pdo->query(
    "SELECT
        id,
        name,
        email,
        role,
        deleted_at
     FROM users
     WHERE deleted_at IS NOT NULL
     ORDER BY deleted_at DESC"
);

$trashed = $stmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>


<div class="container py-5">


    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                &#128465; User Trash
            </h2>

            <p class="text-muted mb-0">
                Deleted users stay here for 30 days before being
                removed for good.
            </p>

        </div>


        <a
            href="users.php"
            class="btn btn-outline-secondary"
        >
            &larr; Back to Users
        </a>

    </div>



    <?php if (isset($_SESSION["success"])): ?>

        <div class="alert alert-success">

            <?php
            echo htmlspecialchars($_SESSION["success"]);
            unset($_SESSION["success"]);
            ?>

        </div>

    <?php endif; ?>



    <?php if (isset($_SESSION["error"])): ?>

        <div class="alert alert-danger">

            <?php
            echo htmlspecialchars($_SESSION["error"]);
            unset($_SESSION["error"]);
            ?>

        </div>

    <?php endif; ?>



    <?php if (count($trashed) > 0): ?>


        <div class="table-responsive">

            <table class="table table-bordered table-hover bg-white align-middle">

                <thead class="table-dark">

                    <tr>

                        <th>#</th>

                        <th>Name</th>

                        <th>Email</th>

                        <th>Role</th>

                        <th>Deleted On</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($trashed as $index => $user): ?>

                        <tr>

                            <td>
                                <?php echo $index + 1; ?>
                            </td>


                            <td>

                                <strong>
                                    <?php echo htmlspecialchars($user["name"]); ?>
                                </strong>

                            </td>


                            <td>
                                <?php echo htmlspecialchars($user["email"]); ?>
                            </td>


                            <td>

                                <span class="badge bg-secondary">
                                    <?php echo ucfirst($user["role"]); ?>
                                </span>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    "M d, Y g:i A",
                                    strtotime($user["deleted_at"])
                                );
                                ?>

                            </td>


                            <td>

                                <form
                                    method="POST"
                                    action="restore-user.php"
                                    class="d-inline"
                                    onsubmit="return confirm('Restore this user?');"
                                >
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo $user["id"]; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        Restore
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="delete-user-forever.php"
                                    class="d-inline"
                                    onsubmit="return confirm('This will permanently delete the user and cannot be undone. Continue?');"
                                >
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo $user["id"]; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Delete Forever
                                    </button>
                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>


    <?php else: ?>

        <div class="alert alert-info">
            Trash is empty.
        </div>

    <?php endif; ?>


</div>


<?php require_once "../includes/footer.php"; ?>
