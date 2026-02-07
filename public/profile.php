<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

/* ================= LOGIN CHECK ================= */
if (empty($_SESSION['customer']['id'])) {
    header("Location: customer-auth.php");
    exit;
}

$customerId = (int)$_SESSION['customer']['id'];
$success = '';
$error = '';

/* ================= FETCH CUSTOMER ================= */
$stmt = $connection->prepare("
    SELECT full_name, email, phone
    FROM customers
    WHERE id = ? AND status = 'active'
    LIMIT 1
");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    die("Customer not found.");
}

/* ================= UPDATE PROFILE ================= */
if (isset($_POST['update_profile'])) {

    $name  = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (!$name || !$email) {
        $error = "Name and Email are required.";
    } else {

        $stmt = $connection->prepare("
            UPDATE customers
            SET full_name = ?, email = ?, phone = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $name, $email, $phone, $customerId);

        if ($stmt->execute()) {
            $_SESSION['customer']['name'] = $name; // update header name
            $success = "Profile updated successfully.";
        } else {
            $error = "Failed to update profile.";
        }
    }
}

/* ================= CHANGE PASSWORD ================= */
if (isset($_POST['change_password'])) {

    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!$current || !$new || !$confirm) {
        $error = "All password fields are required.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {

        $stmt = $connection->prepare("
            SELECT password FROM customers WHERE id = ?
        ");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!password_verify($current, $row['password'])) {
            $error = "Current password is incorrect.";
        } else {

            $hash = password_hash($new, PASSWORD_DEFAULT);

            $stmt = $connection->prepare("
                UPDATE customers SET password = ? WHERE id = ?
            ");
            $stmt->bind_param("si", $hash, $customerId);
            $stmt->execute();

            $success = "Password changed successfully.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body class="d-flex flex-column min-vh-100">

<?php include 'layout/header.php'; ?>

<div class="flex-grow-1 container py-5">

<div class="row justify-content-center">
<div class="col-lg-8">

<h3 class="fw-bold mb-4">My Profile</h3>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- ================= PROFILE INFO ================= -->
<div class="card mb-4 p-4">
<h5 class="fw-bold mb-3">Profile Information</h5>

<form method="post">

<div class="mb-3">
    <label class="form-label">Full Name</label>
    <input type="text" name="full_name"
           class="form-control"
           value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>"
           required>
</div>

<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email"
           class="form-control"
           value="<?= htmlspecialchars($profile['email'] ?? '') ?>"
           required>
</div>

<div class="mb-3">
    <label class="form-label">Phone</label>
    <input type="text" name="phone"
           class="form-control"
           value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
</div>

<button type="submit"
        name="update_profile"
        class="btn btn-dark rounded-pill px-4">
    Update Profile
</button>

</form>
</div>

<!-- ================= CHANGE PASSWORD ================= -->
<div class="card p-4">
<h5 class="fw-bold mb-3">Change Password</h5>

<form method="post">

<div class="mb-3">
    <label class="form-label">Current Password</label>
    <input type="password" name="current_password"
           class="form-control" required>
</div>

<div class="mb-3">
    <label class="form-label">New Password</label>
    <input type="password" name="new_password"
           class="form-control" required>
</div>

<div class="mb-3">
    <label class="form-label">Confirm New Password</label>
    <input type="password" name="confirm_password"
           class="form-control" required>
</div>

<button type="submit"
        name="change_password"
        class="btn btn-outline-dark rounded-pill px-4">
    Change Password
</button>

</form>
</div>

</div>
</div>
</div>>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
