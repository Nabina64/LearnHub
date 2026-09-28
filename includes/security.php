<?php

/**
 * Security helpers shared across the whole app:
 *
 *   - send_security_headers()   HTTP security headers on every response
 *   - get_client_ip()           best-effort real visitor IP
 *   - app_base_url()            absolute site URL (for links inside emails)
 *   - validate_password_strength()   the password policy, in one place
 *   - rate_limit_hit() / rate_limit_count() / rate_limit_prune()
 *         a tiny, generic "N events per time window" counter used for
 *         login throttling, password-reset requests and verification
 *         email resends.
 *
 * This file only defines functions - it has no side effects on its
 * own, so it is safe to require_once from includes/session.php before
 * the database connection even exists.
 */


/* =====================================================
   HTTP security headers
===================================================== */

/**
 * Sends a standard set of hardening headers on every response.
 *
 * Must be called before any HTML/output is sent (same rule as
 * session_start() / header() in general). includes/session.php calls
 * this automatically at the very top of every page.
 */
function send_security_headers()
{
    if (headers_sent()) {
        return;
    }

    // Stops the site from ever being framed by another site
    // (clickjacking protection). "SAMEORIGIN" still allows the app
    // to frame its own pages if it ever needs to.
    header("X-Frame-Options: SAMEORIGIN");

    // Stops the browser from "guessing" a different content type
    // than what the server declared (helps block some XSS vectors
    // via uploaded files served with an unexpected MIME type).
    header("X-Content-Type-Options: nosniff");

    // Legacy header, ignored by modern browsers but still expected
    // by some security scanners. Real XSS protection comes from
    // output escaping (clean()/htmlspecialchars()) throughout the app.
    header("X-XSS-Protection: 1; mode=block");

    // Only send the origin (not the full URL/path) as a Referer to
    // other sites, and nothing at all over a downgrade to plain http.
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // Disable browser features this site never uses.
    header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()");

    // Content-Security-Policy: the app only ever loads Bootstrap from
    // jsdelivr and its own CSS/JS/images, plus embedding YouTube /
    // Vimeo / Google Drive players for lesson videos.
    header(
        "Content-Security-Policy: "
        . "default-src 'self'; "
        . "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; "
        . "style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; "
        . "img-src 'self' data: https:; "
        . "font-src 'self' https://cdn.jsdelivr.net data:; "
        . "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com "
        . "https://player.vimeo.com https://drive.google.com; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'self'"
    );

    // Once the site is actually served over HTTPS (see FORCE_HTTPS in
    // .env), tell browsers to always use https for the next year -
    // never send this over a plain http response, or a real
    // certificate problem could lock visitors out of the site.
    if (function_exists("is_https_request") && is_https_request()) {

        header(
            "Strict-Transport-Security: max-age=31536000; includeSubDomains"
        );
    }
}


/* =====================================================
   Client IP (used by rate limiting + audit log)
===================================================== */

/**
 * Best-effort real visitor IP address.
 *
 * Trusts X-Forwarded-For / X-Real-IP only because this app is small
 * and commonly deployed behind a single trusted reverse proxy - on a
 * setup with no proxy this simply falls back to REMOTE_ADDR.
 */
function get_client_ip()
{
    $candidates = [
        $_SERVER["HTTP_X_FORWARDED_FOR"] ?? null,
        $_SERVER["HTTP_X_REAL_IP"] ?? null,
        $_SERVER["REMOTE_ADDR"] ?? null,
    ];

    foreach ($candidates as $value) {

        if (!$value) {
            continue;
        }

        // X-Forwarded-For can be "client, proxy1, proxy2"
        $first = trim(explode(",", $value)[0]);

        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }

    return "0.0.0.0";
}


/* =====================================================
   Absolute site URL (for links inside emails)
===================================================== */

/**
 * Builds an absolute URL back to this site, for use inside emails
 * (a relative link makes no sense once it leaves the browser).
 *
 * Set APP_URL in .env for a predictable value in production
 * (recommended). Falls back to detecting it from the current
 * request, which works fine for local development.
 */
function app_base_url()
{
    $configured = function_exists("env") ? env("APP_URL", "") : "";

    if ($configured) {
        return rtrim($configured, "/");
    }

    $scheme = (function_exists("is_https_request") && is_https_request())
        ? "https"
        : "http";

    $host = $_SERVER["HTTP_HOST"] ?? "localhost";

    return $scheme . "://" . $host . "/online-learning-platform";
}


/* =====================================================
   Password policy
===================================================== */

/**
 * The single source of truth for "is this a strong enough password".
 * Used by registration, password change and password reset so the
 * rule can never drift out of sync between those three forms.
 *
 * Returns an array of error strings - empty array = password is OK.
 */
function validate_password_strength($password)
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if (strlen($password) > 72) {
        // bcrypt (PASSWORD_DEFAULT) silently ignores anything past 72
        // bytes - reject early instead of accepting a false sense of
        // a very long password.
        $errors[] = "Password must be at most 72 characters long.";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }

    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character (e.g. !@#$%).";
    }

    // A short, well-known blocklist - catches the most common weak
    // choices without needing an external wordlist.
    $common = [
        "password", "password1", "password123", "12345678", "123456789",
        "qwerty123", "letmein1", "welcome1", "admin123", "iloveyou1",
    ];

    if (in_array(strtolower($password), $common, true)) {
        $errors[] = "That password is too common. Please choose a different one.";
    }

    return $errors;
}


/**
 * One-line helper text shown under password fields in every form
 * that uses validate_password_strength(), so the rule is only
 * written out once.
 */
function password_policy_hint()
{
    return "At least 8 characters, with uppercase, lowercase, "
        . "a number and a special character.";
}


/* =====================================================
   Generic rate limiting ("N events per time window")
===================================================== */

/**
 * Records one event under $bucket (e.g. "login:ip:1.2.3.4" or
 * "pwreset:email:jane@example.com"). Never throws - a rate-limit
 * write must never be the reason a page breaks.
 */
function rate_limit_hit($pdo, $bucket)
{
    try {

        $stmt = $pdo->prepare(
            "INSERT INTO rate_limit_hits (bucket) VALUES (?)"
        );

        $stmt->execute([$bucket]);

        // Occasionally sweep old rows so the table never grows
        // forever - no cron job required for a small app like this.
        if (random_int(1, 50) === 1) {
            rate_limit_prune($pdo);
        }

    } catch (Throwable $e) {

        error_log("LearnHub rate_limit_hit: " . $e->getMessage());
    }
}


/**
 * Counts how many events were recorded under $bucket within the
 * last $window_seconds. Fails "open" (returns 0) if the table is
 * missing or the query fails, so a database hiccup never locks
 * everyone out of the site.
 */
function rate_limit_count($pdo, $bucket, $window_seconds)
{
    try {

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total
             FROM rate_limit_hits
             WHERE bucket = ?
             AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );

        $stmt->execute([$bucket, $window_seconds]);

        return (int) $stmt->fetch()["total"];

    } catch (Throwable $e) {

        error_log("LearnHub rate_limit_count: " . $e->getMessage());

        return 0;
    }
}


/** Deletes rate-limit rows older than a day - keeps the table small. */
function rate_limit_prune($pdo)
{
    try {

        $pdo->exec(
            "DELETE FROM rate_limit_hits
             WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );

    } catch (Throwable $e) {
        // Not critical - try again next time.
    }
}


/**
 * Human-friendly "try again in X minutes" text.
 */
function format_wait_time($seconds)
{
    $minutes = (int) ceil($seconds / 60);

    if ($minutes <= 1) {
        return "a minute";
    }

    return $minutes . " minutes";
}
