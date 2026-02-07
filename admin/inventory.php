<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireStockKeeper();

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

/* =========================
   EXCEL DOWNLOAD
========================= */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=inventory_export.csv");

    $out = fopen("php://output", "w");

    fputcsv($out, [
        'Product',
        'Size',
        'Color',
        'Total Stock',
        'Reserved Stock',
        'Available Stock',
        'Status'
    ]);

    $sql = "
        SELECT 
            p.name AS product,
            s.size_label,
            c.name AS color,
            pv.stock,
            pv.reserved_stock,
            (pv.stock - pv.reserved_stock) AS available
        FROM product_variants pv
        JOIN products p ON pv.product_id = p.id
        JOIN sizes s ON pv.size_id = s.id
        JOIN colors c ON pv.color_id = c.id
        $where
        ORDER BY p.name, s.size_label, c.name
    ";

    $stmt = $connection->prepare($sql);
    if ($search !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        $status = ($r['available'] <= 0)
            ? 'Out of Stock'
            : (($r['available'] <= 5) ? 'Low Stock' : 'In Stock');

        fputcsv($out, [
            $r['product'],
            $r['size_label'],
            $r['color'],
            $r['stock'],
            $r['reserved_stock'],
            $r['available'],
            $status
        ]);
    }

    fclose($out);
    exit;
}

/* =========================
   COUNT TOTAL
========================= */
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

/* =========================
   FETCH INVENTORY
========================= */
$sql = "
    SELECT 
        pv.id,
        p.name AS product_name,
        s.size_label,
        c.name AS color_name,
        pv.stock,
        pv.reserved_stock,
        (pv.stock - pv.reserved_stock) AS available_stock
    FROM product_variants pv
    JOIN products p ON pv.product_id = p.id
    JOIN sizes s ON pv.size_id = s.id
    JOIN colors c ON pv.color_id = c.id
    $where
    ORDER BY p.name, s.size_label, c.name
    LIMIT $limit OFFSET $offset
";

$stmt = $connection->prepare($sql);
if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inventory</title>

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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-semibold mb-0">Inventory Management</h4>

    <div class="d-flex gap-2">
        <form method="get" class="d-flex">
            <input type="text" name="q"
                   value="<?= htmlspecialchars($search) ?>"
                   class="form-control form-control-sm me-2"
                   placeholder="Search product / size / color">
            <button class="btn btn-sm btn-dark">
                <i class="fa fa-search"></i>
            </button>
        </form>

        <a href="?export=excel&q=<?= urlencode($search) ?>"
           class="btn btn-sm btn-success">
            <i class="fa fa-file-excel"></i> Excel
        </a>
    </div>
</div>

<div class="card shadow-sm">
<div class="card-body table-responsive p-0">

<table class="table table-hover align-middle mb-0">
<thead class="table-light">
<tr>
    <th>Product</th>
    <th class="text-center">Size</th>
    <th class="text-center">Color</th>
    <th class="text-center">Total</th>
    <th class="text-center">Reserved</th>
    <th class="text-center">Available</th>
    <th class="text-center">Status</th>
</tr>
</thead>

<tbody>
<?php if ($items->num_rows === 0): ?>
<tr>
<td colspan="7" class="text-center text-muted py-4">
No inventory records found.
</td>
</tr>
<?php endif; ?>

<?php while ($row = $items->fetch_assoc()): ?>
<?php
$available = (int)$row['available_stock'];
$lowStock  = $available <= 5;
?>
<tr>
<td class="fw-semibold"><?= htmlspecialchars($row['product_name']) ?></td>
<td class="text-center"><?= $row['size_label'] ?></td>
<td class="text-center"><?= $row['color_name'] ?></td>

<td class="text-center">
<span class="badge bg-dark"><?= $row['stock'] ?></span>
</td>

<td class="text-center">
<?= $row['reserved_stock'] > 0
    ? '<span class="badge bg-warning text-dark">'.$row['reserved_stock'].'</span>'
    : '<span class="text-muted">0</span>' ?>
</td>

<td class="text-center">
<span class="badge <?= $lowStock ? 'bg-danger':'bg-success' ?>">
<?= $available ?>
</span>
</td>

<td class="text-center">
<?php if ($available <= 0): ?>
<span class="badge bg-danger">Out</span>
<?php elseif ($lowStock): ?>
<span class="badge bg-warning text-dark">Low</span>
<?php else: ?>
<span class="badge bg-success">In</span>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</div>

<?php if ($totalPages > 1): ?>
<div class="p-3">
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
</div>
<?php endif; ?>

</div>

</div>

<?php include 'layout/footer.php'; ?>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
