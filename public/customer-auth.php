<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$error = '';
$success = '';

/* ================= REGISTER ================= */
if (isset($_POST['register'])) {

    $name  = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $pass  = $_POST['password'];

    if (!$name || !$email || !$phone || !$pass) {
        $error = "All fields are required.";
    } else {

        /* 🔍 Check duplicate email */
        $check = $connection->prepare("
            SELECT id FROM customers WHERE email = ?
        ");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email is already registered.";
        } else {

            $hash = password_hash($pass, PASSWORD_DEFAULT);

            $stmt = $connection->prepare("
                INSERT INTO customers (full_name, email, phone, password)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("ssss", $name, $email, $phone, $hash);

            if ($stmt->execute()) {
                $success = "Account created successfully. Please login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

/* ================= LOGIN ================= */
if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    $stmt = $connection->prepare("
        SELECT id, full_name, password
        FROM customers
        WHERE email = ? AND status = 'active'
        LIMIT 1
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();

        if (password_verify($pass, $user['password'])) {

            $_SESSION['customer'] = [
                'id'    => $user['id'],
                'name'  => $user['full_name'],
                'email' => $email
            ];
        
            header("Location: index.php");
            exit;
        }
        
    }

    $error = "Invalid email or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Login</title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/auth.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

</head>

<body class="d-flex flex-column min-vh-100">

<?php include 'layout/header.php'; ?>

<div class="flex-grow-1 container py-5">


<div class="row justify-content-center">
<div class="col-lg-6">

<div class="auth-card p-4 p-md-5">

<h3 class="fw-bold text-center mb-4">Customer Account</h3>

<?php if ($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<ul class="nav nav-pills justify-content-center mb-4">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#login">
            Login
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#register">
            Register
        </button>
    </li>
</ul>

<div class="tab-content">

<!-- ================= LOGIN ================= -->
<div class="tab-pane fade show active" id="login">
<form method="post">

    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
        <a href="forgot-password.php" class="small text-decoration-none">
            Forgot password?
        </a>
    </div>

    <button name="login" class="btn btn-dark w-100 rounded-pill py-2">
        Login
    </button>

</form>
</div>

<!-- ================= REGISTER ================= -->
<div class="tab-pane fade" id="register">
<form method="post">

    <div class="mb-3">
        <label>Full Name</label>
        <input type="text" name="full_name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <button name="register" class="btn btn-outline-dark w-100 rounded-pill py-2">
        Create Account
    </button>

</form>
</div>

</div>
</div>
</div>
</div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
