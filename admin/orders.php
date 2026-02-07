<?php
require_once 'auth-check.php';
require_once 'role-check.php';
requireStockKeeper();
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= FILTER INPUTS ================= */
$status    = $_GET['status'] ?? 'all';
$dateFrom  = $_GET['from'] ?? '';
$dateTo    = $_GET['to'] ?? '';
$search    = trim($_GET['q'] ?? '');

/* ================= PAGINATION ================= */
$limit  = 10;
$page   = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
$types  = '';

if ($status !== 'all') {
    $where[] = "o.order_status = ?";
    $params[] = $status;
    $types   .= 's';
}

if (!empty($dateFrom)) {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
    $types   .= 's';
}

if (!empty($dateTo)) {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $dateTo;
    $types   .= 's';
}

if ($search !== '') {
    $where[] = "(o.tracking_code LIKE ? OR o.customer_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= 'ss';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ================= EXCEL EXPORT ================= */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=orders_export.csv");

    $out = fopen("php://output", "w");
    fputcsv($out, [
        'Tracking Code',
        'Customer',
        'Total',
        'Order Status',
        'Payment Status',
        'Date'
    ]);

    $sql = "
        SELECT o.tracking_code, o.customer_name, o.total,
               o.order_status, o.payment_status, o.created_at
        FROM orders o
        $whereSql
        ORDER BY o.created_at ASC
    ";

    $stmt = $connection->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        fputcsv($out, [
            $r['tracking_code'],
            $r['customer_name'],
            $r['total'],
            ucfirst($r['order_status']),
            ucfirst($r['payment_status']),
            date('Y-m-d', strtotime($r['created_at']))
        ]);
    }
    fclose($out);
    exit;
}

/* ================= COUNT TOTAL ================= */
$countSql = "
    SELECT COUNT(*) AS total
    FROM orders o
    $whereSql
";
$countStmt = $connection->prepare($countSql);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalOrders = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages  = ceil($totalOrders / $limit);

/* ================= FETCH ORDERS ================= */
$sql = "
SELECT o.id, o.tracking_code, o.customer_name, o.total,
       o.order_status, o.payment_status, o.created_at
FROM orders o
{$whereSql}
ORDER BY o.created_at DESC
LIMIT $limit OFFSET $offset
";

$stmt = $connection->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result();

/* ================= BADGES ================= */
function orderBadge($status) {
    return match ($status) {
        'pending'    => 'warning',
        'processing' => 'info',
        'shipped'    => 'primary',
        'delivered'  => 'success',
        'cancelled'  => 'secondary',
        default      => 'dark'
    };
}

function paymentBadge($status) {
    return match ($status) {
        'paid'    => 'success',
        'pending' => 'warning',
        'failed'  => 'danger',
        default   => 'dark'
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Orders</title>
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

<h4 class="fw-semibold mb-3">Orders</h4>

<!-- FILTER + SEARCH -->
<form method="get" class="card shadow-sm p-3 mb-3">
<div class="row g-3 align-items-end">

<div class="col-md-2">
<label class="form-label">Status</label>
<select name="status" class="form-select">
<option value="all">All</option>
<?php foreach(['pending','processing','shipped','delivered','cancelled'] as $s): ?>
<option value="<?= $s ?>" <?= $status==$s?'selected':'' ?>>
<?= ucfirst($s) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2">
<label class="form-label">From</label>
<input type="date" name="from" class="form-control" value="<?= $dateFrom ?>">
</div>

<div class="col-md-2">
<label class="form-label">To</label>
<input type="date" name="to" class="form-control" value="<?= $dateTo ?>">
</div>

<div class="col-md-3">
<label class="form-label">Search</label>
<input type="text" name="q" class="form-control"
placeholder="Order / Customer" value="<?= htmlspecialchars($search) ?>">
</div>

<div class="col-md-3 d-flex gap-2">
<button class="btn btn-dark rounded-pill px-4">
<i class="fa fa-search"></i> Filter
</button>

<a href="?export=excel&<?= http_build_query($_GET) ?>"
class="btn btn-success rounded-pill px-4">
<i class="fa fa-file-excel"></i> Excel
</a>
</div>

</div>
</form>

<!-- TABLE -->
<div class="card shadow-sm">
<div class="card-body table-responsive p-0">

<table class="table align-middle table-hover mb-0">
<thead class="table-light">
<tr>
<th>#</th>
<th>Order</th>
<th>Customer</th>
<th>Total</th>
<th>Status</th>
<th>Payment</th>
<th>Date</th>
<th class="text-end">Action</th>
</tr>
</thead>
<tbody>

<?php if ($orders->num_rows): ?>
<?php while($o=$orders->fetch_assoc()): ?>
<tr>
<td>#<?= $o['id'] ?></td>
<td class="fw-semibold"><?= $o['tracking_code'] ?></td>
<td><?= htmlspecialchars($o['customer_name']) ?></td>
<td>Rs.<?= number_format($o['total'],2) ?></td>
<td><span class="badge bg-<?= orderBadge($o['order_status']) ?>">
<?= ucfirst($o['order_status']) ?></span></td>
<td><span class="badge bg-<?= paymentBadge($o['payment_status']) ?>">
<?= ucfirst($o['payment_status']) ?></span></td>
<td><?= date('d M Y', strtotime($o['created_at'])) ?></td>

<td class="text-end">

<a href="order-view.php?id=<?= $o['id'] ?>"
   class="btn btn-sm btn-outline-primary me-1"
   title="View Order">
   <i class="fa fa-eye"></i>
</a>

<a href="invoice.php?id=<?= $o['id'] ?>"
   class="btn btn-sm btn-outline-success"
   title="View Invoice">
   <i class="fa fa-file-invoice"></i>
</a>

</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="8" class="text-center text-muted py-4">
No orders found
</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>

<?php if ($totalPages > 1): ?>
<div class="p-3">
<ul class="pagination pagination-sm mb-0">
<?php for($p=1;$p<=$totalPages;$p++): ?>
<li class="page-item <?= $p==$page?'active':'' ?>">
<a class="page-link"
href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>">
<?= $p ?>
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
