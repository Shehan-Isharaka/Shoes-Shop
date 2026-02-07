<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customer = $_SESSION['customer'] ?? null;

require_once __DIR__ . '/../includes/settings.php';

$logo = setting('header_logo');
$siteName = setting('site_name', 'Shoe Shop');
$logoUrl = '/shoe-shop/admin/uploads/branding/' . $logo;
?>

<header class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">

        <!-- LOGO -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <?php if ($logo): ?>
                <img src="<?= $logoUrl ?>"
                     alt="<?= htmlspecialchars($siteName) ?>"
                     style="height:60px; max-width:180px; object-fit:contain;">
            <?php else: ?>
                <strong class="fs-4"><?= htmlspecialchars($siteName) ?></strong>
            <?php endif; ?>
        </a>

        <!-- MOBILE TOGGLER -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- MENU -->
        <div class="collapse navbar-collapse" id="mainNavbar">

            <!-- CENTER MENU -->
            <ul class="navbar-nav mx-auto fw-semibold gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link" href="shop.php?cat=men">Men</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="shop.php?cat=women">Women</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="shop.php?cat=kids">Kids</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="shop.php">All Products</a>
                </li>
            </ul>

            <!-- RIGHT ACTIONS -->
            <div class="d-flex align-items-center gap-3">

            <!-- TRACK ORDER -->
            <a href="track-order.php"
            class="btn btn-outline-secondary btn-sm rounded-pill px-4">
                <i class="bi bi-truck me-1"></i>
                Track Order
            </a>

            <!-- CART -->
            <a href="cart.php" class="position-relative text-dark fs-5">
                <i class="bi bi-cart3"></i>
            </a>

            <?php if (!empty($customer)): ?>

                <!-- LOGGED USER -->
                <div class="dropdown">
                    <button class="btn btn-dark btn-sm rounded-pill px-4 dropdown-toggle"
                            data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($customer['name']) ?>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <a class="dropdown-item" href="my-orders.php">
                                <i class="bi bi-box-seam me-2"></i> My Orders
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person me-2"></i> Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>

            <?php else: ?>

                <!-- GUEST -->
                <a href="customer-auth.php"
                class="btn btn-outline-dark btn-sm rounded-pill px-4">
                    Login / Register
                </a>

            <?php endif; ?>

            </div>

        </div>
    </div>
</header>

<!-- HEADER HOVER EFFECT -->
<style>
.navbar .nav-link {
    position: relative;
    transition: color .2s ease;
}

.navbar .nav-link::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 0%;
    height: 2px;
    background: #000;
    transition: width .3s ease;
}

.navbar .nav-link:hover::after {
    width: 100%;
}
</style>
