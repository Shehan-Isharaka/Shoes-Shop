<?php
$role = $_SESSION['user_role'] ?? '';
?>

<aside class="sidebar">

    <!-- BRAND -->
    <div class="brand">
        <span class="logo">👟</span>
        <span class="name">PINO SHOE SHOP</span>
    </div>

    <!-- ================= DASHBOARD ================= -->
    <div class="menu-group">
        <a href="dashboard.php" class="menu-item">
            <i class="fa fa-gauge"></i>
            <span>Dashboard</span>
        </a>
    </div>

    <!-- ================= ADMIN : PRODUCT MASTER ================= -->
    <?php if ($role === 'admin'): ?>
    <div class="menu-group">
        <span class="menu-title">PRODUCTS</span>
        <a href="products.php"><i class="fa fa-box"></i> Products</a>
        <a href="product-images.php"><i class="fa fa-images"></i> Product Images</a>
        <a href="categories.php"><i class="fa fa-list"></i> Categories</a>
        <a href="brands.php"><i class="fa fa-tag"></i> Brands</a>
        <a href="sizes.php"><i class="fa fa-ruler"></i> Sizes</a>
        <a href="colors.php"><i class="fa fa-palette"></i> Colors</a>
        
    </div>
    <?php endif; ?>

    <!-- ================= INVENTORY ================= -->
    <?php if (in_array($role, ['admin', 'stock_keeper'])): ?>
    <div class="menu-group">
        <span class="menu-title">INVENTORY</span>
        <a href="product-variants.php">
            <i class="fa fa-layer-group"></i> Product Variants
        </a>
        <a href="inventory.php">
            <i class="fa fa-boxes"></i> Stock Overview
        </a>
    </div>
    <?php endif; ?>

    <!-- ================= ORDERS (VIEW FOR STOCK KEEPER) ================= -->
    <?php if (in_array($role, ['admin', 'stock_keeper'])): ?>
    <div class="menu-group">
        <span class="menu-title">ORDERS</span>
        <a href="orders.php">
            <i class="fa fa-shopping-cart"></i>
            <?= $role === 'admin' ? 'Orders' : 'Orders (View Only)' ?>
        </a>
    </div>
    <?php endif; ?>

    <!-- ================= ADMIN : CUSTOMERS ================= -->
    <?php if ($role === 'admin'): ?>
    <div class="menu-group">
        <span class="menu-title">CUSTOMERS</span>
        <a href="customers.php"><i class="fa fa-users"></i> Customers</a>
    </div>

    <!-- ================= ADMIN : WEBSITE ================= -->
    <div class="menu-group">
        <span class="menu-title">WEBSITE</span>
        <a href="website-settings.php"><i class="fa fa-gear"></i> Website Settings</a>
        <a href="page-settings.php"><i class="fa fa-file-lines"></i> Pages</a>
        <a href="delivery-settings.php"><i class="fa fa-truck"></i> Delivery</a>
    </div>

    <!-- ================= ADMIN : REPORTS ================= -->
    <div class="menu-group">
        <span class="menu-title">REPORTS</span>
        <a href="sales-reports.php"><i class="fa fa-chart-line"></i> Sales Reports</a>
        <a href="stock-reports.php"><i class="fa fa-chart-pie"></i> Stock Reports</a>
    </div>

    <!-- ================= ADMIN : SYSTEM ================= -->
    <div class="menu-group">
        <span class="menu-title">SYSTEM</span>
        <a href="users.php"><i class="fa fa-user-gear"></i> Users</a>
    </div>
    <?php endif; ?>

    <!-- ================= LOGOUT ================= -->
    <div class="menu-group">
        <span class="menu-title">ACCOUNT</span>
        <a href="#" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="fa fa-sign-out-alt me-2"></i> Logout
        </a>
    </div>


</aside>
