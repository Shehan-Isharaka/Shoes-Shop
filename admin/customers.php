<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireAdmin();

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
    $where = "WHERE c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
    $types  = "sss";
}

/* =========================
   EXCEL DOWNLOAD
========================= */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=customers_export.csv");

    $out = fopen("php://output", "w");

    fputcsv($out, [
        'Name',
        'Email',
        'Phone',
        'Total Orders',
        'Status',
        'Joined Date'
    ]);

    $sql = "
        SELECT 
            c.full_name,
            c.email,
            c.phone,
            c.status,
            c.created_at,
            COUNT(o.id) AS total_orders
        FROM customers c
        LEFT JOIN orders o ON o.customer_id = c.id
        $where
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ";

    $stmt = $connection->prepare($sql);
    if ($search !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        fputcsv($out, [
            $r['full_name'],
            $r['email'],
            $r['phone'],
            $r['total_orders'],
            ucfirst($r['status']),
            date('Y-m-d', strtotime($r['created_at']))
        ]);
    }

    fclose($out);
    exit;
}

/* =========================
   COUNT TOTAL
========================= */
$countSql = "
    SELECT COUNT(DISTINCT c.id) AS total
    FROM customers c
    LEFT JOIN orders o ON o.customer_id = c.id
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
   FETCH CUSTOMERS
========================= */
$sql = "
    SELECT 
        c.id,
        c.full_name,
        c.email,
        c.phone,
        c.status,
        c.created_at,
        COUNT(o.id) AS total_orders
    FROM customers c
    LEFT JOIN orders o ON o.customer_id = c.id
    $where
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $connection->prepare($sql);
if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$customers = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customers</title>

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
    <h4 class="fw-semibold mb-0">Customers</h4>

    <div class="d-flex gap-2">
        <form method="get" class="d-flex">
            <input type="text" name="q"
                   value="<?= htmlspecialchars($search) ?>"
                   class="form-control form-control-sm me-2"
                   placeholder="Search name / email / phone">
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

<table class="table align-middle table-hover mb-0">
<thead class="table-light">
<tr>
    <th>#</th>
    <th>Name</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Orders</th>
    <th>Status</th>
    <th>Joined</th>
    <th class="text-end">Action</th>
</tr>
</thead>

<tbody>
<?php if ($customers->num_rows > 0): ?>
<?php $i = ($page - 1) * $limit + 1; ?>
<?php while ($c = $customers->fetch_assoc()): ?>
<tr>
<td><?= $i++ ?></td>
<td class="fw-semibold"><?= htmlspecialchars($c['full_name']) ?></td>
<td><?= htmlspecialchars($c['email']) ?></td>
<td><?= htmlspecialchars($c['phone']) ?></td>

<td>
<span class="badge bg-info"><?= (int)$c['total_orders'] ?></span>
</td>

<td>
<?= $c['status'] === 'active'
    ? '<span class="badge bg-success">Active</span>'
    : '<span class="badge bg-danger">Blocked</span>' ?>
</td>

<td><?= date('d M Y', strtotime($c['created_at'])) ?></td>

<td class="text-end">
<a href="customer-view.php?id=<?= $c['id'] ?>"
   class="btn btn-sm btn-outline-dark rounded-pill px-3">
View
</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="8" class="text-center text-muted py-4">
No customers found
</td>
</tr>
<?php endif; ?>
</tbody>
</table>

</div>

<?php if ($totalPages > 1): ?>
<div class="p-3">
<ul class="pagination pagination-sm mb-0">
<?php for ($p=1; $p<=$totalPages; $p++): ?>
<li class="page-item <?= $p==$page?'active':'' ?>">
<a class="page-link"
href="?page=<?= $p ?>&q=<?= urlencode($search) ?>">
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
