<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= LOGIN REQUIRED ================= */
if (empty($_SESSION['customer'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header("Location: customer-auth.php?login_required=1");
    exit;
}

$customer = $_SESSION['customer'];

require_once __DIR__ . '/../includes/db.php';

/* ================= CART NORMALIZER ================= */
function normalizeCart() {
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) return [];

    $normalized = [];
    foreach ($cart as $variantId => $qty) {
        $variantId = (int)$variantId;
        $qty = (int)$qty;
        if ($variantId > 0 && $qty > 0) {
            $normalized[$variantId] = $qty;
        }
    }
    return $normalized;
}

/* ================= TRACKING CODE ================= */
function generateTrackingCode() {
    return 'PS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

$cart = normalizeCart();
if (empty($cart)) {
    die('<div class="container py-5 text-center">Your cart is empty.</div>');
}

/* ================= DELIVERY DISTRICTS (FROM DB) ================= */
$districts = [];
$res = $connection->query("
    SELECT district_name, delivery_charge
    FROM delivery_districts
    WHERE status = 'active'
    ORDER BY district_name ASC
");

while ($row = $res->fetch_assoc()) {
    $districts[$row['district_name']] = (float)$row['delivery_charge'];
}

/* ================= FETCH CART ITEMS + STOCK ================= */
$ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$sql = "
SELECT
    pv.id AS variant_id,
    pv.stock,
    pv.reserved_stock,
    (pv.stock - pv.reserved_stock) AS available_stock,
    p.id AS product_id,
    p.name,
    p.price,
    p.discount
FROM product_variants pv
JOIN products p ON p.id = pv.product_id
WHERE pv.id IN ($placeholders)
FOR UPDATE
";

$connection->begin_transaction();

$stmt = $connection->prepare($sql);
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
$subtotal = 0;
$variantRows = [];

while ($row = $res->fetch_assoc()) {
    $variantRows[$row['variant_id']] = $row;
}

/* ================= STOCK VALIDATION ================= */
foreach ($cart as $variantId => $qty) {

    if (!isset($variantRows[$variantId])) {
        $connection->rollback();
        die("Invalid product in cart.");
    }

    $row = $variantRows[$variantId];
    $available = (int)$row['available_stock'];

    if ($qty > $available) {
        $connection->rollback();
        die("
            <div class='container py-5 text-center'>
                <h5 class='text-danger'>Stock Changed</h5>
                <p>
                    <strong>{$row['name']}</strong> has only
                    <strong>{$available}</strong> item(s) left.
                </p>
                <a href='cart.php' class='btn btn-dark rounded-pill'>
                    Back to Cart
                </a>
            </div>
        ");
    }

    $unitPrice = $row['price'] - ($row['discount'] ?? 0);
    $lineTotal = $unitPrice * $qty;
    $subtotal += $lineTotal;

    $items[] = [
        'variant_id' => $variantId,
        'product_id' => $row['product_id'],
        'name'       => $row['name'],
        'qty'        => $qty,
        'unit_price'=> $unitPrice,
        'line_total'=> $lineTotal
    ];
}

$error = '';

/* ================= PLACE ORDER ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $district = $_POST['district'];
    $city     = trim($_POST['city']);
    $postcode = trim($_POST['postcode']);
    $address  = trim($_POST['address']);

    $paymentMethod = ($_POST['payment_method'] === 'bank') ? 'bank' : 'cod';
    $paymentStatus = 'pending';

    if (!$name || !$phone || !$district || !$city || !$address) {
        $error = "Please fill all required fields.";
    } elseif (!isset($districts[$district])) {
        $error = "Invalid delivery district selected.";
    } else {

        $delivery = $districts[$district];
        $grandTotal = $subtotal + $delivery;
        $trackingCode = generateTrackingCode();
        $orderStatus = 'pending';

        /* INSERT ORDER */
        $stmt = $connection->prepare("
            INSERT INTO orders
            (tracking_code, customer_id, customer_name, email, phone,
             address, district, city, postcode,
             subtotal, delivery_fee, total,
             payment_method, payment_status, order_status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "sissssssddddsss",
            $trackingCode,
            $customer['id'],
            $name,
            $email,
            $phone,
            $address,
            $district,
            $city,
            $postcode,
            $subtotal,
            $delivery,
            $grandTotal,
            $paymentMethod,
            $paymentStatus,
            $orderStatus
        );
        $stmt->execute();
        $orderId = $stmt->insert_id;

        /* INSERT ITEMS + RESERVE STOCK */
        $oi = $connection->prepare("
            INSERT INTO order_items
            (order_id, product_id, variant_id, qty, price)
            VALUES (?,?,?,?,?)
        ");

        $reserve = $connection->prepare("
            UPDATE product_variants
            SET reserved_stock = reserved_stock + ?
            WHERE id = ?
        ");

        foreach ($items as $i) {
            $oi->bind_param(
                "iiiid",
                $orderId,
                $i['product_id'],
                $i['variant_id'],
                $i['qty'],
                $i['unit_price']
            );
            $oi->execute();

            $reserve->bind_param("ii", $i['qty'], $i['variant_id']);
            $reserve->execute();
        }

        /* STATUS HISTORY */
        $hist = $connection->prepare("
            INSERT INTO order_status_history (order_id, status, note)
            VALUES (?, 'pending', 'Order placed')
        ");
        $hist->bind_param("i", $orderId);
        $hist->execute();

        $connection->commit();

        unset($_SESSION['cart']);

        $_SESSION['order_success'] = [
            'order_id' => $orderId,
            'tracking_code' => $trackingCode
        ];

        header("Location: checkout-success.php?track=".$trackingCode);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/checkout.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<?php include 'layout/header.php'; ?>

<div class="container py-5">
<h3 class="fw-bold mb-4">Checkout</h3>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">

<!-- LEFT -->
<div class="col-lg-7">
<div class="card checkout-card p-4">

<form method="post">

<h6 class="fw-bold mb-3">Customer Details</h6>

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full Name *</label>
        <input name="name" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control">
    </div>

    <div class="col-md-6">
        <label class="form-label">Phone *</label>
        <input name="phone" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">District *</label>
        <select name="district" id="districtSelect" class="form-select" required>
            <option value="">Select District</option>
            <?php foreach ($districts as $d => $fee): ?>
                <option value="<?= htmlspecialchars($d) ?>" data-fee="<?= $fee ?>">
                    <?= htmlspecialchars($d) ?> (Rs.<?= number_format($fee,2) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">City *</label>
        <input name="city" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Postcode</label>
        <input name="postcode" class="form-control">
    </div>

    <div class="col-12">
        <label class="form-label">Address *</label>
        <textarea name="address" class="form-control" rows="3" required></textarea>
    </div>
</div>

<hr class="my-4">

<h6 class="fw-bold mb-3">Payment Method</h6>

<div class="form-check mb-2">
    <input class="form-check-input" type="radio"
           name="payment_method" value="cod" checked
           id="payCod">
    <label class="form-check-label fw-semibold" for="payCod">
        Cash on Delivery
    </label>
    <div class="small text-muted ms-4">
        Pay with cash when your order is delivered to your doorstep.
    </div>
</div>

<div class="form-check mt-3">
    <input class="form-check-input" type="radio"
           name="payment_method" value="bank"
           id="payBank">
    <label class="form-check-label fw-semibold" for="payBank">
        Bank Transfer
    </label>
    <div class="small text-muted ms-4">
        Transfer the amount to our bank account after placing the order.
        <br>
        Once the bank transfer is completed, please send the payment confirmation
        via WhatsApp to <strong>+94 078 654 9356</strong>.
        Our team will verify the payment and process your order accordingly.
    </div>
</div>



<div class="d-flex justify-content-between mt-4">
    <a href="cart.php" class="btn btn-outline-secondary rounded-pill px-4">
        ← Back to Cart
    </a>
    <button class="btn btn-dark btn-lg rounded-pill px-5">Place Order</button>
</div>

</form>
</div>
</div>

<!-- RIGHT -->
<div class="col-lg-5">
<div class="card checkout-summary p-4">

<h6 class="fw-bold mb-3">Order Summary</h6>

<?php foreach ($items as $i): ?>
<div class="d-flex justify-content-between small mb-2">
    <span><?= htmlspecialchars($i['name']) ?> × <?= $i['qty'] ?></span>
    <strong>Rs.<?= number_format($i['line_total'],2) ?></strong>
</div>
<?php endforeach; ?>

<hr>

<div class="d-flex justify-content-between">
<span>Subtotal</span>
<strong id="subtotal">Rs.<?= number_format($subtotal,2) ?></strong>
</div>

<div class="d-flex justify-content-between">
<span>Delivery</span>
<strong id="deliveryFee">Rs.0</strong>
</div>

<hr>

<div class="d-flex justify-content-between fw-bold">
<span>Total</span>
<strong id="grandTotal">Rs.<?= number_format($subtotal,2) ?></strong>
</div>

</div>
</div>

</div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script>
const fees = <?= json_encode($districts) ?>;
const subtotal = <?= $subtotal ?>;

document.getElementById('districtSelect').addEventListener('change', function () {
    const fee = fees[this.value] || 0;
    document.getElementById('deliveryFee').textContent =
    'Rs. ' + fee.toFixed(2);

    document.getElementById('grandTotal').textContent =
        'Rs. ' + (subtotal + fee).toFixed(2);

});
</script>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
