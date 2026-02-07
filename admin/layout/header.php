<?php
$userName = $_SESSION['user_name'] ?? 'User';
$userRole = $_SESSION['user_role'] ?? '';
?>

<header class="topbar">
    <h5 class="mb-0"><strong><?= htmlspecialchars($userName) ?> Panel</h5>

    <div class="topbar-right d-flex align-items-center gap-3">

        <span class="text-muted">
            Hello, <strong><?= htmlspecialchars($userName) ?></strong>
        </span>

        <?php if ($userRole): ?>
            <span class="badge bg-dark text-uppercase">
                <?= str_replace('_', ' ', $userRole) ?>
            </span>
        <?php endif; ?>

        

        <a href="#" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="fa fa-right-from-bracket"></i> Logout
        </a>



    </div>
</header>
