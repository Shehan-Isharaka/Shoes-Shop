<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireAdmin();

$openMenu = 'catalog';
$currentPage = 'brands';

$successMsg = '';
$errorMsg   = '';

if (isset($_GET['updated'])) {
    $successMsg = "Brand updated successfully.";
}

/* =========================
   ADD / UPDATE BRAND
========================= */
if (isset($_POST['save'])) {

    $id     = intval($_POST['id'] ?? 0);
    $name   = trim($_POST['name']);
    $status = $_POST['status'];

    if (!$name) {
        $errorMsg = "Brand name is required.";
    } else {
    
        // 🔍 Check duplicate brand name
        $checkSql = "SELECT id FROM brands WHERE name = ? AND id != ?";
        $checkStmt = $connection->prepare($checkSql);
        $checkStmt->bind_param("si", $name, $id);
        $checkStmt->execute();
        $checkStmt->store_result();
    
        if ($checkStmt->num_rows > 0) {
            $errorMsg = "Brand name already exists.";
        } else {
    
            if ($id === 0) { 
                // INSERT
                $stmt = $connection->prepare("
                    INSERT INTO brands (name, status)
                    VALUES (?, ?)
                ");
                $stmt->bind_param("ss", $name, $status);
                $stmt->execute();
                $successMsg = "Brand added successfully.";
            } else {
                // UPDATE
                $stmt = $connection->prepare("
                    UPDATE brands SET name=?, status=?
                    WHERE id=?
                ");
                $stmt->bind_param("ssi", $name, $status, $id);
                $stmt->execute();
                header("Location: brands.php?updated=1");
                exit;
            }
        }
    }    
}

/* =========================
   DELETE BRAND
========================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $connection->query("DELETE FROM brands WHERE id=$id");
    $successMsg = "Brand deleted successfully.";
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
    $where = "WHERE name LIKE ?";
    $params[] = "%$search%";
    $types = "s";
}

/* TOTAL COUNT */
$countSql = "SELECT COUNT(*) AS total FROM brands $where";
$countStmt = $connection->prepare($countSql);
if ($search !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages   = ceil($totalRecords / $limit);

/* FETCH BRANDS */
$sql = "
    SELECT * FROM brands
    $where
    ORDER BY name
    LIMIT $limit OFFSET $offset
";
$stmt = $connection->prepare($sql);
if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$brands = $stmt->get_result();

/* EDIT MODE */
$edit = null;
if (isset($_GET['edit'])) {
    $edit = $connection->query("
        SELECT * FROM brands WHERE id=".(int)$_GET['edit']
    )->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Brand Management</title>
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

<h4 class="fw-semibold mb-3">Brand Management</h4>

<?php if ($successMsg): ?>
<div class="alert alert-success"><?= $successMsg ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= $errorMsg ?></div>
<?php endif; ?>

<div class="row g-4">

<!-- ADD / EDIT FORM -->
<div class="col-lg-4">
<div class="card shadow-sm p-3">
<h6><?= $edit ? 'Edit Brand' : 'Add Brand' ?></h6>

<form method="post">
<input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

<input type="text" name="name"
class="form-control mb-2"
placeholder="Brand Name"
value="<?= $edit['name'] ?? '' ?>" required>

<select name="status" class="form-select mb-3">
<option value="active" <?= ($edit && $edit['status']=='active')?'selected':'' ?>>
Active
</option>
<option value="inactive" <?= ($edit && $edit['status']=='inactive')?'selected':'' ?>>
Inactive
</option>
</select>

<button class="btn btn-dark w-100" name="save">
<?= $edit ? 'Update' : 'Save' ?>
</button>
</form>
</div>
</div>

<!-- BRAND LIST -->
<div class="col-lg-8">
<div class="card shadow-sm">

<!-- HEADER + SEARCH -->
<div class="d-flex justify-content-between align-items-center p-3 border-bottom">
<h6 class="mb-0 fw-semibold">Brand List</h6>

<form method="get" class="d-flex">
<input type="text" name="q"
value="<?= htmlspecialchars($search) ?>"
class="form-control form-control-sm me-2"
placeholder="Search brand...">
<button class="btn btn-sm btn-dark">
<i class="fa fa-search"></i>
</button>
</form>
</div>

<div class="card-body p-0">

<table class="table table-bordered table-hover mb-0">
<thead class="table-light">
<tr>
<th>Name</th>
<th>Status</th>
<th width="120">Action</th>
</tr>
</thead>

<tbody>
<?php if ($brands->num_rows === 0): ?>
<tr>
<td colspan="3" class="text-center text-muted">No brands found</td>
</tr>
<?php endif; ?>

<?php while ($b = $brands->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($b['name']) ?></td>
<td>
<span class="badge bg-<?= $b['status']=='active'?'success':'secondary' ?>">
<?= ucfirst($b['status']) ?>
</span>
</td>
<td>
<a href="brands.php?edit=<?= $b['id'] ?>"
class="btn btn-sm btn-outline-primary me-1"
title="Edit">
<i class="fa fa-pen"></i>
</a>
<a href="brands.php?delete=<?= $b['id'] ?>"
onclick="return confirm('Delete brand?')"
class="btn btn-sm btn-outline-danger"
title="Delete">
<i class="fa fa-trash"></i>
</a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<div class="p-3">
<nav>
<ul class="pagination pagination-sm mb-0">
<?php for ($i=1; $i<=$totalPages; $i++): ?>
<li class="page-item <?= $i==$page?'active':'' ?>">
<a class="page-link"
href="?page=<?= $i ?>&q=<?= urlencode($search) ?>">
<?= $i ?>
</a>
</li>
<?php endfor; ?>
</ul>
</nav>
</div>
<?php endif; ?>

</div>
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
