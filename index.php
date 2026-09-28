<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/functions.php";


/*
 * Home page
 *
 * Everyone can open this page, logged in or not - clicking
 * "Home" in the navbar should always land here.
 * The buttons below just change depending on login state.
 */

$is_logged_in = isset($_SESSION["user_id"]);

$dashboard_url = "auth/login.php";

if ($is_logged_in) {

    if ($_SESSION["user_role"] === "admin") {

        $dashboard_url = "admin/dashboard.php";

    } elseif ($_SESSION["user_role"] === "teacher") {

        $dashboard_url = "teacher/dashboard.php";

    } else {

        $dashboard_url = "student/dashboard.php";

    }
}


$pageTitle = "LearnHub - Online Learning Platform";

require_once "includes/header.php";
require_once "includes/navbar.php";

?>

<!-- ================= HERO ================= -->

<section class="home-hero">

    <div class="container">

        <div class="row align-items-center">

            <!-- LEFT -->

            <div class="col-lg-6">

                <div class="hero-label">
                    <span></span>
                    ONLINE LEARNING PLATFORM
                </div>

                <h1 class="hero-title">
                    Learn today.<br>
                    <span>Grow tomorrow.</span>
                </h1>

                <p class="hero-description">
                    Build valuable skills through structured courses,
                    practical lessons and interactive quizzes.
                    Start your learning journey with LearnHub.
                </p>

                <div class="hero-buttons">

                    <?php if ($is_logged_in): ?>

                        <a
                            href="<?php echo $dashboard_url; ?>"
                            class="btn btn-primary hero-btn"
                        >
                            Go to Dashboard
                            <span>→</span>
                        </a>

                    <?php else: ?>

                        <a
                            href="auth/login.php"
                            class="btn btn-primary hero-btn"
                        >
                            Login
                            <span>→</span>
                        </a>

                        <a
                            href="auth/register.php"
                            class="btn hero-outline-btn"
                        >
                            Create Account
                        </a>

                    <?php endif; ?>

                </div>

                <div class="hero-trust">

                    <div class="trust-item">
                        <strong>Learn</strong>
                        <small>At your own pace</small>
                    </div>

                    <div class="trust-line"></div>

                    <div class="trust-item">
                        <strong>Practice</strong>
                        <small>With interactive quizzes</small>
                    </div>

                    <div class="trust-line"></div>

                    <div class="trust-item">
                        <strong>Grow</strong>
                        <small>With new skills</small>
                    </div>

                </div>

            </div>


            <!-- RIGHT -->

            <div class="col-lg-6">

                <div class="hero-visual">

                    <div class="floating-card floating-top">

                        <div class="floating-icon">
                            ✓
                        </div>

                        <div>
                            <strong>Learning Progress</strong>
                            <small>Keep going!</small>
                        </div>

                    </div>


                    <div class="learning-dashboard">

                        <div class="dashboard-top">

                            <div>
                                <small>WELCOME TO</small>

                                <h3>
                                    LearnHub
                                </h3>
                            </div>

                            <div class="dashboard-circle">
                                LH
                            </div>

                        </div>


                        <div class="dashboard-main">

                            <p class="small-title">
                                YOUR LEARNING JOURNEY
                            </p>

                            <h4>
                                Learn something new today.
                            </h4>

                            <div class="progress-info">

                                <span>
                                    Overall progress
                                </span>

                                <strong>
                                    75%
                                </strong>

                            </div>

                            <div class="custom-progress">

                                <div
                                    class="custom-progress-bar"
                                    style="width: 75%;"
                                ></div>

                            </div>

                        </div>


                        <div class="dashboard-cards">

                            <div class="dashboard-card">

                                <div class="card-icon">
                                    C
                                </div>

                                <div>
                                    <strong>
                                        Courses
                                    </strong>

                                    <small>
                                        Explore & learn
                                    </small>
                                </div>

                            </div>


                            <div class="dashboard-card">

                                <div class="card-icon">
                                    Q
                                </div>

                                <div>
                                    <strong>
                                        Quizzes
                                    </strong>

                                    <small>
                                        Test your skills
                                    </small>
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="floating-card floating-bottom">

                        <div class="success-icon">
                            ✓
                        </div>

                        <div>
                            <strong>Keep Learning</strong>
                            <small>Small steps matter.</small>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= FEATURES ================= -->

