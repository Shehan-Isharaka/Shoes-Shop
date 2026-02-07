<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customer = $_SESSION['customer'] ?? null;

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/settings.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) die('Invalid product');

/* ================= PRODUCT ================= */
$stmt = $connection->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) die('Product not found');

/* ================= IMAGES ================= */
$imgs = $connection->prepare("
    SELECT image_path
    FROM product_images
    WHERE product_id = ?
    ORDER BY is_primary DESC, id DESC
");
$imgs->bind_param("i", $productId);
$imgs->execute();
$imagesRes = $imgs->get_result();

/* ================= VARIANTS (STOCK SAFE) ================= */
$variants = $connection->prepare("
    SELECT
        pv.id,
        pv.product_id,
        pv.size_id,
        pv.color_id,
        pv.stock,
        pv.reserved_stock,
        (pv.stock - pv.reserved_stock) AS available_stock,
        pv.sku,
        pv.status,
        s.size_label AS size_name,
        c.name AS color_name,
        c.hex_code
    FROM product_variants pv
    LEFT JOIN sizes s ON s.id = pv.size_id AND s.status = 'active'
    LEFT JOIN colors c ON c.id = pv.color_id AND c.status = 'active'
    WHERE pv.product_id = ?
      AND pv.status = 'active'
");
$variants->bind_param("i", $productId);
$variants->execute();
$variantData = $variants->get_result();

/* ================= GROUP VARIANTS ================= */
$sizes = [];
$colors = [];
$variantMap = [];

while ($v = $variantData->fetch_assoc()) {

    if (empty($v['size_name']) || empty($v['color_name'])) continue;
    if ((int)$v['available_stock'] <= 0) continue;

    $sizes[$v['size_id']] = $v['size_name'];
    $colors[$v['color_id']] = [
        'name' => $v['color_name'],
        'hex'  => $v['hex_code'] ?: '#111'
    ];

    $variantMap[$v['size_id']][$v['color_id']] = $v;
}

/* ================= RELATED PRODUCTS ================= */
$relatedResult = null;
if (!empty($product['category_id'])) {
    $relStmt = $connection->prepare("
        SELECT p.id, p.name, p.price, p.discount, pi.image_path
        FROM products p
        LEFT JOIN product_images pi
          ON pi.product_id = p.id AND pi.is_primary = 1
        WHERE p.category_id = ?
          AND p.id != ?
        ORDER BY RAND()
        LIMIT 4
    ");
    $relStmt->bind_param("ii", $product['category_id'], $productId);
    $relStmt->execute();
    $relatedResult = $relStmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($product['name']) ?></title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/product.css">
<link rel="stylesheet"
 href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<?php include 'layout/header.php'; ?>

<main class="container py-5">

<div class="row g-5">

<!-- ================= IMAGES ================= -->
<div class="col-lg-6">
<?php
$images = [];
while ($img = $imagesRes->fetch_assoc()) $images[] = $img['image_path'];
$mainImg = $images[0] ?? null;
?>

<?php if ($mainImg): ?>
<div class="product-main-img mb-3">
    <img id="mainImage"
         src="../admin/<?= htmlspecialchars($mainImg) ?>"
         class="img-fluid">
</div>

<?php if (count($images) > 1): ?>
<div class="product-thumbs d-flex gap-2 flex-wrap">
<?php foreach ($images as $path): ?>
<button type="button"
        class="thumb-btn"
        data-img="../admin/<?= htmlspecialchars($path) ?>">
    <img src="../admin/<?= htmlspecialchars($path) ?>">
</button>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php else: ?>
<div class="alert alert-warning">No images uploaded.</div>
<?php endif; ?>
</div>

<!-- ================= DETAILS ================= -->
<div class="col-lg-6">

<h2 class="fw-bold"><?= htmlspecialchars($product['name']) ?></h2>

<div class="price mb-3">
<?php if ($product['discount'] > 0): ?>
    <span class="price-new">
        Rs. <?= number_format($product['price'] - $product['discount'], 2) ?>
    </span>
    <span class="price-old">
        Rs. <?= number_format($product['price'], 2) ?>
    </span>
<?php else: ?>
    <span class="price-new">
        Rs. <?= number_format($product['price'], 2) ?>
    </span>
<?php endif; ?>
</div>


<p class="text-muted"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>

<?php if (empty($variantMap)): ?>
<div class="alert alert-danger">
Product is currently out of stock.
</div>
<?php else: ?>

<!-- SIZE -->
<div class="variant-group">
<label>Size</label>
<div class="variant-options">
<?php foreach ($sizes as $sid => $slabel): ?>
<button type="button"
        class="variant-btn size-btn"
        data-size="<?= $sid ?>">
<?= htmlspecialchars($slabel) ?>
</button>
<?php endforeach; ?>
</div>
</div>

<!-- COLOR -->
<div class="variant-group">
<label>Color</label>
<div class="variant-options">
<?php foreach ($colors as $cid => $c): ?>
<button type="button"
        class="variant-btn color-btn"
        style="background:<?= htmlspecialchars($c['hex']) ?>"
        data-color="<?= $cid ?>">
</button>
<?php endforeach; ?>
</div>
</div>

<div class="mt-3 small text-muted" id="stockText">
Select size & color to see stock.
</div>

<button id="addToCartBtn"
        class="btn btn-dark btn-lg mt-3 w-100"
        disabled>
<i class="bi bi-cart3 me-2"></i>Add to Cart
</button>

<?php endif; ?>

</div>
</div>

</main>

<!-- ================= RELATED PRODUCTS ================= -->
<?php if ($relatedResult && $relatedResult->num_rows > 0): ?>
<section class="py-5 bg-light">
<div class="container">
<h3 class="fw-bold mb-4">Related Products</h3>

<div class="row g-4">
<?php while ($row = $relatedResult->fetch_assoc()): ?>
<div class="col-6 col-md-4 col-lg-3">
<div class="product-card h-100">
<div class="product-img">
<img src="../admin/<?= htmlspecialchars($row['image_path']) ?>">
</div>

<div class="product-info text-center p-3">
<h6><?= htmlspecialchars($row['name']) ?></h6>
<?php if (!empty($row['discount']) && $row['discount'] > 0): ?>
                            <div class="price">
                                <span class="price-new">
                                    Rs. <?= number_format($row['price'] - $row['discount'], 2) ?>
                                </span>
                                <span class="price-old">
                                    Rs. <?= number_format($row['price'], 2) ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="price">
                                <span class="price-new">
                                    Rs. <?= number_format($row['price'], 2) ?>
                                </span>
                            </div>
                        <?php endif; ?>

<a href="product.php?id=<?= $row['id'] ?>"
   class="btn btn-dark btn-sm w-100 mt-2">
View Product
</a>
</div>
</div>
</div>
<?php endwhile; ?>
</div>
</div>
</section>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>

<script>
const variantMap = <?= json_encode($variantMap) ?>;
let selectedSize = null;
let selectedColor = null;

const btn = document.getElementById('addToCartBtn');
const stockText = document.getElementById('stockText');

document.querySelectorAll('.size-btn').forEach(b => {
  b.onclick = () => {
    document.querySelectorAll('.size-btn').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    selectedSize = b.dataset.size;
    checkVariant();
  };
});

document.querySelectorAll('.color-btn').forEach(b => {
  b.onclick = () => {
    document.querySelectorAll('.color-btn').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    selectedColor = b.dataset.color;
    checkVariant();
  };
});

function checkVariant() {
  btn.disabled = true;

  if (!selectedSize || !selectedColor) {
    stockText.textContent = "Select size & color to see stock.";
    return;
  }

  const v = variantMap?.[selectedSize]?.[selectedColor];
  if (!v) {
    stockText.textContent = "Variant not available.";
    return;
  }

  const available = parseInt(v.available_stock || 0);
  if (available <= 0) {
    stockText.textContent = "Out of stock.";
    return;
  }

  stockText.textContent = `In stock: ${available}`;
  btn.disabled = false;

  btn.onclick = () => {
    window.location = `cart.php?action=add&variant=${v.id}`;
  };
}

/* IMAGE GALLERY */
document.querySelectorAll('.thumb-btn').forEach(t => {
  t.onclick = () => {
    document.getElementById('mainImage').src = t.dataset.img;
  };
});
</script>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
