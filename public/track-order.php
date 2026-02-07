<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

$order = null;
$items = null;
$timeline = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tracking = trim($_POST['tracking_code']);

    if (!$tracking) {
        $error = "Please enter your tracking ID.";
    } else {

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
            $error = "No order found for this tracking ID.";
        } else {

            /* FETCH ITEMS */
            $itemsStmt = $connection->prepare("
                SELECT p.name, oi.qty, oi.price
                FROM order_items oi
                JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = ?
            ");
            $itemsStmt->bind_param("i", $order['id']);
            $itemsStmt->execute();
            $items = $itemsStmt->get_result();

            /* FETCH TIMELINE */
            $timelineStmt = $connection->prepare("
                SELECT status, note, reason, created_at
                FROM order_status_history
                WHERE order_id = ?
                ORDER BY created_at ASC
            ");
            $timelineStmt->bind_param("i", $order['id']);
            $timelineStmt->execute();
            $timeline = $timelineStmt->get_result();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Track Order</title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

<style>
.track-box {
    max-width: 680px;
    margin: auto;
}
.status-pill {
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
}
.status-pending { background:#fff3cd; color:#856404; }
.status-processing { background:#cce5ff; color:#004085; }
.status-shipped { background:#d4edda; color:#155724; }
.status-delivered { background:#e2e3e5; color:#383d41; }
.status-cancelled { background:#f8d7da; color:#721c24; }

.timeline {
    border-left: 3px solid #dee2e6;
    padding-left: 15px;
}
.timeline-item {
    position: relative;
    margin-bottom: 15px;
}
.timeline-item::before {
    content:'';
    width: 10px;
    height: 10px;
    background:#6c757d;
    border-radius:50%;
    position:absolute;
    left:-6px;
    top:4px;
}
</style>
</head>

<body class="d-flex flex-column min-vh-100">

<?php include 'layout/header.php'; ?>

<div class="flex-grow-1 container py-5">

<div class="card track-box shadow p-4">

<h3 class="fw-bold text-center mb-2">Track Your Order</h3>
<p class="text-muted text-center mb-4">
Enter your tracking ID to see order status
</p>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" class="d-flex gap-2 mb-4">
<input type="text"
       name="tracking_code"
       class="form-control"
       placeholder="e.g. PS-20240101-AB12CD"
       value="<?= htmlspecialchars($_POST['tracking_code'] ?? '') ?>"
       required>
<button class="btn btn-dark px-4">Track</button>
</form>

<?php if ($order): ?>

<hr>

<div class="mb-3">
<strong>Tracking ID:</strong>
<?= htmlspecialchars($order['tracking_code']) ?>
</div>

<div class="mb-3">
<strong>Status:</strong>
<span class="status-pill status-<?= $order['order_status'] ?>">
<?= ucfirst($order['order_status']) ?>
</span>
</div>

<?php if ($order['order_status'] === 'cancelled' && !empty($order['cancel_reason'])): ?>
<div class="alert alert-danger small">
<strong>Cancellation Reason:</strong><br>
<?= nl2br(htmlspecialchars($order['cancel_reason'])) ?>
</div>
<?php endif; ?>

<div class="mb-3">
<strong>Delivery Address:</strong><br>
<?= htmlspecialchars($order['address']) ?>,
<?= htmlspecialchars($order['city']) ?>,
<?= htmlspecialchars($order['district']) ?>
</div>

<hr>

<h6 class="fw-bold mb-3">Order Items</h6>

<?php while ($i = $items->fetch_assoc()): ?>
<div class="d-flex justify-content-between small mb-2">
<span><?= htmlspecialchars($i['name']) ?> × <?= (int)$i['qty'] ?></span>
<strong>Rs.<?= number_format($i['price'] * $i['qty'], 2) ?></strong>
</div>
<?php endwhile; ?>

<hr>

<div class="d-flex justify-content-between">
<span>Subtotal</span>
<strong>Rs.<?= number_format($order['subtotal'], 2) ?></strong>
</div>

<div class="d-flex justify-content-between">
<span>Delivery</span>
<strong>Rs.<?= number_format($order['delivery_fee'], 2) ?></strong>
</div>

<div class="d-flex justify-content-between fw-bold fs-5 mt-2">
<span>Total</span>
<span>Rs.<?= number_format($order['total'], 2) ?></span>
</div>

<hr>

<h6 class="fw-bold mb-3">Order Timeline</h6>

<div class="timeline">
<?php while ($t = $timeline->fetch_assoc()): ?>
<div class="timeline-item">
<strong><?= ucfirst($t['status']) ?></strong>
<div class="text-muted small">
<?= date('d M Y, h:i A', strtotime($t['created_at'])) ?>
</div>

<?php if (!empty($t['reason'])): ?>
<div class="text-danger small mt-1">
Reason: <?= htmlspecialchars($t['reason']) ?>
</div>
<?php endif; ?>
</div>
<?php endwhile; ?>
</div>

<?php endif; ?>

</div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
