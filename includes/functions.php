<?php

function redirect($url)
{
    header("Location: " . $url);
    exit;
}

function clean($data)
{
    return htmlspecialchars(
        trim($data),
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Records one view for a course.
 *
 * The same user is counted only once per hour so that
 * page refresh does not inflate the numbers.
 */
function record_course_view($pdo, $course_id, $user_id = null)
{
    $course_id = intval($course_id);

    if ($course_id <= 0) {
        return;
    }

    try {

        if ($user_id) {

            // Skip if this user already viewed within last hour
            $stmt = $pdo->prepare(
                "SELECT id
                 FROM course_views
                 WHERE course_id = ?
                 AND user_id = ?
                 AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                 LIMIT 1"
            );

            $stmt->execute([$course_id, $user_id]);

            if ($stmt->fetch()) {
                return;
            }
        }

        $stmt = $pdo->prepare(
            "INSERT INTO course_views
             (course_id, user_id)
             VALUES (?, ?)"
        );

        $stmt->execute([
            $course_id,
            $user_id ?: null
        ]);

    } catch (PDOException $e) {

        // View tracking must never break the page
        return;
    }
}


/**
 * Turns a teacher's video link into something a student's
 * browser can actually play.
 *
 * WHY: a normal YouTube link (youtube.com/watch?v=...) can NOT
 * be placed inside an <iframe> - YouTube refuses to connect.
 * It must be converted to the /embed/ address first.
 *
 * Returns an array:
 *
 *   ["type" => "iframe", "src" => "..."]   YouTube / Vimeo / Drive
 *   ["type" => "video",  "src" => "..."]   direct .mp4 / .webm file
 *   ["type" => "link",   "src" => "..."]   any other http(s) link
 *   null                                   empty / unsafe link
 */
function get_video_embed($url)
{
    $result = build_video_embed($url);

    if ($result !== null) {

        // The link the teacher pasted (for the "Open in new tab" button)
        $original = trim((string) $url);

        if (!preg_match('#^[a-z][a-z0-9+.\-]*:#i', $original)) {
            $original = "https://" . $original;
        }

        $result["original"] = $original;
    }

    return $result;
}


function build_video_embed($url)
{
    $url = trim((string) $url);

    if ($url === "") {
        return null;
    }

    // Add https:// when the teacher typed "www.youtube.com/..."
    if (!preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url)) {
        $url = "https://" . $url;
    }

    $parts = parse_url($url);

    // Only normal web links are allowed (blocks javascript:, data: ...)
    if (
        !$parts
        || empty($parts["host"])
        || !in_array(
            strtolower($parts["scheme"] ?? ""),
            ["http", "https"],
            true
        )
    ) {
        return null;
    }

    $host = strtolower($parts["host"]);
    $host = preg_replace('/^(www\.|m\.)/', '', $host);
    $path = $parts["path"] ?? "";

    parse_str($parts["query"] ?? "", $query);


    /* ---------- YouTube ---------- */

    $video_id = "";

    if ($host === "youtu.be") {

        $video_id = trim($path, "/");

    } elseif (
        $host === "youtube.com"
        || $host === "youtube-nocookie.com"
    ) {

        if (!empty($query["v"])) {

            $video_id = $query["v"];

        } elseif (
            preg_match(
                '#^/(embed|shorts|live|v)/([^/?]+)#',
                $path,
                $m
            )
        ) {

            $video_id = $m[2];
        }

        // Playlist link without a single video
        if (
            $video_id === ""
            && !empty($query["list"])
            && preg_match('/^[A-Za-z0-9_-]+$/', $query["list"])
        ) {

            return [
                "type" => "iframe",
                "src"  => "https://www.youtube.com/embed/videoseries?list="
                          . $query["list"]
            ];
        }
    }

    if ($video_id !== "") {

        if (!preg_match('/^[A-Za-z0-9_-]{6,20}$/', $video_id)) {
            return ["type" => "link", "src" => $url];
        }

        $src = "https://www.youtube.com/embed/" . $video_id;

        // Keep "start at" time from links like ...&t=90s
        $start = $query["t"] ?? ($query["start"] ?? "");

        if (preg_match('/^(\d+)s?$/', (string) $start, $m)) {
            $src .= "?start=" . $m[1];
        }

        return ["type" => "iframe", "src" => $src];
    }


    /* ---------- Vimeo ---------- */

    if (
        $host === "vimeo.com"
        && preg_match('#^/(?:channels/[^/]+/)?(\d+)#', $path, $m)
    ) {

        return [
            "type" => "iframe",
            "src"  => "https://player.vimeo.com/video/" . $m[1]
        ];
    }

    if (
        $host === "player.vimeo.com"
        && preg_match('#^/video/(\d+)#', $path)
    ) {

        return ["type" => "iframe", "src" => $url];
    }


    /* ---------- Google Drive ---------- */

    if (
        $host === "drive.google.com"
        && preg_match('#^/file/d/([A-Za-z0-9_-]+)#', $path, $m)
    ) {

        return [
            "type" => "iframe",
            "src"  => "https://drive.google.com/file/d/" . $m[1] . "/preview"
        ];
    }


    /* ---------- Direct video file ---------- */

    if (preg_match('/\.(mp4|webm|ogg|ogv|mov|m4v)$/i', $path)) {

        return ["type" => "video", "src" => $url];
    }


    /* ---------- Anything else: just give a link ---------- */

    return ["type" => "link", "src" => $url];
}


/* =====================================================
   Site settings (admin -> Settings page)
===================================================== */

/**
 * Default values. A setting that was never saved uses these,
 * so the website works before the admin opens Settings.
 */
function setting_defaults()
{
    return [
        "allow_registration"       => "1",  // 1 = new users may register
        "teacher_needs_approval"   => "1",  // 1 = teacher must be approved
        "contact_email"            => "",
        "contact_phone"            => "",
        "contact_address"          => "",
    ];
}


/** Reads one setting (all settings are loaded with ONE query). */
function get_setting($pdo, $key, $default = null)
{
    static $cache = null;

    if ($cache === null) {

        $cache = setting_defaults();

        try {

            $rows = $pdo->query(
                "SELECT setting_key, setting_value
                 FROM site_settings"
            )->fetchAll();

            foreach ($rows as $row) {
                $cache[$row["setting_key"]] = (string) $row["setting_value"];
            }

        } catch (Throwable $e) {
            // Table missing -> defaults are used
        }
    }

    return $cache[$key] ?? $default;
}


/** Saves one setting. Returns true on success. */
function set_setting($pdo, $key, $value)
{
    try {

        $stmt = $pdo->prepare(
            "INSERT INTO site_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );

        return $stmt->execute([$key, (string) $value]);

    } catch (Throwable $e) {

        error_log("LearnHub set_setting: " . $e->getMessage());

        return false;
    }
}


/* =====================================================
   Secure image upload (course thumbnails)
===================================================== */

/**
 * Validates and stores an uploaded image safely.
 *
 * - Checks the upload actually came from a real form submission.
 * - Enforces a max file size.
 * - Verifies the file is a genuine image by its content (not just
 *   its extension or the browser-supplied MIME type, both of which
 *   an attacker can fake) using getimagesize() + finfo.
 * - Always saves it under a new, random file name so nothing the
 *   user typed ever becomes part of a file path.
 *
 * Returns the new file name on success, or null (with a message
 * pushed onto $errors) on failure. Writes nothing when no file was
 * submitted.
 */
function handle_image_upload($file, $destinationDir, array &$errors)
{
    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $errors[] = "Failed to upload image.";
        return null;
    }

    if (!is_uploaded_file($file["tmp_name"])) {
        $errors[] = "Invalid upload.";
        return null;
    }

    $maxBytes = 5 * 1024 * 1024; // 5 MB

    if ($file["size"] > $maxBytes) {
        $errors[] = "Image must be smaller than 5MB.";
        return null;
    }

    // Only these image types are allowed. Extension is derived
    // from the DETECTED type below, never from the uploaded name.
    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp",
    ];

    // getimagesize() decodes the actual image header - a renamed
    // .php file or other non-image content will fail this check
    // even if it was uploaded with a ".jpg" name.
    $imageInfo = @getimagesize($file["tmp_name"]);

    if ($imageInfo === false || empty($imageInfo["mime"])) {
        $errors[] = "The uploaded file is not a valid image.";
        return null;
    }

    $mime = $imageInfo["mime"];

    // Double-check with finfo against the file content too.
    if (function_exists("finfo_open")) {

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected = finfo_file($finfo, $file["tmp_name"]);
        finfo_close($finfo);

        if ($detected !== $mime) {
            $mime = $detected;
        }
    }

    if (!isset($allowedTypes[$mime])) {
        $errors[] = "Only JPG, PNG and WEBP images are allowed.";
        return null;
    }

    $extension = $allowedTypes[$mime];

    // Cryptographically random name - never derived from user input.
    $newFileName = bin2hex(random_bytes(16)) . "." . $extension;

    $uploadPath = rtrim($destinationDir, "/") . "/" . $newFileName;

    if (!move_uploaded_file($file["tmp_name"], $uploadPath)) {
        $errors[] = "Failed to save the uploaded image.";
        return null;
    }

    chmod($uploadPath, 0644);

    return $newFileName;
}


