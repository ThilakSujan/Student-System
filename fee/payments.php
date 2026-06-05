<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';

$page_title = "Fee Payments";

$success = '';
$error   = '';

// Fetch fee structures for the dropdown
$structs_r = $mysqli->query(
    "SELECT fs.id, fc.name AS cat_name, fs.academic_year, fs.amount,
            COALESCE(cl.class_name, 'All Classes') AS class_name
     FROM fee_structures fs
     JOIN fee_categories fc ON fc.id = fs.category_id
     LEFT JOIN classes cl   ON cl.id = fs.class_id
     WHERE fs.status = 'Active'
     ORDER BY fc.name, fs.academic_year"
);
$structures = $structs_r ? $structs_r->fetch_all(MYSQLI_ASSOC) : [];

$students_r = $mysqli->query(
    "SELECT id, student_name FROM students WHERE status = 'Active' ORDER BY student_name"
);
$students = $students_r ? $students_r->fetch_all(MYSQLI_ASSOC) : [];

// ── DELETE (AJAX) ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    header('Content-Type: application/json');
    $del_id = (int)($_POST['id'] ?? 0);
    if ($del_id && $mysqli->query("DELETE FROM fee_payments WHERE id = $del_id")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $mysqli->error ?: 'Invalid request.']);
    }
    exit;
}

