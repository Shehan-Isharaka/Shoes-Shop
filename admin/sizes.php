<?php
require_once 'auth-check.php';
require_once '../includes/db.php';

require_once 'role-check.php';
requireAdmin();



$successMsg = '';
$errorMsg = '';


if (isset($_GET['updated'])) {
    $successMsg = "Size updated successfully.";
}


if (isset($_POST['save'])) {

    $id     = intval($_POST['id'] ?? 0);
    $size   = trim($_POST['size_label']);
    $status = $_POST['status'];

    if (!$size) {
        $errorMsg = "Size is required.";
    } else {

        // 🔍 Duplicate size check
        $checkSql = "SELECT id FROM sizes WHERE size_label = ? AND id != ?";
        $checkStmt = $connection->prepare($checkSql);
        $checkStmt->bind_param("si", $size, $id);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $errorMsg = "This size already exists.";
        } else {

            if ($id === 0) {
                // INSERT
                $stmt = $connection->prepare("
                    INSERT INTO sizes (size_label, status)
                    VALUES (?, ?)
                ");
                $stmt->bind_param("ss", $size, $status);
                $stmt->execute();
                $successMsg = "Size added successfully.";
            } else {
                // UPDATE
                $stmt = $connection->prepare("
                    UPDATE sizes SET size_label=?, status=?
                    WHERE id=?
                ");
                $stmt->bind_param("ssi", $size, $status, $id);
                $stmt->execute();
                header("Location: sizes.php?updated=1");
                exit;
            }
        }
    }
}


if (isset($_GET['delete'])) {
    $connection->query("DELETE FROM sizes WHERE id=".(int)$_GET['delete']);
    $successMsg = "Size deleted successfully.";
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = $connection->query("SELECT * FROM sizes WHERE id=".(int)$_GET['edit'])->fetch_assoc();
}

$sizes = $connection->query("SELECT * FROM sizes ORDER BY size_label");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sizes</title>
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
<h4 class="fw-semibold mb-3">Size Management</h4>

<?php if ($successMsg): ?><div class="alert alert-success"><?= $successMsg ?></div><?php endif; ?>
<?php if ($errorMsg): ?><div class="alert alert-danger"><?= $errorMsg ?></div><?php endif; ?>

<div class="row g-4">

<div class="col-lg-4">
<div class="card shadow-sm p-3">
<h6><?= $edit ? 'Edit Size' : 'Add Size' ?></h6>

<form method="post">
<input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

<div class="mb-3">
    <label class="form-label">Size</label>
    <input type="text" name="size_label" class="form-control"
           placeholder="UK 8 / EU 42"
           value="<?= $edit['size_label'] ?? '' ?>" required>
</div>

<div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
        <option value="active" <?= ($edit && $edit['status']=='active')?'selected':'' ?>>Active</option>
        <option value="inactive" <?= ($edit && $edit['status']=='inactive')?'selected':'' ?>>Inactive</option>
    </select>
</div>

<button class="btn btn-dark w-100" name="save">
<?= $edit ? 'Update' : 'Save' ?>
</button>
</form>
</div>
</div>

<div class="col-lg-8">
<div class="card shadow-sm p-3">
<h6>Size List</h6>

<table class="table table-bordered">
<thead class="table-light">
<tr>
<th>Size</th>
<th>Status</th>
<th width="140">Action</th>
</tr>
</thead>
<tbody>
<?php while ($s = $sizes->fetch_assoc()): ?>
<tr>
<td><?= $s['size_label'] ?></td>
<td><?= ucfirst($s['status']) ?></td>
<td>
    <a href="?edit=<?= $s['id'] ?>"
       class="btn btn-sm btn-outline-primary me-1"
       title="Edit">
        <i class="fa fa-pen"></i>
    </a>

    <a href="?delete=<?= $s['id'] ?>"
       onclick="return confirm('Delete size?')"
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

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/admin-dashboard.js"></script>
</body>
</html>
