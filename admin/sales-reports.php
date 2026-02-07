<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireAdmin();

$openMenu = 'reports';
$currentPage = 'sales-reports';

/* ================= FILTERS ================= */
$status   = $_GET['status'] ?? 'all';
$from     = $_GET['from'] ?? '';
$to       = $_GET['to'] ?? '';

$where = [];
$params = [];
$types  = '';

if ($status !== 'all') {
    $where[] = "o.order_status = ?";
    $params[] = $status;
    $types   .= 's';
}
if ($from) {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $from;
    $types   .= 's';
}
if ($to) {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $to;
    $types   .= 's';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ================= PAGINATION ================= */
$limit = 10;
$page  = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* ================= TOTAL COUNT ================= */
$countSql = "SELECT COUNT(*) AS total FROM orders o {$whereSql}";
$countStmt = $connection->prepare($countSql);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows  = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

/* ================= FETCH SALES ================= */
$sql = "
SELECT
    o.id,
    o.tracking_code,
    o.customer_name,
    o.subtotal,
    o.delivery_fee,
    o.total,
    o.payment_method,
    o.order_status,
    o.created_at
FROM orders o
{$whereSql}
ORDER BY o.created_at DESC
LIMIT {$limit} OFFSET {$offset}
";

$stmt = $connection->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$sales = $stmt->get_result();

/* ================= EXCEL EXPORT ================= */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=sales-report.xls");

    echo "Order ID\tTracking\tCustomer\tSubtotal\tDelivery\tTotal\tPayment\tStatus\tDate\n";

    $exportStmt = $connection->prepare(str_replace(
        "LIMIT {$limit} OFFSET {$offset}",
        "",
        $sql
    ));
    if ($params) {
        $exportStmt->bind_param($types, ...$params);
    }
    $exportStmt->execute();
    $res = $exportStmt->get_result();

    while ($r = $res->fetch_assoc()) {
        echo "{$r['id']}\t{$r['tracking_code']}\t{$r['customer_name']}\t{$r['subtotal']}\t{$r['delivery_fee']}\t{$r['total']}\t{$r['payment_method']}\t{$r['order_status']}\t{$r['created_at']}\n";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Sales Report</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/admin-dashboard.css">
</head>

<body>
<?php include 'layout/sidebar.php'; ?>

<div class="app-content">
<?php include 'layout/header.php'; ?>

<div class="container-fluid p-4">

<h4 class="fw-semibold mb-3">Sales Report</h4>

<!-- FILTER -->
<form class="card p-3 mb-3">
<div class="row g-3 align-items-end">
    <div class="col-md-3">
        <label>Status</label>
        <select name="status" class="form-select">
            <option value="all">All</option>
            <?php foreach(['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $status==$s?'selected':'' ?>>
                    <?= ucfirst($s) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label>From</label>
        <input type="date" name="from" value="<?= $from ?>" class="form-control">
    </div>
    <div class="col-md-3">
        <label>To</label>
        <input type="date" name="to" value="<?= $to ?>" class="form-control">
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-dark">Filter</button>
        <a href="?export=excel&status=<?= $status ?>&from=<?= $from ?>&to=<?= $to ?>"
           class="btn btn-success">
           Export Excel
        </a>
    </div>
</div>
</form>

<div class="card">
<div class="card-body table-responsive p-0">
<table class="table table-hover mb-0">
<thead class="table-light">
<tr>
<th>ID</th><th>Customer</th><th>Subtotal</th>
<th>Delivery</th><th>Total</th><th>Status</th><th>Date</th>
</tr>
</thead>
<tbody>
<?php while($r=$sales->fetch_assoc()): ?>
<tr>
<td>#<?= $r['id'] ?></td>
<td><?= $r['customer_name'] ?></td>
<td>Rs.<?= number_format($r['subtotal'],2) ?></td>
<td>Rs.<?= number_format($r['delivery_fee'],2) ?></td>
<td>Rs.<?= number_format($r['total'],2) ?></td>
<td><?= ucfirst($r['order_status']) ?></td>
<td><?= date('d M Y',strtotime($r['created_at'])) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
<ul class="pagination pagination-sm justify-content-end">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<li class="page-item <?= $i==$page?'active':'' ?>">
<a class="page-link"
href="?page=<?= $i ?>&status=<?= $status ?>&from=<?= $from ?>&to=<?= $to ?>">
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
