<?php
$pageTitle = $pageTitle ?? "LearnHub";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo htmlspecialchars($pageTitle); ?>
    </title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- LearnHub Custom CSS -->
    <link
        rel="stylesheet"
        href="/online-learning-platform/assets/css/style.css?v=6"
    >

    <link
        rel="stylesheet"
        href="/online-learning-platform/assets/css/theme-polish.css?v=1"
    >

    <?php foreach (($extraCss ?? []) as $extra_css): ?>
        <link
            rel="stylesheet"
            href="/online-learning-platform/assets/css/<?php echo htmlspecialchars($extra_css); ?>?v=2"
        >
    <?php endforeach; ?>

</head>

<body>