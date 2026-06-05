<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';

$page_title = "Fee Categories";

$success = '';
$error   = '';

// ── ADD ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $stat  = in_array($_POST['status'] ?? '', ['Active','Inactive']) ? $_POST['status'] : 'Active';
    $uid   = (int)($_SESSION['user_id'] ?? 0);

    if ($name === '') {
        $error = "Category name is required.";
    } else {
        $stmt = $mysqli->prepare(
            "INSERT INTO fee_categories (name, description, status, created_by) VALUES (?,?,?,?)"
        );
        $stmt->bind_param('sssi', $name, $desc, $stat, $uid);
        if ($stmt->execute()) {
            $success = "Category \"$name\" added successfully.";
        } else {
            $error = "Failed to add category: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// ── EDIT ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $edit_id = (int)($_POST['edit_id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $desc    = trim($_POST['description'] ?? '');
    $stat    = in_array($_POST['status'] ?? '', ['Active','Inactive']) ? $_POST['status'] : 'Active';

    if (!$edit_id || $name === '') {
        $error = "Category name is required.";
    } else {
        $stmt = $mysqli->prepare(
            "UPDATE fee_categories SET name=?, description=?, status=? WHERE id=?"
        );
        $stmt->bind_param('sssi', $name, $desc, $stat, $edit_id);
        if ($stmt->execute()) {
            $success = "Category updated successfully.";
        } else {
            $error = "Failed to update category: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// ── Fetch all ─────────────────────────────────────────────────
// Use a subquery for created_by to avoid any JOIN-multiplication issues
$result = $mysqli->query(
    "SELECT fc.*,
            (SELECT username FROM users WHERE id = fc.created_by) AS created_by_name,
            (SELECT COUNT(*) FROM fee_structures WHERE category_id = fc.id) AS structure_count
     FROM fee_categories fc
     ORDER BY fc.created_at DESC"
);
$categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <!-- Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-tags me-2 text-primary"></i>Fee Categories</h4>
            <small class="text-muted">Define custom fee categories for your school</small>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCatModal">
                <i class="bi bi-plus-circle me-1"></i>Add Category
            </button>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-x-circle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <?php
    $total_cats  = count($categories);
    $active_cats = count(array_filter($categories, fn($c) => $c['status'] === 'Active'));
    ?>
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Total Categories', $total_cats,               'bi-tags',         '#e0e7ff','#4338ca'],
            ['Active',           $active_cats,              'bi-check-circle', '#d1fae5','#065f46'],
            ['Inactive',         $total_cats - $active_cats,'bi-dash-circle',  '#fee2e2','#991b1b'],
        ] as [$l,$v,$ic,$bg,$fg]): ?>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div style="width:44px;height:44px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-size:20px;">
                        <i class="bi <?= $ic ?>" style="color:<?= $fg ?>"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px"><?= $l ?></div>
                        <div class="fw-bold fs-4"><?= $v ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white">
            <strong><i class="bi bi-list-ul me-1"></i>All Fee Categories</strong>
        </div>
        <div class="card-body p-0">
            <?php if (empty($categories)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted">No fee categories yet.</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCatModal">
                        <i class="bi bi-plus-circle me-1"></i>Add First Category
                    </button>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="catsTable" class="table table-hover table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Structures</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $i => $cat): ?>
                        <tr id="cat-row-<?= $cat['id'] ?>">
                            <td class="text-muted align-middle"><?= $i + 1 ?></td>
                            <td class="align-middle fw-semibold"><?= htmlspecialchars($cat['name']) ?></td>
                            <td class="align-middle text-muted" style="font-size:13px">
                                <?= htmlspecialchars($cat['description'] ?: '—') ?>
                            </td>
                            <td class="align-middle">
                                <span class="badge bg-info text-dark"><?= (int)$cat['structure_count'] ?></span>
                            </td>
                            <td class="align-middle">
                                <?php if ($cat['status'] === 'Active'): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle text-muted" style="font-size:13px">
                                <?= htmlspecialchars($cat['created_by_name'] ?? '—') ?>
                            </td>
                            <td class="align-middle text-muted" style="font-size:13px">
                                <?= date('d M Y', strtotime($cat['created_at'])) ?>
                            </td>
                            <td class="align-middle text-center" style="white-space:nowrap">
                                <button class="btn btn-warning btn-sm" title="Edit"
                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($cat)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" title="Delete"
                                    onclick="deleteCat(<?= (int)$cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<!-- ADD Modal -->
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Fee Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               placeholder="e.g. Tuition Fee, Library Fee, Sports Fee" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Brief description of this fee category..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT Modal -->
<div class="modal fade" id="editCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Fee Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editCatForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="edit_cat_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_cat_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="edit_cat_desc" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="edit_cat_status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
if (!empty($success)) echo "<script>window._toastMsg=".json_encode($success).";window._toastType='success';</script>";
if (!empty($error))   echo "<script>window._toastMsg=".json_encode($error).";window._toastType='danger';</script>";
?>
<?php include '../includes/footer.php'; ?>
</div><!-- /#content -->

<script>
$(document).ready(function () {
    $('#catsTable').DataTable({
        pageLength : 10,
        lengthMenu : [[5, 10, 25, 50], [5, 10, 25, 50]],
        columnDefs : [{ orderable: false, targets: 7 }],
        order      : [[0, 'asc']]
    });
});

function openEditModal(cat) {
    document.getElementById('edit_cat_id').value     = cat.id;
    document.getElementById('edit_cat_name').value   = cat.name;
    document.getElementById('edit_cat_desc').value   = cat.description || '';
    document.getElementById('edit_cat_status').value = cat.status;
    new bootstrap.Modal(document.getElementById('editCatModal')).show();
}

function deleteCat(id, name) {
    if (!confirm('Delete category "' + name + '"?\nThis will fail if it has fee structures attached.')) return;
    fetch('delete_category.php', {
        method  : 'POST',
        headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
        body    : 'id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            window.showToast('Category deleted!', 'success');
            setTimeout(() => {
                const row = document.getElementById('cat-row-' + id);
                if (row) row.remove();
            }, 400);
        } else {
            window.showToast(d.message || 'Failed to delete.', 'danger');
        }
    })
    .catch(() => window.showToast('Unexpected error.', 'danger'));
}
</script>
