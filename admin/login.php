<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $email    = cleanInput($_POST['email']);
    $password = $_POST['password'];

    if (!$email || !$password) {
        $error = "All fields are required";
    } elseif (!isValidEmail($email)) {
        $error = "Invalid email format";
    } else {

        // allow admin & stock_keeper
        $stmt = $connection->prepare(
            "SELECT id, name, password, role, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = "Invalid login credentials";
        } elseif ($user['status'] !== 'active') {
            $error = "Account is inactive";
        } else {

            // Secure session handling
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role']; // admin | stock_keeper

            // Optional role-based redirect
            if ($user['role'] === 'stock_keeper') {
                header("Location: dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login | Shoe Shop</title>

<!-- CSS Libraries -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/animate.min.css">
<link rel="stylesheet" href="assets/css/login.css">

<!-- Icons -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
    <div class="row login-card shadow-lg animate__animated animate__fadeInUp">

        <!-- IMAGE SECTION -->
        <div class="col-md-6 d-none d-md-flex login-image">
            <img src="assets/images/admin-login.png" alt="Admin Login">
        </div>

        <!-- FORM SECTION -->
        <div class="col-md-6 p-5 bg-white rounded-end">
            <h3 class="fw-bold mb-1">Admin Panel</h3>
            <p class="text-muted mb-4">Manage your shoe shop</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <?php csrfField(); ?>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fa fa-envelope"></i>
                        </span>
                        <input type="email" name="email"
                               class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fa fa-lock"></i>
                        </span>
                        <input type="password" name="password"
                               id="password" class="form-control" required>
                        <span class="input-group-text toggle-eye"
                              onclick="togglePassword()">
                            <i class="fa fa-eye"></i>
                        </span>
                    </div>
                </div>

                <button class="btn btn-dark w-100 mt-3">
                    <i class="fa fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="text-center mt-4 text-muted small">
                © <?= date('Y') ?> Shoe Shop Admin
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/login.js"></script>

</body>
</html>
