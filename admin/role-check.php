<?php
if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit;
}

function requireAdmin() {
    if ($_SESSION['user_role'] !== 'admin') {
        header("Location: dashboard.php");
        exit;
    }
}

function requireStockKeeper() {
    if (!in_array($_SESSION['user_role'], ['admin', 'stock_keeper'])) {
        header("Location: dashboard.php");
        exit;
    }
}
