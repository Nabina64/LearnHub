<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/account.php";

/* Admin Only */

require_role("admin", "../index.php");

$pageTitle = "Settings - LearnHub";


/* -----------------------------------------------------
   Platform settings form
----------------------------------------------------- */

$platform_errors = [];
$platform_success = null;

$values = [
    "allow_registration"     => get_setting($pdo, "allow_registration"),
    "teacher_needs_approval" => get_setting($pdo, "teacher_needs_approval"),
    "contact_email"          => get_setting($pdo, "contact_email"),
    "contact_phone"          => get_setting($pdo, "contact_phone"),
    "contact_address"        => get_setting($pdo, "contact_address"),
];

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["action"] ?? "") === "save_platform"
) {

    if (!csrf_valid()) {

        $platform_errors[] = "Security check failed. Please try again.";

    } else {

        $values["allow_registration"] =
            isset($_POST["allow_registration"]) ? "1" : "0";

        $values["teacher_needs_approval"] =
            isset($_POST["teacher_needs_approval"]) ? "1" : "0";

        $values["contact_email"] = trim($_POST["contact_email"] ?? "");
        $values["contact_phone"] = trim($_POST["contact_phone"] ?? "");
        $values["contact_address"] = trim($_POST["contact_address"] ?? "");

        if (
            $values["contact_email"] !== ""
            && !filter_var($values["contact_email"], FILTER_VALIDATE_EMAIL)
        ) {
            $platform_errors[] = "Contact email is not a valid email address.";
        }

        if (
            $values["contact_phone"] !== ""
            && !preg_match('/^[0-9+\-()\s]{5,30}$/', $values["contact_phone"])
        ) {
            $platform_errors[] =
                "Phone number can only contain digits, spaces, + - ( ) "
                . "(5 to 30 characters).";
        }

        if (strlen($values["contact_address"]) > 200) {
            $platform_errors[] = "Address is too long (maximum 200 characters).";
        }

        if (empty($platform_errors)) {

            $saved = true;

            foreach ($values as $key => $value) {
                $saved = set_setting($pdo, $key, $value) && $saved;
            }

            if ($saved) {
                $platform_success = "Platform settings were saved.";
            } else {
                $platform_errors[] =
                    "Could not save the settings. Please try again.";
            }
        }
    }
}


/* Must run before any HTML is printed */

$account = handle_account_post($pdo, "admin");

$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                Settings
            </h2>

            <p class="text-muted mb-0">
                Control how LearnHub works and manage your admin account.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="btn btn-outline-secondary"
        >
            &larr; Dashboard
        </a>

    </div>

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <!-- Platform Settings -->

            <?php if ($platform_success): ?>

                <div class="alert alert-success">
                    <?php echo $h($platform_success); ?>
                </div>

            <?php endif; ?>

            <?php if (!empty($platform_errors)): ?>

                <div class="alert alert-danger">

                    <ul class="mb-0">

                        <?php foreach ($platform_errors as $error): ?>

                            <li><?php echo $h($error); ?></li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body p-4">

                    <h4 class="fw-bold mb-1">
                        Platform Settings
                    </h4>

                    <p class="text-muted">
                        These settings apply to the whole website.
                    </p>

                    <form method="POST">

                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="save_platform">

                        <!-- Registration -->

                        <div class="form-check form-switch mb-1">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="allow_registration"
                                name="allow_registration"
                                value="1"
                                <?php echo $values["allow_registration"] === "1" ? "checked" : ""; ?>
                            >

                            <label class="form-check-label fw-bold" for="allow_registration">
                                Allow new registrations
                            </label>

                        </div>

                        <p class="text-muted small ms-5">
                            When off, the Register page shows a "registration closed" message.
                            Existing users can still log in.
                        </p>


                        <!-- Teacher approval -->

                        <div class="form-check form-switch mb-1">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="teacher_needs_approval"
                                name="teacher_needs_approval"
                                value="1"
                                <?php echo $values["teacher_needs_approval"] === "1" ? "checked" : ""; ?>
                            >

                            <label class="form-check-label fw-bold" for="teacher_needs_approval">
                                New teachers need admin approval
                            </label>

                        </div>

                        <p class="text-muted small ms-5">
                            When off, teachers can log in immediately after registering
                            and no request appears in Teacher Requests.
                        </p>

                        <hr>

                        <h6 class="fw-bold">
                            Contact Information
                        </h6>

                        <p class="text-muted small">
                            Shown on the public Contact page. Leave a field empty to hide it.
                        </p>

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Contact Email
                                </label>

                                <input
                                    type="email"
                                    name="contact_email"
                                    class="form-control"
                                    maxlength="150"
                                    placeholder="support@example.com"
                                    value="<?php echo $h($values["contact_email"]); ?>"
                                >

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Contact Phone
                                </label>

                                <input
                                    type="text"
                                    name="contact_phone"
                                    class="form-control"
                                    maxlength="30"
                                    placeholder="+977 98XXXXXXXX"
                                    value="<?php echo $h($values["contact_phone"]); ?>"
                                >

                            </div>

                        </div>

                        <div class="mb-4">

                            <label class="form-label fw-bold">
                                Address
                            </label>

                            <input
                                type="text"
                                name="contact_address"
                                class="form-control"
                                maxlength="200"
                                placeholder="City, Country"
                                value="<?php echo $h($values["contact_address"]); ?>"
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Save Settings
                        </button>

                    </form>

                </div>

            </div>


            <!-- Admin account -->

            <?php render_account_settings($account, "admin"); ?>

        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
