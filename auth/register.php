<?php

require_once __DIR__ . "/../includes/session.php";

require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/mailer.php";

$pageTitle = "Register - LearnHub";

$errors = [];

/* Admin can close registration from Admin -> Settings */

$registration_open = get_setting($pdo, "allow_registration") === "1";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !csrf_valid()) {

    $errors[] = "Security check failed. Please try again.";

} elseif ($_SERVER["REQUEST_METHOD"] === "POST" && !$registration_open) {

    $errors[] = "Registration is currently closed.";

} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = clean($_POST["name"] ?? "");
    $email = clean($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "student";


    // Name validation
    if ($name === "") {
        $errors[] = "Name is required.";
    }


    // Email validation
    if ($email === "") {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }


    // Password validation
    if ($password === "") {
        $errors[] = "Password is required.";
    } else {
        $errors = array_merge($errors, validate_password_strength($password));
    }


    // Confirm password
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }


    // Only allow student and teacher registration
    if (!in_array($role, ["student", "teacher"])) {
        $role = "student";
    }


    // Check email only if there are no previous errors
    if (empty($errors)) {

        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $errors[] = "Email is already registered.";

        } else {

            // Hash password
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // Teachers need admin approval (Admin -> Settings), students do not
            $status = ($role === "teacher"
                && get_setting($pdo, "teacher_needs_approval") === "1")
                ? "pending"
                : "approved";

            $approvedAt = ($status === "approved")
                ? date("Y-m-d H:i:s")
                : null;


            // Insert user
            $stmt = $pdo->prepare(
                "INSERT INTO users
                (name, email, password, role, status, approved_at)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $name,
                $email,
                $hashedPassword,
                $role,
                $status,
                $approvedAt
            ]);

            $newUserId = (int) $pdo->lastInsertId();

            audit_log($pdo, $newUserId, "registered", "Registered as $role.", $email);


            // Email verification - generate a token, email the link,
            // and keep the account unverified until they click it.

            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash("sha256", $rawToken);
            $tokenExpires = date("Y-m-d H:i:s", time() + 86400); // 24 hours

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET verify_token_hash = ?, verify_token_expires = ?
                 WHERE id = ?"
            );
            $stmt->execute([$tokenHash, $tokenExpires, $newUserId]);

            $verifyLink = app_base_url() . "/auth/verify-email.php?token=" . $rawToken;

            $emailSent = send_app_email(
                $email,
                "Verify your LearnHub email address",
                "Hi $name,\n\n"
                . "Thanks for registering at LearnHub. Please confirm "
                . "your email address by opening this link:\n\n"
                . $verifyLink . "\n\n"
                . "This link expires in 24 hours. If you did not "
                . "create this account, you can ignore this email.\n"
            );


            // Registration successful

            if ($status === "pending") {

                $_SESSION["success"] =
                    "Registration successful! We've sent a verification "
                    . "link to your email - please confirm it first. "
                    . "Your teacher account will also need admin "
                    . "approval before you can login.";

            } else {

                $_SESSION["success"] =
                    "Registration successful! We've sent a verification "
                    . "link to your email address. Please confirm it, "
                    . "then login.";
            }

            if (!$emailSent) {

                $_SESSION["success"] .=
                    " (If the email does not arrive, use the "
                    . "\"Resend verification email\" link on the login page.)";
            }

            redirect("login.php");
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
                        Create Account
                    </h2>


                    <!-- Error Messages -->

                    <?php if (!empty($errors)): ?>

                        <div class="alert alert-danger">

                            <ul class="mb-0">

                                <?php foreach ($errors as $error): ?>

                                    <li>
                                        <?php echo htmlspecialchars($error); ?>
                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        </div>

                    <?php endif; ?>


                    <!-- Registration Form -->

                    <?php if ($registration_open): ?>

                    <form method="POST">

                        <?php echo csrf_field(); ?>


                        <!-- Name -->

                        <div class="mb-3">

                            <label class="form-label">
                                Full Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                placeholder="Enter your name"
                                value="<?php echo htmlspecialchars($name ?? ""); ?>"
                                required
                            >

                        </div>


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

                        <div class="mb-3">

                            <label class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="<?php echo htmlspecialchars(password_policy_hint()); ?>"
                                required
                            >

                            <small class="text-muted">
                                <?php echo htmlspecialchars(password_policy_hint()); ?>
                            </small>

                        </div>


                        <!-- Confirm Password -->

                        <div class="mb-3">

                            <label class="form-label">
                                Confirm Password
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                placeholder="Re-enter password"
                                required
                            >

                        </div>


                        <!-- Role -->

                        <div class="mb-4">

                            <label class="form-label">
                                Register As
                            </label>

                            <select
                                name="role"
                                class="form-select"
                            >

                                <option value="student">
                                    Student
                                </option>

                                <option value="teacher">
                                    Teacher
                                </option>

                            </select>

                            <small class="text-muted">
                                Teacher accounts need admin approval
                                before the first login.
                            </small>

                        </div>


                        <!-- Submit -->

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Create Account
                        </button>

                    </form>

                    <?php else: ?>

                        <div class="alert alert-warning mb-3">
                            New registrations are currently closed.
                            Please check back later.
                        </div>

                    <?php endif; ?>


                    <p class="text-center mt-4 mb-0">

                        Already have an account?

                        <a href="login.php">
                            Login
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