<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";
require_once __DIR__ . "/includes/mailer.php";

$pageTitle = "Contact - LearnHub";
$extraCss = ["public.css"];

$errors = [];

$old = ["name" => "", "email" => "", "subject" => "", "message" => ""];

if (isset($_SESSION["user_id"])) {
    $old["name"] = $_SESSION["user_name"] ?? "";
    $old["email"] = html_entity_decode($_SESSION["user_email"] ?? "", ENT_QUOTES, "UTF-8");
}

$contact_email = get_setting($pdo, "contact_email");
$contact_phone = get_setting($pdo, "contact_phone");
$contact_address = get_setting($pdo, "contact_address");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $old["name"] = trim($_POST["name"] ?? "");
    $old["email"] = trim($_POST["email"] ?? "");
    $old["subject"] = trim($_POST["subject"] ?? "");
    $old["message"] = trim($_POST["message"] ?? "");

    if (!csrf_valid()) {

        $errors[] = "Security check failed. Please try again.";

    } elseif (trim($_POST["website"] ?? "") !== "") {

        // Hidden field that only robots fill: pretend everything is fine
        $_SESSION["success"] = "Thank you! Your message has been sent.";
        redirect("contact.php");

    } else {

        if ($old["name"] === "" || strlen($old["name"]) > 100) {
            $errors[] = "Please enter your name (maximum 100 characters).";
        }

        if (!filter_var($old["email"], FILTER_VALIDATE_EMAIL) || strlen($old["email"]) > 150) {
            $errors[] = "Please enter a valid email address.";
        }

        if ($old["subject"] === "" || strlen($old["subject"]) > 200) {
            $errors[] = "Please enter a subject (maximum 200 characters).";
        }

        if (strlen($old["message"]) < 10) {
            $errors[] = "Your message is too short (at least 10 characters).";
        } elseif (strlen($old["message"]) > 2000) {
            $errors[] = "Your message is too long (maximum 2000 characters).";
        }

        // One message every 30 seconds per visitor
        if (
            empty($errors)
            && isset($_SESSION["last_contact_at"])
            && time() - $_SESSION["last_contact_at"] < 30
        ) {
            $errors[] = "Please wait a few seconds before sending another message.";
        }

        if (empty($errors)) {

            try {

                $stmt = $pdo->prepare(
                    "INSERT INTO contact_messages (user_id, name, email, subject, message)
                     VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $_SESSION["user_id"] ?? null,
                    $old["name"],
                    $old["email"],
                    $old["subject"],
                    $old["message"]
                ]);

                // Also deliver it straight to the inbox set in
                // Admin > Settings (falls back to the SMTP sender
                // account if no contact_email is configured yet).
                $notifyAddress = $contact_email !== ""
                    ? $contact_email
                    : env("SMTP_USER", "");

                if ($notifyAddress !== "") {

                    send_app_email(
                        $notifyAddress,
                        "[LearnHub Contact] " . $old["subject"],
                        "New message from the Contact page:\r\n\r\n"
                            . "Name: " . $old["name"] . "\r\n"
                            . "Email: " . $old["email"] . "\r\n\r\n"
                            . $old["message"],
                        $old["email"],
                        $old["name"]
                    );
                }

                $_SESSION["last_contact_at"] = time();
                $_SESSION["success"] = "Thank you! Your message has been sent. We will get back to you soon.";

                redirect("contact.php");

            } catch (Throwable $e) {

                error_log("LearnHub contact: " . $e->getMessage());

                $errors[] = "Sorry, your message could not be sent. Please try again later.";
            }
        }
    }
}

$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
?>

<div class="lh-courses-page">

    <section class="lh-courses-hero lh-page-hero">

        <div class="container">

            <div class="lh-courses-hero-content">

                <span class="lh-courses-eyebrow">
                    CONTACT US
                </span>

                <h1>
                    We would love to <span>hear from you</span>
                </h1>

                <p>
                    Send a message and our team will reply as soon as possible.
                </p>

            </div>

        </div>

    </section>


    <section class="lh-courses-main">

        <div class="container">

            <div class="row g-4 justify-content-center">

                <!-- Form -->

                <div class="col-lg-7">

                    <div class="lh-info-card">

                        <h3>Send us a message</h3>

                        <p class="text-muted">
                            Questions, problems or ideas - we would like to hear them.
                        </p>

                        <?php if (isset($_SESSION["success"])): ?>

                            <div class="alert alert-success">
                                <?php
                                echo $h($_SESSION["success"]);
                                unset($_SESSION["success"]);
                                ?>
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

                        <form method="POST">

                            <?php echo csrf_field(); ?>

                            <!-- Robots fill this, people never see it -->

                            <div style="position:absolute; left:-9999px;" aria-hidden="true">
                                <label>Leave this empty</label>
                                <input type="text" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="row">

                                <div class="col-md-6 mb-3">

                                    <label class="form-label fw-bold">Your Name</label>

                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control"
                                        maxlength="100"
                                        value="<?php echo $h($old["name"]); ?>"
                                        required
                                    >

                                </div>

                                <div class="col-md-6 mb-3">

                                    <label class="form-label fw-bold">Email Address</label>

                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control"
                                        maxlength="150"
                                        value="<?php echo $h($old["email"]); ?>"
                                        required
                                    >

                                </div>

                            </div>

                            <div class="mb-3">

                                <label class="form-label fw-bold">Subject</label>

                                <input
                                    type="text"
                                    name="subject"
                                    class="form-control"
                                    maxlength="200"
                                    value="<?php echo $h($old["subject"]); ?>"
                                    required
                                >

                            </div>

                            <div class="mb-4">

                                <label class="form-label fw-bold">Message</label>

                                <textarea
                                    name="message"
                                    class="form-control"
                                    rows="6"
                                    maxlength="2000"
                                    required
                                ><?php echo $h($old["message"]); ?></textarea>

                            </div>

                            <button type="submit" class="btn btn-primary px-4">
                                Send Message
                            </button>

                            <?php if (isset($_SESSION["user_id"])): ?>
                                <p class="text-muted small mt-2 mb-0">
                                    You can also see this message and our reply anytime under
                                    <strong>Messages</strong> in your dashboard.
                                </p>
                            <?php endif; ?>

                        </form>

                    </div>

                </div>


                <!-- Info -->

                <div class="col-lg-5">

                    <div class="lh-info-card h-auto">

                        <h3>Get in touch</h3>

                        <p class="text-muted">
                            You can also reach us directly.
                        </p>

                        <?php if ($contact_email !== ""): ?>

                            <div class="lh-contact-line">
                                <div class="icon">✉️</div>
                                <div>
                                    <small>Email</small>
                                    <a href="mailto:<?php echo $h($contact_email); ?>"><?php echo $h($contact_email); ?></a>
                                </div>
                            </div>

                        <?php endif; ?>

                        <?php if ($contact_phone !== ""): ?>

                            <div class="lh-contact-line">
                                <div class="icon">📞</div>
                                <div>
                                    <small>Phone</small>
                                    <strong><?php echo $h($contact_phone); ?></strong>
                                </div>
                            </div>

                        <?php endif; ?>

                        <?php if ($contact_address !== ""): ?>

                            <div class="lh-contact-line">
                                <div class="icon">📍</div>
                                <div>
                                    <small>Address</small>
                                    <strong><?php echo $h($contact_address); ?></strong>
                                </div>
                            </div>

                        <?php endif; ?>

                        <div class="lh-contact-line mb-0">
                            <div class="icon">❓</div>
                            <div>
                                <small>Quick answers</small>
                                <a href="faq.php">Read the FAQ</a>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
