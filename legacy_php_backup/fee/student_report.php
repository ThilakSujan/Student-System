<?php
session_start();
require_once '../includes/auth.php';
require_role(['student', 'admin', 'staff']);
require_once '../config/db.php';

$page_title  = "My Fee Report";
$role        = $_SESSION['role'] ?? '';
$current_uid = (int)($_SESSION['user_id'] ?? 0);

// Admin/staff can pass ?student_id=X
if (in_array($role, ['admin', 'staff']) && isset($_GET['student_id'])) {
    $student_id = (int)$_GET['student_id'];
} else {
    // Strictly enforce student role sees only their own record
    $student_id = (int)($_SESSION['student_id'] ?? 0);
    
    // Fallback if student_id not in session but email is
    if (!$student_id && !empty($_SESSION['email'])) {
        $email_q    = $mysqli->real_escape_string($_SESSION['email']);
        $sq         = $mysqli->query("SELECT id FROM students WHERE email='$email_q' LIMIT 1");
        $student_id = $sq ? (int)($sq->fetch_assoc()['id'] ?? 0) : 0;
    }
}

// Fetch student info
$student = null;
if ($student_id) {
    $sr      = $mysqli->query("SELECT * FROM students WHERE id=$student_id LIMIT 1");
    $student = $sr ? $sr->fetch_assoc() : null;
}

