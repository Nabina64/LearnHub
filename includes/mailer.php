<?php

/**
 * A small, dependency-free SMTP client.
 *
 * There's no Composer/PHPMailer in this project, so this talks
 * SMTP directly to Gmail (smtp.gmail.com:587 + STARTTLS) using an
 * App Password. This is exactly what PHPMailer/Composer libraries
 * do under the hood - it is just written out by hand here.
 *
 * Configure it in .env (see .env.example):
 *   SMTP_HOST=smtp.gmail.com
 *   SMTP_PORT=587
 *   SMTP_USER=your-gmail-address@gmail.com
 *   SMTP_PASS=your-16-character-app-password
 *   MAIL_FROM_NAME=LearnHub
 *
 * IMPORTANT: SMTP_PASS must be a Gmail "App Password", not your
 * normal Gmail password. Google requires 2-Step Verification to be
 * turned on for the Gmail account before it will issue one:
 * https://myaccount.google.com/apppasswords
 */

class SimpleSmtpMailer
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromName;

    public $lastError = "";

    public function __construct($host, $port, $username, $password, $fromName)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->fromName = $fromName;
    }


    /**
     * Send a plain-text email.
     *
     * @param string $to        Destination address (e.g. the admin's contact_email).
     * @param string $subject
     * @param string $body      Plain-text body.
     * @param string $replyTo   Optional Reply-To address (the visitor's email).
     * @param string $replyToName
     */
    public function send($to, $subject, $body, $replyTo = null, $replyToName = null)
    {
        if ($this->username === "" || $this->password === "") {
            $this->lastError = "SMTP is not configured (SMTP_USER / SMTP_PASS missing).";
            return false;
        }

        $scheme = ((int) $this->port === 465) ? "ssl://" : "tcp://";

        $socket = @stream_socket_client(
            $scheme . $this->host . ":" . $this->port,
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            $this->lastError = "Could not connect to " . $this->host . ":" . $this->port . " - " . $errstr
                . " (if this times out, your hosting provider may be blocking outgoing SMTP connections - ask them, or try port 465).";
            return false;
        }

        stream_set_timeout($socket, 15);

        try {

            $this->expect($socket, "220");

            $this->command($socket, "EHLO localhost", "250");

            // Port 465 is already encrypted (implicit TLS) - only
            // ports like 587 need the separate STARTTLS upgrade.
            if ($scheme === "tcp://") {

                $this->command($socket, "STARTTLS", "220");

                if (
                    !stream_socket_enable_crypto(
                        $socket,
                        true,
                        STREAM_CRYPTO_METHOD_TLS_CLIENT
                    )
                ) {
                    throw new Exception("TLS negotiation with the SMTP server failed.");
                }

                $this->command($socket, "EHLO localhost", "250");
            }

            $this->command($socket, "AUTH LOGIN", "334");
            $this->command($socket, base64_encode($this->username), "334");
            $this->command($socket, base64_encode($this->password), "235");

            $this->command($socket, "MAIL FROM:<" . $this->username . ">", "250");
            $this->command($socket, "RCPT TO:<" . $to . ">", "250");

            $this->command($socket, "DATA", "354");

            $headers = [];
            $headers[] = "From: " . $this->encodeHeader($this->fromName) . " <" . $this->username . ">";
            $headers[] = "To: <" . $to . ">";

            if ($replyTo) {
                $name = $replyToName !== null ? $this->encodeHeader($replyToName) . " " : "";
                $headers[] = "Reply-To: " . $name . "<" . $replyTo . ">";
            }

            $headers[] = "Subject: " . $this->encodeHeader($subject);
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
            $headers[] = "Content-Transfer-Encoding: 8bit";
            $headers[] = "Date: " . date("r");

            // Dot-stuff any line that starts with a lone "."
            $safeBody = preg_replace("/^\./m", "..", $body);

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $safeBody . "\r\n.";

            $this->command($socket, $message, "250");

            $this->command($socket, "QUIT", "221");

            fclose($socket);

            return true;

        } catch (Exception $e) {

            $this->lastError = $e->getMessage();

            fclose($socket);

            return false;
        }
    }


    /** Encode a header value that might contain non-ASCII characters (e.g. Devanagari names). */
    private function encodeHeader($value)
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return "=?UTF-8?B?" . base64_encode($value) . "?=";
        }

        return $value;
    }


    private function command($socket, $line, $expectedCode)
    {
        fwrite($socket, $line . "\r\n");

        $this->expect($socket, $expectedCode);
    }


    private function expect($socket, $expectedCode)
    {
        $response = "";

        while (!feof($socket)) {

            $line = fgets($socket, 515);

            if ($line === false) {
                break;
            }

            $response .= $line;

            // A space (not a dash) after the 3-digit code marks the last line.
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }

        if (strpos($response, $expectedCode) !== 0) {
            throw new Exception(
                "Unexpected SMTP response (expected " . $expectedCode . "): " . trim($response)
            );
        }

        return $response;
    }
}


/**
 * Convenience wrapper used throughout the app.
 *
 * Reads SMTP_* settings from .env and sends one plain-text email.
 * Returns true on success. On failure it logs the real reason to
 * the server error log and returns false - callers should show the
 * visitor a generic message, never $mailer->lastError.
 */
function send_app_email($to, $subject, $body, $replyTo = null, $replyToName = null)
{
    $host = env("SMTP_HOST", "smtp.gmail.com");
    $port = (int) env("SMTP_PORT", "587");
    $user = env("SMTP_USER", "");
    $pass = env("SMTP_PASS", "");
    $fromName = env("MAIL_FROM_NAME", "LearnHub");

    if ($user === "" || $pass === "") {
        error_log("LearnHub mailer: SMTP_USER/SMTP_PASS not configured in .env - email not sent.");
        return false;
    }

    $mailer = new SimpleSmtpMailer($host, $port, $user, $pass, $fromName);

    $sent = $mailer->send($to, $subject, $body, $replyTo, $replyToName);

    if (!$sent) {
        error_log("LearnHub mailer: failed to send email - " . $mailer->lastError);
    }

    return $sent;
}
