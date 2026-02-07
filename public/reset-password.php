<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$error = '';
$success = '';

$email = $_SESSION['reset_email'] ?? '';
if (!$email) {
    header("Location: forgot-password.php");
    exit;
}

if (isset($_POST['reset_password'])) {

    $otp      = trim($_POST['otp'] ?? '');
    $newPass  = $_POST['new_password'] ?? '';
    $confPass = $_POST['confirm_password'] ?? '';

    if (!$otp || !$newPass || !$confPass) {
        $error = "All fields are required.";
    } elseif ($newPass !== $confPass) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPass) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {

        // Verify OTP + expiry
        $stmt = $connection->prepare("
            SELECT id, reset_otp, otp_expires
            FROM customers
            WHERE email = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows !== 1) {
            $error = "Account not found.";
        } else {
            $u = $res->fetch_assoc();

            if (!$u['reset_otp'] || !$u['otp_expires']) {
                $error = "No OTP request found. Please request a new OTP.";
            } elseif ($otp !== $u['reset_otp']) {
                $error = "Invalid OTP. Please try again.";
            } elseif (strtotime($u['otp_expires']) < time()) {
                $error = "OTP expired. Please request a new OTP.";
            } else {

                // Update password
                $hash = password_hash($newPass, PASSWORD_DEFAULT);

                $upd = $connection->prepare("
                    UPDATE customers
                    SET password = ?, reset_otp = NULL, otp_expires = NULL
                    WHERE id = ?
                ");
                $upd->bind_param("si", $hash, $u['id']);
                $upd->execute();

                unset($_SESSION['reset_email']);
                $success = "Password reset successfully. You can login now.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include 'layout/header.php'; ?>

<div class="flex-grow-1 container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-5">

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <div class="display-6 mb-2"><i class="bi bi-key"></i></div>
                        <h4 class="fw-bold mb-1">Reset Password</h4>
                        <p class="text-muted mb-0">Enter OTP and set a new password.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <div class="text-center">
                            <a href="customer-auth.php" class="btn btn-dark rounded-pill px-4">
                                Go to Login
                            </a>
                        </div>
                    <?php else: ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">OTP Code</label>
                            <input type="text" name="otp" class="form-control form-control-lg"
                                   placeholder="Enter 6-digit OTP" maxlength="6" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control form-control-lg"
                                   placeholder="New password" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control form-control-lg"
                                   placeholder="Confirm password" required>
                        </div>

                        <button type="submit" name="reset_password"
                                class="btn btn-dark w-100 rounded-pill py-2">
                            <i class="bi bi-check2-circle"></i> Reset Password
                        </button>

                        <div class="text-center mt-3">
                            <a href="forgot-password.php" class="text-decoration-none small">
                                Didn’t get OTP? Request again
                            </a>
                        </div>
                    </form>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>