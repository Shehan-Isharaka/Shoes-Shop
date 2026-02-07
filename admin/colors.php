<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireAdmin();

$openMenu = 'catalog';
$currentPage = 'colors';

$successMsg = '';
$errorMsg = '';

if (isset($_GET['updated'])) {
    $successMsg = "Color updated successfully.";
}


/* =========================
   ADD / UPDATE COLOR
========================= */
if (isset($_POST['save'])) {

    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name']);
    $hex = $_POST['hex_code'];
    $status = $_POST['status'];

    if (!$name) {
        $errorMsg = "Color name is required.";
    } else {

        // 🔍 Duplicate check (name OR hex code)
        $checkSql = "
            SELECT id FROM colors
            WHERE (name = ? OR hex_code = ?)
            AND id != ?
        ";
        $checkStmt = $connection->prepare($checkSql);
        $checkStmt->bind_param("ssi", $name, $hex, $id);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $errorMsg = "This color name or color code already exists.";
        } else {

            if ($id === 0) {
                // INSERT
                $stmt = $connection->prepare("
                    INSERT INTO colors (name, hex_code, status)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("sss", $name, $hex, $status);
                $stmt->execute();
                $successMsg = "Color added successfully.";
            } else {
                // UPDATE
                $stmt = $connection->prepare("
                    UPDATE colors
                    SET name=?, hex_code=?, status=?
                    WHERE id=?
                ");
                $stmt->bind_param("sssi", $name, $hex, $status, $id);
                $stmt->execute();
                header("Location: colors.php?updated=1");
                exit;
            }
        }
    }
}


/* =========================
   DELETE COLOR
========================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $connection->query("DELETE FROM colors WHERE id=$id");
    $successMsg = "Color deleted successfully.";
}

/* =========================
   SEARCH + PAGINATION
========================= */
$limit = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$search = trim($_GET['q'] ?? '');

$where = '';
$params = [];
$types = '';

if ($search !== '') {
    $where = "WHERE name LIKE ?";
    $params[] = "%$search%";
    $types = "s";
}

/* TOTAL COUNT */
$countSql = "SELECT COUNT(*) AS total FROM colors $where";
$countStmt = $connection->prepare($countSql);
if ($search !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

/* FETCH COLORS */
$sql = "
    SELECT * FROM colors
    $where
    ORDER BY name
    LIMIT $limit OFFSET $offset
";
$stmt = $connection->prepare($sql);
if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$colors = $stmt->get_result();

/* EDIT MODE */
$edit = null;
if (isset($_GET['edit'])) {
    $edit = $connection->query("
        SELECT * FROM colors WHERE id=" . (int)$_GET['edit'])->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Color Management</title>
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

<h4 class="fw-semibold mb-3">Color Management</h4>

<?php if ($successMsg) : ?>
<div class="alert alert-success"><?= $successMsg ?></div>
<?php endif; ?>
<?php if ($errorMsg) : ?>
<div class="alert alert-danger"><?= $errorMsg ?></div>
<?php endif; ?>

<div class="row g-4">

<!-- ADD / EDIT FORM -->
<div class="col-lg-4">
<div class="card shadow-sm p-3">
<h6><?= $edit ? 'Edit Color' : 'Add Color' ?></h6>

<form method="post">
<input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

<div class="mb-3">
<label class="form-label">Color Name</label>
<input type="text" name="name" class="form-control"
value="<?= $edit['name'] ?? '' ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Color Code</label>
<input type="color" name="hex_code"
class="form-control form-control-color"
value="<?= $edit['hex_code'] ?? '#000000' ?>">
</div>

<div class="mb-3">
<label class="form-label">Status</label>
<select name="status" class="form-select">
<option value="active" <?= ($edit && $edit['status'] == 'active') ? 'selected' : '' ?>>
Active
</option>
<option value="inactive" <?= ($edit && $edit['status'] == 'inactive') ? 'selected' : '' ?>>
Inactive
</option>
</select>
</div>

<button class="btn btn-dark w-100" name="save">
<?= $edit ? 'Update' : 'Save' ?>
</button>
</form>
</div>
</div>

<!-- COLOR LIST -->
<div class="col-lg-8">
<div class="card shadow-sm">

<!-- HEADER + SEARCH -->
<div class="d-flex justify-content-between align-items-center p-3 border-bottom">
<h6 class="mb-0 fw-semibold">Color List</h6>

<form method="get" class="d-flex">
<input type="text" name="q"
value="<?= htmlspecialchars($search) ?>"
class="form-control form-control-sm me-2"
placeholder="Search color...">
<button class="btn btn-sm btn-dark">
<i class="fa fa-search"></i>
</button>
</form>
</div>

<div class="card-body p-0">

<table class="table table-bordered table-hover mb-0">
<thead class="table-light">
<tr>
<th>Color</th>
<th>Preview</th>
<th>Status</th>
<th width="120">Action</th>
</tr>
</thead>

<tbody>
<?php if ($colors->num_rows === 0) : ?>
<tr>
<td colspan="4" class="text-center text-muted">No colors found</td>
</tr>
<?php endif; ?>

<?php while ($c = $colors->fetch_assoc()) : ?>
<tr>
<td><?= htmlspecialchars($c['name']) ?></td>
<td>
<span style="
display:inline-block;
width:32px;
height:18px;
background:<?= $c['hex_code'] ?>;
border:1px solid #ccc;
border-radius:3px;"></span>
</td>
<td>
<span class="badge bg-<?= $c['status'] == 'active' ? 'success' : 'secondary' ?>">
<?= ucfirst($c['status']) ?>
</span>
</td>
<td>
<a href="colors.php?edit=<?= $c['id'] ?>"
class="btn btn-sm btn-outline-primary me-1"
title="Edit">
<i class="fa fa-pen"></i>
</a>
<a href="colors.php?delete=<?= $c['id'] ?>"
onclick="return confirm('Delete color?')"
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
<?php if ($totalPages > 1) : ?>
<div class="p-3">
<nav>
<ul class="pagination pagination-sm mb-0">
<?php for ($i = 1; $i <= $totalPages; $i++) : ?>
<li class="page-item <?= $i == $page ? 'active' : '' ?>">
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
