<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Admin Only */

require_role("admin", "../index.php");

$pageTitle = "Messages - LearnHub";

$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};


/* Contact messages */

$messages_unread = 0;
$messages_total = 0;
$messages = [];

try {

    $messages_total = (int) $pdo->query(
        "SELECT COUNT(*) FROM contact_messages"
    )->fetchColumn();

    $messages_unread = (int) $pdo->query(
        "SELECT COUNT(*) FROM contact_messages WHERE is_read = 0"
    )->fetchColumn();

    $messages = $pdo->query(
        "SELECT id, name, email, subject, message, is_read, created_at, reply, replied_at
         FROM contact_messages
         ORDER BY is_read ASC, created_at DESC, id DESC"
    )->fetchAll();

} catch (Throwable $e) {
    // table not created yet -> no messages
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <!-- Success / Error -->

    <?php if (isset($_SESSION["success"])): ?>

        <div class="alert alert-success">

            <?php
            echo $h($_SESSION["success"]);
            unset($_SESSION["success"]);
            ?>

        </div>

    <?php endif; ?>

    <?php if (isset($_SESSION["error"])): ?>

        <div class="alert alert-danger">

            <?php
            echo $h($_SESSION["error"]);
            unset($_SESSION["error"]);
            ?>

        </div>

    <?php endif; ?>


    <!-- Header -->

    <div class="mb-4">

        <h2 class="fw-bold mb-1">
            Messages
            <?php if ($messages_unread > 0): ?>
                <span class="badge bg-danger fs-6 align-middle">
                    <?php echo $messages_unread; ?> new
                </span>
            <?php endif; ?>
        </h2>

        <p class="text-muted mb-0">
            Messages sent from the Contact page.
        </p>

    </div>


    <!-- Contact Messages -->

    <?php if (count($messages) > 0): ?>

        <?php foreach ($messages as $message): ?>

            <div class="card border-0 shadow-sm mb-3 <?php echo $message["is_read"] ? "" : "bg-light"; ?>">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

                        <div>

                            <strong><?php echo $h($message["subject"]); ?></strong>

                            <?php if (!$message["is_read"]): ?>
                                <span class="badge bg-danger">New</span>
                            <?php endif; ?>

                            <div class="text-muted small">
                                <?php echo $h($message["name"]); ?>
                                &lt;<a href="mailto:<?php echo $h($message["email"]); ?>"><?php echo $h($message["email"]); ?></a>&gt;
                                &bull;
                                <?php echo date("M d, Y h:i A", strtotime($message["created_at"])); ?>
                            </div>

                        </div>

                        <form method="POST" action="message-action.php" class="d-flex gap-2">

                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int) $message["id"]; ?>">

                            <button
                                type="submit"
                                name="action"
                                value="<?php echo $message["is_read"] ? "unread" : "read"; ?>"
                                class="btn btn-outline-secondary btn-sm"
                            >
                                <?php echo $message["is_read"] ? "Mark Unread" : "Mark Read"; ?>
                            </button>

                            <button
                                type="submit"
                                name="action"
                                value="delete"
                                class="btn btn-danger btn-sm"
                                onclick="return confirm('Delete this message?');"
                            >
                                Delete
                            </button>

                        </form>

                    </div>

                    <p class="mt-3 mb-0"><?php echo nl2br($h($message["message"])); ?></p>

                    <?php if (!empty($message["reply"])): ?>

                        <div class="alert alert-info mt-3 mb-0">
                            <strong>Your reply</strong>
                            <span class="text-muted small">
                                (<?php echo date("M d, Y h:i A", strtotime($message["replied_at"])); ?>)
                            </span>
                            <p class="mb-0 mt-1"><?php echo nl2br($h($message["reply"])); ?></p>
                        </div>

                    <?php endif; ?>

                    <details class="mt-3">

                        <summary class="btn btn-outline-primary btn-sm">
                            <?php echo !empty($message["reply"]) ? "Edit Reply" : "Reply"; ?>
                        </summary>

                        <form method="POST" action="message-reply.php" class="mt-2">

                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int) $message["id"]; ?>">

                            <div class="mb-2">
                                <label class="form-label small fw-bold">
                                    To: <?php echo $h($message["email"]); ?>
                                </label>
                            </div>

                            <textarea
                                name="reply"
                                class="form-control mb-2"
                                rows="4"
                                placeholder="Type your reply..."
                                required
                            ><?php echo $h($message["reply"] ?? ""); ?></textarea>

                            <button type="submit" class="btn btn-primary btn-sm">
                                Send Reply
                            </button>

                        </form>

                    </details>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-5">
                <div class="fs-1">✉️</div>
                <h4 class="fw-bold mt-3">No Messages Yet</h4>
                <p class="text-muted mb-0">
                    Messages sent from the Contact page will show up here.
                </p>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php require_once "../includes/footer.php"; ?>
