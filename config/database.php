<?php

require_once __DIR__ . "/env.php";

load_env(__DIR__ . "/../.env");

$app_env = env("APP_ENV", "production");


/**
 * Error visibility.
 *
 * Production: nothing is printed to the visitor's browser -
 * everything is written to the PHP error log instead.
 *
 * Development (APP_ENV=development in .env): errors are shown on
 * screen to make local debugging easier. Never use this on a live
 * site.
 */
error_reporting(E_ALL);
ini_set("log_errors", "1");

if ($app_env === "development") {

    ini_set("display_errors", "1");

} else {

    ini_set("display_errors", "0");
}


/* Database credentials - from .env / real environment variables only. */

$host = env("DB_HOST", "localhost");
$dbname = env("DB_NAME", "online_learning");
$username = env("DB_USER", "root");
$password = env("DB_PASS", "");

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    // Full detail goes to the server log only - never to the visitor.
    error_log("LearnHub DB connection failed: " . $e->getMessage());

    if ($app_env === "development") {
        die("Database connection failed: " . $e->getMessage());
    }

    http_response_code(500);
    die("Something went wrong. Please try again later.");

}


/* Add any column/table that is missing (see includes/schema.php) */

require_once __DIR__ . "/../includes/schema.php";

ensure_schema($pdo);
