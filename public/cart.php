<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customer = $_SESSION['customer'] ?? null;

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/settings.php';

/* ---------------------------
   Detect if array is a "list" (0..n keys)
---------------------------- */
function isListArray(array $arr): bool {
    $i = 0;
    foreach ($arr as $k => $v) {
        if ($k !== $i) return false;
        $i++;
    }
    return true;
}

/* ---------------------------
   Normalize cart to: [variantId => qty]
   Supports:
   1) [0=>5,1=>5,2=>9]  (list)
   2) [5=>2,9=>1]      (map)
---------------------------- */
function getNormalizedCart(): array {
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) $cart = [];

    $normalized = [];

    if (empty($cart)) return [];

    // If it's a list => values are variant IDs
    if (isListArray($cart)) {
        foreach ($cart as $variantId) {
            $variantId = (int)$variantId;
            if ($variantId <= 0) continue;
            $normalized[$variantId] = ($normalized[$variantId] ?? 0) + 1;
        }
    } else {
        // If it's a map => key is variant, value is qty
        foreach ($cart as $variantId => $qty) {
            $variantId = (int)$variantId;
            $qty = (int)$qty;
            if ($variantId <= 0) continue;
            if ($qty <= 0) continue;
            $normalized[$variantId] = $qty;
        }
    }

    return $normalized;
}

function saveCart(array $cart): void {
    $_SESSION['cart'] = $cart; // always save as map
}

/* ---------------------------
   Load cart
---------------------------- */
$cart = getNormalizedCart();

/* ---------------------------
   Actions: add / remove / update
---------------------------- */
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$variantId = isset($_GET['variant']) ? (int)$_GET['variant'] : (isset($_POST['variant']) ? (int)$_POST['variant'] : 0);

if ($action === 'add' && $variantId > 0) {
    $cart[$variantId] = ($cart[$variantId] ?? 0) + 1;
    saveCart($cart);
    header("Location: cart.php");
    exit;
}

if ($action === 'remove' && $variantId > 0) {
    unset($cart[$variantId]);
    saveCart($cart);
    header("Location: cart.php");
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $qtyArr = $_POST['qty'] ?? [];

    foreach ($qtyArr as $vId => $q) {
        $vId = (int)$vId;
        $q = (int)$q;

        if ($vId <= 0) continue;

        if ($q <= 0) {
            unset($cart[$vId]); // remove item
        } else {
            $cart[$vId] = $q; // update qty
        }
    }

    saveCart($cart);
    header("Location: cart.php");
    exit;
}

/* ---------------------------
   Fetch cart items from DB
---------------------------- */
$items = [];
$subtotal = 0;

if (!empty($cart)) {

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $sql = "
        SELECT
            pv.id AS variant_id,
            p.id AS product_id,
            p.name,
            p.price,
            p.discount,
            pi.image_path,
            s.size_label,
            c.name AS color_name
        FROM product_variants pv
        JOIN products p ON p.id = pv.product_id
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
        LEFT JOIN sizes s ON s.id = pv.size_id
        LEFT JOIN colors c ON c.id = pv.color_id
        WHERE pv.id IN ($placeholders)
    ";

    $stmt = $connection->prepare($sql);
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();

    $dbRows = [];
    while ($row = $res->fetch_assoc()) {
        $dbRows[(int)$row['variant_id']] = $row;
    }

    foreach ($cart as $vId => $qty) {
        if (!isset($dbRows[$vId])) continue;

        $row = $dbRows[$vId];

        $unit = (float)$row['price'];
        if (!empty($row['discount']) && (float)$row['discount'] > 0) {
            $unit = $unit - (float)$row['discount'];
        }

        $row['qty'] = (int)$qty;
        $row['unit_price'] = $unit;
        $row['total'] = $unit * (int)$qty;

        $subtotal += $row['total'];
        $items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Cart</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/cart.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body class="d-flex flex-column min-vh-100">
<?php include 'layout/header.php'; ?>

<main class="flex-grow-1 container py-5">

<div class="d-flex align-items-center justify-content-between mb-4">
  <h3 class="fw-bold mb-0">Shopping Cart</h3>
  <a href="shop.php" class="btn btn-outline-dark rounded-pill px-4">Continue Shopping</a>
</div>

<?php if (empty($items)): ?>
    <div class="alert alert-info text-center">
        Your cart is empty.
    </div>
<?php else: ?>




<form method="post" action="cart.php">
<input type="hidden" name="action" value="update">


<div class="cart-wrapper">

<?php foreach ($items as $item): ?>
    <?php
        $img = !empty($item['image_path']) ? "../admin/" . $item['image_path'] : "assets/img/no-image.png";
    ?>
    <div class="cart-item">
        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($item['name'] ?? 'Product') ?>">

        <div class="cart-details">
            <h6 class="mb-1"><?= htmlspecialchars($item['name'] ?? 'Product') ?></h6>
            <small class="text-muted">
                Size: <?= htmlspecialchars($item['size_label'] ?? '-') ?> |
                Color: <?= htmlspecialchars($item['color_name'] ?? '-') ?>
            </small>
        </div>

        <div class="cart-price fw-semibold">
            Rs. <?= number_format((float)$item['unit_price'],2) ?>
        </div>

        <input type="number"
               name="qty[<?= (int)$item['variant_id'] ?>]"
               value="<?= (int)$item['qty'] ?>"
               min="0"
               class="cart-qty"
               title="Set 0 to remove">

        <div class="cart-total fw-bold">
            Rs. <?= number_format((float)$item['total'],2) ?>
        </div>

        <a href="cart.php?action=remove&variant=<?= (int)$item['variant_id'] ?>"
           class="btn btn-sm btn-outline-danger rounded-pill">
            Remove
        </a>
    </div>
<?php endforeach; ?>
</div>



<div class="cart-actions mt-4">
    <div class="fw-bold fs-5">
        Subtotal: Rs. <?= number_format((float)$subtotal,2) ?>
    </div>

    <div class="d-flex gap-2 flex-wrap justify-content-end">
        <button type="submit" class="btn btn-secondary rounded-pill px-4">
            Update Cart
        </button>
        <?php if (!empty($_SESSION['customer'])): ?>

    <a href="checkout.php" class="btn btn-dark rounded-pill px-5">
        Proceed to Checkout
    </a>

<?php else: ?>

    <button type="button"
        class="btn btn-dark rounded-pill px-5"
        data-bs-toggle="modal"
        data-bs-target="#loginRequiredModal">
    Proceed to Checkout
</button>


<?php endif; ?>

    </div>
</div>

</form>

<div class="modal fade" id="loginRequiredModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-4 text-center">

      <h5 class="fw-bold mb-2">Login Required</h5>
      <p class="text-muted">
        Please login to place your order.
      </p>

      <div class="d-flex justify-content-center gap-3 mt-3">
        <a href="customer-auth.php"
           class="btn btn-dark rounded-pill px-4">
           Login / Register
        </a>

        <button class="btn btn-outline-secondary rounded-pill px-4"
                data-bs-dismiss="modal">
           Continue Shopping
        </button>
      </div>

    </div>
  </div>
</div>

<?php endif; ?>
</main>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
