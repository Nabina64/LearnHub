<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";

$pageTitle = "Terms & Conditions - LearnHub";
$extraCss = ["public.css"];

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
?>

<div class="lh-courses-page">

    <section class="lh-courses-hero lh-page-hero">

        <div class="container">

            <div class="lh-courses-hero-content">

                <span class="lh-courses-eyebrow">
                    TERMS &amp; CONDITIONS
                </span>

                <h1>
                    The rules of <span>using LearnHub</span>
                </h1>

                <p>
                    Please read this carefully.
                </p>

            </div>

        </div>

    </section>


    <section class="lh-courses-main">

        <div class="container">

            <div class="row justify-content-center">

                <div class="col-lg-9">

                    <div class="lh-info-card lh-legal">

                        <p class="text-muted"><strong>Last updated:</strong> September 2026</p>

                        <p>These terms explain how LearnHub may be used by students, teachers and visitors.</p>

                        <h2>1. Accepting these terms</h2>
                        <p>By creating an account or using LearnHub you agree to these Terms &amp; Conditions. If you do not agree, please do not use the platform.</p>

                        <h2>2. Accounts</h2>
                        <p>You must give correct information when you register and keep your password private. You are responsible for everything that happens under your account. Tell us at once if you think someone else is using it.</p>

                        <h2>3. Student use</h2>
                        <p>Students may enroll in courses, watch lessons and take quizzes for their own learning. Course material may not be copied, resold or shared publicly without the permission of the teacher who created it.</p>

                        <h2>4. Teacher use</h2>
                        <p>Teachers may publish only content that they created or have the right to use. Teacher accounts may need approval by an administrator. Content that is illegal, harmful, misleading or that breaks someone else's rights will be removed.</p>

                        <h2>5. Acceptable behaviour</h2>
                        <p>Do not try to break, overload or gain unauthorised access to the platform, do not upload malicious material, and do not harass other users. We may suspend or delete accounts that break these rules.</p>

                        <h2>6. Content and ownership</h2>
                        <p>Teachers keep the rights to the courses they create. By publishing a course on LearnHub the teacher allows the platform to show that course to its users. The LearnHub name, design and software belong to LearnHub.</p>

                        <h2>7. Deleting accounts and content</h2>
                        <p>You can delete your account from Settings. Deleted accounts, courses and lessons are first moved to trash and can be restored by an administrator for a limited time before they are removed for good.</p>

                        <h2>8. Availability</h2>
                        <p>We work to keep LearnHub available but we cannot promise that it will always be free of errors or interruptions. Features may change or be removed.</p>

                        <h2>9. Limit of responsibility</h2>
                        <p>Courses are prepared by their teachers. LearnHub does not guarantee any particular learning result and is not responsible for the accuracy of third-party content, such as embedded videos.</p>

                        <h2>10. Changes</h2>
                        <p>We may update these terms from time to time. The date at the top of this page shows the latest version. Continuing to use LearnHub after a change means you accept the new terms.</p>

                        <h2>11. Contact</h2>
                        <p>Questions about these terms? Please use the <a href="contact.php">Contact page</a>.</p>

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
