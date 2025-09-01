<!-- base layout -->
<?php
session_start();
$title = "NBC Leave Filing System";

// set the default content file to login.php
$content = isset($_GET['page']) ? __DIR__ . '/' . $_GET['page'] . '.php' : __DIR__ . '/login.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/bootstrap-icons/bootstrap-icons.css">
</head>

<body>
    <header>
        <h1 class="ms-5 fw-bold"><?php echo $title; ?></h1>
    </header>
    <main class="container my-5">
        <?php
        if (file_exists($content)) {
            include $content;
        } else {
            echo "<div class='text-center text-danger' style='margin-top:300px;'><h2>404 - Page Not Found</h2></div>";
        }
        ?>
    </main>
    <?php include 'config/footer.php'; ?>
</body>
<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>

</html>