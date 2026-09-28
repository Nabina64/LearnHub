<?php

require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";

if (isset($_SESSION["user_id"])) {

    audit_log($pdo, $_SESSION["user_id"], "logout", "Logged out.", $_SESSION["user_email"] ?? null);
}

$_SESSION = [];


/* Remove the session cookie from the browser */

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: login.php");
exit;