/* =====================================================
   CSRF protection for forms
===================================================== */

/** Token to put inside every POST form. */
function csrf_token()
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(16));
    }

    return $_SESSION["csrf_token"];
}


/** <input> tag with the token. */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8")
        . '">';
}


/** true when the submitted token is correct. */
function csrf_valid()
{
    return !empty($_SESSION["csrf_token"])
        && hash_equals(
            $_SESSION["csrf_token"],
            (string) ($_POST["csrf_token"] ?? "")
        );
}


/* =====================================================
   Pagination (Bootstrap look)
===================================================== */

/**
 * Prints  « 1 2 3 »  links.
 *
 * $params = the current filters, for example ["q" => "php"].
 * The "page" number is added automatically.
 */
function render_pagination($page, $total_pages, $params = [])
{
    if ($total_pages <= 1) {
        return;
    }

    $link = function ($number) use ($params) {

        $params["page"] = $number;

        return "?" . htmlspecialchars(
            http_build_query($params),
            ENT_QUOTES,
            "UTF-8"
        );
    };

    // Show at most 7 numbers around the current page
    $start = max(1, $page - 3);
    $end = min($total_pages, $page + 3);

    echo '<nav aria-label="Pages"><ul class="pagination justify-content-center mb-0">';

    echo '<li class="page-item' . ($page <= 1 ? " disabled" : "") . '">'
        . '<a class="page-link" href="' . $link(max(1, $page - 1)) . '">&laquo;</a></li>';

    if ($start > 1) {
        echo '<li class="page-item"><a class="page-link" href="' . $link(1) . '">1</a></li>';
        if ($start > 2) {
            echo '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        echo '<li class="page-item' . ($i === $page ? " active" : "") . '">'
            . '<a class="page-link" href="' . $link($i) . '">' . $i . '</a></li>';
    }

    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            echo '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
        echo '<li class="page-item"><a class="page-link" href="' . $link($total_pages) . '">' . $total_pages . '</a></li>';
    }

    echo '<li class="page-item' . ($page >= $total_pages ? " disabled" : "") . '">'
        . '<a class="page-link" href="' . $link(min($total_pages, $page + 1)) . '">&raquo;</a></li>';

    echo '</ul></nav>';
}
