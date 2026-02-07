<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customer = $_SESSION['customer'] ?? null;

// DB + Settings
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/settings.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= setting('site_name','Pino Shoes') ?></title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/site.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body>

<!-- ================= HEADER ================= -->
<?php include __DIR__ . '/layout/header.php'; ?>

<main>

<?php
$heroTitle    = setting('hero_title', 'Step Into Style');
$heroSubtitle = setting('hero_subtitle', 'Premium shoes for every lifestyle');
$heroImage    = setting('hero_image');
?>

<!-- ================= HERO SECTION ================= -->
<section class="hero-section py-5">
    <div class="container">
        <div class="row align-items-center g-5">

            <!-- TEXT -->
            <div class="col-lg-6 text-center text-lg-start">
                <h1 class="fw-bold display-5 mb-3">
                    <?= htmlspecialchars($heroTitle) ?>
                </h1>

                <p class="lead text-muted mb-4">
                    <?= htmlspecialchars($heroSubtitle) ?>
                </p>

                <!-- ✅ BUTTONS (DB CONTROLLED) -->
                <div class="d-flex justify-content-center justify-content-lg-start gap-3 flex-wrap">

                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <?php
                        $label  = setting("hero_btn{$i}_label");
                        $link   = setting("hero_btn{$i}_link");
                        $active = setting("hero_btn{$i}_active");
                        ?>

                        <?php if ($active == '1' && $label && $link): ?>
                            <a href="<?= htmlspecialchars($link) ?>"
                               class="btn <?= $i === 1 ? 'btn-dark' : 'btn-outline-dark' ?> btn-lg px-4">
                                <?= htmlspecialchars($label) ?>
                            </a>
                        <?php endif; ?>

                    <?php endfor; ?>

                </div>
            </div>

            <!-- IMAGE (UNCHANGED) -->
            <div class="col-lg-6 text-center">
                <?php if ($heroImage && file_exists(__DIR__ . '/../admin/uploads/hero/' . $heroImage)): ?>
                    <img src="../admin/uploads/hero/<?= $heroImage ?>"
                         class="img-fluid hero-image"
                         alt="Hero Image">
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>



<!-- ================= SERVICES SECTION ================= -->
<section class="home-services py-5">
    <div class="container">

        <div class="text-center mb-5">
            <h2 class="fw-bold">Why Shop With Us</h2>
            <p class="text-muted">Quality, comfort, and trust in every step</p>
        </div>

        <div class="row g-4 justify-content-center">

            <?php for ($i = 1; $i <= 4; $i++): ?>
                <?php
                $enabled = setting("service{$i}_enabled", '0');
                $icon    = setting("service{$i}_icon");
                $title   = setting("service{$i}_title");
                $text    = setting("service{$i}_text");
                ?>

                <?php if ($enabled == '1' && $title): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="service-card h-100 text-center p-4">

                            <?php if ($icon): ?>
                                <div class="service-icon mb-3">
                                    <i class="<?= htmlspecialchars($icon) ?>"></i>
                                </div>
                            <?php endif; ?>

                            <h5 class="fw-semibold mb-2">
                                <?= htmlspecialchars($title) ?>
                            </h5>

                            <p class="text-muted small mb-0">
                                <?= htmlspecialchars($text) ?>
                            </p>

                        </div>
                    </div>
                <?php endif; ?>

            <?php endfor; ?>

        </div>
    </div>
</section>


<!-- ================= CATEGORIES SECTION ================= -->
<section class="home-categories py-5">
    <div class="container">

        <div class="text-center mb-5">
            <h2 class="fw-bold">Shop by Categories</h2>
            <p class="text-muted">Find the perfect pair for everyone</p>
        </div>

        <div class="row g-4">

            <?php
            $categories = [
                'men'   => ['label' => 'Men',   'img' => setting('cat_men_img')],
                'women' => ['label' => 'Women', 'img' => setting('cat_women_img')],
                'kids'  => ['label' => 'Kids',  'img' => setting('cat_kids_img')],
            ];
            ?>

            <?php foreach ($categories as $slug => $cat): ?>
                <?php if (!empty($cat['img']) && file_exists(__DIR__ . '/../admin/uploads/categories/' . $cat['img'])): ?>

                    <div class="col-md-6 col-lg-4">
                        <a href="shop.php?cat=<?= $slug ?>" class="category-card">

                            <div class="category-img">
                                <img src="../admin/uploads/categories/<?= $cat['img'] ?>"
                                     alt="<?= htmlspecialchars($cat['label']) ?>">
                            </div>

                            <div class="category-overlay">
                                <h4><?= htmlspecialchars($cat['label']) ?></h4>
                                <span>Shop Now</span>
                            </div>

                        </a>
                    </div>

                <?php endif; ?>
            <?php endforeach; ?>

        </div>
    </div>
