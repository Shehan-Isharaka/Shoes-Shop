<?php
require_once 'auth-check.php';
require_once '../includes/db.php';
require_once 'role-check.php';
requireAdmin();

$openMenu = 'catalog';
$currentPage = 'categories';

$successMsg = '';
$errorMsg   = '';

if (isset($_GET['updated'])) {
    $successMsg = "Sub-category updated successfully.";
}

/* =========================
   ADD / UPDATE CATEGORY
========================= */
if (isset($_POST['save_category'])) {

    $id        = intval($_POST['id'] ?? 0);
    $parent_id = intval($_POST['parent_id']);
    $name      = trim($_POST['name']);

    if (!$parent_id || !$name) {
        $errorMsg = "All fields are required.";
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        if ($id === 0) {
            $stmt = $connection->prepare("
                SELECT id FROM categories
                WHERE parent_id = ? AND name = ?
            ");
            $stmt->bind_param("is", $parent_id, $name);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errorMsg = "Sub-category already exists.";
            } else {
                $stmt = $connection->prepare("
                    INSERT INTO categories (parent_id, name, slug)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("iss", $parent_id, $name, $slug);
                $stmt->execute();
                $successMsg = "Sub-category added successfully.";
            }
        } else {
            $stmt = $connection->prepare("
                UPDATE categories
                SET name = ?, slug = ?
                WHERE id = ? AND parent_id IS NOT NULL
            ");
            $stmt->bind_param("ssi", $name, $slug, $id);
            $stmt->execute();
            $successMsg = "Sub-category updated successfully.";
            header("Location: categories.php?updated=1");
            exit;
        }
    }
}

/* =========================
   DELETE (SUB ONLY)
========================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $connection->prepare("
        DELETE FROM categories
        WHERE id = ? AND parent_id IS NOT NULL
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $successMsg = "Sub-category deleted successfully.";
}

/* =========================
   SEARCH + PAGINATION
========================= */
$limit  = 10;
$page   = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$search = trim($_GET['q'] ?? '');

$where = '';
$params = [];
$types = '';

if ($search !== '') {
    $where = "WHERE c.name LIKE ? OR p.name LIKE ?";
    $params = ["%$search%", "%$search%"];
    $types = "ss";
}

/* TOTAL COUNT */
$countSql = "
    SELECT COUNT(*) AS total
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    $where
";
$countStmt = $connection->prepare($countSql);
if ($search !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages   = ceil($totalRecords / $limit);

/* FETCH DATA */
$sql = "
    SELECT c.id, c.name, c.parent_id, c.status,
           p.name AS parent_name
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    $where
    ORDER BY p.name, c.name
    LIMIT $limit OFFSET $offset
";
$stmt = $connection->prepare($sql);
if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$categories = $stmt->get_result();

/* MAIN CATEGORIES */
$mainCats = $connection->query("
    SELECT id, name FROM categories
    WHERE parent_id IS NULL AND status='active'
");

/* EDIT MODE */
$editData = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $editData = $connection->query("
        SELECT * FROM categories
        WHERE id = $id AND parent_id IS NOT NULL
    ")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Category Management</title>
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

<h4 class="fw-semibold mb-3">Category Management</h4>

<?php if ($successMsg): ?>
<div class="alert alert-success"><?= $successMsg ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= $errorMsg ?></div>
<?php endif; ?>

<div class="row g-4">

<!-- ADD / EDIT FORM -->
<div class="col-lg-4">
<div class="card p-3">
<h6><?= $editData ? 'Edit Sub Category' : 'Add Sub Category' ?></h6>

<form method="post">
<input type="hidden" name="id" value="<?= $editData['id'] ?? 0 ?>">

<div class="mb-3">
<label>Main Category</label>
<select name="parent_id" class="form-select" required>
<option value="">Select</option>
<?php while ($m = $mainCats->fetch_assoc()): ?>
<option value="<?= $m['id'] ?>"
<?= ($editData && $editData['parent_id']==$m['id'])?'selected':'' ?>>
<?= htmlspecialchars($m['name']) ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="mb-3">
<label>Sub Category Name</label>
<input type="text" name="name" class="form-control"
value="<?= $editData['name'] ?? '' ?>" required>
</div>

<button class="btn btn-dark w-100" name="save_category">
<?= $editData ? 'Update' : 'Save' ?>
</button>
</form>
</div>
</div>

<!-- CATEGORY LIST -->
<div class="col-lg-8">
<div class="card shadow-sm">

<!-- HEADER + SEARCH -->
<div class="d-flex justify-content-between align-items-center p-3 border-bottom">
<h6 class="mb-0 fw-semibold">Category List</h6>

<form method="get" class="d-flex">
<input type="text" name="q"
value="<?= htmlspecialchars($search) ?>"
class="form-control form-control-sm me-2"
placeholder="Search category...">
<button class="btn btn-sm btn-dark">
<i class="fa fa-search"></i>
</button>
</form>
</div>

<div class="card-body p-0">

<table class="table table-bordered table-hover mb-0">
<thead class="table-light">
<tr>
<th>Main Category</th>
<th>Category Name</th>
<th>Status</th>
<th width="120">Action</th>
</tr>
</thead>

<tbody>
<?php if ($categories->num_rows === 0): ?>
<tr>
<td colspan="4" class="text-center text-muted">No categories found</td>
</tr>
<?php endif; ?>

<?php while ($c = $categories->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($c['parent_name'] ?? '—') ?></td>
<td><?= htmlspecialchars($c['name']) ?></td>
<td>
<span class="badge bg-<?= $c['status']=='active'?'success':'secondary' ?>">
<?= ucfirst($c['status']) ?>
</span>
</td>
<td>
<?php if ($c['parent_id']): ?>
<a href="categories.php?edit=<?= $c['id'] ?>"
class="btn btn-sm btn-outline-primary me-1"
title="Edit">
<i class="fa fa-pen"></i>
</a>
<a href="categories.php?delete=<?= $c['id'] ?>"
onclick="return confirm('Delete this sub-category?')"
class="btn btn-sm btn-outline-danger"
title="Delete">
<i class="fa fa-trash"></i>
</a>
<?php else: ?>
<span class="text-muted small">Locked</span>
<?php endif; ?>
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
