<?php

require_once __DIR__ . "/../config/env.php";
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/audit.php";

load_env(__DIR__ . "/../.env");

/* HTTP security headers on every single response (see includes/security.php) */
send_security_headers();

/**
 * Central session setup for LearnHub.
 *
 * - The session cookie is deleted when the browser is closed,
 *   so the site does not open in a "already logged in" state.
 * - The session also expires after 30 minutes of inactivity.
 * - Over HTTPS, the cookie is marked "secure" so browsers will
 *   never send it back over a plain http:// connection.
 * - When FORCE_HTTPS=1 (see .env), every http request is redirected
 *   to https before anything else runs.
 *
 * This file must be included BEFORE any session_start().
 */

define("SESSION_IDLE_LIMIT", 1800); // 30 minutes


/** True when the current request arrived over HTTPS. */
function is_https_request()
{
    if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") {
        return true;
    }

    // Behind a load balancer / reverse proxy (Nginx, a CDN, etc.)
    if (
        !empty($_SERVER["HTTP_X_FORWARDED_PROTO"])
        && strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) === "https"
    ) {
        return true;
    }

    return isset($_SERVER["SERVER_PORT"]) && (int) $_SERVER["SERVER_PORT"] === 443;
}


/* Force HTTPS in production, once FORCE_HTTPS=1 is set in .env
   (kept off by default so local http-only development still works). */

if (env("FORCE_HTTPS", "0") === "1" && !is_https_request() && php_sapi_name() !== "cli") {

    $host = $_SERVER["HTTP_HOST"] ?? "";

    $url = "https://" . $host . ($_SERVER["REQUEST_URI"] ?? "/");

    header("Location: " . $url, true, 301);
    exit;
}


if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([

        // 0 = cookie dies when the browser is closed
        "lifetime" => 0,

        "path"     => "/",
        "httponly" => true,
        "samesite" => "Lax",

        // Only sent back to the browser over HTTPS once the site is
        // actually served over HTTPS - set FORCE_HTTPS=1 in .env
        // once your certificate is in place.
        "secure"   => is_https_request()
    ]);

    session_start();
}


/* Auto logout after long inactivity - unless "Remember Me" was
   checked at login, in which case the session is allowed to live
   for up to 30 days of inactivity instead of 30 minutes. */

$idle_limit = !empty($_SESSION["remember"])
    ? 30 * 24 * 60 * 60
    : SESSION_IDLE_LIMIT;

if (
    isset($_SESSION["last_activity"])
    && (time() - $_SESSION["last_activity"]) > $idle_limit
) {

    $_SESSION = [];

    session_destroy();

    session_start();

    $_SESSION["success"] =
        "Your session expired. Please login again.";
}


$_SESSION["last_activity"] = time();
