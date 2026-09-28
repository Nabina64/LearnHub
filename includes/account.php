<?php

/**
 * Account settings shared by Student, Teacher and Admin.
 *
 *   1. handle_account_post()      -> call BEFORE any HTML is printed
 *   2. render_account_settings()  -> prints the three cards
 *
 * Forms handled (hidden field "action"):
 *   update_profile   name + email   (needs current password)
 *   change_password  new password   (needs current password)
 *   delete_account   soft delete    (needs password, not for admin)
 */

function handle_account_post($pdo, $role)
{
    $result = [
        "errors"  => [],
        "success" => null,
        "form"    => "",
        "name"    => $_SESSION["user_name"] ?? "",
        "email"   => $_SESSION["user_email"] ?? "",
    ];

    $user_id = (int) $_SESSION["user_id"];

    /* Current account row */

    $stmt = $pdo->prepare(
        "SELECT id, name, email, password, created_at
         FROM users
         WHERE id = ?
         AND deleted_at IS NULL"
    );

    $stmt->execute([$user_id]);

    $user = $stmt->fetch();

    if (!$user) {

        $_SESSION = [];
        session_destroy();
        session_start();

        redirect("../auth/login.php");
    }

    $result["user"]  = $user;
    $result["name"]  = $user["name"];
    $result["email"] = $user["email"];

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return $result;
    }

    $action = $_POST["action"] ?? "";

    if (!in_array(
        $action,
        ["update_profile", "change_password", "delete_account"],
        true
    )) {
        return $result;
    }

    $result["form"] = $action;

    if (!csrf_valid()) {

        $result["errors"][] = "Security check failed. Please try again.";

        return $result;
    }

    $errors = [];


    /* ---------- Update name / email ---------- */

    if ($action === "update_profile") {

        $name = trim($_POST["name"] ?? "");
        $email = clean($_POST["email"] ?? "");
        $password = $_POST["current_password"] ?? "";

        $result["name"] = $name;
        $result["email"] = $email;

        if ($name === "") {
            $errors[] = "Name is required.";
        } elseif (strlen($name) > 100) {
            $errors[] = "Name is too long (maximum 100 characters).";
        }

        if ($email === "") {
            $errors[] = "Email is required.";
        } elseif (
            !filter_var(
                html_entity_decode($email, ENT_QUOTES, "UTF-8"),
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors[] = "Please enter a valid email address.";
        } elseif (strlen($email) > 150) {
            $errors[] = "Email is too long.";
        }

        if (!password_verify($password, $user["password"])) {
            $errors[] = "Your current password is incorrect.";
        }

        if (empty($errors) && $email !== $user["email"]) {

            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE email = ? AND id <> ?"
            );

            $stmt->execute([$email, $user_id]);

            if ($stmt->fetch()) {
                $errors[] = "That email is already used by another account.";
            }
        }

        if (empty($errors)) {

            $stmt = $pdo->prepare(
                "UPDATE users SET name = ?, email = ? WHERE id = ?"
            );

            $stmt->execute([$name, $email, $user_id]);

            $_SESSION["user_name"] = $name;
            $_SESSION["user_email"] = $email;

            $result["success"] = "Your account information was updated.";
            $result["form"] = "";
        }
    }


    /* ---------- Change password ---------- */

    if ($action === "change_password") {

        $current = $_POST["current_password"] ?? "";
        $new = $_POST["new_password"] ?? "";
        $confirm = $_POST["confirm_password"] ?? "";

        if (!password_verify($current, $user["password"])) {
            $errors[] = "Your current password is incorrect.";
        }

        $errors = array_merge($errors, validate_password_strength($new));

        if ($new === $current) {
            $errors[] = "New password must be different from the current one.";
        }

        if ($new !== $confirm) {
            $errors[] = "New passwords do not match.";
        }

        if (empty($errors)) {

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET password = ?, must_change_password = 0
                 WHERE id = ?"
            );

            $stmt->execute([
                password_hash($new, PASSWORD_DEFAULT),
                $user_id
            ]);

            session_regenerate_id(true);

            $_SESSION["must_change_password"] = 0;

            audit_log($pdo, $user_id, "password_changed", "Password changed from account settings.", $user["email"]);

            $result["success"] = "Your password was changed.";
            $result["form"] = "";
        }
    }


    /* ---------- Delete my account (student / teacher) ---------- */

    if ($action === "delete_account") {

        $password = $_POST["delete_password"] ?? "";

        if ($role === "admin") {
            $errors[] = "An admin account cannot be deleted here.";
        } elseif (!password_verify($password, $user["password"])) {
            $errors[] = "Password is incorrect. Your account was not deleted.";
        }

        if (empty($errors)) {

            $stmt = $pdo->prepare(
                "UPDATE users SET deleted_at = NOW() WHERE id = ?"
            );

            $stmt->execute([$user_id]);

            audit_log($pdo, $user_id, "account_deleted", "Deleted own account (self-service).", $user["email"]);

            $_SESSION = [];
            session_destroy();
            session_start();

            $_SESSION["success"] =
                "Your account has been deleted. "
                . "An admin can restore it within 30 days.";

            redirect("../auth/login.php");
        }
    }

    $result["errors"] = $errors;

    return $result;
}


