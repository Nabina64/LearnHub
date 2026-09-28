<?php

require_once __DIR__ . "/session.php";

$is_logged_in = isset($_SESSION["user_id"]);
$user_role = $_SESSION["user_role"] ?? "";

?>


<nav class="navbar navbar-expand-lg navbar-light learnhub-navbar">

    <div class="container">


        <!-- Logo / Brand -->

        <a
            class="navbar-brand fw-bold"
            href="/online-learning-platform/"
        >
            LearnHub
        </a>


        <!-- Mobile Menu Button -->

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <!-- Navigation -->

        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">


                <!-- Home -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="/online-learning-platform/"
                    >
                        Home
                    </a>

                </li>


                <?php if (!$is_logged_in): ?>

                    <!-- Public Courses -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/courses.php"
                        >
                            Courses
                        </a>
                    </li>

                    <!-- About -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/about.php"
                        >
                            About
                        </a>
                    </li>

                    <!-- FAQ -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/faq.php"
                        >
                            FAQ
                        </a>
                    </li>

                    <!-- Contact -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/contact.php"
                        >
                            Contact
                        </a>
                    </li>

                    <!-- Login -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/auth/login.php"
                        >
                            Login
                        </a>
                    </li>

                    <!-- Register -->
                    <li class="nav-item">
                        <a
                            class="btn btn-primary ms-lg-2"
                            href="/online-learning-platform/auth/register.php"
                        >
                            Register
                        </a>
                    </li>

                <?php elseif ($user_role === "student"): ?>

                    <!-- Student Dashboard -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/student/dashboard.php"
                        >
                            Dashboard
                        </a>
                    </li>

                    <!-- My Courses -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/student/my-courses.php"
                        >
                            My Courses
                        </a>
                    </li>

                    <!-- Explore Courses -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/student/courses.php"
                        >
                            Explore Courses
                        </a>
                    </li>

                    <!-- Quiz Results -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/student/quiz-results.php"
                        >
                            Quiz Results
                        </a>
                    </li>

                    <!-- My Messages -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/student/messages.php"
                        >
                            Messages
                        </a>
                    </li>

                    <!-- Student Profile -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/student/profile.php"
                        >
                            Profile
                        </a>
                    </li>

                    <!-- Settings -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/student/settings.php"
                        >
                            Settings
                        </a>
                    </li>

                    <!-- Logout -->
                    <li class="nav-item">
                        <a
                            class="btn btn-danger btn-sm ms-lg-2"
                            href="/online-learning-platform/auth/logout.php"
                        >
                            Logout
                        </a>
                    </li>

                <?php elseif ($user_role === "teacher"): ?>

                    <!-- Teacher Dashboard -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/teacher/dashboard.php"
                        >
                            Dashboard
                        </a>
                    </li>

                    <!-- My Courses -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/teacher/courses/index.php"
                        >
                            My Courses
                        </a>
                    </li>

                    <!-- Lessons -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/teacher/lessons/index.php"
                        >
                            Lessons
                        </a>
                    </li>

                    <!-- Quizzes -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/teacher/quizzes/index.php"
                        >
                            Quizzes
                        </a>
                    </li>

                    <!-- My Students -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/teacher/students.php"
                        >
                            Students
                        </a>
                    </li>

                    <!-- Analytics -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/teacher/analytics.php"
                        >
                            Analytics
                        </a>
                    </li>

                    <!-- My Messages -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/teacher/messages.php"
                        >
                            Messages
                        </a>
                    </li>

                    <!-- Teacher Profile -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/teacher/profile.php"
                        >
                            Profile
                        </a>
                    </li>

                    <!-- Settings -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/teacher/settings.php"
                        >
                            Settings
                        </a>
                    </li>

                    <!-- Logout -->
                    <li class="nav-item">
                        <a
                            class="btn btn-danger btn-sm ms-lg-2"
                            href="/online-learning-platform/auth/logout.php"
                        >
                            Logout
                        </a>
                    </li>

                <?php elseif ($user_role === "admin"): ?>

                    <!-- Admin Dashboard -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/admin/dashboard.php"
                        >
                            Dashboard
                        </a>
                    </li>

                    <!-- Users -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/admin/users.php"
                        >
                            Users
                        </a>
                    </li>

                    <!-- Teacher Requests -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/admin/teacher-requests.php"
                        >
                            Teacher Requests
                        </a>
                    </li>

                    <!-- Courses -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/admin/courses.php"
                        >
                            Courses
                        </a>
                    </li>

                    <!-- Enrollments -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/admin/enrollments.php"
                        >
                            Enrollments
                        </a>
                    </li>

                    <!-- Reports -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/admin/reports.php"
                        >
                            Reports
                        </a>
                    </li>

                    <!-- Messages -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/admin/messages.php"
                        >
                            Messages
                        </a>
                    </li>

                    <!-- Audit Logs -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/admin/audit-logs.php"
                        >
                            Audit Logs
                        </a>
                    </li>

                    <!-- Settings -->
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="/online-learning-platform/admin/settings.php"
                        >
                            Settings
                        </a>
                    </li>

                    <!-- Logout -->
                    <li class="nav-item">
                        <a
                            class="btn btn-danger btn-sm ms-lg-2"
                            href="/online-learning-platform/auth/logout.php"
                        >
                            Logout
                        </a>
                    </li>

                <?php endif; ?>


            </ul>

        </div>

    </div>

</nav>