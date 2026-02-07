<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireAdmin();

$successMsg = '';
$errorMsg   = '';

/* =========================
   SAVE FREE DELIVERY SETTING
========================= */
if (isset($_POST['save_setting'])) {
    $value = floatval($_POST['free_delivery_min_amount']);

    $stmt = $connection->prepare("
        INSERT INTO settings (setting_key, setting_value)
        VALUES ('free_delivery_min_amount', ?)
        ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)
    ");
    $stmt->bind_param("s", $value);
    $stmt->execute();

    $successMsg = "Delivery settings updated successfully.";
}

/* =========================
   ADD / UPDATE DISTRICT
========================= */
if (isset($_POST['save_district'])) {

    $id     = intval($_POST['id'] ?? 0);
    $name   = trim($_POST['district_name']);
    $charge = floatval($_POST['delivery_charge']);
    $status = $_POST['status'];

    if (!$name || $charge < 0) {
        $errorMsg = "District name and valid delivery charge required.";
    } else {
        if ($id === 0) {
            $stmt = $connection->prepare("
                INSERT INTO delivery_districts (district_name, delivery_charge, status)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("sds", $name, $charge, $status);
            $stmt->execute();
            $successMsg = "District added successfully.";
        } else {
            $stmt = $connection->prepare("
                UPDATE delivery_districts
                SET district_name=?, delivery_charge=?, status=?
                WHERE id=?
            ");
            $stmt->bind_param("sdsi", $name, $charge, $status, $id);
            $stmt->execute();
            $successMsg = "District updated successfully.";
        }
    }
}

/* DELETE DISTRICT */
if (isset($_GET['delete'])) {
    $connection->query("DELETE FROM delivery_districts WHERE id=".(int)$_GET['delete']);
    $successMsg = "District deleted successfully.";
}

/* =========================
   SEARCH + PAGINATION
========================= */
$limit  = 10;
$page   = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$search = trim($_GET['q'] ?? '');

$where  = '';
$params = [];
$types  = '';

if ($search !== '') {
    $where = "WHERE district_name LIKE ?";
    $params[] = "%$search%";
    $types = "s";
}

/* COUNT */
$countSql = "SELECT COUNT(*) AS total FROM delivery_districts $where";
$countStmt = $connection->prepare($countSql);
if ($search !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages   = ceil($totalRecords / $limit);

/* FETCH DISTRICTS */
$sql = "
    SELECT * FROM delivery_districts
    $where
    ORDER BY district_name
    LIMIT $limit OFFSET $offset
";
$stmt = $connection->prepare($sql);
if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$districts = $stmt->get_result();

/* FREE DELIVERY */
$res = $connection->query("
    SELECT setting_value FROM settings
    WHERE setting_key='free_delivery_min_amount'
");
$row = $res->fetch_assoc();
$freeDelivery = $row['setting_value'] ?? 0;

/* EDIT MODE */
$edit = null;
if (isset($_GET['edit'])) {
    $edit = $connection->query("
        SELECT * FROM delivery_districts WHERE id=".(int)$_GET['edit']
    )->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Delivery Settings</title>
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

<h4 class="fw-semibold mb-3">Delivery Management</h4>

<?php if ($successMsg): ?><div class="alert alert-success"><?= $successMsg ?></div><?php endif; ?>
<?php if ($errorMsg): ?><div class="alert alert-danger"><?= $errorMsg ?></div><?php endif; ?>

<ul class="nav nav-tabs mb-3">
<li class="nav-item">
<button class="nav-link active" data-bs-toggle="tab" data-bs-target="#districts">
District Charges
</button>
</li>
<li class="nav-item">
<button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings">
Free Delivery Settings
</button>
</li>
</ul>

<div class="tab-content">

<!-- DISTRICTS -->
<div class="tab-pane fade show active" id="districts">
<div class="row g-4">

<div class="col-lg-4">
<div class="card shadow-sm p-3">
<h6><?= $edit ? 'Edit District' : 'Add District' ?></h6>

<form method="post">
<input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

<input type="text" name="district_name" class="form-control mb-2"
value="<?= $edit['district_name'] ?? '' ?>" placeholder="District Name" required>

<input type="number" step="0.01" name="delivery_charge"
class="form-control mb-2"
value="<?= $edit['delivery_charge'] ?? '' ?>" placeholder="Delivery Charge" required>

<select name="status" class="form-select mb-3">
<option value="active" <?= ($edit && $edit['status']=='active')?'selected':'' ?>>Active</option>
<option value="inactive" <?= ($edit && $edit['status']=='inactive')?'selected':'' ?>>Inactive</option>
</select>

<button class="btn btn-dark w-100" name="save_district">
<?= $edit ? 'Update' : 'Save' ?>
</button>
</form>
</div>
</div>

<div class="col-lg-8">
<div class="card shadow-sm">

<div class="d-flex justify-content-between align-items-center p-3 border-bottom">
<h6 class="mb-0 fw-semibold">District List</h6>

<form method="get" class="d-flex">
<input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
class="form-control form-control-sm me-2" placeholder="Search district">
<button class="btn btn-sm btn-dark"><i class="fa fa-search"></i></button>
</form>
</div>

<div class="card-body p-0">
<table class="table table-bordered table-hover mb-0">
<thead class="table-light">
<tr>
<th>District</th>
<th>Charge</th>
<th>Status</th>
<th width="90">Action</th>
</tr>
</thead>
<tbody>

<?php if ($districts->num_rows === 0): ?>
<tr><td colspan="4" class="text-center text-muted">No districts found.</td></tr>
<?php endif; ?>

<?php while ($d = $districts->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($d['district_name']) ?></td>
<td>Rs. <?= number_format($d['delivery_charge'],2) ?></td>
<td>
<span class="badge bg-<?= $d['status']=='active'?'success':'secondary' ?>">
<?= ucfirst($d['status']) ?>
</span>
</td>
<td>
<a href="?edit=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary">
<i class="fa fa-pen"></i>
</a>
<a href="?delete=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger"
onclick="return confirm('Delete district?')">
<i class="fa fa-trash"></i>
</a>
</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

<?php if ($totalPages > 1): ?>
<div class="p-3">
<ul class="pagination pagination-sm mb-0">
<?php for ($i=1;$i<=$totalPages;$i++): ?>
<li class="page-item <?= $i==$page?'active':'' ?>">
<a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>">
<?= $i ?>
</a>
</li>
<?php endfor; ?>
</ul>
</div>
<?php endif; ?>

</div>
</div>
</div>

</div>
</div>

<!-- FREE DELIVERY -->
<div class="tab-pane fade" id="settings">
<div class="card shadow-sm p-3 col-lg-4">
<h6>Free Delivery Rule</h6>

<form method="post">
<input type="number" step="0.01" name="free_delivery_min_amount"
class="form-control mb-3"
value="<?= $freeDelivery ?>" required>

<button class="btn btn-dark w-100" name="save_setting">Save Settings</button>
</form>
</div>
</div>

</div>
</div>

<?php include 'layout/footer.php'; ?>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
