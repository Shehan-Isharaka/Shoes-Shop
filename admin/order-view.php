<?php
require_once 'auth-check.php';
require_once 'role-check.php';
requireStockKeeper(); // admin + stock keeper
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) die("Invalid order ID.");

/* ================= FLASH ================= */
$successMsg = $_SESSION['success'] ?? '';
$errorMsg   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

/* ================= UPDATE STATUS (ADMIN ONLY) ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    // Admin only
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        $_SESSION['error'] = "Unauthorized action.";
        header("Location: orders.php");
        exit;
    }

    $orderStatus   = $_POST['order_status'] ?? '';
    $paymentStatus = $_POST['payment_status'] ?? '';
    $cancelReason  = trim($_POST['cancel_reason'] ?? '');

    // Allow only valid values (prevents bad input)
    $allowedOrderStatuses = ['pending','processing','shipped','delivered','cancelled'];
    $allowedPayStatuses   = ['pending','paid','failed'];

    if (!in_array($orderStatus, $allowedOrderStatuses, true)) {
        $_SESSION['error'] = "Invalid order status.";
        header("Location: order-view.php?id=".$orderId);
        exit;
    }
    if (!in_array($paymentStatus, $allowedPayStatuses, true)) {
        $_SESSION['error'] = "Invalid payment status.";
        header("Location: order-view.php?id=".$orderId);
        exit;
    }

    // Cancellation reason required ONLY for cancelled
    if ($orderStatus === 'cancelled' && $cancelReason === '') {
        $_SESSION['error'] = "Cancellation reason is required.";
        header("Location: order-view.php?id=".$orderId);
        exit;
    }

    // For non-cancelled statuses, don't store reason
    if ($orderStatus !== 'cancelled') {
        $cancelReason = null; // will be stored as NULL
    }

    $connection->begin_transaction();

    try {

        /* FETCH CURRENT STATUS (LOCK ROW) */
        $curRes = $connection->query("
            SELECT order_status
            FROM orders
            WHERE id = $orderId
            FOR UPDATE
        ");
        $cur = $curRes ? $curRes->fetch_assoc() : null;

        if (!$cur) {
            throw new Exception("Order not found.");
        }

        /* RELEASE RESERVED STOCK ON CANCEL */
        if ($cur['order_status'] !== 'cancelled' && $orderStatus === 'cancelled') {
            $items = $connection->query("
                SELECT variant_id, qty FROM order_items WHERE order_id = $orderId
            ");
            while ($i = $items->fetch_assoc()) {
                $variantId = (int)$i['variant_id'];
                $qty       = (int)$i['qty'];

                $connection->query("
                    UPDATE product_variants
                    SET reserved_stock = reserved_stock - $qty
                    WHERE id = $variantId
                ");
            }
        }

        /* DEDUCT STOCK ON DELIVERED */
        if ($cur['order_status'] !== 'delivered' && $orderStatus === 'delivered') {
            $items = $connection->query("
                SELECT variant_id, qty FROM order_items WHERE order_id = $orderId
            ");
            while ($i = $items->fetch_assoc()) {
                $variantId = (int)$i['variant_id'];
                $qty       = (int)$i['qty'];

                $connection->query("
                    UPDATE product_variants
                    SET stock = stock - $qty,
                        reserved_stock = reserved_stock - $qty
                    WHERE id = $variantId
                ");
            }
        }

        /* UPDATE ORDER */
        $stmt = $connection->prepare("
            UPDATE orders
            SET order_status = ?,
                payment_status = ?,
                cancel_reason = ?
            WHERE id = ?
        ");
        // NOTE: NULL is OK here; MySQL will store NULL if column allows it
        $stmt->bind_param("sssi", $orderStatus, $paymentStatus, $cancelReason, $orderId);
        $stmt->execute();

        if ($stmt->affected_rows < 0) {
            throw new Exception("Order update failed.");
        }

        /* STATUS HISTORY */
        $note = "Status changed to {$orderStatus}";
        $hist = $connection->prepare("
            INSERT INTO order_status_history (order_id, status, note, reason)
            VALUES (?,?,?,?)
        ");
        $hist->bind_param("isss", $orderId, $orderStatus, $note, $cancelReason);
        $hist->execute();

        $connection->commit();
        $_SESSION['success'] = "Order updated successfully.";

    } catch (Exception $e) {
        $connection->rollback();
        $_SESSION['error'] = "Order update failed. " . $e->getMessage();
    }

    header("Location: order-view.php?id=".$orderId);
    exit;
}

/* ================= FETCH ORDER ================= */
$order = $connection->query("
    SELECT o.*, c.full_name, c.email, c.phone
    FROM orders o
    LEFT JOIN customers c ON c.id = o.customer_id
    WHERE o.id = $orderId
")->fetch_assoc();

if (!$order) die("Order not found.");

/* ================= ITEMS ================= */
$items = $connection->query("
    SELECT oi.*, p.name AS product_name, pv.reserved_stock
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN product_variants pv ON pv.id = oi.variant_id
    WHERE oi.order_id = $orderId
");

/* ================= TIMELINE ================= */
$timeline = $connection->query("
    SELECT status, note, reason, created_at
    FROM order_status_history
    WHERE order_id = $orderId
    ORDER BY created_at ASC
");

/* BADGES */
function badgeOrder($s){return match($s){
'pending'=>'warning','processing'=>'info','shipped'=>'primary',
'delivered'=>'success','cancelled'=>'secondary',default=>'dark'};}
function badgePay($s){return match($s){
'paid'=>'success','pending'=>'warning','failed'=>'danger',default=>'dark'};}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order View</title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/admin-dashboard.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php include 'layout/sidebar.php'; ?>
<div class="app-content">
<?php include 'layout/header.php'; ?>

<div class="container-fluid p-4">

<h4 class="fw-semibold mb-3">
Order #<?= $order['id'] ?>
<span class="text-muted small"> (<?= $order['tracking_code'] ?>)</span>
</h4>

<?php if ($successMsg): ?><div class="alert alert-success"><?= $successMsg ?></div><?php endif; ?>
<?php if ($errorMsg): ?><div class="alert alert-danger"><?= $errorMsg ?></div><?php endif; ?>

<div class="row g-4">

<!-- LEFT -->
<div class="col-lg-8">

<div class="card shadow-sm">
<div class="card-body">

<div class="d-flex justify-content-between mb-3">
<h6 class="fw-bold">Order Items</h6>
<div>
<span class="badge bg-<?= badgeOrder($order['order_status']) ?>">
<?= ucfirst($order['order_status']) ?></span>
<span class="badge bg-<?= badgePay($order['payment_status']) ?>">
<?= ucfirst($order['payment_status']) ?></span>
</div>
</div>

<table class="table">
<thead class="table-light">
<tr>
<th>Product</th>
<th class="text-center">Qty</th>
<th class="text-center">Reserved</th>
<th class="text-end">Price</th>
<th class="text-end">Total</th>
</tr>
</thead>
<tbody>
<?php while ($i = $items->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($i['product_name']) ?></td>
<td class="text-center"><?= $i['qty'] ?></td>
<td class="text-center"><?= $i['reserved_stock'] ?></td>
<td class="text-end">Rs.<?= number_format($i['price'],2) ?></td>
<td class="text-end fw-bold">
Rs.<?= number_format($i['price']*$i['qty'],2) ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<hr>

<div class="row">
<div class="col-6 text-muted">Subtotal</div>
<div class="col-6 text-end">Rs.<?= number_format($order['subtotal'],2) ?></div>

<div class="col-6 text-muted">Delivery</div>
<div class="col-6 text-end">Rs.<?= number_format($order['delivery_fee'],2) ?></div>

<div class="col-6 fw-bold mt-2">Total</div>
<div class="col-6 fw-bold text-end mt-2 fs-5">
Rs.<?= number_format($order['total'],2) ?>
</div>
</div>

</div>
</div>

<!-- TIMELINE -->
<div class="card shadow-sm mt-4">
<div class="card-body">
<h6 class="fw-bold mb-3">Order Timeline</h6>
<ul class="list-group list-group-flush">
<?php while ($t = $timeline->fetch_assoc()): ?>
<li class="list-group-item">
<strong><?= ucfirst($t['status']) ?></strong>
<div class="small text-muted">
<?= date('d M Y, h:i A', strtotime($t['created_at'])) ?>
</div>
<?php if ($t['status']=='cancelled' && $t['reason']): ?>
<div class="text-danger small mt-1">
Reason: <?= htmlspecialchars($t['reason']) ?>
</div>
<?php endif; ?>
</li>
<?php endwhile; ?>
</ul>
</div>
</div>

</div>

<!-- RIGHT -->
<div class="col-lg-4">

<?php if (($_SESSION['user_role'] ?? '')==='admin'): ?>
<div class="card shadow-sm mb-4">
<div class="card-body">
<h6 class="fw-bold mb-3">Customer Details</h6>
<p><strong>Name:</strong> <?= htmlspecialchars($order['full_name']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
<p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
<p><strong>Address:</strong><br>
<?= htmlspecialchars($order['address']) ?><br>
<?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['district']) ?>
</p>
</div>
</div>
<?php endif; ?>

<?php if (($_SESSION['user_role'] ?? '')==='admin'): ?>
<div class="card shadow-sm">
<div class="card-body">
<h6 class="fw-bold mb-3">Update Status</h6>

<form method="post">
<input type="hidden" name="update_status" value="1">

<select name="order_status" id="orderStatus" class="form-select mb-2">
<?php foreach(['pending','processing','shipped','delivered','cancelled'] as $s): ?>
<option value="<?= $s ?>" <?= $order['order_status']===$s?'selected':'' ?>>
<?= ucfirst($s) ?>
</option>
<?php endforeach; ?>
</select>

<select name="payment_status" class="form-select mb-2">
<?php foreach(['pending','paid','failed'] as $p): ?>
<option value="<?= $p ?>" <?= $order['payment_status']===$p?'selected':'' ?>>
<?= ucfirst($p) ?>
</option>
<?php endforeach; ?>
</select>

<!-- Keep it AVAILABLE always -->
<div id="cancelBox" class="mt-2">
<label class="form-label fw-semibold">Cancellation Reason (only for Cancelled)</label>
<textarea name="cancel_reason" id="cancelReason" class="form-control mb-2"
placeholder="Enter cancellation reason (required only when status is Cancelled)"><?= htmlspecialchars($order['cancel_reason'] ?? '') ?></textarea>
</div>

<button class="btn btn-dark w-100">Update</button>
</form>

</div>
</div>
<?php endif; ?>

</div>

</div>
</div>

<?php include 'layout/footer.php'; ?>
</div>

<script>
const statusSel     = document.getElementById('orderStatus');
const cancelReason  = document.getElementById('cancelReason');

function applyCancelRule() {
  const isCancelled = (statusSel.value === 'cancelled');

  // Required ONLY when cancelled
  cancelReason.required = isCancelled;

  // Optional: visual helper
  cancelReason.placeholder = isCancelled
    ? "Cancellation reason (required)"
    : "Cancellation reason (optional)";
}

applyCancelRule();
statusSel.addEventListener('change', applyCancelRule);
</script>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
