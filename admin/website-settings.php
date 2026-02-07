<?php
require_once 'auth-check.php';
require_once 'role-check.php';
requireAdmin();
require_once '../includes/db.php';

define('ADMIN_UPLOAD_PATH', __DIR__ . '/uploads/');
define('ADMIN_UPLOAD_URL', 'uploads/');

/* Ensure uploads directory exists */
if (!is_dir(ADMIN_UPLOAD_PATH)) {
    die('Admin uploads directory not found: ' . ADMIN_UPLOAD_PATH);
}


$success = '';
$errors  = [];

/* FIX CHECKBOX ISSUE */
for ($i = 1; $i <= 4; $i++) {
    if (!isset($_POST["service{$i}_enabled"])) {
        $_POST["service{$i}_enabled"] = '0';
    }
}

$socials = ['facebook','instagram','tiktok','youtube'];
foreach ($socials as $s) {
    if (!isset($_POST["social_{$s}_enabled"])) {
        $_POST["social_{$s}_enabled"] = '0';
    }
}

/* LOAD EXISTING SETTINGS (needed for image replace) */
$settings = [];
$res = $connection->query("SELECT setting_key, setting_value FROM website_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

/* SAVE SETTINGS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* BASIC VALIDATION */
    if (empty($_POST['site_name'])) {
        $errors[] = "Site name is required.";
    }

    if (!empty($_POST['latest_products_limit']) && !is_numeric($_POST['latest_products_limit'])) {
        $errors[] = "Product limits must be numbers.";
    }

    if (empty($errors)) {

        foreach ($_POST as $key => $value) {
            $value = trim($value);

            $stmt = $connection->prepare("
                INSERT INTO website_settings (setting_key, setting_value)
                VALUES (?,?)
                ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)
            ");
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
        }

        /* FILE UPLOADS WITH REPLACE */
        $uploads = [
                'header_logo'   => 'branding/',
                'footer_logo'   => 'branding/',
                'hero_image'    => 'hero/',
                'cat_kids_img'  => 'categories/',
                'cat_men_img'   => 'categories/',
                'cat_women_img' => 'categories/'
            ];

        foreach ($uploads as $field => $folder) {

            if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === 0) {
        
                /* Delete old image */
                if (!empty($settings[$field])) {
                    $oldFile = ADMIN_UPLOAD_PATH . $folder . $settings[$field];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
        
                /* Validate extension */
                $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp'];
        
                if (!in_array($ext, $allowed)) {
                    $errors[] = "Invalid file type for $field";
                    continue;
                }
        
                /* Generate safe filename */
                $filename = uniqid($field . '_') . '.' . $ext;
        
                /* Ensure folder exists */
                if (!is_dir(ADMIN_UPLOAD_PATH . $folder)) {
                    mkdir(ADMIN_UPLOAD_PATH . $folder, 0755, true);
                }
        
                /* Move file */
                move_uploaded_file(
                    $_FILES[$field]['tmp_name'],
                    ADMIN_UPLOAD_PATH . $folder . $filename
                );
        
                /* Save to DB */
                $stmt = $connection->prepare("
                    INSERT INTO website_settings (setting_key, setting_value)
                    VALUES (?,?)
                    ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)
                ");
                $stmt->bind_param("ss", $field, $filename);
                $stmt->execute();
        
                /* Update local preview */
                $settings[$field] = $filename;
            }
        }        

        $success = "Website settings updated successfully.";
    }
}