</section>


<!-- ================= NEW ARRIVALS ================= -->

<?php
$limit = (int) setting('latest_products_limit', 8);

$stmt = $connection->prepare("
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
    ORDER BY p.created_at DESC
    LIMIT ?
");

$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();
?>


<!-- ================= NEW ARRIVALS ================= -->
<section class="py-5 bg-light">
    <div class="container">

        <div class="text-center mb-4">
            <h3 class="fw-bold mb-1">New Arrivals</h3>
            <p class="text-muted mb-3">Fresh styles just dropped</p>


        </div>


        <div class="row g-4">

        <?php if (isset($result) && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>

            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card h-100">

                    <!-- IMAGE -->
                    <div class="product-img position-relative">

    <img src="../admin/<?= htmlspecialchars($row['image_path']) ?>"
         alt="<?= htmlspecialchars($row['name']) ?>">

</div>


                    <!-- INFO -->
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
        <?php else: ?>
            <div class="col-12 text-center text-muted">
                No products found.
            </div>
        <?php endif; ?>

        </div>
    </div>
</section>


<!-- ================= FEATURED PRODUCTS ================= -->
<section class="featured-products py-5">
    <div class="container">

        <!-- HEADER -->
        <div class="text-center mb-5">
            <h2 class="fw-bold">Featured Products</h2>
            <p class="text-muted">Hand-picked styles you’ll love</p>
        </div>

        <div class="row g-4">

        <?php
        $limit = (int) setting('featured_products_limit', 4);

        $stmt = $connection->prepare("
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
            WHERE p.is_featured = 1
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        ?>

        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-6 col-md-4 col-lg-3">

                <div class="featured-card h-100">

                    <!-- IMAGE -->
                    <!-- <div class="featured-img">
                        <span class="badge-featured">⭐ Featured</span>

                        <img src="../admin/<?= htmlspecialchars($row['image_path']) ?>"
                             alt="<?= htmlspecialchars($row['name']) ?>">
                    </div> -->
                    <div class="product-img position-relative">

                    <img src="../admin/<?= htmlspecialchars($row['image_path']) ?>"
                        alt="<?= htmlspecialchars($row['name']) ?>">

                </div>


                    <!-- INFO -->
                    <div class="featured-info text-center">
                        <h6 class="fw-semibold mb-2">
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
                           class="btn btn-dark btn-sm w-100">
                            View Product
                        </a>
                    </div>

                </div>

            </div>
        <?php endwhile; ?>

        </div>
    </div>
</section>

<!-- ================= SOCIAL MEDIA SECTION ================= -->
<section class="social-section py-5">
    <div class="container text-center">

        <h3 class="fw-bold mb-2">Connect With Us</h3>
        <p class="text-muted mb-4">
            Follow us on social media for latest offers & updates
        </p>

        <div class="d-flex justify-content-center gap-4 flex-wrap">

            <?php
            $socials = [
                'facebook'  => ['icon' => 'bi-facebook',  'class' => 'facebook'],
                'instagram' => ['icon' => 'bi-instagram', 'class' => 'instagram'],
                'tiktok'    => ['icon' => 'bi-tiktok',    'class' => 'tiktok'],
                'youtube'   => ['icon' => 'bi-youtube',   'class' => 'youtube'],
            ];

            foreach ($socials as $key => $data):
                if (
                    setting("social_{$key}_enabled") == '1' &&
                    setting("social_{$key}_url")
                ):
            ?>
                <a href="<?= htmlspecialchars(setting("social_{$key}_url")) ?>"
                   target="_blank"
                   class="social-icon <?= $data['class'] ?>">
                    <i class="bi <?= $data['icon'] ?>"></i>
                </a>
            <?php
                endif;
            endforeach;
            ?>

        </div>
    </div>
</section>


<?php include __DIR__ . '/layout/footer.php'; ?>


</main>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
