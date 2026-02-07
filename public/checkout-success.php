<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customer = $_SESSION['customer'] ?? null;


require_once __DIR__ . '/../includes/db.php';

/* ================= GET ORDER ================= */
$tracking = $_GET['track'] ?? null;

if (!$tracking) {
    die('<div class="container py-5 text-center">Invalid access</div>');
}

/* FETCH ORDER */
$stmt = $connection->prepare("
    SELECT *
    FROM orders
    WHERE tracking_code = ?
    LIMIT 1
");
$stmt->bind_param("s", $tracking);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die('<div class="container py-5 text-center">Order not found</div>');
}

/* FETCH ORDER ITEMS */
$itemsStmt = $connection->prepare("
    SELECT 
        oi.qty,
        oi.price,
        p.name
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$itemsStmt->bind_param("i", $order['id']);
$itemsStmt->execute();
$items = $itemsStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Successful</title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

<style>
@media print {
  .no-print { display:none; }
}
.receipt-box {
  max-width: 700px;
  margin: auto;
}
</style>
</head>
<body>

<?php include 'layout/header.php'; ?>

<div class="container py-5">

<div class="card receipt-box shadow p-5">

    <div class="text-center mb-4">
        <h2 class="fw-bold text-success">Thank You!</h2>
        <p class="text-muted mb-1">Your order has been placed successfully</p>
        <span class="badge bg-dark fs-6">
            Tracking ID: <?= htmlspecialchars($order['tracking_code']) ?>
        </span>
    </div>

    <hr>

    <h6 class="fw-bold mb-3">Customer Details</h6>
    <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
    <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
    <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?></p>
    <p class="mb-3"><strong>District:</strong> <?= htmlspecialchars($order['district']) ?></p>

    <hr>

    <h6 class="fw-bold mb-3">Order Summary</h6>

    <?php while ($i = $items->fetch_assoc()): ?>
        <div class="d-flex justify-content-between small mb-2">
            <span><?= htmlspecialchars($i['name']) ?> × <?= $i['qty'] ?></span>
            <strong>Rs.<?= number_format($i['price'] * $i['qty'],2) ?></strong>
        </div>
    <?php endwhile; ?>

    <hr>

    <div class="d-flex justify-content-between">
        <span>Subtotal</span>
        <strong>Rs.<?= number_format($order['subtotal'],2) ?></strong>
    </div>

    <div class="d-flex justify-content-between">
        <span>Delivery</span>
        <strong>Rs.<?= number_format($order['delivery_fee'],2) ?></strong>
    </div>

    <div class="d-flex justify-content-between fs-5 fw-bold mt-2">
        <span>Total</span>
        <span>Rs.<?= number_format($order['total'],2) ?></span>
    </div>

    <hr>

    <div class="alert alert-info small mt-3">
        Order Status: <strong><?= ucfirst($order['order_status']) ?></strong><br>
        Payment Method: <strong><?= strtoupper($order['payment_method']) ?></strong>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4">
            🖨 Print Invoice
        </button>

        <a href="track-order.php" class="btn btn-dark rounded-pill px-4 ms-2">
            Track Order
        </a>

        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 ms-2">
            Back to Home
        </a>
    </div>

</div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
