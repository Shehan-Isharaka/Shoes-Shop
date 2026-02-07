<?php
require_once 'auth-check.php';
require_once '../includes/db.php';

require_once 'role-check.php';
requireStockKeeper();


$successMsg = '';
$errorMsg = '';

/* =========================
   UPDATE ALERT STATUS
========================= */
if (isset($_POST['update_alert'])) {

    $id = intval($_POST['id']);
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);

    $stmt = $connection->prepare("
        UPDATE low_stock_alerts
        SET status=?, notes=?, updated_at=NOW()
        WHERE id=?
    ");
    $stmt->bind_param("ssi", $status, $notes, $id);
    $stmt->execute();

    $successMsg = "Low stock alert updated successfully.";
}

/* =========================
   FETCH LOW STOCK ALERTS
========================= */
$alerts = $connection->query("
    SELECT 
        lsa.id,
        lsa.current_stock,
        lsa.threshold,
        lsa.status,
        lsa.notes,
        p.name AS product_name,
        s.size_label,
        c.name AS color_name
    FROM low_stock_alerts lsa
    JOIN product_variants pv ON lsa.product_variant_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN sizes s ON pv.size_id = s.id
    JOIN colors c ON pv.color_id = c.id
    ORDER BY lsa.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Low Stock Alerts</title>
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

<h4 class="fw-semibold mb-3">Low Stock Alerts</h4>

<?php if ($successMsg): ?>
<div class="alert alert-success"><?= $successMsg ?></div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= $errorMsg ?></div>
<?php endif; ?>

<div class="card shadow-sm">
<div class="card-body p-0">

<table class="table table-bordered table-hover mb-0">
<thead class="table-light">
<tr>
    <th>Product</th>
    <th>Size</th>
    <th>Color</th>
    <th>Stock</th>
    <th>Threshold</th>
    <th>Status</th>
    <th>Notes</th>
    <th width="160">Action</th>
</tr>
</thead>
<tbody>

<?php if ($alerts->num_rows === 0): ?>
<tr>
    <td colspan="8" class="text-center text-muted">
        No low stock alerts found.
    </td>
</tr>
<?php endif; ?>

<?php while ($a = $alerts->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($a['product_name']) ?></td>
    <td><?= htmlspecialchars($a['size_label']) ?></td>
    <td><?= htmlspecialchars($a['color_name']) ?></td>
    <td>
        <span class="badge bg-danger"><?= $a['current_stock'] ?></span>
    </td>
    <td><?= $a['threshold'] ?></td>
    <td>
        <span class="badge 
            <?=
            $a['status']=='new' ? 'bg-danger' :
            ($a['status']=='reviewed' ? 'bg-warning text-dark' :
            ($a['status']=='reordered' ? 'bg-info' : 'bg-success'))
            ?>">
            <?= ucfirst($a['status']) ?>
        </span>
    </td>
    <td><?= nl2br(htmlspecialchars($a['notes'])) ?></td>
    <td>
        <button class="btn btn-sm btn-dark"
                data-bs-toggle="modal"
                data-bs-target="#alertModal<?= $a['id'] ?>">
            Update
        </button>
    </td>
</tr>

<!-- MODAL -->
<div class="modal fade" id="alertModal<?= $a['id'] ?>" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">

<form method="post">
<div class="modal-header">
    <h6 class="modal-title">Update Alert</h6>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<input type="hidden" name="id" value="<?= $a['id'] ?>">

<div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
        <option value="new" <?= $a['status']=='new'?'selected':'' ?>>New</option>
        <option value="reviewed" <?= $a['status']=='reviewed'?'selected':'' ?>>Reviewed</option>
        <option value="reordered" <?= $a['status']=='reordered'?'selected':'' ?>>Reordered</option>
        <option value="resolved" <?= $a['status']=='resolved'?'selected':'' ?>>Resolved</option>
    </select>
</div>

<div class="mb-3">
    <label class="form-label">Notes</label>
    <textarea name="notes" rows="3"
              class="form-control"><?= htmlspecialchars($a['notes']) ?></textarea>
</div>
</div>

<div class="modal-footer">
    <button type="submit" name="update_alert" class="btn btn-dark">
        Save Changes
    </button>
</div>

</form>

</div>
</div>
</div>
<?php endwhile; ?>

</tbody>
</table>

</div>
</div>

</div>

<?php include 'layout/footer.php'; ?>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/admin-dashboard.js"></script>
</body>
</html>