/* LOAD SETTINGS */
$settings = [];
$res = $connection->query("SELECT setting_key, setting_value FROM website_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Website Settings</title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/admin-dashboard.css">
<link rel="stylesheet"
 href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body>

<?php include 'layout/sidebar.php'; ?>

<div class="app-content">
<?php include 'layout/header.php'; ?>

<div class="container-fluid p-4">

<h4 class="fw-semibold mb-3">Website Settings</h4>

<?php if ($success): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4" id="settingsTabs">
    <li class="nav-item">
        <button class="nav-link active"
                data-bs-toggle="tab"
                data-bs-target="#branding"
                data-tab="branding">Logo & Branding</button>
    </li>
    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#hero"
                data-tab="hero">Hero Section</button>
    </li>
    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#services"
                data-tab="services">Home Services</button>
    </li>
    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#categories"
                data-tab="categories">Categories</button>
    </li>
    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#products"
                data-tab="products">Products</button>
    </li>
    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#footer"
                data-tab="footer">Footer & Contact</button>
    </li>
</ul>


<form method="post" enctype="multipart/form-data">

<input type="hidden" name="active_tab" id="active_tab"
       value="<?= htmlspecialchars($_POST['active_tab'] ?? 'branding') ?>">


<div class="tab-content">

<!-- LOGO & BRANDING -->
<div class="tab-pane fade show active" id="branding">
<div class="card p-4 mb-4">
<h6>Logo & Branding</h6>

<label>Site Name *</label>
<input class="form-control mb-3" name="site_name" value="<?= $settings['site_name'] ?? '' ?>">

<label>Tagline</label>
<input class="form-control mb-3" name="site_tagline" value="<?= $settings['site_tagline'] ?? '' ?>">

<label>Header Logo</label>
<input type="file" class="form-control mb-3" name="header_logo">
<?php if (!empty($settings['header_logo'])): ?>
<div class="border rounded p-2 mb-3 bg-light text-center">
    <img src="<?= ADMIN_UPLOAD_URL ?>branding/<?= $settings['header_logo'] ?>"
         style="max-height:70px; object-fit:contain">
</div>
<?php endif; ?>


<label>Footer Logo</label>
<input type="file" class="form-control" name="footer_logo">
<?php if (!empty($settings['footer_logo'])): ?>
<div class="border rounded p-2 mb-3 bg-light text-center">
    <img src="<?= ADMIN_UPLOAD_URL ?>branding/<?= $settings['footer_logo'] ?>"
         style="max-height:70px; object-fit:contain">
</div>
<?php endif; ?>



</div>
</div>


<!-- HERO SECTION -->
<div class="tab-pane fade" id="hero">
<div class="card p-4 mb-4">
<h6>Home Hero Section</h6>

<label>Hero Title *</label>
<input class="form-control mb-3"
       name="hero_title"
       value="<?= $settings['hero_title'] ?? '' ?>" required>

<label>Hero Subtitle</label>
<textarea class="form-control mb-3"
          name="hero_subtitle"><?= $settings['hero_subtitle'] ?? '' ?></textarea>

<hr>

<h6 class="mb-3">Hero Buttons</h6>


<?php for ($i = 1; $i <= 3; $i++): ?>
<div class="border rounded p-3 mb-3">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Button <?= $i ?></strong>

        <div class="form-check form-switch">
            <!-- hidden fallback -->
            <input type="hidden" name="hero_btn<?= $i ?>_active" value="0">

            <input class="form-check-input"
                   type="checkbox"
                   name="hero_btn<?= $i ?>_active"
                   value="1"
                   <?= ($settings["hero_btn{$i}_active"] ?? '0') == '1' ? 'checked' : '' ?>>
            <label class="form-check-label">Active</label>
        </div>
    </div>

    <label>Button Label</label>
    <input class="form-control mb-2"
           name="hero_btn<?= $i ?>_label"
           value="<?= htmlspecialchars($settings["hero_btn{$i}_label"] ?? '') ?>">

    <label>Button Link</label>
    <input class="form-control"
           name="hero_btn<?= $i ?>_link"
           value="<?= htmlspecialchars($settings["hero_btn{$i}_link"] ?? '') ?>">

</div>
<?php endfor; ?>



<label class="mt-3">Hero Image</label>
<input type="file" class="form-control" name="hero_image">

<?php if (!empty($settings['hero_image'])): ?>
<div class="border rounded p-2 mt-3 bg-light text-center">
    <img src="<?= ADMIN_UPLOAD_URL ?>hero/<?= $settings['hero_image'] ?>"
         style="max-height:220px; object-fit:contain"
         class="img-fluid">
</div>
<?php endif; ?>

</div>
</div>


<!-- HOME SERVICES -->
<div class="tab-pane fade" id="services">
<div class="card p-4">

<h6 class="mb-2">Home Services / Value Icons</h6>
<p class="text-muted small mb-4">
Configure up to four value propositions shown under the hero.
</p>

<?php for ($i = 1; $i <= 4; $i++): ?>
<div class="border rounded p-3 mb-4">

<div class="d-flex justify-content-between align-items-center mb-3">
<strong>Service <?= $i ?></strong>

<div class="form-check form-switch">
<input class="form-check-input"
       type="checkbox"
       name="service<?= $i ?>_enabled"
       value="1"
       <?= ($settings["service{$i}_enabled"] ?? '1') == '1' ? 'checked' : '' ?>>
<label class="form-check-label text-muted">
Show this service
</label>
</div>
</div>

<label class="form-label">
Icon class <small class="text-danger">e.g. bi bi-truck</small>
</label>
<input class="form-control mb-3"
       name="service<?= $i ?>_icon"
       value="<?= $settings["service{$i}_icon"] ?? '' ?>">

<label class="form-label">Title</label>
<input class="form-control mb-3"
       name="service<?= $i ?>_title"
       value="<?= $settings["service{$i}_title"] ?? '' ?>">

<label class="form-label">Text</label>
<textarea class="form-control"
          rows="2"
          name="service<?= $i ?>_text"><?= $settings["service{$i}_text"] ?? '' ?></textarea>

</div>
<?php endfor; ?>

</div>
</div>

<!-- CATEGORIES -->
<div class="tab-pane fade" id="categories">
<div class="card p-4 mb-4">
<h6>Shop by Categories</h6>

<label>Kids Image</label>
<input type="file" class="form-control mb-3" name="cat_kids_img">
<?php if (!empty($settings['cat_kids_img'])): ?>
<div class="border rounded p-2 mb-3 bg-light text-center">
    <img src="<?= ADMIN_UPLOAD_URL ?>categories/<?= $settings['cat_kids_img'] ?>"
         style="max-height:160px; object-fit:cover"
         class="img-fluid">
</div>
<?php endif; ?>



<label>Men Image</label>
<input type="file" class="form-control mb-3" name="cat_men_img">
<?php if (!empty($settings['cat_kids_img'])): ?>
<div class="border rounded p-2 mb-3 bg-light text-center">
    <img src="<?= ADMIN_UPLOAD_URL ?>categories/<?= $settings['cat_men_img'] ?>"
         style="max-height:160px; object-fit:cover"
         class="img-fluid">
</div>
<?php endif; ?>



<label>Women Image</label>
<input type="file" class="form-control" name="cat_women_img">
<?php if (!empty($settings['cat_kids_img'])): ?>
<div class="border rounded p-2 mb-3 bg-light text-center">
    <img src="<?= ADMIN_UPLOAD_URL ?>categories/<?= $settings['cat_women_img'] ?>"
         style="max-height:160px; object-fit:cover"
         class="img-fluid">
</div>
<?php endif; ?>

</div>
</div>

<!-- PRODUCTS -->
<div class="tab-pane fade" id="products">
<div class="card p-4 mb-4">
<h6>Home Page Products</h6>

<label>Latest Products Count</label>
<input class="form-control mb-3" name="latest_products_limit"
value="<?= $settings['latest_products_limit'] ?? 8 ?>">

<label>Featured Products Count</label>
<input class="form-control mb-3" name="featured_products_limit"
value="<?= $settings['featured_products_limit'] ?? 4 ?>">

<label>Popular Products Count</label>
<input class="form-control"
name="popular_products_limit"
value="<?= $settings['popular_products_limit'] ?? 4 ?>">
</div>
</div>

<!-- FOOTER -->
<div class="tab-pane fade" id="footer">
<div class="card p-4 mb-4">
<h6>Footer & Contact</h6>

<label>Address</label>
<input class="form-control mb-2" name="footer_address" value="<?= $settings['footer_address'] ?? '' ?>">

<label>Phone</label>
<input class="form-control mb-2" name="footer_phone" value="<?= $settings['footer_phone'] ?? '' ?>">

<label>Email</label>
<input class="form-control mb-2" name="footer_email" value="<?= $settings['footer_email'] ?? '' ?>">

<label>Opening Hours</label>
<input class="form-control" name="opening_hours" value="<?= $settings['opening_hours'] ?? '' ?>">



<hr class="my-4">

<h6 class="mb-3">Social Media Links</h6>

<!-- FACEBOOK -->
<div class="border rounded p-3 mb-3">
<div class="d-flex justify-content-between align-items-center mb-2">
<strong><i class="bi bi-facebook text-primary"></i> Facebook</strong>
<div class="form-check form-switch">
<input class="form-check-input" type="checkbox"
name="social_facebook_enabled" value="1"
<?= ($settings['social_facebook_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
</div>
</div>
<input class="form-control"
placeholder="Facebook URL"
name="social_facebook_url"
value="<?= $settings['social_facebook_url'] ?? '' ?>">
</div>

<!-- INSTAGRAM -->
<div class="border rounded p-3 mb-3">
<div class="d-flex justify-content-between align-items-center mb-2">
<strong><i class="bi bi-instagram text-danger"></i> Instagram</strong>
<div class="form-check form-switch">
<input class="form-check-input" type="checkbox"
name="social_instagram_enabled" value="1"
<?= ($settings['social_instagram_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
</div>
</div>
<input class="form-control"
placeholder="Instagram URL"
name="social_instagram_url"
value="<?= $settings['social_instagram_url'] ?? '' ?>">
</div>

<!-- TIKTOK -->
<div class="border rounded p-3 mb-3">
<div class="d-flex justify-content-between align-items-center mb-2">
<strong><i class="bi bi-tiktok"></i> TikTok</strong>
<div class="form-check form-switch">
<input class="form-check-input" type="checkbox"
name="social_tiktok_enabled" value="1"
<?= ($settings['social_tiktok_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
</div>
</div>
<input class="form-control"
placeholder="TikTok URL"
name="social_tiktok_url"
value="<?= $settings['social_tiktok_url'] ?? '' ?>">
</div>

<!-- YOUTUBE -->
<div class="border rounded p-3">
<div class="d-flex justify-content-between align-items-center mb-2">
<strong><i class="bi bi-youtube text-danger"></i> YouTube</strong>
<div class="form-check form-switch">
<input class="form-check-input" type="checkbox"
name="social_youtube_enabled" value="1"
<?= ($settings['social_youtube_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
</div>
</div>
<input class="form-control"
placeholder="YouTube URL"
name="social_youtube_url"
value="<?= $settings['social_youtube_url'] ?? '' ?>">
</div>


</div>
</div>

</div>

<button class="btn btn-primary mt-3">Update</button>
</form>

</div>

<?php include 'layout/footer.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const hiddenInput = document.getElementById('active_tab');
    const savedTab = hiddenInput.value;

    if (savedTab) {
        const triggerEl = document.querySelector(
            `.nav-link[data-tab="${savedTab}"]`
        );
        if (triggerEl) {
            new bootstrap.Tab(triggerEl).show();
        }
    }

    document.querySelectorAll('.nav-link[data-tab]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (e) {
            hiddenInput.value = this.getAttribute('data-tab');
        });
    });
});
</script>


<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
