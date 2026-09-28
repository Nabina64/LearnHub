<?php

require_once __DIR__ . "/../includes/session.php";

require_once "../config/database.php";
require_once "../includes/functions.php";

$pageTitle = "Login - LearnHub";

$error = "";

// Login rate limiting - keyed on both the submitted email (protects
// one account from a targeted attack) and the visitor's IP (slows
// down someone spraying many different email addresses).
$client_ip = get_client_ip();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = clean($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $email_bucket = "login:email:" . strtolower($email);
    $ip_bucket = "login:ip:" . $client_ip;

    $email_attempts = $email !== ""
        ? rate_limit_count($pdo, $email_bucket, 900) // 15 minutes
        : 0;

    $ip_attempts = rate_limit_count($pdo, $ip_bucket, 900);

    if ($email_attempts >= 5 || $ip_attempts >= 20) {

        $error = "Too many login attempts. Please wait "
            . format_wait_time(900) . " and try again.";

        audit_log(
            $pdo,
            null,
            "login_locked",
            "Login temporarily blocked after repeated failed attempts.",
            $email
        );

    } elseif (!csrf_valid()) {

        $error = "Security check failed. Please try again.";

    } elseif ($email === "" || $password === "") {

        $error = "Please enter email and password.";

    } else {

        $stmt = $pdo->prepare(
            "SELECT id, name, email, password, role, status,
                    must_change_password, email_verified_at
             FROM users
             WHERE email = ?
             AND deleted_at IS NULL"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {

            // A correct password clears this account's own failed
            // counter, but the IP-wide counter is left alone - it
            // exists to slow down attacks, not to reward one lucky
            // guess.

            if (empty($user["email_verified_at"])) {

                $error = "Please verify your email address before "
                    . "logging in. Check your inbox for the "
                    . "verification link we sent when you registered.";

                $show_resend = true;

                rate_limit_hit($pdo, $email_bucket);
                rate_limit_hit($pdo, $ip_bucket);

                audit_log($pdo, $user["id"], "login_failed", "Email not verified yet.", $user["email"]);

            } elseif ($user["status"] === "pending") {

                $error = "Your teacher account is still waiting "
                    . "for admin approval. Please try again later.";

                audit_log($pdo, $user["id"], "login_failed", "Teacher account pending approval.", $user["email"]);

            } elseif ($user["status"] === "rejected") {

                $error = "Your teacher account request was "
                    . "rejected by the admin. "
                    . "Please contact the administrator.";

                audit_log($pdo, $user["id"], "login_failed", "Teacher account rejected.", $user["email"]);

            } else {

            // New session ID on every successful login, so a
            // session ID seen/guessed before login can never be
            // reused afterwards (session fixation protection).
            session_regenerate_id(true);

            // Remember Me - keeps you logged in across browser
            // restarts instead of the normal 30-minute-idle session.
            $remember = !empty($_POST["remember"]);

            $_SESSION["remember"] = $remember;

            if ($remember) {

                // Re-issue the session cookie with a 30-day expiry
                // (it is normally a "until browser closes" cookie).
                setcookie(
                    session_name(),
                    session_id(),
                    time() + 30 * 24 * 60 * 60,
                    "/",
                    "",
                    is_https_request(),
                    true
                );
            }

            // Store user information in session
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["user_role"] = $user["role"];
            $_SESSION["must_change_password"] = (int) $user["must_change_password"];

            audit_log($pdo, $user["id"], "login_success", "Logged in.", $user["email"]);

            // Redirect according to role

            if ($user["role"] === "admin") {

                redirect("../admin/dashboard.php");

            } elseif ($user["role"] === "teacher") {

                redirect("../teacher/dashboard.php");

            } else {

                redirect("../student/dashboard.php");
            }

            }

        } else {

            $error = "Invalid email or password.";

            rate_limit_hit($pdo, $email_bucket);
            rate_limit_hit($pdo, $ip_bucket);

            audit_log($pdo, $user["id"] ?? null, "login_failed", "Invalid email or password.", $email);
        }
    }
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6 col-lg-5">

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    <h2 class="text-center fw-bold mb-4">
                        Login
                    </h2>


                    <!-- Success Message -->

                    <?php if (isset($_SESSION["success"])): ?>

                        <div class="alert alert-success">

                            <?php
                            echo htmlspecialchars($_SESSION["success"]);
                            unset($_SESSION["success"]);
                            ?>

                        </div>

                    <?php endif; ?>


                    <!-- Error Message -->

                    <?php if ($error !== ""): ?>

                        <div class="alert alert-danger">

                            <?php echo htmlspecialchars($error); ?>

                            <?php if (!empty($show_resend)): ?>

                                <br>
                                <a href="resend-verification.php">
                                    Resend verification email
                                </a>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>


                    <!-- Login Form -->

                    <form method="POST">

                        <?php echo csrf_field(); ?>


                        <!-- Email -->

                        <div class="mb-3">

                            <label class="form-label">
                                Email Address
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="Enter your email"
                                value="<?php echo htmlspecialchars($email ?? ""); ?>"
                                required
                            >

                        </div>


                        <!-- Password -->

                        <div class="mb-4">

                            <label class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Enter your password"
                                required
                            >

                            <div class="d-flex justify-content-between align-items-center mt-2">

                                <a href="forgot-password.php" class="small">
                                    Forgot password?
                                </a>

                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        value="1"
                                        class="form-check-input"
                                        id="remember"
                                    >
                                    <label class="form-check-label small" for="remember">
                                        Remember me
                                    </label>
                                </div>

                            </div>

                        </div>


                        <!-- Submit -->

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Login
                        </button>

                    </form>


                    <p class="text-center mt-4 mb-0">

                        Don't have an account?

                        <a href="register.php">
                            Register
                        </a>

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


<?php

require_once "../includes/footer.php";

?>