<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireStockKeeper(); // admin + stock keeper

$role = $_SESSION['user_role'];

/* ======================
   KPI DATA
====================== */

// PRODUCTS
$totalProducts = (int)$connection->query("
    SELECT COUNT(*) total FROM products
")->fetch_assoc()['total'];

// VARIANTS
$totalVariants = (int)$connection->query("
    SELECT COUNT(*) total FROM product_variants
")->fetch_assoc()['total'];

// TOTAL STOCK
$totalStock = (int)$connection->query("
    SELECT COALESCE(SUM(stock),0) total FROM product_variants
")->fetch_assoc()['total'];

// AVAILABLE STOCK
$availableStock = (int)$connection->query("
    SELECT COALESCE(SUM(stock - reserved_stock),0) total
    FROM product_variants
")->fetch_assoc()['total'];

// LOW STOCK
$lowStock = (int)$connection->query("
    SELECT COUNT(*) total
    FROM product_variants
    WHERE stock < 5
")->fetch_assoc()['total'];

// OUT OF STOCK
$outOfStock = (int)$connection->query("
    SELECT COUNT(*) total
    FROM product_variants
    WHERE stock <= 0
")->fetch_assoc()['total'];

// ORDERS
$totalOrders = (int)$connection->query("
    SELECT COUNT(*) total FROM orders
")->fetch_assoc()['total'];

$pendingOrders = (int)$connection->query("
    SELECT COUNT(*) total FROM orders WHERE order_status='pending'
")->fetch_assoc()['total'];

// SALES
$totalSales = (float)$connection->query("
    SELECT COALESCE(SUM(total),0) total
    FROM orders
    WHERE order_status='delivered'
")->fetch_assoc()['total'];

// CUSTOMERS
$totalCustomers = (int)$connection->query("
    SELECT COUNT(*) total FROM customers
")->fetch_assoc()['total'];

/* ======================
   CHART DATA
====================== */

// Sales last 7 days (delivered only) - always show 7 days
$salesDayKeys   = []; // Y-m-d
$salesDayLabels = []; // Mon, Tue...
$salesMap       = []; // date => amount

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $salesDayKeys[] = $d;
    $salesDayLabels[] = date('D', strtotime($d));
    $salesMap[$d] = 0;
}

$res = $connection->query("
    SELECT DATE(created_at) AS d, COALESCE(SUM(total),0) AS amt
    FROM orders
    WHERE order_status = 'delivered'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      AND created_at <  DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
");

while ($r = $res->fetch_assoc()) {
    $salesMap[$r['d']] = (float)$r['amt'];
}

$salesAmounts = array_values($salesMap);

// Order status chart (all time)
$statusLabels = [];
$statusCounts = [];
$res = $connection->query("
    SELECT order_status, COUNT(*) total
    FROM orders
    GROUP BY order_status
");
while ($r = $res->fetch_assoc()) {
    $statusLabels[] = ucfirst($r['order_status']);
    $statusCounts[] = (int)$r['total'];
}

// Recent orders
$recentOrders = $connection->query("
    SELECT id, tracking_code, total, order_status, created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 6
");

/* ======================
   KPI CARD HELPER
====================== */
function kpiCard($title, $value, $icon, $color) {
    echo "
    <div class='col-xl-3 col-md-6'>
        <div class='card shadow-sm border-0'>
            <div class='card-body d-flex justify-content-between align-items-center'>
                <div>
                    <h6 class='text-muted'>$title</h6>
                    <h3 class='fw-bold'>$value</h3>
                </div>
                <div class='text-$color fs-2'>
                    <i class='fa $icon'></i>
                </div>
            </div>
        </div>
    </div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>

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

<h4 class="fw-semibold mb-4">Dashboard</h4>

<!-- ================= KPI SECTION ================= -->

<?php if ($role === 'admin'): ?>
<div class="row g-4 mb-4">
<?php
kpiCard('Total Products', $totalProducts, 'fa-box', 'primary');
kpiCard('Total Variants', $totalVariants, 'fa-layer-group', 'info');
kpiCard('Total Stock', $totalStock, 'fa-boxes-stacked', 'success');
kpiCard('Low Stock Items', $lowStock, 'fa-triangle-exclamation', 'danger');

kpiCard('Total Orders', $totalOrders, 'fa-shopping-cart', 'dark');
kpiCard('Pending Orders', $pendingOrders, 'fa-clock', 'warning');
kpiCard('Total Sales', 'LKR '.number_format($totalSales,2), 'fa-coins', 'success');
kpiCard('Total Customers', $totalCustomers, 'fa-users', 'secondary');
?>
</div>
<?php endif; ?>

<?php if ($role === 'stock_keeper'): ?>
<div class="row g-4 mb-4">
<?php
kpiCard('Total Variants', $totalVariants, 'fa-layer-group', 'info');
kpiCard('Available Stock', $availableStock, 'fa-warehouse', 'success');
kpiCard('Low Stock Items', $lowStock, 'fa-triangle-exclamation', 'warning');
kpiCard('Out of Stock', $outOfStock, 'fa-ban', 'danger');
?>
</div>
<?php endif; ?>

<!-- ================= CHARTS ================= -->
<?php if ($role === 'admin'): ?>
<div class="row g-4 mb-4">

<div class="col-lg-8">
<div class="card shadow-sm">
<div class="card-body">
<h6 class="fw-semibold mb-3">Sales – Last 7 Days (Delivered)</h6>
<canvas id="salesChart" height="110"></canvas>
</div>
</div>
</div>

<div class="col-lg-4">
<div class="card shadow-sm">
<div class="card-body">
<h6 class="fw-semibold mb-3">Order Status</h6>
<canvas id="statusChart" height="230"></canvas>
</div>
</div>
</div>

</div>
<?php endif; ?>

<!-- ================= RECENT ORDERS ================= -->
<div class="card shadow-sm">
<div class="card-body">
<h6 class="fw-semibold mb-3">Recent Orders</h6>

<table class="table table-hover align-middle">
<thead class="table-light">
<tr>
<th>Order</th>
<th>Date</th>
<th>Status</th>
<th>Total</th>
<th></th>
</tr>
</thead>
<tbody>
<?php while ($o = $recentOrders->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($o['tracking_code']) ?></td>
<td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
<td>
<span class="badge bg-secondary">
<?= ucfirst($o['order_status']) ?>
</span>
</td>
<td>LKR <?= number_format($o['total'],2) ?></td>
<td>
<a href="order-view.php?id=<?= $o['id'] ?>"
   class="btn btn-sm btn-outline-dark rounded-pill">
View
</a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</div>
</div>

</div>

<?php include 'layout/footer.php'; ?>
</div>

<script src="assets/js/chart.min.js"></script>
<script>
<?php if ($role === 'admin'): ?>

new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($salesDayLabels) ?>,
        datasets: [{
            label: 'Sales (LKR)',
            data: <?= json_encode($salesAmounts) ?>,
            tension: 0.35,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusCounts) ?>,
            backgroundColor: ['#ffc107','#0dcaf0','#0d6efd','#198754','#6c757d','#dc3545']
        }]
    }
});

<?php endif; ?>
</script>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
