<?php
require_once 'auth-check.php';
require_once 'role-check.php';
requireAdmin();
require_once '../includes/db.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) die("Invalid order");

/* ================= FETCH ORDER ================= */
$stmt = $connection->prepare("
    SELECT 
        o.*,
        c.full_name,
        c.email,
        c.phone
    FROM orders o
    LEFT JOIN customers c ON c.id = o.customer_id
    WHERE o.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) die("Order not found");

/* ================= FETCH ITEMS ================= */
$items = $connection->prepare("
    SELECT 
        oi.qty,
        oi.price,
        p.name,
        s.size_label,
        col.name AS color_name
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    LEFT JOIN product_variants pv ON pv.id = oi.variant_id
    LEFT JOIN sizes s ON s.id = pv.size_id
    LEFT JOIN colors col ON col.id = pv.color_id
    WHERE oi.order_id = ?
");
$items->bind_param("i", $orderId);
$items->execute();
$itemRes = $items->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice #<?= $orderId ?></title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet"
 href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

<style>
body {
    background: #f6f7f9;
}
.invoice-box {
    max-width: 900px;
    margin: auto;
    background: #fff;
    padding: 40px;
    border-radius: 12px;
}
.invoice-title {
    font-size: 28px;
    font-weight: 700;
}
.invoice-meta {
    font-size: 14px;
    color: #666;
}
.table th {
    background: #f1f3f5;
}
@media print {
    body { background: #fff; }
    .no-print { display: none; }
}
</style>
</head>

<body>

<div class="container py-5">

<div class="invoice-box shadow-sm">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">

<div>
<h2 class="invoice-title mb-1">INVOICE</h2>
<div class="invoice-meta">
Invoice #: <?= $order['id'] ?><br>
Tracking Code: <?= htmlspecialchars($order['tracking_code']) ?><br>
Date: <?= date('d M Y', strtotime($order['created_at'])) ?>
</div>
</div>

<div class="text-end">
<h5 class="fw-bold mb-1">Pino Shoes</h5>
<div class="small text-muted">
131/A, Kandakapapu Junction, Kirillawala<br>
pinoshoe@gmail.com<br>
+94 78 654 9356
</div>
</div>

</div>

<hr>

<!-- CUSTOMER INFO -->
<div class="row mb-4">

<div class="col-md-6">
<h6 class="fw-bold">Billed To</h6>
<p class="mb-1"><?= htmlspecialchars($order['customer_name']) ?></p>
<p class="mb-1"><?= htmlspecialchars($order['email']) ?></p>
<p class="mb-1"><?= htmlspecialchars($order['phone']) ?></p>
</div>

<div class="col-md-6 text-md-end">
<h6 class="fw-bold">Delivery Address</h6>
<p class="mb-1">
<?= nl2br(htmlspecialchars(
    $order['address'] . "\n" .
    $order['city'] . "\n" .
    $order['district'] . " " . $order['postcode']
)) ?>
</p>
</div>

</div>

<!-- ITEMS -->
<table class="table table-bordered align-middle">
<thead>
<tr>
<th>#</th>
<th>Product</th>
<th>Variant</th>
<th class="text-center">Qty</th>
<th class="text-end">Price</th>
<th class="text-end">Total</th>
</tr>
</thead>

<tbody>
<?php
$i = 1;
while ($row = $itemRes->fetch_assoc()):
$lineTotal = $row['price'] * $row['qty'];
?>
<tr>
<td><?= $i++ ?></td>
<td><?= htmlspecialchars($row['name']) ?></td>
<td>
Size: <?= htmlspecialchars($row['size_label'] ?? '-') ?><br>
Color: <?= htmlspecialchars($row['color_name'] ?? '-') ?>
</td>
<td class="text-center"><?= $row['qty'] ?></td>
<td class="text-end">Rs. <?= number_format($row['price'],2) ?></td>
<td class="text-end fw-semibold">
Rs. <?= number_format($lineTotal,2) ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<!-- TOTALS -->
<div class="row justify-content-end mt-4">

<div class="col-md-5">
<table class="table">
<tr>
<th>Subtotal</th>
<td class="text-end">Rs. <?= number_format($order['subtotal'],2) ?></td>
</tr>
<tr>
<th>Delivery Fee</th>
<td class="text-end">Rs. <?= number_format($order['delivery_fee'],2) ?></td>
</tr>
<tr class="fw-bold">
<th>Total</th>
<td class="text-end">Rs. <?= number_format($order['total'],2) ?></td>
</tr>
</table>
</div>

</div>

<hr>

<!-- FOOTER -->
<div class="row">

<div class="col-md-6">
<p class="mb-1"><strong>Payment Method:</strong>
<?= strtoupper($order['payment_method']) ?></p>
<p class="mb-1"><strong>Payment Status:</strong>
<?= ucfirst($order['payment_status']) ?></p>
<p class="mb-1"><strong>Order Status:</strong>
<?= ucfirst($order['order_status']) ?></p>
</div>

<div class="col-md-6 text-md-end">
<p class="small text-muted">
Thank you for shopping with us.<br>
This is a system generated invoice.
</p>
</div>

</div>

<!-- ACTIONS -->
<div class="text-center mt-4 no-print">
<button onclick="window.print()"
        class="btn btn-dark rounded-pill px-5">
<i class="bi bi-printer me-2"></i>Print Invoice
</button>
</div>

</div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
