<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";

$pageTitle = "About - LearnHub";
$extraCss = ["public.css"];

/* Live numbers for the About page */

$stats = [
    "courses"  => (int) $pdo->query("SELECT COUNT(*) FROM courses WHERE deleted_at IS NULL")->fetchColumn(),
    "lessons"  => (int) $pdo->query("SELECT COUNT(*) FROM lessons INNER JOIN courses ON lessons.course_id = courses.id WHERE lessons.deleted_at IS NULL AND courses.deleted_at IS NULL")->fetchColumn(),
    "students" => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND deleted_at IS NULL")->fetchColumn(),
    "teachers" => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'approved' AND deleted_at IS NULL")->fetchColumn(),
];

$is_logged_in = isset($_SESSION["user_id"]);

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
?>

<div class="lh-courses-page">

    <section class="lh-courses-hero lh-page-hero">

        <div class="container">

            <div class="lh-courses-hero-content">

                <span class="lh-courses-eyebrow">
                    ABOUT LEARNHUB
                </span>

                <h1>
                    Learning made simple. <span>For everyone.</span>
                </h1>

                <p>
                    A place where teachers share knowledge and students turn it into skills.
                </p>

            </div>

        </div>

    </section>


    <section class="lh-courses-main">

        <div class="container">

            <!-- Mission + numbers -->

            <div class="row g-4 align-items-center mb-5">

                <div class="col-lg-6">

                    <h2 class="lh-section-title mb-3">Our Mission</h2>

                    <p class="text-muted">
                        LearnHub is an online learning platform that connects
                        students with teachers. Teachers share what they know
                        through structured courses, and students learn at their
                        own pace, from any place, on any device.
                    </p>

                    <p class="text-muted mb-0">
                        We believe good learning is simple: clear lessons,
                        a short quiz to check your understanding, and a place
                        where you can see how you are improving.
                    </p>

                </div>

                <div class="col-lg-6">

                    <div class="row g-3">

                        <div class="col-6">
                            <div class="lh-stat-box">
                                <strong><?php echo $stats["courses"]; ?></strong>
                                <span>Courses</span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="lh-stat-box">
                                <strong><?php echo $stats["lessons"]; ?></strong>
                                <span>Lessons</span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="lh-stat-box">
                                <strong><?php echo $stats["students"]; ?></strong>
                                <span>Students</span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="lh-stat-box">
                                <strong><?php echo $stats["teachers"]; ?></strong>
                                <span>Teachers</span>
                            </div>
                        </div>

                    </div>

                </div>

            </div>


            <!-- What we offer -->

            <h2 class="lh-section-title">What You Get</h2>
            <p class="lh-section-sub">Everything you need to teach and to learn.</p>

            <div class="row g-4 mb-5">

                <div class="col-md-4">
                    <div class="lh-info-card">
                        <div class="lh-info-icon">📚</div>
                        <h4>Structured Courses</h4>
                        <p class="text-muted mb-0">
                            Every course is organised into lessons in a clear order,
                            so you always know what to study next.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="lh-info-card">
                        <div class="lh-info-icon">🎬</div>
                        <h4>Video &amp; Text Lessons</h4>
                        <p class="text-muted mb-0">
                            Watch video lessons (YouTube, Vimeo and more) and read
                            the written notes that come with them.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="lh-info-card">
                        <div class="lh-info-icon">📝</div>
                        <h4>Quizzes &amp; Results</h4>
                        <p class="text-muted mb-0">
                            Test yourself with multiple-choice quizzes, retake them
                            any time and keep track of your scores.
                        </p>
                    </div>
                </div>

            </div>


            <!-- How it works -->

            <h2 class="lh-section-title">How It Works</h2>
            <p class="lh-section-sub">Start in three simple steps.</p>

            <div class="row g-4 mb-5">

                <div class="col-md-4">
                    <div class="lh-info-card">
                        <div class="lh-step-number">1</div>
                        <h4>Create an account</h4>
                        <p class="text-muted mb-0">
                            Register as a student to learn, or as a teacher to
                            share your knowledge.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="lh-info-card">
                        <div class="lh-step-number">2</div>
                        <h4>Choose a course</h4>
                        <p class="text-muted mb-0">
                            Browse the course list and enroll in the ones that
                            match your goals.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="lh-info-card">
                        <div class="lh-step-number">3</div>
                        <h4>Learn and practise</h4>
                        <p class="text-muted mb-0">
                            Follow the lessons, take the quizzes and see your
                            progress on your dashboard.
                        </p>
                    </div>
                </div>

            </div>


            <!-- Call to action -->

            <div class="lh-info-card text-center">

                <h3>Ready to start?</h3>

                <p class="text-muted">
                    Explore the courses, or get in touch if you have a question.
                </p>

                <div class="d-flex justify-content-center gap-2 flex-wrap">

                    <a href="courses.php" class="btn btn-primary">
                        Browse Courses
                    </a>

                    <?php if (!$is_logged_in): ?>

                        <a href="auth/register.php" class="btn btn-outline-primary">
                            Create Free Account
                        </a>

                    <?php endif; ?>

                    <a href="contact.php" class="btn btn-outline-secondary">
                        Contact Us
                    </a>

                </div>

            </div>

        </div>

    </section>

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
