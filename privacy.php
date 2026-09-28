<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";

$pageTitle = "Privacy Policy - LearnHub";
$extraCss = ["public.css"];

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
?>

<div class="lh-courses-page">

    <section class="lh-courses-hero lh-page-hero">

        <div class="container">

            <div class="lh-courses-hero-content">

                <span class="lh-courses-eyebrow">
                    PRIVACY POLICY
                </span>

                <h1>
                    Your privacy <span>matters</span>
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

                        <p>This policy explains what information LearnHub keeps and how it is used.</p>

                        <h2>1. What we collect</h2>
                        <p>When you register we store your name, email address and a scrambled (hashed) version of your password. While you use the platform we store your course enrollments, quiz results, which course pages you open (used for the teacher's view statistics) and, for teachers, the courses and lessons you create. If you write to us through the Contact page we store your name, email and message.</p>

                        <h2>2. Why we collect it</h2>
                        <p>We use this information only to run LearnHub: to log you in, show your courses and results, let teachers see who joined their courses, and answer your messages.</p>

                        <h2>3. Who can see it</h2>
                        <p>Your name and email are visible to administrators. Teachers can see the name and email of students enrolled in their own courses. Other students cannot see your details. We do not sell or rent your data.</p>

                        <h2>4. Cookies</h2>
                        <p>LearnHub uses one session cookie to keep you logged in. It is removed when you close the browser, and your session ends automatically after 30 minutes without activity. We do not use advertising or tracking cookies.</p>

                        <h2>5. Other services</h2>
                        <p>Pages load the Bootstrap library from a public CDN. Lessons may contain videos from services such as YouTube or Vimeo; when you play such a video, that service may collect data according to its own privacy policy.</p>

                        <h2>6. Keeping your data safe</h2>
                        <p>Passwords are stored in hashed form and administrative pages are only open to administrators. No system is perfect, so please also use a strong, private password.</p>

                        <h2>7. Your choices</h2>
                        <p>You can change your name, email and password in Settings. You can delete your account in Settings; it is moved to trash and permanently removed later. You can ask us through the Contact page to correct or remove data about you.</p>

                        <h2>8. Children</h2>
                        <p>LearnHub is meant for learners who can agree to these terms. If you are under the age required in your country, please use the platform together with a parent or teacher.</p>

                        <h2>9. Changes</h2>
                        <p>We may update this policy. The date at the top of the page shows the latest version.</p>

                        <h2>10. Contact</h2>
                        <p>Questions about privacy? Please use the <a href="contact.php">Contact page</a>.</p>

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
