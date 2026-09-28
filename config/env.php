<?php

/**
 * Minimal .env loader (no Composer package required).
 *
 * Reads KEY=VALUE pairs from a ".env" file in the project root and
 * exposes them through env(). Real server environment variables
 * (set via Apache/Nginx/Docker/etc.) always win over the .env file,
 * so .env is only a convenience for local development.
 *
 * The .env file must NEVER be committed to version control -
 * see .env.example for the list of keys the app needs.
 */

function load_env($path)
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;

    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {

        $line = trim($line);

        // Skip comments
        if ($line === "" || $line[0] === "#") {
            continue;
        }

        if (strpos($line, "=") === false) {
            continue;
        }

        [$key, $value] = explode("=", $line, 2);

        $key = trim($key);
        $value = trim($value);

        // Strip surrounding quotes, e.g. DB_PASS="my pass"
        if (
            strlen($value) >= 2
            && (
                ($value[0] === '"' && substr($value, -1) === '"')
                || ($value[0] === "'" && substr($value, -1) === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        // Do not override a real environment variable that is
        // already set at the OS / web-server level.
        if (getenv($key) === false) {
            putenv($key . "=" . $value);
        }
    }
}


/** Read one env value, with an optional default. */
function env($key, $default = null)
{
    $value = getenv($key);

    return ($value === false) ? $default : $value;
}