<section class="features-section">

    <div class="container">

        <div class="section-heading">

            <span>
                WHY LEARNHUB
            </span>

            <h2>
                Everything you need to learn better
            </h2>

            <p>
                A simple learning experience designed to help
                you learn, practice and improve.
            </p>

        </div>


        <div class="row g-4">

            <div class="col-md-4">

                <div class="modern-feature">

                    <div class="feature-number">
                        01
                    </div>

                    <div class="feature-symbol">
                        ↗
                    </div>

                    <h4>
                        Structured Courses
                    </h4>

                    <p>
                        Learn through organized courses created
                        by teachers with clear learning materials.
                    </p>

                </div>

            </div>


            <div class="col-md-4">

                <div class="modern-feature">

                    <div class="feature-number">
                        02
                    </div>

                    <div class="feature-symbol">
                        ◉
                    </div>

                    <h4>
                        Learn at Your Pace
                    </h4>

                    <p>
                        Access lessons whenever you want and
                        continue learning according to your schedule.
                    </p>

                </div>

            </div>


            <div class="col-md-4">

                <div class="modern-feature">

                    <div class="feature-number">
                        03
                    </div>

                    <div class="feature-symbol">
                        ✓
                    </div>

                    <h4>
                        Test Your Knowledge
                    </h4>

                    <p>
                        Take quizzes after learning and instantly
                        see how well you understand the topic.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= HOW IT WORKS ================= -->

<section class="process-section">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-lg-5">

                <div class="section-heading text-start">

                    <span>
                        HOW IT WORKS
                    </span>

                    <h2>
                        Start learning in three simple steps.
                    </h2>

                    <p>
                        No complicated process. Create your account,
                        choose a course and start learning.
                    </p>

                </div>

            </div>


            <div class="col-lg-7">

                <div class="process-list">

                    <div class="process-item">

                        <div class="process-number">
                            01
                        </div>

                        <div>
                            <h5>
                                Create your account
                            </h5>

                            <p>
                                Register on LearnHub and get access
                                to the learning platform.
                            </p>
                        </div>

                    </div>


                    <div class="process-item">

                        <div class="process-number">
                            02
                        </div>

                        <div>
                            <h5>
                                Choose a course
                            </h5>

                            <p>
                                Browse available courses and enroll
                                in the subject you want to learn.
                            </p>
                        </div>

                    </div>


                    <div class="process-item">

                        <div class="process-number">
                            03
                        </div>

                        <div>
                            <h5>
                                Learn and practice
                            </h5>

                            <p>
                                Complete lessons and take quizzes
                                to check your understanding.
                            </p>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= CTA ================= -->

<section class="cta-wrapper">

    <div class="container">

        <div class="cta-box">

            <div>

                <span>
                    START YOUR JOURNEY
                </span>

                <h2>
                    <?php echo $is_logged_in
                        ? "Ready to keep learning?"
                        : "Ready to learn something new?"; ?>
                </h2>

                <p>

                    <?php if ($is_logged_in): ?>

                        Jump back into your dashboard and pick up
                        where you left off.

                    <?php else: ?>

                        Create your LearnHub account and begin
                        exploring courses today.

                    <?php endif; ?>

                </p>

            </div>


            <a
                href="<?php echo $is_logged_in ? $dashboard_url : 'auth/register.php'; ?>"
                class="btn cta-button"
            >
                <?php echo $is_logged_in ? "Go to Dashboard" : "Get Started"; ?>
                <span>→</span>
            </a>

        </div>

    </div>

</section>


<?php

require_once "includes/footer.php";

?>