function render_account_settings($account, $role)
{
    $h = function ($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    };

    $user = $account["user"];
    $errors = $account["errors"];
    $form = $account["form"];

    $role_label = ucfirst($role);
    ?>

    <?php if (!empty($_SESSION["must_change_password"])): ?>

        <div class="alert alert-warning">
            <strong>Please change your password.</strong>
            This account is still using its original default password.
            Use the "Change Password" form below to set a new one before
            continuing.
        </div>

    <?php endif; ?>


    <?php if ($account["success"]): ?>

        <div class="alert alert-success">
            <?php echo $h($account["success"]); ?>
        </div>

    <?php endif; ?>


    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <ul class="mb-0">

                <?php foreach ($errors as $error): ?>

                    <li><?php echo $h($error); ?></li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <!-- Account Information -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body p-4">

            <h4 class="fw-bold mb-1">
                Account Information
            </h4>

            <p class="text-muted">
                Change your name or email. Enter your current password to confirm.
            </p>

            <form method="POST" autocomplete="off">

                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="mb-3">

                    <label class="form-label fw-bold">
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        maxlength="100"
                        value="<?php echo $h($form === "update_profile" ? $account["name"] : $user["name"]); ?>"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label class="form-label fw-bold">
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        maxlength="150"
                        value="<?php echo $h(html_entity_decode($form === "update_profile" ? $account["email"] : $user["email"], ENT_QUOTES, "UTF-8")); ?>"
                        required
                    >

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-bold">
                            Account Role
                        </label>

                        <div class="form-control bg-light">
                            <?php echo $h($role_label); ?>
                        </div>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-bold">
                            Member Since
                        </label>

                        <div class="form-control bg-light">
                            <?php echo date("M d, Y", strtotime($user["created_at"])); ?>
                        </div>

                    </div>

                </div>

                <div class="mb-4">

                    <label class="form-label fw-bold">
                        Current Password
                    </label>

                    <input
                        type="password"
                        name="current_password"
                        class="form-control"
                        placeholder="Needed to save changes"
                        autocomplete="current-password"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Changes
                </button>

            </form>

        </div>

    </div>


    <!-- Change Password -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body p-4">

            <h4 class="fw-bold mb-1">
                Change Password
            </h4>

            <p class="text-muted">
                <?php echo htmlspecialchars(password_policy_hint()); ?>
            </p>

            <form method="POST" autocomplete="off">

                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="change_password">

                <div class="mb-3">

                    <label class="form-label fw-bold">
                        Current Password
                    </label>

                    <input
                        type="password"
                        name="current_password"
                        class="form-control"
                        autocomplete="current-password"
                        required
                    >

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-bold">
                            New Password
                        </label>

                        <input
                            type="password"
                            name="new_password"
                            class="form-control"
                            placeholder="<?php echo $h(password_policy_hint()); ?>"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-bold">
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Re-enter new password"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Update Password
                </button>

            </form>

        </div>

    </div>


    <?php if ($role !== "admin"): ?>

        <!-- Delete Account -->

        <div class="card shadow-sm border-0 border-danger mb-4">

            <div class="card-body p-4">

                <h4 class="fw-bold text-danger mb-1">
                    Delete Account
                </h4>

                <p class="text-muted">
                    Your account is moved to trash and you are logged out.
                    An admin can restore it within 30 days.
                </p>

                <form
                    method="POST"
                    autocomplete="off"
                    onsubmit="return confirm('Delete your account? You will be logged out.');"
                >

                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete_account">

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Enter your password to confirm
                        </label>

                        <input
                            type="password"
                            name="delete_password"
                            class="form-control"
                            autocomplete="current-password"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        Delete My Account
                    </button>

                </form>

            </div>

        </div>

    <?php endif; ?>

    <?php
}
