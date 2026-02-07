<?php
require_once 'auth-check.php';
require_once 'role-check.php';
requireAdmin();

require_once '../includes/db.php';

$success = '';
$error   = '';

/* =====================
   ADD / UPDATE USER
===================== */
if (isset($_POST['save_user'])) {

    $id     = intval($_POST['id'] ?? 0);
    $name   = trim($_POST['name']);
    $email  = trim($_POST['email']);
    $role   = $_POST['role'];
    $status = $_POST['status'];
    $pass   = $_POST['password'] ?? '';

    if (!$name || !$email || !$role) {
        $error = "Name, email and role are required.";
    } else {

        if ($id === 0) {
            // CREATE
            if (!$pass) {
                $error = "Password is required.";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);

                $stmt = $connection->prepare("
                    INSERT INTO users (name,email,password,role,status)
                    VALUES (?,?,?,?,?)
                ");
                $stmt->bind_param("sssss", $name,$email,$hash,$role,$status);
                $stmt->execute();

                $success = "User created successfully.";
            }
        } else {
            // UPDATE
            if ($pass) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $connection->prepare("
                    UPDATE users
                    SET name=?, email=?, role=?, status=?, password=?
                    WHERE id=?
                ");
                $stmt->bind_param("sssssi",
                    $name,$email,$role,$status,$hash,$id
                );
            } else {
                $stmt = $connection->prepare("
                    UPDATE users
                    SET name=?, email=?, role=?, status=?
                    WHERE id=?
                ");
                $stmt->bind_param("ssssi",
                    $name,$email,$role,$status,$id
                );
            }
            $stmt->execute();
            $success = "User updated successfully.";
        }
    }
}

/* =====================
   DELETE USER
===================== */
if (isset($_GET['delete'])) {
    $connection->query(
        "DELETE FROM users WHERE id=".(int)$_GET['delete']
    );
    $success = "User deleted successfully.";
}

/* =====================
   EDIT USER
===================== */
$edit = null;
if (isset($_GET['edit'])) {
    $edit = $connection->query(
        "SELECT * FROM users WHERE id=".(int)$_GET['edit']
    )->fetch_assoc();
}

/* =====================
   FETCH USERS
===================== */
$users = $connection->query(
    "SELECT * FROM users ORDER BY created_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
<title>User Management</title>

<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/admin-dashboard.css">
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php include 'layout/sidebar.php'; ?>
<div class="app-content">
<?php include 'layout/header.php'; ?>

<div class="container-fluid p-4">

<h4 class="fw-semibold mb-3">User Management</h4>

<?php if ($success): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<div class="row g-4">

<!-- FORM -->
<div class="col-lg-4">
<div class="card shadow-sm p-3">
<h6><?= $edit ? 'Edit User' : 'Add User' ?></h6>

<form method="post">
<input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

<div class="mb-2">
<label>Name</label>
<input type="text" name="name" class="form-control"
       value="<?= $edit['name'] ?? '' ?>" required>
</div>

<div class="mb-2">
<label>Email</label>
<input type="email" name="email" class="form-control"
       value="<?= $edit['email'] ?? '' ?>" required>
</div>

<div class="mb-2">
<label>Password <?= $edit ? '(leave blank to keep)' : '' ?></label>
<input type="password" name="password" class="form-control">
</div>

<div class="mb-2">
<label>Role</label>
<select name="role" class="form-select" required>
<option value="admin"
<?= ($edit && $edit['role']=='admin')?'selected':'' ?>>Admin</option>
<option value="stock_keeper"
<?= ($edit && $edit['role']=='stock_keeper')?'selected':'' ?>>
Stock Keeper</option>
</select>
</div>

<div class="mb-3">
<label>Status</label>
<select name="status" class="form-select">
<option value="active"
<?= ($edit && $edit['status']=='active')?'selected':'' ?>>Active</option>
<option value="inactive"
<?= ($edit && $edit['status']=='inactive')?'selected':'' ?>>Inactive</option>
</select>
</div>

<button class="btn btn-dark w-100" name="save_user">
<?= $edit ? 'Update User' : 'Create User' ?>
</button>
</form>
</div>
</div>

<!-- TABLE -->
<div class="col-lg-8">
<div class="card shadow-sm p-3">
<h6>User List</h6>

<table class="table table-bordered table-hover mt-2">
<thead class="table-light">
<tr>
<th>Name</th>
<th>Email</th>
<th>Role</th>
<th>Status</th>
<th width="150">Action</th>
</tr>
</thead>
<tbody>

<?php while ($u = $users->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($u['name']) ?></td>
<td><?= $u['email'] ?></td>
<td>
<span class="badge bg-secondary">
<?= strtoupper(str_replace('_',' ',$u['role'])) ?>
</span>
</td>
<td>
<span class="badge <?= $u['status']=='active'?'bg-success':'bg-danger' ?>">
<?= ucfirst($u['status']) ?>
</span>
</td>
<td>
<a href="?edit=<?= $u['id'] ?>"
   class="btn btn-sm btn-outline-primary me-1"
   title="Edit">
   <i class="fa fa-pen"></i>
</a>

<a href="?delete=<?= $u['id'] ?>"
   onclick="return confirm('Delete user?')"
   class="btn btn-sm btn-outline-danger"
   title="Delete">
   <i class="fa fa-trash"></i>
</a>
</td>

</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>
</div>

</div>
</div>

<?php include 'layout/footer.php'; ?>
</div>
</body>
</html>