// ── ADD ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $student_id  = (int)($_POST['student_id']       ?? 0);
    $struct_id   = (int)($_POST['fee_assignment_id'] ?? 0);
    $amount_paid = (float)($_POST['amount_paid']     ?? 0);
    $pay_date    = trim($_POST['payment_date']        ?? '');
    $mode        = trim($_POST['payment_mode']        ?? 'Cash');
    $receipt     = trim($_POST['receipt_no']          ?? '');
    $remarks     = trim($_POST['remarks']             ?? '');
    $uid         = (int)($_SESSION['user_id']         ?? 0);

    $valid_modes = ['Cash', 'Bank Transfer', 'Cheque', 'Online', 'Other'];
    if (!in_array($mode, $valid_modes)) $mode = 'Cash';

    if (!$student_id || !$struct_id || $amount_paid <= 0 || !$pay_date) {
        $error = "Student, fee structure, amount and payment date are required.";
    } else {
        $stmt = $mysqli->prepare(
            "INSERT INTO fee_payments
             (student_id, fee_assignment_id, amount_paid, payment_date, payment_mode, receipt_no, remarks, recorded_by)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('iidssssi', $student_id, $struct_id, $amount_paid, $pay_date, $mode, $receipt, $remarks, $uid);
        if ($stmt->execute()) {
            $success = "Payment recorded successfully.";
        } else {
            $error = "Failed to record payment: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// ── EDIT ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $edit_id     = (int)($_POST['edit_id']           ?? 0);
    $student_id  = (int)($_POST['student_id']        ?? 0);
    $struct_id   = (int)($_POST['fee_assignment_id'] ?? 0);
    $amount_paid = (float)($_POST['amount_paid']     ?? 0);
    $pay_date    = trim($_POST['payment_date']        ?? '');
    $mode        = trim($_POST['payment_mode']        ?? 'Cash');
    $receipt     = trim($_POST['receipt_no']          ?? '');
    $remarks     = trim($_POST['remarks']             ?? '');

    $valid_modes = ['Cash', 'Bank Transfer', 'Cheque', 'Online', 'Other'];
    if (!in_array($mode, $valid_modes)) $mode = 'Cash';

    if (!$edit_id || !$student_id || !$struct_id || $amount_paid <= 0 || !$pay_date) {
        $error = "All required fields must be filled.";
    } else {
        $stmt = $mysqli->prepare(
            "UPDATE fee_payments
             SET student_id=?, fee_assignment_id=?, amount_paid=?, payment_date=?,
                 payment_mode=?, receipt_no=?, remarks=?
             WHERE id=?"
        );
        $stmt->bind_param('iidsssi', $student_id, $struct_id, $amount_paid, $pay_date, $mode, $receipt, $remarks, $edit_id);
        if ($stmt->execute()) {
            $success = "Payment updated successfully.";
        } else {
            $error = "Failed to update: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// ── Fetch all payments ────────────────────────────────────────
$result = $mysqli->query(
    "SELECT fp.*, s.student_name, fc.name AS cat_name, fs.academic_year, fs.amount AS fee_amount,
            COALESCE(cl.class_name, 'All Classes') AS class_name,
            u.username AS recorded_by_name
     FROM fee_payments fp
     JOIN students s         ON s.id  = fp.student_id
     JOIN fee_structures fs  ON fs.id = fp.fee_assignment_id
     JOIN fee_categories fc  ON fc.id = fs.category_id
     LEFT JOIN classes cl    ON cl.id = fs.class_id
     LEFT JOIN users u       ON u.id  = fp.recorded_by
     ORDER BY fp.payment_date DESC, fp.created_at DESC"
);
$payments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$total_amount  = array_sum(array_column($payments, 'amount_paid'));
$auto_open_add = isset($_GET['action']) && $_GET['action'] === 'add';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-credit-card me-2 text-info"></i>Fee Payments</h4>
            <small class="text-muted">Record and manage student fee payments</small>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPayModal">
                <i class="bi bi-plus-circle me-1"></i>Record Payment
            </button>
        </div>
    </div>

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

    <?php if (empty($structures)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        No active fee structures found.
        <a href="structures.php" class="alert-link">Add fee structures first</a> before recording payments.
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Total Payments',  count($payments),                    'bi-receipt',    '#e0e7ff','#4338ca'],
            ['Total Collected', '₹'.number_format($total_amount,2), 'bi-cash-stack', '#d1fae5','#065f46'],
        ] as [$l,$v,$ic,$bg,$fg]): ?>
        <div class="col-sm-6">
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

    <!-- Payments table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white">
            <strong><i class="bi bi-list-ul me-1"></i>All Payment Records</strong>
        </div>
        <div class="card-body p-0">
            <?php if (empty($payments)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted">No payments recorded yet.</p>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPayModal">
                        Record First Payment
                    </button>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="paymentsTable" class="table table-hover table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Fee Category</th>
                            <th>Class</th>
                            <th>Acad. Year</th>
                            <th>Fee Amount</th>
                            <th>Paid</th>
                            <th>Method</th>
                            <th>Receipt #</th>
                            <th>Date</th>
                            <th>Recorded By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($payments as $i => $p): ?>
                        <tr id="pay-row-<?= $p['id'] ?>">
                            <td class="text-muted align-middle"><?= $i + 1 ?></td>
                            <td class="align-middle fw-semibold"><?= htmlspecialchars($p['student_name']) ?></td>
                            <td class="align-middle">
                                <span class="badge bg-primary"><?= htmlspecialchars($p['cat_name']) ?></span>
                            </td>
                            <td class="align-middle text-muted" style="font-size:13px">
                                <?= htmlspecialchars($p['class_name']) ?>
                            </td>
                            <td class="align-middle"><?= htmlspecialchars($p['academic_year']) ?></td>
                            <td class="align-middle text-muted">₹<?= number_format($p['fee_amount'], 2) ?></td>
                            <td class="align-middle fw-bold text-success">₹<?= number_format($p['amount_paid'], 2) ?></td>
                            <td class="align-middle">
                                <?php
                                $badge = match($p['payment_mode']) {
                                    'Cash'          => 'bg-success',
                                    'Bank Transfer' => 'bg-info text-dark',
                                    'Cheque'        => 'bg-warning text-dark',
                                    'Online'        => 'bg-primary',
                                    default         => 'bg-secondary',
                                };
                                ?>
                                <span class="badge <?= $badge ?>"><?= htmlspecialchars($p['payment_mode']) ?></span>
                            </td>
                            <td class="align-middle text-muted" style="font-size:13px">
                                <?= htmlspecialchars($p['receipt_no'] ?: '—') ?>
                            </td>
                            <td class="align-middle text-muted" style="font-size:13px">
                                <?= date('d M Y', strtotime($p['payment_date'])) ?>
                            </td>
                            <td class="align-middle text-muted" style="font-size:13px">
                                <?= htmlspecialchars($p['recorded_by_name'] ?? '—') ?>
                            </td>
                            <td class="align-middle text-center" style="white-space:nowrap">
                                <button class="btn btn-warning btn-sm" title="Edit"
                                    onclick="openEditPay(<?= htmlspecialchars(json_encode($p)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" title="Delete"
                                    onclick="deletePay(<?= (int)$p['id'] ?>, '<?= htmlspecialchars(addslashes($p['student_name'])) ?>')">
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

<!-- ADD Payment Modal -->
<div class="modal fade" id="addPayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Record Fee Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Student <span class="text-danger">*</span></label>
                            <select name="student_id" class="form-select" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students as $st): ?>
                                    <option value="<?= $st['id'] ?>">
                                        <?= htmlspecialchars($st['student_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fee Structure <span class="text-danger">*</span></label>
                            <select name="fee_assignment_id" class="form-select" required>
                                <option value="">-- Select Fee Structure --</option>
                                <?php foreach ($structures as $fs): ?>
                                    <option value="<?= $fs['id'] ?>">
                                        <?= htmlspecialchars(
                                            $fs['cat_name'] . ' – ' . $fs['class_name'] .
                                            ' (' . $fs['academic_year'] . ') ₹' .
                                            number_format($fs['amount'], 2)
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount Paid (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="amount_paid" class="form-control"
                                   step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method</label>
                            <select name="payment_mode" class="form-select">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Online">Online</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Receipt No.</label>
                            <input type="text" name="receipt_no" class="form-control"
                                   placeholder="Optional receipt number">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2"
                                      placeholder="Optional remarks..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT Payment Modal -->
<div class="modal fade" id="editPayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Fee Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editPayForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="ep_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Student <span class="text-danger">*</span></label>
                            <select name="student_id" id="ep_student" class="form-select" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students as $st): ?>
                                    <option value="<?= $st['id'] ?>">
                                        <?= htmlspecialchars($st['student_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fee Structure <span class="text-danger">*</span></label>
                            <select name="fee_assignment_id" id="ep_struct" class="form-select" required>
                                <option value="">-- Select Fee Structure --</option>
                                <?php foreach ($structures as $fs): ?>
                                    <option value="<?= $fs['id'] ?>">
                                        <?= htmlspecialchars(
                                            $fs['cat_name'] . ' – ' . $fs['class_name'] .
                                            ' (' . $fs['academic_year'] . ') ₹' .
                                            number_format($fs['amount'], 2)
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount Paid (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="amount_paid" id="ep_amount" class="form-control"
                                   step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" id="ep_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method</label>
                            <select name="payment_mode" id="ep_mode" class="form-select">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Online">Online</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Receipt No.</label>
                            <input type="text" name="receipt_no" id="ep_receipt" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea name="remarks" id="ep_remarks" class="form-control" rows="2"></textarea>
                        </div>
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
if ($auto_open_add)   echo "<script>document.addEventListener('DOMContentLoaded',()=>{new bootstrap.Modal(document.getElementById('addPayModal')).show();});</script>";
?>
<?php include '../includes/footer.php'; ?>
</div><!-- /#content -->

<script>
$(document).ready(function () {
    $('#paymentsTable').DataTable({
        pageLength : 10,
        lengthMenu : [[5, 10, 25, 50], [5, 10, 25, 50]],
        columnDefs : [{ orderable: false, targets: 11 }],
        order      : [[0, 'asc']]
    });
});

function openEditPay(p) {
    document.getElementById('ep_id').value      = p.id;
    document.getElementById('ep_student').value = p.student_id;
    document.getElementById('ep_struct').value  = p.fee_assignment_id;
    document.getElementById('ep_amount').value  = p.amount_paid;
    document.getElementById('ep_date').value    = p.payment_date;
    document.getElementById('ep_mode').value    = p.payment_mode;
    document.getElementById('ep_receipt').value = p.receipt_no  || '';
    document.getElementById('ep_remarks').value = p.remarks     || '';
    new bootstrap.Modal(document.getElementById('editPayModal')).show();
}

function deletePay(id, name) {
    if (!confirm('Delete payment record for "' + name + '"? This cannot be undone.')) return;
    fetch('payments.php', {
        method  : 'POST',
        headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
        body    : 'action=delete&id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            window.showToast('Payment deleted!', 'success');
            setTimeout(() => {
                const row = document.getElementById('pay-row-' + id);
                if (row) row.remove();
            }, 400);
        } else {
            window.showToast(d.message || 'Failed to delete.', 'danger');
        }
    })
    .catch(() => window.showToast('Unexpected error.', 'danger'));
}
</script>