// ── Fetch applicable fee structures for this student ─────────
$fee_data = [];
if ($student_id) {
    $res = $mysqli->query(
        "SELECT fs.id AS struct_id, fc.name AS category, fs.academic_year,
                fs.amount, fs.due_date, fs.description,
                COALESCE(cl.class_name,'—') AS class_name,
                COALESCE(cl.section,'') AS section,
                COALESCE(
                    (SELECT SUM(fp.amount_paid)
                     FROM fee_payments fp
                     WHERE fp.student_id = $student_id
                       AND fp.fee_assignment_id = fs.id), 0
                ) AS paid
         FROM fee_structures fs
         JOIN fee_categories fc ON fc.id = fs.category_id
         LEFT JOIN classes cl   ON cl.id = fs.class_id
         WHERE fs.status = 'Active'
           AND (
               fs.class_id IS NULL
               OR fs.class_id IN (
                   SELECT class_id FROM class_students WHERE student_id = $student_id
               )
           )
         ORDER BY fs.academic_year DESC, fc.name"
    );
    $fee_data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// Compute totals
$grand_total   = array_sum(array_column($fee_data, 'amount'));
$grand_paid    = array_sum(array_column($fee_data, 'paid'));
$grand_pending = $grand_total - $grand_paid;

// For admin/staff: list of students for selector
$all_students = [];
if (in_array($role, ['admin', 'staff'])) {
    $asr          = $mysqli->query("SELECT id, student_name FROM students WHERE status='Active' ORDER BY student_name");
    $all_students = $asr ? $asr->fetch_all(MYSQLI_ASSOC) : [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
.status-paid    { background:#d1fae5; color:#065f46; }
.status-partial { background:#fef3c7; color:#92400e; }
.status-pending { background:#fee2e2; color:#991b1b; }
.status-overdue { background:#fce7f3; color:#9d174d; }
</style>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i>
                <?= $role === 'student' ? 'My Fee Report' : 'Student Fee Report' ?>
            </h4>
            <small class="text-muted">Detailed fee payment and pending summary</small>
        </div>
        <div class="d-flex gap-2">
            <?php if (in_array($role, ['admin', 'staff'])): ?>
                <a href="staff_report.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Pending Dues
                </a>
            <?php else: ?>
                <a href="../dashboard/dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Dashboard
                </a>
            <?php endif; ?>
            
            <?php if ($student_id): ?>
            <!-- Opens the standalone invoice template in the same tab -->
            <a href="student_invoice.php?student_id=<?= $student_id ?>" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>Download Payment Invoice
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Admin/Staff: Student Selector -->
    <?php if (in_array($role, ['admin','staff'])): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold mb-1">Select Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">-- Choose a student --</option>
                        <?php foreach ($all_students as $st): ?>
                            <option value="<?= $st['id'] ?>"
                                <?= $student_id == $st['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($st['student_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>View Report
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$student): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= in_array($role, ['admin','staff']) ? 'Please select a student above.' : 'No student record found linked to your account.' ?>
    </div>
    <?php else: ?>

    <!-- Student info card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:26px;color:#fff;font-weight:700;">
                        <?= strtoupper(substr($student['student_name'],0,1)) ?>
                    </div>
                </div>
                <div class="col">
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($student['student_name']) ?></h5>
                    <div class="text-muted" style="font-size:13px">
                        <span class="me-3"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($student['email'] ?? '—') ?></span>
                        <span class="me-3"><i class="bi bi-building me-1"></i><?= htmlspecialchars($student['department'] ?? '—') ?></span>
                        <span><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($student['phone'] ?? '—') ?></span>
                    </div>
                </div>
                <div class="col-auto text-end">
                    <span class="badge <?= $student['status']==='Active' ? 'bg-success' : 'bg-danger' ?> fs-6">
                        <?= htmlspecialchars($student['status'] ?? '—') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats (Marksheet style) -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Total Fees',    '₹'.number_format($grand_total,2),   'bi-cash-stack'],
            ['Total Paid',    '₹'.number_format($grand_paid,2),    'bi-check-circle-fill'],
            ['Total Pending', '₹'.number_format($grand_pending,2), 'bi-exclamation-circle'],
        ] as [$l,$v,$ic]): ?>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:52px;height:52px;border-radius:14px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;">
                        <i class="bi <?= $ic ?> text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:13px; font-weight:500;"><?= $l ?></div>
                        <div class="fw-bold fs-4 text-dark"><?= $v ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Fee Details Table -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <h5 class="card-title fw-bold mb-3"><i class="bi bi-bar-chart-fill me-2 text-dark"></i>My Fee Details</h5>
            <div class="mb-3">
                <strong>Total:</strong> ₹<?= number_format($grand_total,2) ?>
                &nbsp;|&nbsp;
                <strong>Paid:</strong> ₹<?= number_format($grand_paid,2) ?>
                &nbsp;|&nbsp;
                <strong>Balance:</strong> <span class="badge bg-<?= $grand_pending > 0 ? 'warning text-dark' : 'success' ?>"><?= $grand_pending > 0 ? '₹'.number_format($grand_pending,2).' Pending' : 'Fully Paid' ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle" id="feeDetailsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fee Category</th>
                            <th>Academic Year</th>
                            <th>Total Fee</th>
                            <th>Paid</th>
                            <th>Progress</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fee_data as $i => $f):
                        $balance = $f['amount'] - $f['paid'];
                        $overdue = $f['due_date'] && $f['due_date'] < date('Y-m-d') && $balance > 0;
                        if ($balance <= 0)           { $status = 'Paid';    $cls = 'status-paid'; }
                        elseif ($f['paid'] > 0)      { $status = 'Partial'; $cls = 'status-partial'; }
                        elseif ($overdue)            { $status = 'Overdue'; $cls = 'status-overdue'; }
                        else                         { $status = 'Pending'; $cls = 'status-pending'; }
                    ?>
                        <tr>
                            <td class="text-muted"><?= $i+1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($f['category']) ?></td>
                            <td><?= htmlspecialchars($f['academic_year']) ?></td>
                            <td class="fw-semibold">₹<?= number_format($f['amount'],2) ?></td>
                            <td class="text-success fw-bold">₹<?= number_format($f['paid'],2) ?></td>
                            <td style="width:200px">
                                <?php $pct = $f['amount']>0 ? round(($f['paid']/$f['amount'])*100) : 0; ?>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-<?= $pct==100 ? 'success' : ($pct>0 ? 'warning' : 'danger') ?>" 
                                             role="progressbar" style="width: <?= $pct ?>%;" 
                                             aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span style="font-size:12px;color:#64748b"><?= $pct ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-3 py-2 <?= $cls ?>">
                                    <?= $status ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <?php
    $history = $mysqli->query(
        "SELECT fp.*, fc.name AS cat_name, fs.academic_year, fs.amount AS fee_amount
         FROM fee_payments fp
         JOIN fee_structures fs ON fs.id = fp.fee_assignment_id
         JOIN fee_categories fc ON fc.id = fs.category_id
         WHERE fp.student_id = $student_id
         ORDER BY fp.payment_date DESC"
    );
    $history_rows = $history ? $history->fetch_all(MYSQLI_ASSOC) : [];
    ?>
    <?php if (!empty($history_rows)): ?>
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <h5 class="card-title fw-bold mb-3"><i class="bi bi-clock-history me-2 text-dark"></i>Payment History</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle" id="paymentHistoryTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Academic Year</th>
                            <th>Amount Paid</th>
                            <th>Method</th>
                            <th>Date</th>
                            <?php if ($role === 'admin'): ?>
                            <th class="text-center">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history_rows as $i => $h): ?>
                        <tr id="pay-row-<?= $h['id'] ?>">
                            <td class="text-muted"><?= $i+1 ?></td>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($h['cat_name']) ?></span></td>
                            <td><?= htmlspecialchars($h['academic_year']) ?></td>
                            <td class="fw-bold text-success">₹<?= number_format($h['amount_paid'],2) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($h['payment_mode']) ?></span></td>
                            <td><?= date('d M Y', strtotime($h['payment_date'])) ?></td>
                            <?php if ($role === 'admin'): ?>
                            <td class="text-center">
                                <button class="btn btn-danger btn-sm" onclick="deletePay(<?= (int)$h['id'] ?>)" title="Delete Payment">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; // end $student check ?>

</div>
</div>
<?php include '../includes/footer.php'; ?>
<script>
$(document).ready(function(){
    $('#feeDetailsTable, #paymentHistoryTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50],[5,10,25,50]]
    });
});

<?php if ($role === 'admin'): ?>
function deletePay(id) {
    if (!confirm('Are you sure you want to delete this payment record? This cannot be undone.')) return;
    
    fetch('payments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=delete&id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            window.showToast ? window.showToast('Payment deleted!', 'success') : alert('Payment deleted!');
            const row = document.getElementById('pay-row-' + id);
            if (row) row.remove();
            setTimeout(() => location.reload(), 800); // reload to update totals
        } else {
            window.showToast ? window.showToast(d.message || 'Error', 'danger') : alert(d.message || 'Error');
        }
    })
    .catch(() => alert('Unexpected error occurred.'));
}
<?php endif; ?>
</script>
</div><!-- /#content -->
