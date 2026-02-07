<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

/* ================= AUTH CHECK ================= */
if (empty($_SESSION['customer'])) {
    header("Location: customer-auth.php");
    exit;
}

$customer   = $_SESSION['customer'];
$customerId = (int)$customer['id'];

/* ================= FLASH ================= */
$successMsg = $_SESSION['success'] ?? '';
$errorMsg   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

/* ================= CANCEL ORDER ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {

    $orderId = (int)$_POST['order_id'];
    $reason  = trim($_POST['cancel_reason']);

    if ($orderId <= 0 || $reason === '') {
        $_SESSION['error'] = "Cancellation reason is required.";
        header("Location: my-orders.php");
        exit;
    }

    /* Verify ownership + status */
    $check = $connection->prepare("
        SELECT id, order_status, payment_status
        FROM orders
        WHERE id = ? AND customer_id = ?
        LIMIT 1
    ");
    $check->bind_param("ii", $orderId, $customerId);
    $check->execute();
    $order = $check->get_result()->fetch_assoc();

    if (
        !$order ||
        $order['order_status'] !== 'pending' ||
        $order['payment_status'] !== 'pending'
    ) {
        $_SESSION['error'] = "This order cannot be cancelled.";
        header("Location: my-orders.php");
        exit;
    }

    $connection->begin_transaction();

    try {
        /* Release reserved stock */
        $items = $connection->prepare("
            SELECT variant_id, qty
            FROM order_items
            WHERE order_id = ?
        ");
        $items->bind_param("i", $orderId);
        $items->execute();
        $res = $items->get_result();

        $release = $connection->prepare("
            UPDATE product_variants
            SET reserved_stock = reserved_stock - ?
            WHERE id = ? AND reserved_stock >= ?
        ");

        while ($row = $res->fetch_assoc()) {
            $qty = (int)$row['qty'];
            $vid = (int)$row['variant_id'];

            $release->bind_param("iii", $qty, $vid, $qty);
            $release->execute();
        }

        /* Update order */
        $upd = $connection->prepare("
            UPDATE orders
            SET order_status = 'cancelled',
                cancel_reason = ?
            WHERE id = ?
        ");
        $upd->bind_param("si", $reason, $orderId);
        $upd->execute();

        /* History */
        $hist = $connection->prepare("
            INSERT INTO order_status_history (order_id, status, note)
            VALUES (?, 'cancelled', ?)
        ");
        $note = "Cancelled by customer: ".$reason;
        $hist->bind_param("is", $orderId, $note);
        $hist->execute();

        $connection->commit();
        $_SESSION['success'] = "Order cancelled successfully.";

    } catch (Exception $e) {
        $connection->rollback();
        $_SESSION['error'] = "Failed to cancel order.";
    }

    header("Location: my-orders.php");
    exit;
}

/* ================= FETCH ORDERS ================= */
$stmt = $connection->prepare("
    SELECT id, tracking_code, total, payment_method,
           payment_status, order_status, created_at
    FROM orders
    WHERE customer_id = ?
    ORDER BY id DESC
");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$orders = $stmt->get_result();

/* ================= BADGE ================= */
function statusBadge($status) {
    return match ($status) {
        'pending'     => 'warning',
        'processing'  => 'info',
        'shipped'     => 'primary',
        'delivered'   => 'success',
        'cancelled'   => 'danger',
        default       => 'secondary'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet"
 href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include 'layout/header.php'; ?>

<div class="flex-grow-1 container py-5">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">My Orders</h3>
    <a href="shop.php" class="btn btn-outline-dark rounded-pill px-4">
        Continue Shopping
    </a>
</div>

<?php if ($successMsg): ?>
<div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<?php if ($orders->num_rows === 0): ?>
<div class="alert alert-info text-center">
    You haven’t placed any orders yet.
</div>
<?php else: ?>

<div class="table-responsive">
<table class="table align-middle table-hover bg-white shadow-sm rounded">
<thead class="table-light">
<tr>
<th>Order</th>
<th>Date</th>
<th>Total</th>
<th>Payment</th>
<th>Status</th>
<th class="text-end">Action</th>
</tr>
</thead>
<tbody>

<?php while ($o = $orders->fetch_assoc()): ?>
<tr>
<td><strong>#<?= htmlspecialchars($o['tracking_code']) ?></strong></td>
<td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
<td>Rs. <?= number_format($o['total'],2) ?></td>

<td>
<?= ucfirst($o['payment_method']) ?><br>
<small class="text-muted"><?= ucfirst($o['payment_status']) ?></small>
</td>

<td>
<span class="badge bg-<?= statusBadge($o['order_status']) ?>">
<?= ucfirst($o['order_status']) ?>
</span>
</td>

<td class="text-end">

<a href="track-order.php?code=<?= urlencode($o['tracking_code']) ?>"
   class="btn btn-sm btn-outline-dark rounded-pill">
<i class="bi bi-geo-alt"></i>
</a>

<?php if ($o['order_status']==='pending' && $o['payment_status']==='pending'): ?>
<button class="btn btn-sm btn-outline-danger rounded-pill"
        data-bs-toggle="modal"
        data-bs-target="#cancelModal<?= $o['id'] ?>">
<i class="bi bi-x-circle"></i>
</button>
<?php endif; ?>

</td>
</tr>

<!-- CANCEL MODAL -->
<div class="modal fade" id="cancelModal<?= $o['id'] ?>" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">

<form method="post">
<input type="hidden" name="cancel_order" value="1">
<input type="hidden" name="order_id" value="<?= $o['id'] ?>">

<div class="modal-header">
<h5 class="modal-title text-danger">Cancel Order</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<p class="mb-2">
Are you sure you want to cancel this order?
</p>

<div class="mb-3">
<label class="form-label">Reason for cancellation</label>
<textarea name="cancel_reason"
          class="form-control"
          rows="3"
          required></textarea>
</div>
</div>

<div class="modal-footer">
<button type="button" class="btn btn-secondary"
        data-bs-dismiss="modal">
No
</button>
<button type="submit" class="btn btn-danger">
Yes, Cancel Order
</button>
</div>

</form>
</div>
</div>
</div>

<?php endwhile; ?>

</tbody>
</table>
</div>

<?php endif; ?>

</div>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
