<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin', 'staff']);
require_once '../config/db.php';

$page_title = "Pending Fee Report";

// ── Filters ─────────────────────────────────────────────────
$filter_year   = trim($_GET['year']     ?? '');
$filter_cat    = (int)($_GET['category'] ?? 0);
$filter_class  = (int)($_GET['class_id'] ?? 0);
$filter_status = trim($_GET['status']   ?? ''); // all | paid | partial | pending | overdue

// Fetch filter options
$years   = $mysqli->query("SELECT DISTINCT academic_year FROM fee_structures ORDER BY academic_year DESC")->fetch_all(MYSQLI_ASSOC);
$cats    = $mysqli->query("SELECT id, name FROM fee_categories WHERE status='Active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$classes = $mysqli->query("SELECT id, class_name, section FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);

// ── Build WHERE clauses ──────────────────────────────────────
$struct_where = ['fs.status = \'Active\''];
if ($filter_year)  $struct_where[] = "fs.academic_year = '" . $mysqli->real_escape_string($filter_year) . "'";
if ($filter_cat)   $struct_where[] = "fs.category_id = $filter_cat";
if ($filter_class) $struct_where[] = "(fs.class_id = $filter_class OR fs.class_id IS NULL)";
$sw = implode(' AND ', $struct_where);

// ── Main query: students × structures with aggregated payments ─
$sql = "
SELECT
    s.id          AS student_id,
    s.student_name,
    s.department,
    s.email,
    s.status      AS student_status,
    fs.id         AS struct_id,
    fc.name       AS category,
    fs.academic_year,
    fs.amount,
    fs.due_date,
    COALESCE(cl.class_name, 'All Classes') AS class_name,
    COALESCE(cl.section, '')               AS section,
    COALESCE(
        (SELECT SUM(fp.amount_paid)
         FROM fee_payments fp
         WHERE fp.student_id = s.id
           AND fp.fee_assignment_id = fs.id), 0
    ) AS paid
FROM students s
CROSS JOIN fee_structures fs
JOIN fee_categories fc ON fc.id = fs.category_id
LEFT JOIN classes cl   ON cl.id = fs.class_id
WHERE s.status = 'Active'
  AND $sw
  AND (
      fs.class_id IS NULL
      OR fs.class_id IN (
          SELECT class_id FROM class_students WHERE student_id = s.id
      )
  )
ORDER BY s.student_name, fs.academic_year DESC, fc.name
";

$result = $mysqli->query($sql);
$raw    = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// ── Group by student, annotate status ───────────────────────
$students_data = [];
$today = date('Y-m-d');

foreach ($raw as $row) {
    $balance = $row['amount'] - $row['paid'];
    $overdue = $row['due_date'] && $row['due_date'] < $today && $balance > 0;

    if ($balance <= 0)       { $status = 'paid';    }
    elseif ($row['paid'] > 0){ $status = 'partial'; }
    elseif ($overdue)        { $status = 'overdue'; }
    else                     { $status = 'pending'; }

    // Apply status filter
    if ($filter_status && $filter_status !== $status) continue;

    $row['balance'] = max($balance, 0);
    $row['status']  = $status;
    $row['overdue'] = $overdue;

    $sid = $row['student_id'];
    if (!isset($students_data[$sid])) {
        $students_data[$sid] = [
            'id'             => $sid,
            'student_name'   => $row['student_name'],
            'department'     => $row['department'],
            'email'          => $row['email'],
            'student_status' => $row['student_status'],
            'total_fee'      => 0,
            'total_paid'     => 0,
            'total_pending'  => 0,
            'has_overdue'    => false,
            'structures'     => [],
        ];
    }
    $students_data[$sid]['total_fee']     += $row['amount'];
    $students_data[$sid]['total_paid']    += $row['paid'];
    $students_data[$sid]['total_pending'] += $row['balance'];
    if ($overdue) $students_data[$sid]['has_overdue'] = true;
    $students_data[$sid]['structures'][]  = $row;
}

