<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireStockKeeper();

$successMsg = '';
$errorMsg   = '';

/* =========================
   SUCCESS MESSAGE
========================= */
if (isset($_GET['updated'])) {
    $successMsg = "Variant updated successfully.";
}

/* =========================
   ADD / UPDATE VARIANT
========================= */
if (isset($_POST['save_variant'])) {

    $id         = intval($_POST['id'] ?? 0);
    $product_id = intval($_POST['product_id'] ?? 0);
    $size_id    = intval($_POST['size_id'] ?? 0);
    $color_id   = intval($_POST['color_id'] ?? 0);
    $stock      = intval($_POST['stock'] ?? 0);
    $sku        = trim($_POST['sku'] ?? '');

    if (!$product_id || !$size_id || !$color_id) {
        $errorMsg = "All fields are required.";
    } else {

        if ($id === 0) {

            /* DUPLICATE CHECK */
            $chk = $connection->prepare("
                SELECT id FROM product_variants
                WHERE product_id=? AND size_id=? AND color_id=?
            ");
            $chk->bind_param("iii", $product_id, $size_id, $color_id);
            $chk->execute();
            $chk->store_result();

            if ($chk->num_rows > 0) {
                $errorMsg = "This variant already exists.";
            } else {
                $stmt = $connection->prepare("
                    INSERT INTO product_variants
                    (product_id, size_id, color_id, stock, sku)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iiiis", $product_id, $size_id, $color_id, $stock, $sku);
                $stmt->execute();
                $successMsg = "Variant added successfully.";
            }
        } else {

            $stmt = $connection->prepare("
                UPDATE product_variants
                SET stock=?, sku=?
                WHERE id=?
            ");
            $stmt->bind_param("isi", $stock, $sku, $id);
            $stmt->execute();

            header("Location: product-variants.php?updated=1");
            exit;
        }
    }
}

/* =========================
   DELETE VARIANT (ADMIN)
========================= */
if (isset($_GET['delete']) && $_SESSION['user_role'] === 'admin') {
    $id = intval($_GET['delete']);
    $connection->query("DELETE FROM product_variants WHERE id=$id");
    $successMsg = "Variant deleted successfully.";
}

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
    $where = "WHERE p.name LIKE ? OR s.size_label LIKE ? OR c.name LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
    $types  = "sss";
}

/* COUNT */
$countSql = "
    SELECT COUNT(*) AS total
    FROM product_variants pv
    JOIN products p ON pv.product_id = p.id
    JOIN sizes s ON pv.size_id = s.id
    JOIN colors c ON pv.color_id = c.id
    $where
";
$countStmt = $connection->prepare($countSql);
if ($search !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages   = ceil($totalRecords / $limit);

/* FETCH VARIANTS */
$sql = "
    SELECT pv.*, p.name AS product, s.size_label, c.name AS color
    FROM product_variants pv
    JOIN products p ON pv.product_id = p.id
    JOIN sizes s ON pv.size_id = s.id
    JOIN colors c ON pv.color_id = c.id
    $where
    ORDER BY p.name
    LIMIT $limit OFFSET $offset
";
$stmt = $connection->prepare($sql);
if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$variants = $stmt->get_result();

/* =========================
   FORM DATA
========================= */
$products = $connection->query("SELECT id, name FROM products ORDER BY name");
$sizes    = $connection->query("SELECT id, size_label FROM sizes WHERE status='active'");
$colors   = $connection->query("SELECT id, name FROM colors WHERE status='active'");

$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit = $connection->query("
        SELECT * FROM product_variants WHERE id=$id
    ")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Product Variants</title>

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

<h4 class="fw-semibold mb-3">Product Variants</h4>

<?php if ($successMsg): ?><div class="alert alert-success"><?= $successMsg ?></div><?php endif; ?>
<?php if ($errorMsg): ?><div class="alert alert-danger"><?= $errorMsg ?></div><?php endif; ?>

<div class="row g-4">

<!-- ================= FORM ================= -->
<div class="col-lg-4">
<div class="card shadow-sm p-3">
<h6 class="fw-semibold mb-3"><?= $edit ? 'Edit Variant' : 'Add Variant' ?></h6>

<form method="post">
<input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

<?php if ($edit): ?>
<input type="hidden" name="product_id" value="<?= $edit['product_id'] ?>">
<input type="hidden" name="size_id" value="<?= $edit['size_id'] ?>">
<input type="hidden" name="color_id" value="<?= $edit['color_id'] ?>">
<?php endif; ?>

<select name="product_id"
        class="form-select product-select mb-2"
        <?= $edit?'disabled':'' ?> required>
<option value="">Search product...</option>
<?php while ($p = $products->fetch_assoc()): ?>
<option value="<?= $p['id'] ?>"
<?= ($edit && $edit['product_id']==$p['id'])?'selected':'' ?>>
<?= htmlspecialchars($p['name']) ?>
</option>
<?php endwhile; ?>
</select>

<select name="size_id" class="form-select mb-2" <?= $edit?'disabled':'' ?> required>
<option value="">Size</option>
<?php while ($s = $sizes->fetch_assoc()): ?>
<option value="<?= $s['id'] ?>" <?= ($edit && $edit['size_id']==$s['id'])?'selected':'' ?>>
<?= htmlspecialchars($s['size_label']) ?>
</option>
<?php endwhile; ?>
</select>

<select name="color_id" class="form-select mb-2" <?= $edit?'disabled':'' ?> required>
<option value="">Color</option>
<?php while ($c = $colors->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" <?= ($edit && $edit['color_id']==$c['id'])?'selected':'' ?>>
<?= htmlspecialchars($c['name']) ?>
</option>
<?php endwhile; ?>
</select>

<input type="number" name="stock" class="form-control mb-2"
value="<?= $edit['stock'] ?? 0 ?>" required>

<input type="text" name="sku" class="form-control mb-3"
value="<?= $edit['sku'] ?? '' ?>" placeholder="SKU">

<button class="btn btn-dark w-100" name="save_variant">
<?= $edit ? 'Update Variant' : 'Save Variant' ?>
</button>
</form>
</div>
</div>

<!-- ================= LIST ================= -->
<div class="col-lg-8">
<div class="card shadow-sm">

<div class="d-flex justify-content-between align-items-center p-3 border-bottom">
<h6 class="mb-0 fw-semibold">Variant List</h6>

<form method="get" class="d-flex">
<input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
class="form-control form-control-sm me-2" placeholder="Search...">
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
<th>Size</th>
<th>Color</th>
<th>Stock</th>
<th width="100">Action</th>
</tr>
</thead>
<tbody>

<?php if ($variants->num_rows === 0): ?>
<tr><td colspan="5" class="text-center text-muted">No variants found.</td></tr>
<?php endif; ?>

<?php while ($v = $variants->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($v['product']) ?></td>
<td><?= htmlspecialchars($v['size_label']) ?></td>
<td><?= htmlspecialchars($v['color']) ?></td>
<td><?= $v['stock'] ?></td>
<td>
<a href="?edit=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary">
<i class="fa fa-pen"></i>
</a>
<?php if ($_SESSION['user_role'] === 'admin'): ?>
<a href="?delete=<?= $v['id'] ?>"
onclick="return confirm('Delete this variant?')"
class="btn btn-sm btn-outline-danger">
<i class="fa fa-trash"></i>
</a>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

<?php if ($totalPages > 1): ?>
<div class="p-3">
<ul class="pagination pagination-sm mb-0">
<?php for ($i=1;$i<=$totalPages;$i++): ?>
<li class="page-item <?= $i==$page?'active':'' ?>">
<a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>">
<?= $i ?>
</a>
</li>
<?php endfor; ?>
</ul>
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

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function () {
    $('.product-select').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search product...',
        width: '100%'
    });
});
</script>

</body>
</html>
