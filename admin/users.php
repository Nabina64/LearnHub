<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


$pageTitle = "Manage Users - LearnHub";


/* -----------------------------------------------------
   Filters  (?q=name or email   &role=   &status=)
----------------------------------------------------- */

$search = trim($_GET["q"] ?? "");
$filter_role = trim($_GET["role"] ?? "");
$filter_status = trim($_GET["status"] ?? "");

$where = ["deleted_at IS NULL"];
$params = [];

if ($search !== "") {

    $where[] = "(name LIKE ? OR email LIKE ?)";

    $like = "%" . $search . "%";

    array_push($params, $like, $like);
}

if ($filter_role !== "") {

    $where[] = "role = ?";
    $params[] = $filter_role;
}

if ($filter_status !== "") {

    $where[] = "status = ?";
    $params[] = $filter_status;
}

$where_sql = "WHERE " . implode(" AND ", $where);


/* Get All Users */

$stmt = $pdo->prepare(
    "SELECT
        id,
        name,
        email,
        role,
        status,
        created_at
     FROM users
     $where_sql
     ORDER BY created_at DESC"
);

$stmt->execute($params);

$users = $stmt->fetchAll();

$keep = [];

if ($search !== "") {
    $keep["q"] = $search;
}

if ($filter_role !== "") {
    $keep["role"] = $filter_role;
}

if ($filter_status !== "") {
    $keep["status"] = $filter_status;
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
                Manage Users
            </h2>

            <p class="text-muted mb-0">
                View and manage registered users.
            </p>

        </div>


        <div class="d-flex gap-2">

            <a
                href="user-trash.php"
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

                <div class="col-lg-5">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Search name or email..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>

                <div class="col-lg-3">

                    <select name="role" class="form-select">

                        <option value="">All Roles</option>

                        <option value="admin" <?php echo $filter_role === "admin" ? "selected" : ""; ?>>Admin</option>
                        <option value="teacher" <?php echo $filter_role === "teacher" ? "selected" : ""; ?>>Teacher</option>
                        <option value="student" <?php echo $filter_role === "student" ? "selected" : ""; ?>>Student</option>

                    </select>

                </div>

                <div class="col-lg-2">

                    <select name="status" class="form-select">

                        <option value="">All Status</option>

                        <option value="approved" <?php echo $filter_status === "approved" ? "selected" : ""; ?>>Approved</option>
                        <option value="pending" <?php echo $filter_status === "pending" ? "selected" : ""; ?>>Pending</option>
                        <option value="rejected" <?php echo $filter_status === "rejected" ? "selected" : ""; ?>>Rejected</option>

                    </select>

                </div>

                <div class="col-lg-2 d-flex gap-2">

                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>

                    <?php if ($keep): ?>

                        <a href="users.php" class="btn btn-outline-secondary">
                            Clear
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </div>

    </div>



    <!-- Users Table -->

    <div class="card shadow-sm border-0">

        <div class="card-body">


            <?php if (count($users) > 0): ?>


                <div class="table-responsive">

                    <table
                        class="table table-bordered table-hover align-middle"
                    >

                        <thead class="table-dark">

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Registered
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($users as $user): ?>


                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <?php
                                        echo $user["id"];
                                        ?>

                                    </td>



                                    <!-- Name -->

                                    <td>

                                        <strong>

                                            <?php

                                            echo htmlspecialchars(
                                                $user["name"]
                                            );

                                            ?>

                                        </strong>

                                    </td>



                                    <!-- Email -->

                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $user["email"]
                                        );

                                        ?>

                                    </td>



                                    <!-- Role -->

                                    <td>


                                        <?php if ($user["role"] === "admin"): ?>

                                            <span class="badge bg-danger">

                                                Admin

                                            </span>


                                        <?php elseif ($user["role"] === "teacher"): ?>

                                            <span
                                                class="badge bg-warning text-dark"
                                            >

                                                Teacher

                                            </span>


                                        <?php else: ?>

                                            <span class="badge bg-primary">

                                                Student

                                            </span>

                                        <?php endif; ?>


                                    </td>



                                    <!-- Status -->

                                    <td>

                                        <?php if ($user["status"] === "pending"): ?>

                                            <span class="badge bg-warning text-dark">

                                                Pending

                                            </span>


                                        <?php elseif ($user["status"] === "rejected"): ?>

                                            <span class="badge bg-danger">

                                                Rejected

                                            </span>


                                        <?php else: ?>

                                            <span class="badge bg-success">

                                                Approved

                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- Registered Date -->

                                    <td>

                                        <?php

                                        echo date(
                                            "M d, Y",
                                            strtotime(
                                                $user["created_at"]
                                            )
                                        );

                                        ?>

                                    </td>



                                    <!-- Action -->

                                    <td>


                                        <?php if (
                                            $user["role"] === "teacher"
                                            && $user["status"] === "pending"
                                        ): ?>

                                            <a
                                                href="approve-teacher.php?id=<?php echo $user["id"]; ?>&action=approve"
                                                class="btn btn-sm btn-success mb-1"
                                                onclick="return confirm('Approve this teacher?');"
                                            >

                                                Approve

                                            </a>

                                        <?php endif; ?>


                                        <?php

                                        if (
                                            $user["id"]
                                            !=
                                            $_SESSION["user_id"]
                                        ):

                                        ?>


                                            <a
                                                href="delete-user.php?id=<?php echo $user["id"]; ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this user?');"
                                            >

                                                Delete

                                            </a>


                                        <?php else: ?>


                                            <span class="text-muted">

                                                Current Account

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="alert alert-info mb-0">

                    <?php echo $keep ? "No users match your search." : "No users found."; ?>

                </div>


            <?php endif; ?>


        </div>

    </div>


</div>


<?php require_once "../includes/footer.php"; ?>