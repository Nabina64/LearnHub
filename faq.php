<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";

$pageTitle = "FAQ - LearnHub";
$extraCss = ["public.css"];

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
?>

<div class="lh-courses-page">

    <section class="lh-courses-hero lh-page-hero">

        <div class="container">

            <div class="lh-courses-hero-content">

                <span class="lh-courses-eyebrow">
                    FAQ
                </span>

                <h1>
                    Frequently asked <span>questions</span>
                </h1>

                <p>
                    Quick answers about learning, teaching and your account.
                </p>

            </div>

        </div>

    </section>


    <section class="lh-courses-main">

        <div class="container">

            <div class="row justify-content-center">

                <div class="col-lg-9">

                    <h2 class="lh-section-title mt-4">General</h2>
                    <div class="accordion lh-faq mb-4" id="faq0">

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-1"
                                    aria-expanded="false"
                                >
                                    What is LearnHub?
                                </button>
                            </h3>
                            <div id="faq-item-1" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    LearnHub is an online learning platform. Teachers publish courses made of lessons and quizzes, and students enroll to learn at their own pace.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-2"
                                    aria-expanded="false"
                                >
                                    Do I need an account to look at the courses?
                                </button>
                            </h3>
                            <div id="faq-item-2" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    No. Anyone can browse the course list on the Courses page. You need a free account to enroll and to open lessons.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-3"
                                    aria-expanded="false"
                                >
                                    Does it cost anything?
                                </button>
                            </h3>
                            <div id="faq-item-3" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Enrolling in a course on LearnHub does not require any payment.
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2 class="lh-section-title mt-4">For Students</h2>
                    <div class="accordion lh-faq mb-4" id="faq3">

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-4"
                                    aria-expanded="false"
                                >
                                    How do I enroll in a course?
                                </button>
                            </h3>
                            <div id="faq-item-4" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Log in as a student, open Explore Courses, choose a course and press Enroll. The course then appears in My Courses.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-5"
                                    aria-expanded="false"
                                >
                                    Where are my lessons?
                                </button>
                            </h3>
                            <div id="faq-item-5" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Open My Courses and press Continue Learning. Lessons are listed on the left; the video and notes of the selected lesson are shown on the right.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-6"
                                    aria-expanded="false"
                                >
                                    Can I retake a quiz?
                                </button>
                            </h3>
                            <div id="faq-item-6" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Yes. You can retake a quiz as many times as you like. Every attempt is saved in Quiz Results with its score and date.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-7"
                                    aria-expanded="false"
                                >
                                    The lesson video does not play. What should I do?
                                </button>
                            </h3>
                            <div id="faq-item-7" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Try the Open Video in New Tab button under the video. If it still does not work, tell us through the Contact page and mention the course name.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-8"
                                    aria-expanded="false"
                                >
                                    How do I change my name, email or password?
                                </button>
                            </h3>
                            <div id="faq-item-8" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Open Settings from the menu. Changes are confirmed with your current password.
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2 class="lh-section-title mt-4">For Teachers</h2>
                    <div class="accordion lh-faq mb-4" id="faq8">

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-9"
                                    aria-expanded="false"
                                >
                                    How do I become a teacher?
                                </button>
                            </h3>
                            <div id="faq-item-9" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Register and choose Teacher as your role. Depending on the site settings, an admin may need to approve your account before you can log in.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-10"
                                    aria-expanded="false"
                                >
                                    How do I create a course?
                                </button>
                            </h3>
                            <div id="faq-item-10" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    After login open My Courses, press Create Course, add a title, category, description and an optional thumbnail. Then add lessons and quizzes to it.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-11"
                                    aria-expanded="false"
                                >
                                    Which video links can I use in a lesson?
                                </button>
                            </h3>
                            <div id="faq-item-11" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Paste a normal YouTube, Vimeo or Google Drive link, or a direct .mp4 / .webm file link. LearnHub turns it into a player automatically.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-12"
                                    aria-expanded="false"
                                >
                                    Can I see who joined my course?
                                </button>
                            </h3>
                            <div id="faq-item-12" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Yes. The Students page lists every student enrolled in your courses, and Analytics shows how your courses are performing.
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2 class="lh-section-title mt-4">Account &amp; Privacy</h2>
                    <div class="accordion lh-faq mb-4" id="faq12">

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-13"
                                    aria-expanded="false"
                                >
                                    I forgot my password. What now?
                                </button>
                            </h3>
                            <div id="faq-item-13" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    There is no automatic reset yet. Send us a message through the Contact page from the email address of your account and an admin will help you.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-14"
                                    aria-expanded="false"
                                >
                                    Can I delete my account?
                                </button>
                            </h3>
                            <div id="faq-item-14" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Yes. Open Settings and use Delete Account. Your account is moved to trash and an admin can restore it within 30 days.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-item-15"
                                    aria-expanded="false"
                                >
                                    What do you do with my data?
                                </button>
                            </h3>
                            <div id="faq-item-15" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Please read the Privacy Policy. In short: we keep only what the platform needs, and we never sell your data.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lh-info-card h-auto text-center mt-5">

                        <h3>Still have a question?</h3>

                        <p class="text-muted">
                            We are happy to help.
                        </p>

                        <a href="contact.php" class="btn btn-primary">
                            Contact Us
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
