<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireAdmin();

$openMenu = 'catalog';
$currentPage = 'product-images';

$successMsg = '';
$errorMsg   = '';

/* =========================
   UPLOAD IMAGE
========================= */
if (isset($_POST['upload_image'])) {

    $product_id = intval($_POST['product_id']);

    if (!$product_id) {
        $errorMsg = "Please select a product.";
    } elseif (empty($_FILES['image']['name'])) {
        $errorMsg = "Please select an image.";
    } else {

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_') . '.' . $ext;
        $path = 'uploads/products/' . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $path)) {

            $stmt = $connection->prepare("
                INSERT INTO product_images (product_id, image_path)
                VALUES (?, ?)
            ");
            $stmt->bind_param("is", $product_id, $path);
            $stmt->execute();

            $successMsg = "Image uploaded successfully.";
        } else {
            $errorMsg = "Image upload failed.";
        }
    }
}

/* =========================
   DELETE IMAGE
========================= */
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $res = $connection->query("
        SELECT image_path FROM product_images WHERE id = $id
    ");
    if ($row = $res->fetch_assoc()) {
        if ($row['image_path'] && file_exists($row['image_path'])) {
            unlink($row['image_path']);
        }
    }

    $connection->query("DELETE FROM product_images WHERE id = $id");
    $successMsg = "Image deleted successfully.";
}

/* PRODUCTS */
$products = $connection->query("
    SELECT id, name FROM products ORDER BY name
");


/* =========================
   SEARCH + PAGINATION
========================= */
$limit  = 10;
$page   = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$search = trim($_GET['q'] ?? '');

$where  = '';
$params = [];
$types  = '';

if ($search !== '') {
    $where = "WHERE p.name LIKE ?";
    $params[] = "%$search%";
    $types = "s";
}

/* TOTAL COUNT */
$countSql = "
    SELECT COUNT(*) AS total
    FROM product_images pi
    JOIN products p ON pi.product_id = p.id
    $where
";
$countStmt = $connection->prepare($countSql);
if ($search !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages   = ceil($totalRecords / $limit);

/* FETCH IMAGES */
$sql = "
    SELECT pi.id, pi.image_path, pi.is_primary, p.name
    FROM product_images pi
    JOIN products p ON pi.product_id = p.id
    $where
    ORDER BY p.name, pi.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $connection->prepare($sql);
if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$images = $stmt->get_result();

/* PRODUCTS FOR UPLOAD */
$products = $connection->query("
    SELECT id, name FROM products ORDER BY name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Product Images</title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/admin-dashboard.css">

<!-- SELECT2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php include 'layout/sidebar.php'; ?>

<div class="app-content">
<?php include 'layout/header.php'; ?>

<div class="container-fluid p-4">

<h4 class="fw-semibold mb-3">Product Images</h4>

<?php if ($successMsg): ?>
<div class="alert alert-success"><?= $successMsg ?></div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= $errorMsg ?></div>
<?php endif; ?>

<div class="row g-4">

<!-- UPLOAD FORM -->
<div class="col-lg-4">
<div class="card shadow-sm">
<div class="card-header bg-white">
<h6 class="mb-0 fw-semibold">Add Gallery Image</h6>
</div>

<div class="card-body">
<form method="post" enctype="multipart/form-data">

<div class="mb-3">
<label class="form-label">Product</label>
<select name="product_id"
        class="form-select product-select"
        required>
<option value="">Select product</option>
<?php while ($p = $products->fetch_assoc()): ?>
<option value="<?= $p['id'] ?>">
<?= htmlspecialchars($p['name']) ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="mb-3">
<label class="form-label">Image</label>
<input type="file"
       name="image"
       class="form-control"
       accept="image/*"
       required>
</div>

<div class="d-grid">
<button type="submit"
        name="upload_image"
        class="btn btn-dark">
Upload Image
</button>
</div>

</form>
</div>
</div>
</div>



<!-- IMAGE LIST -->
<div class="col-lg-8">
<div class="card shadow-sm">

<!-- HEADER + SEARCH -->
<div class="d-flex justify-content-between align-items-center p-3 border-bottom">
<h6 class="mb-0 fw-semibold">Gallery Images</h6>

<form method="get" class="d-flex">
<input type="text" name="q"
value="<?= htmlspecialchars($search) ?>"
class="form-control form-control-sm me-2"
placeholder="Search product...">
<button class="btn btn-sm btn-dark">
<i class="fa fa-search"></i>
</button>
</form>
</div>

<div class="card-body p-0">

<table class="table table-bordered table-hover mb-0">
<thead class="table-light">
<tr>
<th>Product</th>
<th>Image</th>
<th>Primary</th>
<th width="100">Action</th>
</tr>
</thead>

<tbody>
<?php if ($images->num_rows === 0): ?>
<tr>
<td colspan="4" class="text-center text-muted">No images found.</td>
</tr>
<?php endif; ?>

<?php while ($i = $images->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($i['name']) ?></td>
<td><img src="<?= $i['image_path'] ?>" width="60" class="rounded border"></td>
<td>
<?= $i['is_primary']
    ? '<span class="badge bg-success">Yes</span>'
    : '-' ?>
</td>
<td>
<?php if (!$i['is_primary']): ?>
<a href="product-images.php?delete=<?= $i['id'] ?>"
onclick="return confirm('Delete this image?')"
class="btn btn-sm btn-outline-danger"
title="Delete">
<i class="fa fa-trash"></i>
</a>
<?php else: ?>
<span class="text-muted small">Main</span>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<div class="p-3">
<nav>
<ul class="pagination pagination-sm mb-0">
<?php for ($i=1; $i<=$totalPages; $i++): ?>
<li class="page-item <?= $i==$page?'active':'' ?>">
<a class="page-link"
href="?page=<?= $i ?>&q=<?= urlencode($search) ?>">
<?= $i ?>
</a>
</li>
<?php endfor; ?>
</ul>
</nav>
</div>
<?php endif; ?>

</div>
</div>
</div>

</div>
</div>


<?php include 'layout/footer.php'; ?>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>

<!-- SELECT2 -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {
    $('.product-select').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search product...',
        allowClear: true,
        width: '100%'
    });
});
</script>

</body>
</html>
