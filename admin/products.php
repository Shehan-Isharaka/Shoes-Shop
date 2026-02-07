<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireAdmin();

$openMenu = 'catalog';
$currentPage = 'products';

$successMsg = '';
$errorMsg   = '';

/* =========================
   SUCCESS AFTER REDIRECT
========================= */
if (isset($_GET['updated'])) {
    $successMsg = "Product updated successfully.";
}



/* =========================
   SEARCH + PAGINATION
========================= */
$limit = 10;
$page  = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$search = trim($_GET['q'] ?? '');
$where  = '';
$params = [];
$types  = '';

if ($search !== '') {
    $where = "WHERE pr.name LIKE ?";
    $params[] = "%{$search}%";
    $types .= 's';
}


/* =========================
   ADD / UPDATE PRODUCT
========================= */
if (isset($_POST['save_product'])) {

    $id          = intval($_POST['id'] ?? 0);
    $category_id = intval($_POST['category_id']);
    $brand_id    = intval($_POST['brand_id']);
    $name        = trim($_POST['name']);
    $price       = floatval($_POST['price']);
    $discount    = floatval($_POST['discount']);
    $description = trim($_POST['description']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    if (!$category_id || !$name || !$price) {
        $errorMsg = "Category, product name and price are required.";
    } else {

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        /* ================= ADD PRODUCT ================= */
        if ($id === 0) {

            $stmt = $connection->prepare("
                INSERT INTO products
                (category_id, brand_id, name, slug, price, discount, description, is_featured)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "iissddsi",
                $category_id,
                $brand_id,
                $name,
                $slug,
                $price,
                $discount,
                $description,
                $is_featured
            );

            $stmt->execute();
            $product_id = $stmt->insert_id;

            /* MAIN IMAGE UPLOAD */
            if (!empty($_FILES['main_image']['name'])) {

                $ext = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('prod_') . '.' . $ext;
                $path = 'uploads/products/' . $filename;

                move_uploaded_file($_FILES['main_image']['tmp_name'], $path);

                $stmtImg = $connection->prepare("
                    INSERT INTO product_images (product_id, image_path, is_primary)
                    VALUES (?, ?, 1)
                ");
                $stmtImg->bind_param("is", $product_id, $path);
                $stmtImg->execute();
            }

            $successMsg = "Product added successfully.";
        }

        /* ================= UPDATE PRODUCT ================= */
        else {

            /* GET OLD IMAGE */
            $old = $connection->query("
                SELECT image_path FROM product_images
                WHERE product_id = $id AND is_primary = 1
                LIMIT 1
            ")->fetch_assoc();

            $imagePath = $old['image_path'] ?? null;

            /* IMAGE REPLACE */
            if (!empty($_FILES['main_image']['name'])) {

                if ($imagePath && file_exists($imagePath)) {
                    unlink($imagePath);
                }

                $ext = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('prod_') . '.' . $ext;
                $imagePath = 'uploads/products/' . $filename;

                move_uploaded_file($_FILES['main_image']['tmp_name'], $imagePath);

                $connection->query("
                    DELETE FROM product_images
                    WHERE product_id = $id AND is_primary = 1
                ");

                $stmtImg = $connection->prepare("
                    INSERT INTO product_images (product_id, image_path, is_primary)
                    VALUES (?, ?, 1)
                ");
                $stmtImg->bind_param("is", $id, $imagePath);
                $stmtImg->execute();
            }

            /* UPDATE PRODUCT */
            $stmt = $connection->prepare("
                UPDATE products SET
                    category_id=?,
                    brand_id=?,
                    name=?,
                    slug=?,
                    price=?,
                    discount=?,
                    description=?,
                    is_featured=?
                WHERE id=?
            ");

            $stmt->bind_param(
                "iissddssi",
                $category_id,
                $brand_id,
                $name,
                $slug,
                $price,
                $discount,
                $description,
                $is_featured,
                $id
            );

            $stmt->execute();

            header("Location: products.php?updated=1");
            exit;
        }
    }
}

/* =========================
   DELETE PRODUCT
========================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $imgs = $connection->query("
        SELECT image_path FROM product_images WHERE product_id = $id
    ");

    while ($img = $imgs->fetch_assoc()) {
        if ($img['image_path'] && file_exists($img['image_path'])) {
            unlink($img['image_path']);
        }
    }

    $connection->query("DELETE FROM product_images WHERE product_id = $id");
    $connection->query("DELETE FROM products WHERE id = $id");

    $successMsg = "Product deleted successfully.";
}

/* =========================
   FETCH DATA
========================= */
$categories = $connection->query("
    SELECT c.id, CONCAT(p.name,' → ',c.name) AS name
    FROM categories c
    JOIN categories p ON c.parent_id = p.id
    WHERE c.parent_id IS NOT NULL AND c.status='active'
");

$brands = $connection->query("
    SELECT id, name FROM brands WHERE status='active'
");

$products = $connection->query("
    SELECT pr.*, c.name AS category_name, b.name AS brand_name,
           pi.image_path
    FROM products pr
    JOIN categories c ON pr.category_id = c.id
    LEFT JOIN brands b ON pr.brand_id = b.id
    LEFT JOIN product_images pi
        ON pr.id = pi.product_id AND pi.is_primary = 1
    ORDER BY pr.created_at DESC
");

/* EDIT MODE */
$editData = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $editData = $connection->query("
        SELECT * FROM products WHERE id = $id
    ")->fetch_assoc();
}



/* =========================
   TOTAL COUNT (FOR PAGINATION)
========================= */
$countSql = "
    SELECT COUNT(*) AS total
    FROM products pr
    $where
";
$countStmt = $connection->prepare($countSql);

if ($search !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages   = ceil($totalRecords / $limit);


/* =========================
   FETCH PRODUCTS (WITH LIMIT)
========================= */
$sql = "
    SELECT pr.*, c.name AS category_name, b.name AS brand_name,
           pi.image_path
    FROM products pr
    JOIN categories c ON pr.category_id = c.id
    LEFT JOIN brands b ON pr.brand_id = b.id
    LEFT JOIN product_images pi
        ON pr.id = pi.product_id AND pi.is_primary = 1
    $where
    ORDER BY pr.created_at DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $connection->prepare($sql);

if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();


?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Product Management</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/admin-dashboard.css">
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
      
</head>

<body>

<?php include 'layout/sidebar.php'; ?>

<div class="app-content">
<?php include 'layout/header.php'; ?>

<div class="container-fluid p-4">

<h4 class="fw-semibold mb-3">Product Management</h4>

<?php if ($successMsg): ?>
<div class="alert alert-success"><?= $successMsg ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= $errorMsg ?></div>
<?php endif; ?>

<div class="row g-4">

<!-- ADD / EDIT FORM -->
<div class="col-lg-4">
<div class="card shadow-sm">
<div class="card-header bg-white">
<h6 class="mb-0 fw-semibold"><?= $editData ? 'Edit Product' : 'Add Product' ?></h6>
</div>
<div class="card-body">

<form method="post" enctype="multipart/form-data">

<input type="hidden" name="id" value="<?= $editData['id'] ?? 0 ?>">

<div class="mb-3">
<label class="form-label">Category</label>
<select name="category_id" class="form-select" required>
<option value="">Select Category</option>
<?php while ($c = $categories->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>"
<?= ($editData && $editData['category_id']==$c['id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="mb-3">
<label class="form-label">Brand</label>
<select name="brand_id" class="form-select">
<option value="">Select Brand</option>
<?php while ($b = $brands->fetch_assoc()): ?>
<option value="<?= $b['id'] ?>"
<?= ($editData && $editData['brand_id']==$b['id'])?'selected':'' ?>>
<?= $b['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="mb-3">
<label class="form-label">Product Name</label>
<input type="text" name="name" class="form-control"
value="<?= $editData['name'] ?? '' ?>" required>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Price</label>
<input type="number" step="0.01" name="price"
class="form-control"
value="<?= $editData['price'] ?? '' ?>" required>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Discount</label>
<input type="number" step="0.01" name="discount"
class="form-control"
value="<?= $editData['discount'] ?? 0 ?>">
</div>
</div>

<div class="mb-3">
<label class="form-label">Description</label>
<textarea name="description" rows="4"
class="form-control"><?= $editData['description'] ?? '' ?></textarea>
</div>

<!-- IMAGE -->
<div class="mb-3">
<label class="form-label">Main Product Image</label>

<?php if (!empty($editData['id'])):
$img = $connection->query("
SELECT image_path FROM product_images
WHERE product_id={$editData['id']} AND is_primary=1
")->fetch_assoc();
if (!empty($img['image_path'])): ?>
<div class="mb-2">
<img src="<?= $img['image_path'] ?>" width="80" class="rounded border">
</div>
<?php endif; endif; ?>

<input type="file" name="main_image"
class="form-control" accept="image/*"
<?= empty($editData) ? 'required' : '' ?>>
</div>

<!-- FEATURED -->
<div class="form-check mb-3">
<input class="form-check-input" type="checkbox"
name="is_featured" value="1"
<?= (!empty($editData) && $editData['is_featured']==1)?'checked':'' ?>>
<label class="form-check-label fw-semibold">
Mark as Featured Product
</label>
</div>

<div class="d-grid">
<button type="submit" name="save_product" class="btn btn-dark">
<?= $editData ? 'Update Product' : 'Save Product' ?>
</button>
</div>

</form>
</div>
</div>
</div>

<!-- PRODUCT LIST -->
<div class="col-lg-8">
<div class="card shadow-sm">
<div class="d-flex justify-content-between align-items-center p-3 border-bottom">
    <h6 class="mb-0 fw-semibold">Product List</h6>

    <form method="get" class="d-flex">
        <input type="text"
               name="q"
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
    <th>Image</th>
    <th>Name</th>
    <th>Category</th>
    <th>Brand</th>
    <th>Price</th>
    <th>Discount</th>
    <th>Description</th>
    <th>Featured</th>
    <th width="150">Action</th>
</tr>
</thead>

<tbody>

<?php if ($products->num_rows === 0): ?>
<tr>
<td colspan="6" class="text-center text-muted">No products found.</td>
</tr>
<?php endif; ?>

<?php while ($p = $products->fetch_assoc()): ?>
<tr>
    <td>
        <?php if ($p['image_path']): ?>
            <img src="<?= $p['image_path'] ?>" width="50">
        <?php else: ?>
            —
        <?php endif; ?>
    </td>

    <td><?= htmlspecialchars($p['name']) ?></td>

    <td><?= htmlspecialchars($p['category_name']) ?></td>

    <td><?= htmlspecialchars($p['brand_name'] ?? '-') ?></td>

    <td>Rs. <?= number_format($p['price'], 2) ?></td>

    <td>
        <?= $p['discount'] > 0 ? 'Rs. ' . number_format($p['discount'],2) : '-' ?>
    </td>

    <td style="max-width:200px">
        <?= !empty($p['description']) 
            ? substr(strip_tags($p['description']), 0, 60) . '...' 
            : '-' ?>
    </td>

    <td>
        <?php if ($p['is_featured'] == 1): ?>
            <span class="badge bg-success">Yes</span>
        <?php else: ?>
            <span class="badge bg-secondary">No</span>
        <?php endif; ?>
    </td>

    <td>
        <a href="products.php?edit=<?= $p['id'] ?>"
        class="btn btn-sm btn-outline-primary me-1"
        title="Edit">
            <i class="fa fa-pen"></i>
        </a>

        <a href="products.php?delete=<?= $p['id'] ?>"
        onclick="return confirm('Delete this product?')"
        class="btn btn-sm btn-outline-danger"
        title="Delete">
            <i class="fa fa-trash"></i>
        </a>
    </td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<?php if ($totalPages > 1): ?>
<div class="p-3">
<nav>
<ul class="pagination pagination-sm mb-0">

<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
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
<script src="assets/js/admin-dashboard.js"></script>
</body>
</html>