// Sort: overdue first, then partial, then pending, then paid
usort($students_data, function($a, $b) {
    $rank = ['overdue'=>0,'partial'=>1,'pending'=>2,'paid'=>3];
    $ra = $a['has_overdue'] ? 0 : ($a['total_pending'] > 0 ? ($a['total_paid'] > 0 ? 1 : 2) : 3);
    $rb = $b['has_overdue'] ? 0 : ($b['total_pending'] > 0 ? ($b['total_paid'] > 0 ? 1 : 2) : 3);
    return $ra === $rb ? strcmp($a['student_name'], $b['student_name']) : $ra - $rb;
});

// Grand totals
$g_total   = array_sum(array_column($students_data, 'total_fee'));
$g_paid    = array_sum(array_column($students_data, 'total_paid'));
$g_pending = array_sum(array_column($students_data, 'total_pending'));
$cnt_overdue = count(array_filter($students_data, fn($s) => $s['has_overdue']));

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
@media print {
    #sidebar, .top-navbar, .sidebar-overlay, .no-print, footer,
    .accordion-button::after { display:none !important; }
    #content { margin:0 !important; padding:0 !important; }
    #main-content { padding:0 !important; }
    .card { box-shadow:none !important; border:1px solid #dee2e6 !important; break-inside:avoid; }
    .accordion-collapse { display:block !important; height:auto !important; }
    .accordion-button { pointer-events:none; background:#f8fafc !important; color:#000 !important; }
    body { background:#fff !important; font-size:12px !important; }
    .print-header { display:block !important; }
}
.print-header { display:none; }
.badge-overdue { background:#fce7f3; color:#9d174d; }
.badge-partial { background:#fef3c7; color:#92400e; }
.badge-pending { background:#fee2e2; color:#991b1b; }
.badge-paid    { background:#d1fae5; color:#065f46; }
.student-card-overdue { border-left:4px solid #ec4899 !important; }
.student-card-partial { border-left:4px solid #f59e0b !important; }
.student-card-pending { border-left:4px solid #ef4444 !important; }
.student-card-paid    { border-left:4px solid #10b981 !important; }
</style>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <!-- Print header -->
    <div class="print-header mb-4">
        <h3>Fee Pending Report — <?= date('d M Y') ?></h3>
        <?php if ($filter_year)  echo "<p class='mb-0'><strong>Year:</strong> $filter_year</p>"; ?>
        <hr>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-clipboard-data me-2 text-danger"></i>Fee Pending Report</h4>
            <small class="text-muted">Student-wise due and pending fee summary</small>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-success btn-sm">
                <i class="bi bi-printer me-1"></i>Print / Download
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-header bg-dark text-white fw-semibold"><i class="bi bi-funnel me-1"></i> Filters</div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Academic Year</label>
                    <select name="year" class="form-select">
                        <option value="">All Years</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?= htmlspecialchars($y['academic_year']) ?>"
                                <?= $filter_year === $y['academic_year'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($y['academic_year']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Fee Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $filter_cat == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Class</label>
                    <select name="class_id" class="form-select">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $cl): ?>
                            <option value="<?= $cl['id'] ?>" <?= $filter_class == $cl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cl['class_name'].($cl['section']?' ('.$cl['section'].')':'')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Payment Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="overdue"  <?= $filter_status==='overdue'  ? 'selected':'' ?>>Overdue</option>
                        <option value="pending"  <?= $filter_status==='pending'  ? 'selected':'' ?>>Pending</option>
                        <option value="partial"  <?= $filter_status==='partial'  ? 'selected':'' ?>>Partial</option>
                        <option value="paid"     <?= $filter_status==='paid'     ? 'selected':'' ?>>Paid</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Apply</button>
                    <a href="staff_report.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Students',          count($students_data),            'bi-people',             '#e0e7ff','#4338ca'],
            ['Overdue',           $cnt_overdue,                     'bi-exclamation-diamond', '#fce7f3','#9d174d'],
            ['Total Fees',        '₹'.number_format($g_total,2),   'bi-cash-stack',          '#fef3c7','#92400e'],
            ['Total Collected',   '₹'.number_format($g_paid,2),    'bi-check-circle-fill',   '#d1fae5','#065f46'],
            ['Total Outstanding', '₹'.number_format($g_pending,2), 'bi-hourglass-split',     '#fee2e2','#991b1b'],
        ] as [$l,$v,$ic,$bg,$fg]): ?>
        <div class="col-12 col-sm-6 col-lg">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div style="width:44px;height:44px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                        <i class="bi <?= $ic ?>" style="color:<?= $fg ?>"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px"><?= $l ?></div>
                        <div class="fw-bold fs-5" style="color:<?= $fg ?>"><?= $v ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Legend -->
    <div class="d-flex gap-3 mb-3 no-print flex-wrap">
        <?php foreach ([
            ['Overdue','badge-overdue'],['Partial','badge-partial'],
            ['Pending','badge-pending'],['Paid','badge-paid']
        ] as [$l,$c]): ?>
        <span class="badge px-3 py-2 rounded-pill <?= $c ?>"><?= $l ?></span>
        <?php endforeach; ?>
        <small class="text-muted align-self-center ms-2">Click a student row to expand fee details</small>
    </div>

    <!-- Student Accordion -->
    <?php if (empty($students_data)): ?>
    <div class="card border-0 shadow-sm">
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>No records found for the selected filters.
        </div>
    </div>
    <?php else: ?>
    <div class="accordion accordion-flush" id="studentsAccordion">
    <?php foreach ($students_data as $idx => $sd):
        $pending_cls = $sd['has_overdue'] ? 'overdue' : ($sd['total_pending'] > 0 ? ($sd['total_paid'] > 0 ? 'partial' : 'pending') : 'paid');
        $card_cls    = "student-card-$pending_cls";
        $badge_cls   = "badge-$pending_cls";
        $label       = ucfirst($pending_cls);
    ?>
    <div class="card border-0 shadow-sm mb-2 <?= $card_cls ?>">
        <div class="card-header p-0 bg-white" id="head<?= $sd['id'] ?>">
            <button class="accordion-button collapsed py-3 px-4 bg-transparent" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#col<?= $sd['id'] ?>"
                    aria-expanded="false">
                <div class="d-flex align-items-center w-100 gap-3 flex-wrap">
                    <!-- Avatar -->
                    <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;font-weight:700;flex-shrink:0;">
                        <?= strtoupper(substr($sd['student_name'],0,1)) ?>
                    </div>
                    <!-- Name + dept -->
                    <div class="flex-grow-1">
                        <div class="fw-bold" style="font-size:15px"><?= htmlspecialchars($sd['student_name']) ?></div>
                        <div class="text-muted" style="font-size:12px">
                            <?= htmlspecialchars($sd['department'] ?? '—') ?>
                            <?php if ($sd['email']): ?>
                            · <?= htmlspecialchars($sd['email']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Fee summary -->
                    <div class="d-flex gap-3 ms-auto text-end flex-wrap no-print">
                        <div>
                            <div class="text-muted" style="font-size:11px">Total</div>
                            <div class="fw-bold">₹<?= number_format($sd['total_fee'],2) ?></div>
                        </div>
                        <div>
                            <div class="text-muted" style="font-size:11px">Paid</div>
                            <div class="fw-bold text-success">₹<?= number_format($sd['total_paid'],2) ?></div>
                        </div>
                        <div>
                            <div class="text-muted" style="font-size:11px">Pending</div>
                            <div class="fw-bold text-danger">₹<?= number_format($sd['total_pending'],2) ?></div>
                        </div>
                    </div>
                    <!-- Status badge -->
                    <span class="badge px-3 py-2 rounded-pill <?= $badge_cls ?>">
                        <?= $label ?>
                        <?= $sd['has_overdue'] ? ' ⚠' : '' ?>
                    </span>
                    <!-- Link to full report -->
                    <a href="student_report.php?student_id=<?= $sd['id'] ?>"
                       class="btn btn-sm btn-outline-primary no-print"
                       onclick="event.stopPropagation()">
                        <i class="bi bi-eye me-1"></i>Full Report
                    </a>
                </div>
            </button>
        </div>

        <div id="col<?= $sd['id'] ?>" class="accordion-collapse collapse"
             data-bs-parent="">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fee Category</th>
                                <th>Class</th>
                                <th>Acad. Year</th>
                                <th>Total Fee</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sd['structures'] as $f):
                            $s_label = ucfirst($f['status']);
                            $s_cls   = 'badge-'.$f['status'];
                        ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($f['category']) ?></td>
                                <td class="text-muted" style="font-size:12px">
                                    <?= htmlspecialchars($f['class_name'].($f['section']?' ('.$f['section'].')':'')) ?>
                                </td>
                                <td><?= htmlspecialchars($f['academic_year']) ?></td>
                                <td>₹<?= number_format($f['amount'],2) ?></td>
                                <td class="text-success fw-semibold">₹<?= number_format($f['paid'],2) ?></td>
                                <td class="<?= $f['balance']>0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                    ₹<?= number_format($f['balance'],2) ?>
                                </td>
                                <td style="font-size:12px">
                                    <?php if ($f['due_date']): ?>
                                        <span class="<?= $f['overdue'] ? 'text-danger fw-bold' : 'text-muted' ?>">
                                            <?= date('d M Y', strtotime($f['due_date'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-2 <?= $s_cls ?>">
                                        <?= $s_label ?><?= $f['overdue'] ? ' ⚠' : '' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">Total</td>
                                <td>₹<?= number_format($sd['total_fee'],2) ?></td>
                                <td class="text-success">₹<?= number_format($sd['total_paid'],2) ?></td>
                                <td class="<?= $sd['total_pending']>0?'text-danger':'' ?>">₹<?= number_format($sd['total_pending'],2) ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Print-only flat table (accordion doesn't print well) -->
    <div class="d-none d-print-block mt-4">
        <table class="table table-bordered" style="font-size:11px">
            <thead class="table-dark">
                <tr>
                    <th>#</th><th>Student</th><th>Department</th><th>Fee Category</th>
                    <th>Year</th><th>Total</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php $rowN = 1; foreach ($students_data as $sd): foreach ($sd['structures'] as $f): ?>
                <tr>
                    <td><?= $rowN++ ?></td>
                    <td><?= htmlspecialchars($sd['student_name']) ?></td>
                    <td><?= htmlspecialchars($sd['department'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($f['category']) ?></td>
                    <td><?= htmlspecialchars($f['academic_year']) ?></td>
                    <td>₹<?= number_format($f['amount'],2) ?></td>
                    <td>₹<?= number_format($f['paid'],2) ?></td>
                    <td style="<?= $f['balance']>0?'color:#991b1b;font-weight:700':'color:#065f46' ?>">
                        ₹<?= number_format($f['balance'],2) ?>
                    </td>
                    <td><?= $f['due_date'] ? date('d M Y',strtotime($f['due_date'])) : '—' ?></td>
                    <td><?= ucfirst($f['status']) ?><?= $f['overdue']?' ⚠':'' ?></td>
                </tr>
            <?php endforeach; endforeach; ?>
            </tbody>
            <tfoot class="table-light" style="font-weight:700">
                <tr>
                    <td colspan="5" class="text-end">Grand Total</td>
                    <td>₹<?= number_format($g_total,2) ?></td>
                    <td>₹<?= number_format($g_paid,2) ?></td>
                    <td>₹<?= number_format($g_pending,2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

</div>
</div>
<?php include '../includes/footer.php'; ?>
</div><!-- /#content -->
