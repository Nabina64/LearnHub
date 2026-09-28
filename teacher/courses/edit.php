<?php

require_once "../../auth/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

// Only teacher can access
require_role("teacher", "../../student/dashboard.php");

$pageTitle = "Edit Course - LearnHub";

$teacherId = $_SESSION["user_id"];

$courseId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;


// Get course
$stmt = $pdo->prepare(
    "SELECT *
     FROM courses
     WHERE id = ? AND teacher_id = ?
     AND deleted_at IS NULL"
);

$stmt->execute([
    $courseId,
    $teacherId
]);

$course = $stmt->fetch();


// Course not found or doesn't belong to teacher
if (!$course) {
    die("Course not found or you do not have permission to edit it.");
}


$errors = [];


// Update course
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


    // Keep old thumbnail
    $thumbnail = $course["thumbnail"];


    // New thumbnail (validated, size-limited, saved under a random name)

    $newThumbnail = handle_image_upload(
        $_FILES["thumbnail"] ?? null,
        __DIR__ . "/../../uploads/courses",
        $errors
    );

    if ($newThumbnail !== null) {

        // Delete old thumbnail
        if (
            !empty($course["thumbnail"]) &&
            file_exists(
                __DIR__ . "/../../uploads/courses/" .
                $course["thumbnail"]
            )
        ) {

            unlink(
                __DIR__ . "/../../uploads/courses/" .
                $course["thumbnail"]
            );
        }

        $thumbnail = $newThumbnail;
    }


    // Update database

    if (empty($errors)) {

        $stmt = $pdo->prepare(
            "UPDATE courses
             SET title = ?,
                 description = ?,
                 category = ?,
                 thumbnail = ?
             WHERE id = ? AND teacher_id = ?"
        );

        $stmt->execute([
            $title,
            $description,
            $category,
            $thumbnail,
            $courseId,
            $teacherId
        ]);


        $_SESSION["success"] =
            "Course updated successfully!";

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
                        Edit Course
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
                                value="<?php echo htmlspecialchars($title ?? $course["title"]); ?>"
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
                                value="<?php echo htmlspecialchars($category ?? $course["category"]); ?>"
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
                                required
                            ><?php echo htmlspecialchars($description ?? $course["description"]); ?></textarea>

                        </div>


                        <!-- Current Thumbnail -->

                        <?php if (!empty($course["thumbnail"])): ?>

                            <div class="mb-3">

                                <label class="form-label fw-semibold">
                                    Current Thumbnail
                                </label>

                                <br>

                                <img
                                    src="../../uploads/courses/<?php echo htmlspecialchars($course["thumbnail"]); ?>"
                                    alt="Current Thumbnail"
                                    style="width: 180px; height: 110px; object-fit: cover;"
                                    class="rounded"
                                >

                            </div>

                        <?php endif; ?>


                        <!-- New Thumbnail -->

                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Change Thumbnail
                            </label>

                            <input
                                type="file"
                                name="thumbnail"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                            <small class="text-muted">
                                Leave empty to keep the current image.
                            </small>

                        </div>


                        <!-- Buttons -->

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Update Course
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