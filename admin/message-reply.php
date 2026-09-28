<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/mailer.php";

/* Admin Only */

require_role("admin", "../index.php");

/* POST + CSRF only */

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valid()) {

    $_SESSION["error"] = "Invalid request.";

    redirect("messages.php");
}

$id = intval($_POST["id"] ?? 0);
$reply = trim($_POST["reply"] ?? "");

if ($id <= 0 || $reply === "") {

    $_SESSION["error"] = "Please write a reply message.";

    redirect("messages.php");
}


/* Load the original message */

$stmt = $pdo->prepare(
    "SELECT id, name, email, subject, message
     FROM contact_messages
     WHERE id = ?"
);

$stmt->execute([$id]);

$original = $stmt->fetch();

if (!$original) {

    $_SESSION["error"] = "Message not found.";

    redirect("messages.php");
}


/* Save the reply - this is what makes it visible on the website,
   in the sender's own "My Messages" page, regardless of whether
   the email below succeeds. */

$pdo->prepare(
    "UPDATE contact_messages
     SET reply = ?,
         replied_at = NOW(),
         is_read = 1
     WHERE id = ?"
)->execute([$reply, $id]);


/* Compose and send the email copy (best-effort) */

$adminName = $_SESSION["user_name"] ?? "LearnHub Support";

$subject = "Re: " . $original["subject"];

$body =
    $reply
    . "\r\n\r\n"
    . "-----------------------------------\r\n"
    . "Your original message:\r\n\r\n"
    . $original["message"]
    . "\r\n"
    . "-----------------------------------\r\n"
    . "- " . $adminName . " (LearnHub)";

$sent = send_app_email(
    $original["email"],
    $subject,
    $body,
    env("SMTP_USER", ""),
    $adminName
);


if ($sent) {

    $_SESSION["success"] = "Reply sent to " . $original["email"] . ".";

} else {

    $_SESSION["success"] =
        "Reply saved. The sender will see it on the website"
        . ($original["email"] !== "" ? " (email notification could not be sent - check admin/mail-test.php)." : ".");
}

redirect("messages.php");
