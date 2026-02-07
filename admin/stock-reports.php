<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireStockKeeper();

$openMenu = 'reports';
$currentPage = 'stock-reports';

/* ================= SEARCH ================= */
$search = trim($_GET['q'] ?? '');
$where  = '';
$params = [];

if ($search !== '') {
    $where = "WHERE p.name LIKE ? OR s.size_label LIKE ? OR c.name LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

/* ================= PAGINATION ================= */
$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* ================= TOTAL ROWS ================= */
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
    $countStmt->bind_param("sss", ...$params);
}
$countStmt->execute();
$totalRows  = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

/* ================= FETCH STOCK ================= */
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
ORDER BY p.name
LIMIT $limit OFFSET $offset
";
$stmt = $connection->prepare($sql);
if ($search !== '') {
    $stmt->bind_param("sss", ...$params);
}
$stmt->execute();
$items = $stmt->get_result();

/* ================= SUMMARY COUNTS ================= */
$summary = $connection->query("
    SELECT
        COUNT(*) AS variants,
        COALESCE(SUM(stock),0) AS stock,
        COALESCE(SUM(reserved_stock),0) AS reserved,
        COALESCE(SUM(stock - reserved_stock),0) AS available
    FROM product_variants
")->fetch_assoc();

/* ================= EXCEL EXPORT ================= */
if (isset($_GET['export']) && $_GET['export']=='excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=stock-report.xls");

    echo "Product\tSize\tColor\tStock\tReserved\tAvailable\n";

    $exportSql = "
        SELECT p.name,s.size_label,c.name,
               pv.stock,pv.reserved_stock,(pv.stock-pv.reserved_stock)
        FROM product_variants pv
        JOIN products p ON pv.product_id=p.id
        JOIN sizes s ON pv.size_id=s.id
        JOIN colors c ON pv.color_id=c.id
        $where
        ORDER BY p.name
    ";
    $exp = $connection->prepare($exportSql);
    if ($search !== '') {
        $exp->bind_param("sss", ...$params);
    }
    $exp->execute();
    $res = $exp->get_result();

    while ($r = $res->fetch_row()) {
        echo implode("\t", $r) . "\n";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Stock Report</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/admin-dashboard.css">
</head>

<body>
<?php include 'layout/sidebar.php'; ?>

<div class="app-content">
<?php include 'layout/header.php'; ?>

<div class="container-fluid p-4">

<h4 class="fw-semibold mb-3">Stock Report</h4>

<!-- ================= SUMMARY CARDS ================= -->
<div class="row g-3 mb-4">
<div class="col-md-3">
<div class="card shadow-sm text-center p-3">
<h6 class="text-muted">Total Variants</h6>
<h4 class="fw-bold"><?= $summary['variants'] ?></h4>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm text-center p-3">
<h6 class="text-muted">Total Stock</h6>
<h4 class="fw-bold"><?= $summary['stock'] ?></h4>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm text-center p-3">
<h6 class="text-muted">Reserved Stock</h6>
<h4 class="fw-bold"><?= $summary['reserved'] ?></h4>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm text-center p-3">
<h6 class="text-muted">Available Stock</h6>
<h4 class="fw-bold"><?= $summary['available'] ?></h4>
</div>
</div>
</div>

<!-- ================= SEARCH + EXPORT ================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
<form method="get" class="d-flex">
<input type="text" name="q"
value="<?= htmlspecialchars($search) ?>"
class="form-control me-2"
placeholder="Search product / size / color">
<button class="btn btn-dark">Search</button>
</form>

<a href="?export=excel&q=<?= urlencode($search) ?>" class="btn btn-success">
Export Excel
</a>
</div>

<div class="card">
<div class="card-body table-responsive p-0">
<table class="table table-hover mb-0">
<thead class="table-light">
<tr>
<th>Product</th>
<th>Size</th>
<th>Color</th>
<th>Stock</th>
<th>Reserved</th>
<th>Available</th>
</tr>
</thead>
<tbody>
<?php if ($items->num_rows === 0): ?>
<tr><td colspan="6" class="text-center text-muted">No records found</td></tr>
<?php endif; ?>

<?php while($r = $items->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($r['product']) ?></td>
<td><?= htmlspecialchars($r['size_label']) ?></td>
<td><?= htmlspecialchars($r['color']) ?></td>
<td><?= $r['stock'] ?></td>
<td><?= $r['reserved_stock'] ?></td>
<td><?= $r['available'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- ================= PAGINATION ================= -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
<ul class="pagination pagination-sm justify-content-end">
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
<?php endif; ?>

</div>
<?php include 'layout/footer.php'; ?>
</div>
</body>
</html>
