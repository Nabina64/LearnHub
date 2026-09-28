<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

/* Admin Only */

require_role("admin", "../index.php");

/* POST only + security token */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    redirect("messages.php");
}

if (!csrf_valid()) {

    $_SESSION["error"] = "Security check failed. Please try again.";

    redirect("messages.php");
}

$id = intval($_POST["id"] ?? 0);

$action = $_POST["action"] ?? "";

if ($id <= 0) {

    $_SESSION["error"] = "Invalid message.";

    redirect("messages.php");
}

if ($action === "read" || $action === "unread") {

    $stmt = $pdo->prepare(
        "UPDATE contact_messages SET is_read = ? WHERE id = ?"
    );

    $stmt->execute([$action === "read" ? 1 : 0, $id]);

    $_SESSION["success"] = $action === "read"
        ? "Message marked as read."
        : "Message marked as unread.";

} elseif ($action === "delete") {

    $stmt = $pdo->prepare(
        "DELETE FROM contact_messages WHERE id = ?"
    );

    $stmt->execute([$id]);

    $_SESSION["success"] = "Message deleted.";

} else {

    $_SESSION["error"] = "Unknown action.";
}

redirect("messages.php");
