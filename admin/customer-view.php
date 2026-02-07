<?php
require_once 'auth-check.php';
require_once 'role-check.php';
requireAdmin();
require_once '../includes/db.php';

$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($customerId <= 0) {
    die("Invalid customer");
}

/* ================= FETCH CUSTOMER ================= */
$stmt = $connection->prepare("
    SELECT 
        c.id,
        c.full_name,
        c.email,
        c.phone,
        c.status,
        c.created_at,
        COUNT(o.id) AS total_orders,
        COALESCE(SUM(o.total),0) AS total_spent
    FROM customers c
    LEFT JOIN orders o ON o.customer_id = c.id
    WHERE c.id = ?
    GROUP BY c.id
    LIMIT 1
");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) {
    die("Customer not found");
}

/* ================= FETCH ORDERS ================= */
$ordersStmt = $connection->prepare("
    SELECT 
        id,
        tracking_code,
        total,
        payment_method,
        payment_status,
        order_status,
        created_at
    FROM orders
    WHERE customer_id = ?
    ORDER BY created_at DESC
");
$ordersStmt->bind_param("i", $customerId);
$ordersStmt->execute();
$orders = $ordersStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Details</title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/admin-dashboard.css">
<link rel="stylesheet"
 href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
 <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
      
</head>

<body>

<?php include 'layout/sidebar.php'; ?>

<div class="app-content">
<?php include 'layout/header.php'; ?>

<div class="container-fluid p-4">

<a href="customers.php"
   class="btn btn-outline-secondary rounded-pill mb-3">
   ← Back to Customers
</a>

<!-- ================= CUSTOMER OVERVIEW ================= -->
<div class="card mb-4">
<div class="card-body">

<div class="row g-4 align-items-center">

<div class="col-md-8">
<h4 class="fw-bold mb-1"><?= htmlspecialchars($customer['full_name']) ?></h4>
<p class="mb-1 text-muted"><?= htmlspecialchars($customer['email']) ?></p>
<p class="mb-1"><?= htmlspecialchars($customer['phone'] ?? '-') ?></p>

<span class="badge <?= $customer['status']=='active'?'bg-success':'bg-danger' ?>">
    <?= ucfirst($customer['status']) ?>
</span>

<p class="mt-2 small text-muted">
Joined: <?= date('d M Y', strtotime($customer['created_at'])) ?>
</p>
</div>

<div class="col-md-4 text-md-end">
<div class="mb-2">
<strong>Total Orders:</strong>
<span class="badge bg-dark"><?= $customer['total_orders'] ?></span>
</div>

<div class="mb-2">
<strong>Total Spent:</strong>
Rs. <?= number_format($customer['total_spent'],2) ?>
</div>

</div>

</div>
</div>
</div>

<!-- ================= ORDERS ================= -->
<div class="card shadow-sm">
<div class="card-header bg-white">
<h5 class="fw-semibold mb-0">Orders</h5>
</div>

<div class="card-body p-0">
<table class="table table-hover align-middle mb-0">
<thead class="table-light">
<tr>
    <th>#</th>
    <th>Tracking</th>
    <th>Total</th>
    <th>Payment</th>
    <th>Status</th>
    <th>Date</th>
    <th class="text-end">Action</th>
</tr>
</thead>

<tbody>
<?php if ($orders->num_rows): ?>
<?php while ($o = $orders->fetch_assoc()): ?>
<tr>
<td>#<?= $o['id'] ?></td>

<td>
<code><?= htmlspecialchars($o['tracking_code']) ?></code>
</td>

<td>Rs. <?= number_format($o['total'],2) ?></td>

<td>
<small><?= ucfirst($o['payment_method']) ?></small><br>
<span class="badge <?= $o['payment_status']=='paid'?'bg-success':'bg-warning' ?>">
<?= ucfirst($o['payment_status']) ?>
</span>
</td>

<td>
<span class="badge bg-info">
<?= ucfirst($o['order_status']) ?>
</span>
</td>

<td>
<?= date('d M Y', strtotime($o['created_at'])) ?>
</td>

<td class="text-end">
<a href="order-view.php?id=<?= $o['id'] ?>"
   class="btn btn-sm btn-outline-primary rounded-pill">
View
</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="7" class="text-center text-muted py-4">
No orders found for this customer.
</td>
</tr>
<?php endif; ?>
</tbody>

</table>
</div>
</div>

</div>

<?php include 'layout/footer.php'; ?>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
