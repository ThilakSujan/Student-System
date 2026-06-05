<?php
session_start();
require_once '../includes/auth.php';
require_role(['student', 'admin', 'staff']);
require_once '../config/db.php';

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

if (!$student) {
    if ($role === 'student') {
        header("Location: ../dashboard/dashboard.php");
    } else {
        header("Location: staff_report.php");
    }
    exit();
}

$institute = $mysqli->query("SELECT * FROM institute_profile LIMIT 1")->fetch_assoc() ?? [];

// Fetch applicable fee structures for this student
$fee_data = [];
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

// Compute totals
$grand_total   = array_sum(array_column($fee_data, 'amount'));
$grand_paid    = array_sum(array_column($fee_data, 'paid'));
$grand_pending = $grand_total - $grand_paid;
$percentage    = $grand_total > 0 ? round(($grand_paid / $grand_total) * 100, 2) : 0;

$status_overall = $grand_pending <= 0 ? 'CLEARED' : 'PENDING';
$acad_year = !empty($fee_data) ? $fee_data[0]['academic_year'] : (date('Y').'-'.(date('Y')+1));

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Fee Report Card — <?= htmlspecialchars($student['student_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; margin:0; }

/* Action bar */
.action-bar {
    background:#212529; padding:12px 24px;
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:100;
}
.action-bar a { color:#fff; font-size:14px; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:8px; }
.action-bar a i { color:#0d6efd; font-size:18px; }

/* Card */
.report-wrap { max-width:800px; margin:32px auto; padding:0 16px 48px; }
.report-card { background:#fff; border-radius:12px; box-shadow:0 4px 24px rgba(0,0,0,.10); overflow:hidden; position:relative; }

/* Header */
.rc-header {
    background:#212529; padding:28px 32px;
    display:flex; align-items:center; gap:24px; position:relative; overflow:hidden;
}
.rc-header::before {
    content:''; position:absolute; inset:0; opacity:.05;
    background-image:repeating-linear-gradient(45deg,#fff 0,#fff 1px,transparent 0,transparent 50%);
    background-size:12px 12px;
}
.rc-logo {
    width:80px; height:80px; border-radius:12px; flex-shrink:0;
    border:3px solid rgba(255,255,255,.2); overflow:hidden;
    background:#fff; display:flex; align-items:center; justify-content:center;
}
.rc-logo img { width:100%; height:100%; object-fit:cover; }
.rc-logo i   { font-size:36px; color:#212529; }
.rc-institute { flex:1; position:relative; z-index:1; }
.rc-institute h2 { color:#fff; font-size:20px; font-weight:700; margin:0 0 4px; }
.rc-institute p  { color:#adb5bd; font-size:12px; margin:0; line-height:1.6; }
.rc-badge { position:relative; z-index:1; text-align:center; flex-shrink:0; }
.rc-badge span {
    background:#0d6efd; color:#fff; font-size:11px; font-weight:700;
    letter-spacing:.12em; text-transform:uppercase;
    padding:6px 14px; border-radius:20px; display:block; margin-bottom:6px;
}
.rc-badge small { color:#6c757d; font-size:11px; }
.rc-badge strong { color:#fff; font-size:13px; display:block; }

.rc-divider { height:5px; background:linear-gradient(90deg,#0d6efd,#6610f2,#0dcaf0); }

/* Sections */
.rc-section { padding:24px 32px; border-bottom:1px solid #f0f0f0; }
.rc-section h5 {
    font-size:12px; font-weight:700; text-transform:uppercase;
    letter-spacing:.1em; color:#6c757d; margin-bottom:16px;
}

/* Info grid */
.info-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
.info-item .lbl { font-size:11px; color:#9ca3af; font-weight:600; text-transform:uppercase; letter-spacing:.06em; margin-bottom:2px; }
.info-item .val { font-size:14px; font-weight:600; color:#1a2332; }

/* Marks table */
.table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.marks-table { width:100%; border-collapse:collapse; font-size:13.5px; min-width: 600px; }
.marks-table thead tr { background:#212529; color:#fff; }
.marks-table thead th { padding:10px 14px; font-weight:600; font-size:12px; }
.marks-table tbody tr { border-bottom:1px solid #f0f2f5; }
.marks-table tbody tr:nth-child(even) { background:#f8f9fa; }
.marks-table tbody td { padding:10px 14px; }
.marks-table tfoot tr { background:#f0f2f5; font-weight:700; }
.marks-table tfoot td { padding:10px 14px; font-size:13px; }
.mini-bar { height:5px; border-radius:3px; background:#e9ecef; margin-top:4px; }
.mini-fill { height:100%; border-radius:3px; }

/* Result grid */
.result-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
.result-box { text-align:center; padding:16px 12px; border-radius:10px; background:#f8f9fa; }
.result-box .rb-lbl { font-size:11px; color:#6c757d; text-transform:uppercase; letter-spacing:.08em; font-weight:600; margin-bottom:8px; }
.result-box .rb-val { font-size:26px; font-weight:800; line-height:1; margin-bottom:4px; }
.result-box .rb-sub { font-size:11px; color:#9ca3af; }
.pass-badge { display:inline-block; padding:6px 20px; border-radius:30px; font-size:15px; font-weight:800; }
.pass-badge.pass { background:#d1fae5; color:#065f46; }
.pass-badge.fail { background:#fee2e2; color:#991b1b; }
.pass-badge.partial { background:#fef3c7; color:#92400e; }

/* Remarks */
.remarks-box { background:#f8f9fa; border-radius:8px; border-left:4px solid #0d6efd; padding:12px 16px; font-size:13px; color:#374151; }

/* Footer */
.rc-footer { padding:20px 32px; display:flex; justify-content:space-between; align-items:flex-end; }
.sig-box { text-align:center; min-width:140px; }
.sig-line { border-top:1.5px solid #212529; margin-bottom:4px; }
.sig-lbl { font-size:11px; color:#6c757d; }
.stamp-circle {
    width:72px; height:72px; border-radius:50%; border:2.5px dashed #0d6efd;
    display:flex; align-items:center; justify-content:center;
    font-size:10px; color:#0d6efd; font-weight:700; text-transform:uppercase;
    letter-spacing:.05em; text-align:center; line-height:1.3; margin:0 auto 4px;
}
.rc-strip { background:#212529; padding:10px 32px; display:flex; justify-content:space-between; }
.rc-strip span { color:#6c757d; font-size:11px; }

/* Watermark */
.watermark {
    position:absolute; top:50%; left:50%;
    transform:translate(-50%,-50%) rotate(-30deg);
    font-size:100px; font-weight:900; color:rgba(220,53,69,.06);
    pointer-events:none; white-space:nowrap; z-index:0;
}

/* Responsive */
@media (max-width: 768px) {
    .rc-header { flex-direction: column; text-align: center; }
    .info-grid { grid-template-columns: repeat(2, 1fr); }
    .result-grid { grid-template-columns: repeat(2, 1fr); }
    .rc-footer { flex-direction: column; gap: 30px; align-items: center; }
}
@media (max-width: 480px) {
    .info-grid { grid-template-columns: 1fr; }
    .result-grid { grid-template-columns: 1fr; }
    .rc-strip { flex-direction: column; text-align: center; gap: 8px; }
    .action-bar { flex-direction: column; gap: 12px; }
}

@media print {
    .action-bar { display:none !important; }
    body { background:#fff; }
    .report-wrap { margin:0; padding:0; max-width:100%; }
    .report-card { box-shadow:none; border-radius:0; }
    @page { margin:10mm; size:A4; }
}
</style>
</head>
<body>

<div class="action-bar">
    <?php if ($role === 'student'): ?>
        <a href="../dashboard/dashboard.php"><i class="bi bi-arrow-left-circle-fill"></i> Back to Dashboard</a>
    <?php else: ?>
        <a href="staff_report.php"><i class="bi bi-arrow-left-circle-fill"></i> Back to Reports</a>
    <?php endif; ?>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-light btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <button onclick="downloadPDF()" class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </button>
    </div>
</div>

<div class="report-wrap">
<div class="report-card">

    <?php if ($status_overall === 'PENDING'): ?><div class="watermark">PENDING</div><?php endif; ?>

    <!-- Header -->
    <div class="rc-header">
        <div class="rc-logo">
            <?php
            $lp = '../uploads/institute/'.($institute['logo']??'');
            if (!empty($institute['logo']) && file_exists($lp)):
            ?>
                <img src="<?= $lp ?>" alt="Logo">
            <?php else: ?>
                <i class="bi bi-building"></i>
            <?php endif; ?>
        </div>
        <div class="rc-institute">
            <h2><?= htmlspecialchars($institute['institute_name']??'My Institute') ?></h2>
            <p>
                <?php if (!empty($institute['address'])): ?>
                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($institute['address']) ?><br>
                <?php endif; ?>
                <?php if (!empty($institute['phone'])): ?>
                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($institute['phone']) ?>&nbsp;&nbsp;
                <?php endif; ?>
                <?php if (!empty($institute['email'])): ?>
                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($institute['email']) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="rc-badge">
            <span>Fee Report</span>
            <small>Academic Year</small>
            <strong><?= $acad_year ?></strong>
        </div>
    </div>

    <div class="rc-divider"></div>

    <!-- Student info -->
    <div class="rc-section">
        <h5><i class="bi bi-person-fill me-1"></i>Student Information</h5>
        <div class="info-grid">
            <?php
            $fields = [
                'Student Name' => $student['student_name'],
                'Student ID'   => '#'.str_pad($student['id'],4,'0',STR_PAD_LEFT),
                'Department'   => $student['department'] ?? 'N/A',
                'Gender'       => $student['gender']     ?? 'N/A',
                'Date of Birth'=> !empty($student['dob']) ? date('d M Y',strtotime($student['dob'])) : 'N/A',
                'Email'        => $student['email']       ?? 'N/A',
            ];
            foreach ($fields as $lbl => $val):
            ?>
            <div class="info-item">
                <div class="lbl"><?= $lbl ?></div>
                <div class="val" style="<?= $lbl==='Email'?'font-size:12px':'' ?>"><?= htmlspecialchars($val) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Fee Details -->
    <div class="rc-section">
        <h5><i class="bi bi-receipt me-1"></i>Fee Details</h5>
        <?php if (empty($fee_data)): ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>No fee structures found.
            </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="marks-table">
            <thead>
                <tr>
                    <th>#</th><th>Category</th><th>Academic Year</th>
                    <th>Total Fee</th><th>Paid</th><th>Progress</th>
                    <th>Balance</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($fee_data as $i => $f):
                $balance = $f['amount'] - $f['paid'];
                $sp = $f['amount'] > 0 ? round(($f['paid'] / $f['amount']) * 100, 1) : 0;
                $bc = $sp == 100 ? '#198754' : ($sp > 0 ? '#ffc107' : '#dc3545');
                
                $overdue = $f['due_date'] && $f['due_date'] < date('Y-m-d') && $balance > 0;
                if ($balance <= 0)           { $status = 'Paid';    $cls = 'success'; }
                elseif ($f['paid'] > 0)      { $status = 'Partial'; $cls = 'warning'; }
                elseif ($overdue)            { $status = 'Overdue'; $cls = 'danger';  }
                else                         { $status = 'Pending'; $cls = 'danger';  }
            ?>
            <tr>
                <td class="text-muted"><?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($f['category']) ?></strong></td>
                <td><?= htmlspecialchars($f['academic_year']) ?></td>
                <td>₹<?= number_format($f['amount'], 2) ?></td>
                <td><strong>₹<?= number_format($f['paid'], 2) ?></strong></td>
                <td style="min-width:80px">
                    <div style="font-size:11px;color:#6c757d"><?= $sp ?>%</div>
                    <div class="mini-bar"><div class="mini-fill" style="width:<?= $sp ?>%;background:<?= $bc ?>"></div></div>
                </td>
                <td class="<?= $balance > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                    ₹<?= number_format(max($balance, 0), 2) ?>
                </td>
                <td>
                    <span class="text-<?= $cls ?> fw-semibold" style="font-size:12px">
                        <i class="bi <?= $balance <= 0 ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?> me-1"></i><?= $status ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total</strong></td>
                    <td><strong>₹<?= number_format($grand_total, 2) ?></strong></td>
                    <td><strong>₹<?= number_format($grand_paid, 2) ?></strong></td>
                    <td style="font-size:12px;color:#6c757d"><?= $percentage ?>%</td>
                    <td colspan="2" class="<?= $grand_pending > 0 ? 'text-danger' : 'text-success' ?>"><strong>₹<?= number_format($grand_pending, 2) ?> Pending</strong></td>
                </tr>
            </tfoot>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Result summary -->
    <div class="rc-section">
        <div class="result-grid">
            <div class="result-box">
                <div class="rb-lbl">Total Fees</div>
                <div class="rb-val text-primary">₹<?= number_format($grand_total, 2) ?></div>
                <div class="rb-sub">Overall Payable</div>
            </div>
            <div class="result-box">
                <div class="rb-lbl">Amount Paid</div>
                <div class="rb-val" style="color:#0891b2">₹<?= number_format($grand_paid, 2) ?></div>
                <div class="rb-sub">Total Collected</div>
            </div>
            <div class="result-box">
                <div class="rb-lbl">Balance Due</div>
                <div class="rb-val" style="color:#dc3545">₹<?= number_format(max($grand_pending, 0), 2) ?></div>
                <div class="rb-sub">Pending Amount</div>
            </div>
            <div class="result-box">
                <div class="rb-lbl">Fee Status</div>
                <div class="rb-val" style="font-size:18px;margin-top:4px">
                    <?php if ($grand_pending <= 0): ?>
                        <span class="pass-badge pass">CLEARED</span>
                    <?php elseif ($grand_paid > 0): ?>
                        <span class="pass-badge partial">PARTIAL</span>
                    <?php else: ?>
                        <span class="pass-badge fail">PENDING</span>
                    <?php endif; ?>
                </div>
                <div class="rb-sub mt-1">Overall Status</div>
            </div>
        </div>
    </div>

    <!-- Remarks -->
    <div class="rc-section">
        <h5><i class="bi bi-chat-square-text me-1"></i>Remarks</h5>
        <div class="remarks-box">
            <?php
            if ($grand_total == 0) {
                echo "No fees assigned yet.";
            } elseif ($grand_pending <= 0) {
                echo "All fees cleared. Outstanding balance is zero.";
            } elseif ($grand_paid > 0) {
                echo "Partial payment received. Please clear the pending balance of ₹" . number_format($grand_pending, 2) . " before the due date.";
            } else {
                echo "No fee payment received. Please pay the total amount of ₹" . number_format($grand_total, 2) . " immediately.";
            }
            ?>
        </div>
    </div>

    <!-- Signatures -->
    <div class="rc-footer">
        <div class="sig-box">
            <div style="height:36px"></div>
            <div class="sig-line"></div>
            <div class="sig-lbl">Accounts Officer</div>
        </div>
        <div class="text-center">
            <div class="stamp-circle"><?= htmlspecialchars(strtoupper(substr($institute['institute_name']??'Institute',0,11))) ?></div>
            <div style="font-size:11px;color:#9ca3af">Official Seal</div>
        </div>
        <div class="sig-box">
            <div style="height:36px">
                <?php if (!empty($institute['principal_name'])): ?>
                    <div style="font-size:13px;font-weight:600;color:#374151"><?= htmlspecialchars($institute['principal_name']) ?></div>
                <?php endif; ?>
            </div>
            <div class="sig-line"></div>
            <div class="sig-lbl">Principal</div>
        </div>
    </div>

    <!-- Bottom strip -->
    <div class="rc-strip">
        <span>Generated on <?= date('d M Y, h:i A') ?></span>
        <span><?= htmlspecialchars($institute['institute_name']??'Student System') ?> &mdash; Finance Department</span>
    </div>

</div><!-- /report-card -->
</div><!-- /report-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function downloadPDF() {
    const bar = document.querySelector('.action-bar');
    bar.style.display = 'none';
    window.print();
    bar.style.display = 'flex';
}
</script>
</body>
</html>
