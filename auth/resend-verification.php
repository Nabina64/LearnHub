<?php

require_once __DIR__ . "/../includes/session.php";

require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/mailer.php";

$pageTitle = "Resend Verification Email - LearnHub";

$error = "";
$success = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = clean($_POST["email"] ?? "");
    $client_ip = get_client_ip();

    $email_bucket = "verify_resend:email:" . strtolower($email);
    $ip_bucket = "verify_resend:ip:" . $client_ip;

    if (!csrf_valid()) {

        $error = "Security check failed. Please try again.";

    } elseif ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (
        rate_limit_count($pdo, $email_bucket, 3600) >= 3
        || rate_limit_count($pdo, $ip_bucket, 3600) >= 10
    ) {

        $error = "Too many requests. Please wait "
            . format_wait_time(3600) . " and try again.";

    } else {

        rate_limit_hit($pdo, $email_bucket);
        rate_limit_hit($pdo, $ip_bucket);

        $stmt = $pdo->prepare(
            "SELECT id, name, email, email_verified_at
             FROM users
             WHERE email = ?
             AND deleted_at IS NULL"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show the same message whether or not the account
        // exists / is already verified, so this form cannot be used
        // to discover which email addresses are registered.
        $success = "If that email exists and is not yet verified, "
            . "we've sent a new verification link.";

        if ($user && empty($user["email_verified_at"])) {

            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash("sha256", $rawToken);
            $tokenExpires = date("Y-m-d H:i:s", time() + 86400);

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET verify_token_hash = ?, verify_token_expires = ?
                 WHERE id = ?"
            );
            $stmt->execute([$tokenHash, $tokenExpires, $user["id"]]);

            $verifyLink = app_base_url() . "/auth/verify-email.php?token=" . $rawToken;

            send_app_email(
                $user["email"],
                "Verify your LearnHub email address",
                "Hi " . $user["name"] . ",\n\n"
                . "Here is your new verification link:\n\n"
                . $verifyLink . "\n\n"
                . "This link expires in 24 hours.\n"
            );

            audit_log($pdo, $user["id"], "verification_resent", "Verification email resent.", $user["email"]);
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
                        Resend Verification Email
                    </h2>

                    <?php if ($success !== ""): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error !== ""): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success === ""): ?>

                    <form method="POST">

                        <?php echo csrf_field(); ?>

                        <div class="mb-4">

                            <label class="form-label">
                                Email Address
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="Enter your email"
                                value="<?php echo htmlspecialchars($email); ?>"
                                required
                            >

                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Send Verification Link
                        </button>

                    </form>

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
