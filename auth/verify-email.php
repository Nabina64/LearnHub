<?php

require_once __DIR__ . "/../includes/session.php";

require_once "../config/database.php";
require_once "../includes/functions.php";

$pageTitle = "Verify Email - LearnHub";

$token = $_GET["token"] ?? "";

$status = "error";
$message = "This verification link is invalid.";

if ($token !== "") {

    $tokenHash = hash("sha256", $token);

    $stmt = $pdo->prepare(
        "SELECT id, name, email, email_verified_at, verify_token_expires
         FROM users
         WHERE verify_token_hash = ?
         AND deleted_at IS NULL"
    );

    $stmt->execute([$tokenHash]);

    $user = $stmt->fetch();

    if (!$user) {

        $message = "This verification link is invalid or has already been used.";

    } elseif (!empty($user["email_verified_at"])) {

        $status = "success";
        $message = "Your email is already verified. You can login now.";

    } elseif (strtotime($user["verify_token_expires"]) < time()) {

        $message = "This verification link has expired. "
            . "Please request a new one below.";

    } else {

        $stmt = $pdo->prepare(
            "UPDATE users
             SET email_verified_at = NOW(),
                 verify_token_hash = NULL,
                 verify_token_expires = NULL
             WHERE id = ?"
        );
        $stmt->execute([$user["id"]]);

        audit_log($pdo, $user["id"], "email_verified", "Email address verified.", $user["email"]);

        $status = "success";
        $message = "Your email address has been verified. You can login now.";
    }
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6 col-lg-5">

            <div class="card shadow border-0">

                <div class="card-body p-4 text-center">

                    <h2 class="fw-bold mb-4">
                        Email Verification
                    </h2>

                    <div class="alert <?php echo $status === "success" ? "alert-success" : "alert-danger"; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                    <?php if ($status === "success"): ?>

                        <a href="login.php" class="btn btn-primary w-100">
                            Go to Login
                        </a>

                    <?php else: ?>

                        <a href="resend-verification.php" class="btn btn-outline-primary w-100">
                            Request a new verification link
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

<?php

require_once "../includes/footer.php";

?>
