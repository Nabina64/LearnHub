<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Teacher Only */

require_role("teacher", "../index.php");

$pageTitle = "My Messages - LearnHub";

$teacher_id = $_SESSION["user_id"];

$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};


/* My messages sent through the Contact page */

$stmt = $pdo->prepare(
    "SELECT id, subject, message, reply, replied_at, created_at
     FROM contact_messages
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->execute([$teacher_id]);

$messages = $stmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">

        <div>
            <h2 class="fw-bold mb-1">My Messages</h2>
            <p class="text-muted mb-0">
                Messages you sent from the <a href="../contact.php">Contact page</a>, and any replies from our team.
            </p>
        </div>

        <a href="../contact.php" class="btn btn-primary">
            + New Message
        </a>

    </div>

    <?php if (empty($messages)): ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-5">
                <div class="fs-1">✉️</div>
                <h4 class="fw-bold mt-3">No Messages Yet</h4>
                <p class="text-muted mb-0">
                    Anything you send from the Contact page will show up here.
                </p>
            </div>
        </div>

    <?php else: ?>

        <?php foreach ($messages as $message): ?>

            <div class="card border-0 shadow-sm mb-3">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

                        <strong><?php echo $h($message["subject"]); ?></strong>

                        <?php if (!empty($message["replied_at"])): ?>
                            <span class="badge bg-success">Replied</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Waiting for reply</span>
                        <?php endif; ?>

                    </div>

                    <div class="text-muted small mb-2">
                        Sent <?php echo date("M d, Y h:i A", strtotime($message["created_at"])); ?>
                    </div>

                    <p class="mb-0"><?php echo nl2br($h($message["message"])); ?></p>

                    <?php if (!empty($message["reply"])): ?>

                        <div class="alert alert-info mt-3 mb-0">
                            <strong>Reply from LearnHub</strong>
                            <span class="text-muted small">
                                (<?php echo date("M d, Y h:i A", strtotime($message["replied_at"])); ?>)
                            </span>
                            <p class="mb-0 mt-1"><?php echo nl2br($h($message["reply"])); ?></p>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

<?php require_once "../includes/footer.php"; ?>
