<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

// Only teacher can access
require_role("teacher", "../../student/dashboard.php");

$pageTitle = "Create Course - LearnHub";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_valid()) {
        $errors[] = "Security check failed. Please try again.";
    }

    $title = clean($_POST["title"] ?? "");
    $description = clean($_POST["description"] ?? "");
    $category = clean($_POST["category"] ?? "");


    // Validation

    if ($title === "") {
        $errors[] = "Course title is required.";
    }

    if ($description === "") {
        $errors[] = "Course description is required.";
    }

    if ($category === "") {
        $errors[] = "Course category is required.";
    }


    // Thumbnail (validated, size-limited, saved under a random name)

    $thumbnail = handle_image_upload(
        $_FILES["thumbnail"] ?? null,
        __DIR__ . "/../../uploads/courses",
        $errors
    );


    // Insert course

    if (empty($errors)) {

        $stmt = $pdo->prepare(
            "INSERT INTO courses
            (teacher_id, title, description, category, thumbnail)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $_SESSION["user_id"],
            $title,
            $description,
            $category,
            $thumbnail
        ]);

        $_SESSION["success"] =
            "Course created successfully!";

        redirect("index.php");
    }
}

require_once "../../includes/header.php";
require_once "../../includes/navbar.php";

?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-4 p-md-5">

                    <h2 class="fw-bold mb-4">
                        Create New Course
                    </h2>


                    <!-- Errors -->

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


                    <form
                        method="POST"
                        enctype="multipart/form-data"
                    >

                        <?php echo csrf_field(); ?>


                        <!-- Title -->

                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Course Title
                            </label>

                            <input
                                type="text"
                                name="title"
                                class="form-control"
                                placeholder="e.g. Web Development Basics"
                                value="<?php echo htmlspecialchars($title ?? ""); ?>"
                                required
                            >

                        </div>


                        <!-- Category -->

                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Category
                            </label>

                            <input
                                type="text"
                                name="category"
                                class="form-control"
                                placeholder="e.g. Programming"
                                value="<?php echo htmlspecialchars($category ?? ""); ?>"
                                required
                            >

                        </div>


                        <!-- Description -->

                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Course Description
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="6"
                                placeholder="Describe your course..."
                                required
                            ><?php echo htmlspecialchars($description ?? ""); ?></textarea>

                        </div>


                        <!-- Thumbnail -->

                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Course Thumbnail
                            </label>

                            <input
                                type="file"
                                name="thumbnail"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                            <small class="text-muted">
                                Optional. JPG, JPEG, PNG or WEBP.
                            </small>

                        </div>


                        <!-- Buttons -->

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Create Course
                            </button>

                            <a
                                href="index.php"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<?php

require_once "../../includes/footer.php";

?>