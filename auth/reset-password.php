<?php

require_once __DIR__ . "/../includes/session.php";

require_once "../config/database.php";
require_once "../includes/functions.php";

$pageTitle = "Reset Password - LearnHub";

$token = $_GET["token"] ?? ($_POST["token"] ?? "");
$errors = [];
$success = "";
$validToken = false;
$user = null;

if ($token === "") {

    $errors[] = "This reset link is invalid.";

} else {

    $tokenHash = hash("sha256", $token);

    $stmt = $pdo->prepare(
        "SELECT id, name, email, reset_token_expires
         FROM users
         WHERE reset_token_hash = ?
         AND deleted_at IS NULL"
    );
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();

    if (!$user) {

        $errors[] = "This reset link is invalid or has already been used.";

    } elseif (strtotime($user["reset_token_expires"]) < time()) {

        $errors[] = "This reset link has expired. Please request a new one.";

    } else {

        $validToken = true;
    }
}

if ($validToken && $_SERVER["REQUEST_METHOD"] === "POST") {

    $newPassword = $_POST["new_password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (!csrf_valid()) {

        $errors[] = "Security check failed. Please try again.";

    } else {

        $errors = array_merge($errors, validate_password_strength($newPassword));

        if ($newPassword !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        }

        if (empty($errors)) {

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET password = ?,
                     reset_token_hash = NULL,
                     reset_token_expires = NULL,
                     must_change_password = 0
                 WHERE id = ?"
            );

            $stmt->execute([
                password_hash($newPassword, PASSWORD_DEFAULT),
                $user["id"]
            ]);

            audit_log($pdo, $user["id"], "password_reset", "Password reset via emailed link.", $user["email"]);

            $success = "Your password has been reset. You can now login with your new password.";
            $validToken = false; // hide the form now that it's done
        }
    }
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6 col-lg-5">

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    <h2 class="text-center fw-bold mb-4">
                        Reset Password
                    </h2>

                    <?php if ($success !== ""): ?>

                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>

                        <a href="login.php" class="btn btn-primary w-100">
                            Go to Login
                        </a>

                    <?php else: ?>

                        <?php if (!empty($errors)): ?>

                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                        <?php endif; ?>

                        <?php if ($validToken): ?>

                        <form method="POST">

                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                            <div class="mb-3">

                                <label class="form-label">
                                    New Password
                                </label>

                                <input
                                    type="password"
                                    name="new_password"
                                    class="form-control"
                                    placeholder="<?php echo htmlspecialchars(password_policy_hint()); ?>"
                                    autocomplete="new-password"
                                    required
                                >

                                <small class="text-muted">
                                    <?php echo htmlspecialchars(password_policy_hint()); ?>
                                </small>

                            </div>

                            <div class="mb-4">

                                <label class="form-label">
                                    Confirm New Password
                                </label>

                                <input
                                    type="password"
                                    name="confirm_password"
                                    class="form-control"
                                    autocomplete="new-password"
                                    required
                                >

                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Reset Password
                            </button>

                        </form>

                        <?php else: ?>

                            <a href="forgot-password.php" class="btn btn-outline-primary w-100">
                                Request a new reset link
                            </a>

                        <?php endif; ?>

                    <?php endif; ?>

                    <p class="text-center mt-4 mb-0">
                        <a href="login.php">Back to Login</a>
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

<?php

require_once "../includes/footer.php";

?>
