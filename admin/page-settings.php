<?php
require_once 'auth-check.php';
require_once 'role-check.php';
requireAdmin();
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('PAGE_UPLOAD_PATH', __DIR__ . '/uploads/pages/');
define('PAGE_UPLOAD_URL', 'uploads/pages/');

/* Ensure directory exists */
if (!is_dir(PAGE_UPLOAD_PATH)) {
    mkdir(PAGE_UPLOAD_PATH, 0755, true);
}

/* Flash messages */
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$activeTab = $_GET['tab'] ?? 'about-us';

/* =========================
   SAVE PAGE DATA
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $slug    = $_POST['slug'] ?? '';
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // IMPORTANT: checkbox fallback fixed
    $active = (isset($_POST['is_active']) && $_POST['is_active'] == '1') ? 1 : 0;

    if (!$slug || !$title || !$content) {
        $_SESSION['error'] = "Title and Content are required.";
        header("Location: page-settings.php?tab=" . urlencode($slug ?: $activeTab));
        exit;
    }

    // Load existing banner
    $stmt = $connection->prepare("SELECT banner FROM pages WHERE slug=? LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $oldPage = $stmt->get_result()->fetch_assoc();

    $oldBanner = $oldPage['banner'] ?? null;
    $newBanner = $oldBanner;

    /* HANDLE IMAGE REPLACEMENT */
    if (!empty($_FILES['banner']['name']) && $_FILES['banner']['error'] === 0) {

        $ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (!in_array($ext, $allowed, true)) {
            $_SESSION['error'] = "Invalid image type. Please upload JPG, PNG, or WEBP.";
            header("Location: page-settings.php?tab=" . urlencode($slug));
            exit;
        }

        // Delete old image
        if (!empty($oldBanner) && file_exists(PAGE_UPLOAD_PATH . $oldBanner)) {
            unlink(PAGE_UPLOAD_PATH . $oldBanner);
        }

        // Upload new image
        $newBanner = $slug . '_' . time() . '.' . $ext;

        $ok = move_uploaded_file(
            $_FILES['banner']['tmp_name'],
            PAGE_UPLOAD_PATH . $newBanner
        );

        if (!$ok) {
            $_SESSION['error'] = "Banner upload failed. Please try again.";
            header("Location: page-settings.php?tab=" . urlencode($slug));
            exit;
        }
    }

    // Update page
    $stmt = $connection->prepare("
        UPDATE pages 
        SET title=?, content=?, banner=?, is_active=?
        WHERE slug=?
        LIMIT 1
    ");
    $stmt->bind_param("sssis", $title, $content, $newBanner, $active, $slug);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Page settings updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update page settings.";
    }

    // POST-REDIRECT-GET (Fix success msg + prevent resubmit)
    header("Location: page-settings.php?tab=" . urlencode($slug));
    exit;
}

/* =========================
   LOAD CURRENT PAGE
========================= */
$stmt = $connection->prepare("SELECT * FROM pages WHERE slug=? LIMIT 1");
$stmt->bind_param("s", $activeTab);
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();

if (!$page) {
    die("Page not found for slug: " . htmlspecialchars($activeTab));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Page Settings</title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/admin-dashboard.css">
<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.editor-toolbar {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 6px;
    border-radius: 6px;
}
.editor-toolbar button {
    border: 1px solid #ccc;
    background: #fff;
    padding: 4px 8px;
    cursor: pointer;
    margin-right: 4px;
    border-radius: 4px;
    font-size: 14px;
}
.editor-toolbar button:hover {
    background: #e9ecef;
}
#editorBox {
    border: 1px solid #ccc;
    min-height: 280px;
    padding: 12px;
    border-radius: 6px;
    background: #fff;
    overflow-y: auto;
}
</style>
</head>

<body>

<?php include 'layout/sidebar.php'; ?>
<div class="app-content">
<?php include 'layout/header.php'; ?>

<div class="container-fluid p-4">

<h4 class="fw-semibold mb-3">Page Settings</h4>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- TABS -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link <?= $activeTab=='about-us'?'active':'' ?>" href="?tab=about-us">About Us</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab=='faq'?'active':'' ?>" href="?tab=faq">FAQ</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab=='contact-us'?'active':'' ?>" href="?tab=contact-us">Contact Us</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab=='terms'?'active':'' ?>" href="?tab=terms">Terms & Conditions</a></li>
</ul>

<form method="post" enctype="multipart/form-data" action="page-settings.php?tab=<?= htmlspecialchars($activeTab) ?>">
<input type="hidden" name="slug" value="<?= htmlspecialchars($page['slug']) ?>">

<div class="card p-4">

<!-- PAGE TITLE -->
<label class="fw-semibold">Page Title *</label>
<input type="text" name="title" class="form-control mb-3"
       value="<?= htmlspecialchars($page['title'] ?? '') ?>" required>

<!-- CONTENT -->
<div class="mb-3">
    <label class="form-label fw-semibold">Content *</label>

    <div class="editor-toolbar mb-2">
        <button type="button" onclick="execCmd('bold')"><b>B</b></button>
        <button type="button" onclick="execCmd('italic')"><i>I</i></button>
        <button type="button" onclick="execCmd('underline')"><u>U</u></button>

        <button type="button" onclick="execCmd('insertUnorderedList')">• List</button>
        <button type="button" onclick="execCmd('insertOrderedList')">1. List</button>

        <button type="button" onclick="createLink()">🔗 Link</button>
        <button type="button" onclick="execCmd('unlink')">Unlink</button>

        <button type="button" onclick="execCmd('justifyLeft')">Left</button>
        <button type="button" onclick="execCmd('justifyCenter')">Center</button>
        <button type="button" onclick="execCmd('justifyRight')">Right</button>
    </div>

    <div id="editorBox" contenteditable="true"><?= $page['content'] ?? '' ?></div>

    <textarea name="content" id="contentInput" hidden></textarea>
</div>

<!-- EXISTING BANNER -->
<?php if (!empty($page['banner'])): ?>
<label class="fw-semibold">Existing Banner</label><br>
<img src="<?= PAGE_UPLOAD_URL . htmlspecialchars($page['banner']) ?>?v=<?= time() ?>"
     class="img-thumbnail mb-3"
     style="max-width:100%; max-height:260px; object-fit:cover;">
<?php endif; ?>

<!-- UPLOAD NEW BANNER -->
<label class="fw-semibold">Upload New Banner</label>
<input type="file" name="banner" class="form-control mb-2" accept=".jpg,.jpeg,.png,.webp">
<small class="text-muted">Only JPG / PNG / WEBP</small>

<hr>

<!-- ACTIVE (FIXED) -->
<div class="form-check form-switch mt-3">
    <!-- hidden fallback -->
    <input type="hidden" name="is_active" value="0">
    <input class="form-check-input" type="checkbox" name="is_active" value="1"
        <?= !empty($page['is_active']) ? 'checked' : '' ?>>
    <label class="form-check-label">Active Page</label>
</div>

<button class="btn btn-primary mt-4">Save Page</button>

</div>
</form>

</div>

<?php include 'layout/footer.php'; ?>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>

<script>
function execCmd(command, value = null) {
    document.execCommand(command, false, value);
    document.getElementById('editorBox').focus();
}

function createLink() {
    const url = prompt("Enter URL:", "https://");
    if (url) execCmd('createLink', url);
}

/* Sync editor content before submit */
document.querySelector("form").addEventListener("submit", function () {
    document.getElementById("contentInput").value =
        document.getElementById("editorBox").innerHTML.trim();
});
</script>

</body>
</html>
