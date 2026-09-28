<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/mailer.php";

/* Admin Only */

require_role("admin", "../index.php");

$pageTitle = "Mail Test - LearnHub";

$result = null;
$detail = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && csrf_valid()) {

    $to = trim($_POST["to"] ?? "");

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {

        $detail = "Please enter a valid email address to send the test to.";

    } else {

        $host = env("SMTP_HOST", "smtp.gmail.com");
        $port = (int) env("SMTP_PORT", "587");
        $user = env("SMTP_USER", "");
        $pass = env("SMTP_PASS", "");
        $fromName = env("MAIL_FROM_NAME", "LearnHub");

        if ($user === "" || $pass === "") {

            $result = false;
            $detail = "SMTP_USER / SMTP_PASS are not set in your .env file.";

        } else {

            $mailer = new SimpleSmtpMailer($host, $port, $user, $pass, $fromName);

            $result = $mailer->send(
                $to,
                "LearnHub SMTP test",
                "If you can read this, your SMTP settings are working correctly."
            );

            $detail = $mailer->lastError;
        }
    }
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5" style="max-width: 700px;">

    <h2 class="fw-bold mb-4">SMTP / Mail Test</h2>

    <p class="text-muted">
        This page tries to send one real email using the SMTP_* values
        currently in your .env file, and shows you the exact error the
        mail server returned - the same detail that is normally only
        written to the PHP error log.
    </p>

    <div class="alert alert-secondary">
        <strong>Current .env values being used:</strong><br>
        SMTP_HOST: <?php echo htmlspecialchars(env("SMTP_HOST", "smtp.gmail.com")); ?><br>
        SMTP_PORT: <?php echo htmlspecialchars(env("SMTP_PORT", "587")); ?><br>
        SMTP_USER: <?php echo htmlspecialchars(env("SMTP_USER", "(not set)")); ?><br>
        SMTP_PASS: <?php echo env("SMTP_PASS", "") !== "" ? "(set, " . strlen(env("SMTP_PASS", "")) . " characters)" : "(not set)"; ?>
    </div>

    <?php if ($result === true): ?>

        <div class="alert alert-success">
            Success! Check the inbox of the address you sent it to.
        </div>

    <?php elseif ($result === false): ?>

        <div class="alert alert-danger">
            <strong>Failed:</strong><br>
            <?php echo htmlspecialchars($detail); ?>
        </div>

    <?php endif; ?>

    <form method="POST" class="card card-body shadow-sm">

        <?php echo csrf_field(); ?>

        <label class="form-label fw-bold">Send a test email to</label>

        <input
            type="email"
            name="to"
            class="form-control mb-3"
            placeholder="you@example.com"
            required
        >

        <button type="submit" class="btn btn-primary">
            Send Test Email
        </button>

    </form>

</div>

<?php require_once "../includes/footer.php"; ?>
