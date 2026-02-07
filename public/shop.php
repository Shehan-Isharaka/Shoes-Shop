<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customer = $_SESSION['customer'] ?? null;

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/settings.php';


/* ================= INPUTS ================= */
$search     = trim($_GET['search'] ?? '');
$catSlug    = $_GET['cat'] ?? '';
$subSlug    = $_GET['category'] ?? '';
$minPrice   = $_GET['min'] ?? '';
$maxPrice   = $_GET['max'] ?? '';

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

/* ================= CATEGORY RESOLVE ================= */
$categoryIds = [];

if ($catSlug || $subSlug) {
    $slug = $subSlug ?: $catSlug;

    // get parent or sub category
    $stmt = $connection->prepare("
        SELECT id FROM categories WHERE slug=? AND status='active'
    ");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $categoryIds[] = $row['id'];

        // also include child categories
        $child = $connection->prepare("
            SELECT id FROM categories WHERE parent_id=? AND status='active'
        ");
        $child->bind_param("i", $row['id']);
        $child->execute();
        $childRes = $child->get_result();
        while ($c = $childRes->fetch_assoc()) {
            $categoryIds[] = $c['id'];
        }
    }
}

/* ================= BUILD WHERE ================= */
$where = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[] = "p.name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if (!empty($categoryIds)) {
    $where[] = "p.category_id IN (" . implode(',', array_fill(0, count($categoryIds), '?')) . ")";
    foreach ($categoryIds as $cid) {
        $params[] = $cid;
        $types .= 'i';
    }
}

if ($minPrice !== '') {
    $where[] = "(p.price - IFNULL(p.discount,0)) >= ?";
    $params[] = $minPrice;
    $types .= 'd';
}

if ($maxPrice !== '') {
    $where[] = "(p.price - IFNULL(p.discount,0)) <= ?";
    $params[] = $maxPrice;
    $types .= 'd';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ================= COUNT ================= */
$countSql = "
    SELECT COUNT(*) AS total
    FROM products p
    $whereSQL
";
$countStmt = $connection->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

/* ================= PRODUCTS ================= */
$sql = "
    SELECT 
        p.id,
        p.name,
        p.price,
        p.discount,
        pi.image_path
    FROM products p
    LEFT JOIN product_images pi
        ON pi.product_id = p.id
       AND pi.is_primary = 1
    $whereSQL
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $connection->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

/* ================= CATEGORIES FOR SIDEBAR ================= */
$cats = $connection->query("
    SELECT * FROM categories
    WHERE parent_id IS NULL AND status='active'
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Shop – <?= setting('site_name','Shoe Shop') ?></title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/site.css">
<link rel="stylesheet" href="assets/css/shop.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body>

<?php include __DIR__ . '/layout/header.php'; ?>

<!-- ================= SHOP HEADER ================= -->
<section class="shop-hero">
    <div class="container">
        <small>EXPLORE OUR COLLECTION</small>
        <h1><?= $catSlug ? ucfirst($catSlug) . ' Shoes' : 'All Products' ?></h1>
        <p><?= $total ?> products found</p>

        <form class="shop-search">
            <input type="text" name="search" placeholder="Search shoes..." value="<?= htmlspecialchars($search) ?>">
            <button><i class="bi bi-search"></i></button>
        </form>
    </div>
</section>

<section class="shop-page py-5">
<div class="container">
<div class="row g-4">

<!-- ================= FILTERS ================= -->
<div class="col-lg-3">
<div class="filter-box">

<h6>Categories</h6>
<a href="shop.php">All Products</a>

<?php while ($cat = $cats->fetch_assoc()): ?>
    <details <?= $catSlug == $cat['slug'] ? 'open' : '' ?>>
        <summary><?= htmlspecialchars($cat['name']) ?></summary>

        <a href="shop.php?cat=<?= $cat['slug'] ?>">All <?= $cat['name'] ?></a>

        <?php
        $subs = $connection->query("
            SELECT * FROM categories
            WHERE parent_id={$cat['id']} AND status='active'
        ");
        while ($sub = $subs->fetch_assoc()):
        ?>
            <a href="shop.php?category=<?= $sub['slug'] ?>">
                <?= htmlspecialchars($sub['name']) ?>
            </a>
        <?php endwhile; ?>
    </details>
<?php endwhile; ?>

</div>

<div class="filter-box">
<h6>Price</h6>
<form>
    <input type="number" name="min" placeholder="Min">
    <input type="number" name="max" placeholder="Max">
    <button>Apply</button>
</form>
</div>
</div>

<!-- ================= PRODUCTS ================= -->
<div class="col-lg-9">

<?php if ($result->num_rows == 0): ?>
    <div class="alert alert-info">
        No products found for current filters.
    </div>
<?php else: ?>

<div class="row g-4">

<?php while ($row = $result->fetch_assoc()): ?>
<div class="col-6 col-md-4 col-lg-3">

<!-- SAME CARD AS HOME -->
<div class="product-card h-100">

    <div class="product-img">
        <img src="../admin/<?= htmlspecialchars($row['image_path']) ?>"
             alt="<?= htmlspecialchars($row['name']) ?>">
    </div>

    <div class="product-info text-center p-3">
        <h6 class="product-title mb-2">
            <?= htmlspecialchars($row['name']) ?>
        </h6>

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
           class="btn btn-dark btn-sm mt-3 w-100">
            View Product
        </a>
    </div>

</div>
</div>
<?php endwhile; ?>

</div>

<!-- ================= PAGINATION ================= -->
<?php if ($totalPages > 1): ?>
<nav class="shop-pagination mt-4">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a class="<?= $i == $page ? 'active' : '' ?>"
       href="?page=<?= $i ?>">
        <?= $i ?>
    </a>
<?php endfor; ?>
</nav>
<?php endif; ?>

<?php endif; ?>

</div>
</div>
</div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
