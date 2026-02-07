<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/settings.php';

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    die('Page not found');
}

/* ================= FETCH PAGE ================= */
$stmt = $connection->prepare("
    SELECT title, content, banner
    FROM pages
    WHERE slug = ? AND is_active = 1
    LIMIT 1
");
$stmt->bind_param("s", $slug);
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();

if (!$page) {
    die('Page not available');
}

$bannerImage = $page['banner']
    ? "../admin/uploads/pages/" . $page['banner']
    : "assets/img/default-banner.jpg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($page['title']) ?> | <?= setting('site_name','Pino Shoes') ?></title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/site.css">
<link rel="stylesheet"
 href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

<style>
/* PAGE HERO */
.page-hero {
    min-height: 260px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.page-hero::after {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.55);
}

.page-hero-content {
    position: relative;
    z-index: 2;
}

/* CONTENT */
.page-content {
    font-size: 1rem;
    line-height: 1.75;
}

.page-content h2,
.page-content h3,
.page-content h4 {
    margin-top: 1.5rem;
    font-weight: 600;
}

.page-content ul {
    padding-left: 1.2rem;
}

.page-content li {
    margin-bottom: .5rem;
}

/* FAQ accordion spacing */
.accordion-button {
    font-weight: 500;
}
</style>
</head>

<body>

<?php include 'layout/header.php'; ?>

<!-- ================= PAGE HERO ================= -->
<section class="page-hero d-flex align-items-center"
         style="background-image:url('<?= htmlspecialchars($bannerImage) ?>')">
    <div class="container text-center page-hero-content text-white">
        <h1 class="fw-bold"><?= htmlspecialchars($page['title']) ?></h1>
    </div>
</section>

<!-- ================= CONTENT ================= -->
<section class="container py-5">
<div class="row justify-content-center">
<div class="col-lg-9">

<div class="page-content">
    <?= $page['content']; /* ADMIN TRUSTED HTML */ ?>
</div>

</div>
</div>
</section>

<?php include 'layout/footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
