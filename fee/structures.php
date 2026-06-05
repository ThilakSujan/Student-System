<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';

$page_title = "Fee Structures";

$success = '';
$error   = '';

// Fetch supporting lists
$cats_r   = $mysqli->query("SELECT id, name FROM fee_categories WHERE status='Active' ORDER BY name");
$cats     = $cats_r ? $cats_r->fetch_all(MYSQLI_ASSOC) : [];

$class_r  = $mysqli->query("SELECT id, class_name, section FROM classes WHERE status='Active' ORDER BY class_name");
$classes  = $class_r ? $class_r->fetch_all(MYSQLI_ASSOC) : [];

// ── DELETE ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $del_id = (int)($_POST['id'] ?? 0);
    $chk    = $mysqli->query("SELECT COUNT(*) c FROM fee_payments WHERE fee_assignment_id=$del_id");
    $used   = $chk ? (int)$chk->fetch_assoc()['c'] : 0;
    if ($used > 0) {
        $error = "Cannot delete: this structure has $used payment record(s). Remove payments first.";
    } else {
        $mysqli->query("DELETE FROM fee_structures WHERE id=$del_id")
            ? $success = "Fee structure deleted."
            : $error = "Failed to delete.";
    }
}

// ── ADD ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $cat_id  = (int)($_POST['category_id'] ?? 0);
    $cls_id  = (int)($_POST['class_id'] ?? 0) ?: null;
    $year    = trim($_POST['academic_year'] ?? '');
    $amount  = (float)($_POST['amount'] ?? 0);
    $due     = trim($_POST['due_date'] ?? '') ?: null;
    $desc    = trim($_POST['description'] ?? '');
    $stat    = in_array($_POST['status'] ?? '', ['Active','Inactive']) ? $_POST['status'] : 'Active';
    $uid     = (int)($_SESSION['user_id'] ?? 0);

    if (!$cat_id || !$year || $amount <= 0) {
        $error = "Category, academic year, and a valid amount are required.";
    } else {
        $stmt = $mysqli->prepare(
            "INSERT INTO fee_structures (category_id,class_id,academic_year,amount,due_date,description,status,created_by)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('iisdsssi', $cat_id, $cls_id, $year, $amount, $due, $desc, $stat, $uid);
        if ($stmt->execute()) {
            $success = "Fee structure added successfully.";
        } else {
            $error = "Failed to add: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// ── EDIT ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $edit_id = (int)$_POST['edit_id'];
    $cat_id  = (int)($_POST['category_id'] ?? 0);
    $cls_id  = (int)($_POST['class_id'] ?? 0) ?: null;
    $year    = trim($_POST['academic_year'] ?? '');
    $amount  = (float)($_POST['amount'] ?? 0);
    $due     = trim($_POST['due_date'] ?? '') ?: null;
    $desc    = trim($_POST['description'] ?? '');
    $stat    = in_array($_POST['status'] ?? '', ['Active','Inactive']) ? $_POST['status'] : 'Active';

    if (!$cat_id || !$year || $amount <= 0) {
        $error = "Category, academic year, and a valid amount are required.";
    } else {
        $stmt = $mysqli->prepare(
            "UPDATE fee_structures SET category_id=?,class_id=?,academic_year=?,amount=?,due_date=?,description=?,status=? WHERE id=?"
        );
        $stmt->bind_param('iisdsssi', $cat_id, $cls_id, $year, $amount, $due, $desc, $stat, $edit_id);
        if ($stmt->execute()) {
            $success = "Fee structure updated successfully.";
        } else {
            $error = "Failed to update: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// ── Fetch all ─────────────────────────────────────────────────
$result = $mysqli->query(
    "SELECT fs.*, fc.name AS cat_name, cl.class_name, cl.section,
            (SELECT COUNT(*) FROM fee_payments fp WHERE fp.fee_assignment_id=fs.id) AS payment_count,
            (SELECT COALESCE(SUM(fp.amount_paid),0) FROM fee_payments fp WHERE fp.fee_assignment_id=fs.id) AS collected
     FROM fee_structures fs
     JOIN fee_categories fc ON fc.id=fs.category_id
     LEFT JOIN classes cl ON cl.id=fs.class_id
     ORDER BY fs.created_at DESC"
);
$structures = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-list-check me-2 text-success"></i>Fee Structures</h4>
            <small class="text-muted">Define the fee amount per category and class</small>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
            <?php if (empty($cats)): ?>
                <button class="btn btn-primary btn-sm" disabled title="Add fee categories first">
                    <i class="bi bi-exclamation-triangle me-1"></i>Add Category First
                </button>
            <?php else: ?>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addStructModal">
                    <i class="bi bi-plus-circle me-1"></i>Add Structure
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-x-circle me-2"></i><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <?php if (empty($cats)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        No active fee categories found. <a href="categories.php" class="alert-link">Add categories first</a> before creating fee structures.
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <?php
    $total_s  = count($structures);
    $active_s = count(array_filter($structures, fn($s)=>$s['status']==='Active'));
    $total_collected = array_sum(array_column($structures,'collected'));
    ?>
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Total Structures', $total_s,                                 'bi-list-check',  '#e0e7ff','#4338ca'],
            ['Active',           $active_s,                                'bi-check-circle','#d1fae5','#065f46'],
            ['Total Collected',  '₹'.number_format($total_collected,2),    'bi-cash-stack',  '#fef3c7','#92400e'],
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
        <div class="card-header bg-dark text-white"><strong><i class="bi bi-list-ul me-1"></i>All Fee Structures</strong></div>
        <div class="card-body p-0">
            <?php if (empty($structures)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted">No fee structures yet.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="structTable" class="table table-hover table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Class</th>
                            <th>Academic Year</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Collected</th>
                            <th>Payments</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($structures as $i => $s): ?>
                        <tr id="struct-row-<?= $s['id'] ?>">
                            <td class="text-muted align-middle"><?= $i+1 ?></td>
                            <td class="align-middle fw-semibold">
                                <span class="badge bg-primary"><?= htmlspecialchars($s['cat_name']) ?></span>
                            </td>
                            <td class="align-middle">
                                <?php if ($s['class_name']): ?>
                                    <?= htmlspecialchars($s['class_name'].($s['section'] ? ' ('.$s['section'].')' : '')) ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">All Classes</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle"><?= htmlspecialchars($s['academic_year']) ?></td>
                            <td class="align-middle fw-bold text-success">₹<?= number_format($s['amount'],2) ?></td>
                            <td class="align-middle text-muted" style="font-size:13px">
                                <?= $s['due_date'] ? date('d M Y', strtotime($s['due_date'])) : '—' ?>
                            </td>
                            <td class="align-middle fw-semibold text-info">₹<?= number_format($s['collected'],2) ?></td>
                            <td class="align-middle">
                                <span class="badge bg-secondary"><?= $s['payment_count'] ?></span>
                            </td>
                            <td class="align-middle">
                                <?php if ($s['status']==='Active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle text-center" style="white-space:nowrap">
                                <button class="btn btn-warning btn-sm" title="Edit"
                                    onclick="openEditStruct(<?= htmlspecialchars(json_encode($s)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" title="Delete"
                                    onclick="deleteStruct(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['cat_name'])) ?>')">
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
<div class="modal fade" id="addStructModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Fee Structure</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fee Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($cats as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Class <small class="text-muted">(leave blank for all)</small></label>
                            <select name="class_id" class="form-select">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $cl): ?>
                                    <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['class_name'].($cl['section']?' ('.$cl['section'].')':'')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Academic Year <span class="text-danger">*</span></label>
                            <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2024-25" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Add Structure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT Modal -->
<div class="modal fade" id="editStructModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Fee Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editStructForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="es_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fee Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="es_cat" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($cats as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Class <small class="text-muted">(blank = all)</small></label>
                            <select name="class_id" id="es_class" class="form-select">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $cl): ?>
                                    <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['class_name'].($cl['section']?' ('.$cl['section'].')':'')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Academic Year <span class="text-danger">*</span></label>
                            <input type="text" name="academic_year" id="es_year" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="es_amount" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Due Date</label>
                            <input type="date" name="due_date" id="es_due" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" id="es_status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" id="es_desc" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE form (for table-driven delete) -->
<form method="POST" id="deleteStructForm" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_struct_id">
</form>

<?php
if (!empty($success)) echo "<script>window._toastMsg=".json_encode($success).";window._toastType='success';</script>";
if (!empty($error))   echo "<script>window._toastMsg=".json_encode($error).";window._toastType='danger';</script>";
?>
<?php include '../includes/footer.php'; ?>
</div><!-- /#content -->

<script>
$(document).ready(function() {
    $('#structTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50],[5,10,25,50]],
        columnDefs: [{orderable:false, targets:9}],
        order: [[0,'asc']]
    });
});

function openEditStruct(s) {
    document.getElementById('es_id').value     = s.id;
    document.getElementById('es_cat').value    = s.category_id;
    document.getElementById('es_class').value  = s.class_id || '';
    document.getElementById('es_year').value   = s.academic_year;
    document.getElementById('es_amount').value = s.amount;
    document.getElementById('es_due').value    = s.due_date || '';
    document.getElementById('es_status').value = s.status;
    document.getElementById('es_desc').value   = s.description || '';
    new bootstrap.Modal(document.getElementById('editStructModal')).show();
}

function deleteStruct(id, name) {
    if (!confirm('Delete fee structure for "' + name + '"?\nThis will fail if payments exist for it.')) return;
    document.getElementById('delete_struct_id').value = id;
    document.getElementById('deleteStructForm').submit();
}
</script>
