<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

require_login();

$user_role = $_SESSION['role'] ?? '';
$user_id   = $_SESSION['user_id'] ?? 0;
$today     = date('Y-m-d');

// Handle flash messages
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

// ── Auto-mark past "Scheduled" exams as "Completed" ──
$mysqli->query("
    UPDATE exam_schedule
    SET status = 'Completed'
    WHERE status = 'Scheduled' AND exam_date < '$today'
");

// ── Fetch exams with joins (role-aware) ──
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['exam_type'] ?? '';

$where = [];
if ($from_date) $where[] = "es.exam_date >= '" . $mysqli->real_escape_string($from_date) . "'";
if ($to_date) $where[] = "es.exam_date <= '" . $mysqli->real_escape_string($to_date) . "'";
if ($status_filter) $where[] = "es.status = '" . $mysqli->real_escape_string($status_filter) . "'";
if ($type_filter) $where[] = "es.exam_type = '" . $mysqli->real_escape_string($type_filter) . "'";

if ($user_role === 'student') {
    $where[] = "es.status = 'Scheduled'";
    $where[] = "es.exam_date >= '$today'";
    
    $where_sql = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";
    // Students only see upcoming/scheduled exams (today or future, not cancelled)
    $query = "
        SELECT es.*,
               sub.subject_name, sub.subject_code,
               c.class_name, c.section
        FROM exam_schedule es
        LEFT JOIN subjects sub ON es.subject_id = sub.id
        LEFT JOIN classes  c   ON es.class_id   = c.id
        $where_sql
        ORDER BY es.exam_date ASC, es.start_time ASC
    ";
} else {
    $where_sql = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";
    // Admin / Staff see all exams
    $query = "
        SELECT es.*,
               sub.subject_name, sub.subject_code,
               c.class_name, c.section,
               u.username AS created_by_name
        FROM exam_schedule es
        LEFT JOIN subjects sub ON es.subject_id = sub.id
        LEFT JOIN classes  c   ON es.class_id   = c.id
        LEFT JOIN users    u   ON es.created_by = u.id
        $where_sql
        ORDER BY es.exam_date ASC, es.start_time ASC
    ";
}

$result = $mysqli->query($query);
$exams  = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
}

// ── Stats (always from full data for admin/staff) ──
if ($user_role === 'student') {
    $total     = count($exams);
    $scheduled = $total;
    $completed = 0;
    $cancelled = 0;
    // Count total completed for context
    $cmp_res   = $mysqli->query("SELECT COUNT(*) FROM exam_schedule WHERE status='Completed'");
    $completed = $cmp_res ? (int)$cmp_res->fetch_row()[0] : 0;
} else {
    $total      = count($exams);
    $scheduled  = count(array_filter($exams, fn($e) => $e['status'] === 'Scheduled'));
    $completed  = count(array_filter($exams, fn($e) => $e['status'] === 'Completed'));
    $cancelled  = count(array_filter($exams, fn($e) => $e['status'] === 'Cancelled'));
}

$page_title = "Exam Schedule";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
/* ══════════════════════════════════════════
   EXAM SCHEDULER — Full Page Styles
══════════════════════════════════════════ */
.exam-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 28px 32px;
    color: #fff;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.exam-hero::before {
    content: ''; position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
}
.exam-hero::after {
    content: ''; position: absolute;
    bottom: -60px; right: 60px;
    width: 120px; height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
}
.exam-hero h2 { font-size: clamp(1.3rem, 3vw, 1.9rem); font-weight: 700; margin-bottom: 4px; }
.exam-hero p  { opacity: .85; font-size: clamp(.85rem, 2vw, 1rem); margin: 0; }

/* ── Stat Cards ── */
.stat-card {
    border-radius: 14px; padding: 20px 18px;
    display: flex; align-items: center; gap: 16px;
    border: none; box-shadow: 0 2px 12px rgba(0,0,0,.07);
    transition: transform .2s, box-shadow .2s; cursor: default;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.11); }
.stat-icon {
    width: 54px; height: 54px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; flex-shrink: 0;
}
.stat-value { font-size: 1.9rem; font-weight: 700; line-height: 1; }
.stat-label { font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; opacity: .7; margin-top: 3px; }

/* ── Filter Bar ── */
.filter-bar {
    background: #fff; border-radius: 12px;
    padding: 16px 20px; box-shadow: 0 2px 10px rgba(0,0,0,.06);
    margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
}
.filter-bar select, .filter-bar input[type="date"] {
    border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 7px 12px;
    font-size: .88rem; background: #f8fafc; transition: border-color .2s; min-width: 140px;
}
.filter-bar select:focus, .filter-bar input[type="date"]:focus {
    border-color: #667eea; outline: none; background: #fff;
}
.filter-label { font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: #64748b; }

/* ── Badge styles ── */
.exam-type-badge {
    font-size: .72rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .06em; padding: 3px 10px; border-radius: 20px;
}
.status-scheduled  { background: #dbeafe; color: #1d4ed8; }
.status-completed  { background: #e5e7eb; color: #6b7280; }
.status-cancelled  { background: #fee2e2; color: #991b1b; }
.type-internal  { background: #ede9fe; color: #5b21b6; }
.type-external  { background: #fef3c7; color: #92400e; }
.type-practical { background: #d1fae5; color: #065f46; }
.type-viva      { background: #fce7f3; color: #9d174d; }
.type-other     { background: #f1f5f9; color: #475569; }

/* ── Inactive / Completed row styling ── */
.exam-row-inactive { opacity: 0.52; }
.exam-row-inactive td { color: #94a3b8 !important; }
.exam-row-inactive strong { color: #94a3b8 !important; font-weight: 500 !important; }
.exam-row-inactive .btn-action { opacity: 0.6; }

.exam-card-inactive {
    opacity: 0.55;
    filter: grayscale(40%);
    border-left: 4px solid #d1d5db !important;
}
.exam-card-inactive .date-block {
    background: linear-gradient(135deg, #9ca3af, #6b7280) !important;
}

/* ── Date Block ── */
.date-block {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px; color: #fff; text-align: center;
    padding: 8px 12px; min-width: 58px; flex-shrink: 0;
}
.date-block .day   { font-size: 1.5rem; font-weight: 700; line-height: 1; }
.date-block .month { font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; opacity: .9; }

/* ── Table ── */
.exam-table th {
    background: linear-gradient(90deg, #667eea22, #764ba211);
    font-size: .8rem; text-transform: uppercase;
    letter-spacing: .07em; color: #475569; font-weight: 600;
    border-bottom: 2px solid #e2e8f0;
}
.exam-table td { vertical-align: middle; font-size: .88rem; }
.exam-table tbody tr:hover { background: #f8faff; }

/* ── Upcoming label ── */
.upcoming-label {
    display: inline-flex; align-items: center; gap: 6px;
    background: #ecfdf5; color: #059669; border-radius: 20px;
    padding: 3px 10px; font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .07em;
}
.days-left-badge {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff; border-radius: 20px; padding: 2px 9px;
    font-size: .7rem; font-weight: 700;
}

/* ── Action buttons ── */
.btn-action {
    width: 32px; height: 32px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .85rem; border: none;
    transition: transform .15s, box-shadow .15s;
}
.btn-action:hover { transform: scale(1.1); box-shadow: 0 3px 10px rgba(0,0,0,.15); }

/* ── Mobile Card ── */
.exam-card {
    border: none; border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    transition: transform .2s, box-shadow .2s; overflow: hidden;
    border-left: 4px solid #667eea;
}
.exam-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.12); }
.exam-card .card-top    { padding: 16px 18px 10px; }
.exam-card .card-bottom { padding: 10px 18px 14px; border-top: 1px solid #f1f5f9; background: #fafbff; }

/* ── Empty State ── */
.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state i   { font-size: 3.5rem; margin-bottom: 16px; display: block; opacity: .5; }
.empty-state h5  { color: #64748b; margin-bottom: 8px; }

/* ── Completed section header ── */
.section-divider {
    display: flex; align-items: center; gap: 12px;
    margin: 24px 0 12px; font-size: .78rem;
    font-weight: 700; text-transform: uppercase;
    letter-spacing: .1em; color: #94a3b8;
}
.section-divider::before, .section-divider::after {
    content: ''; flex: 1; height: 1px; background: #e2e8f0;
}

/* ── Responsive: hide table on mobile ── */
@media (max-width: 767px) {
    .table-view  { display: none !important; }
    .cards-view  { display: block !important; }
    .filter-bar  { padding: 12px 14px; gap: 8px; }
    .filter-bar select,
    .filter-bar input[type="date"] { min-width: 0; flex: 1; font-size: .83rem; }
    .exam-hero   { padding: 20px 18px; border-radius: 12px; margin-bottom: 20px; }
    .stat-card   { padding: 14px 14px; }
    .stat-icon   { width: 42px; height: 42px; font-size: 18px; }
    .stat-value  { font-size: 1.5rem; }
}
@media (min-width: 768px) {
    .table-view { display: block !important; }
    .cards-view { display: none !important; }
}
</style>

<div id="content">
    <?php include '../includes/navbar.php'; ?>

    <div id="main-content">
        <div class="container-fluid">

            <!-- Hero Banner -->
            <div class="exam-hero">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div style="flex:1; min-width:180px;">
                        <h2><i class="bi bi-calendar-event-fill me-2"></i>
                            <?= $user_role === 'student' ? 'Upcoming Exams' : 'Exam Schedule' ?>
                        </h2>
                        <p>
                            <?= $user_role === 'student'
                                ? 'Your upcoming scheduled examinations'
                                : 'Manage and view all scheduled examinations' ?>
                        </p>
                    </div>
                    <?php if (in_array($user_role, ['admin','staff'])): ?>
                    <a href="add.php" class="btn btn-light fw-semibold px-4 py-2" style="border-radius:10px; color:#667eea;">
                        <i class="bi bi-plus-circle-fill me-2"></i>Schedule Exam
                    </a>
                    <?php endif; ?>
                    <?php if ($user_role === 'admin'): ?>
                    <button onclick="exportTable('#examTable', 'Exam Schedule Report', 'excel')" class="btn btn-success fw-semibold px-3 py-2" title="Export to Excel">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
                    <button onclick="exportTable('#examTable', 'Exam Schedule Report', 'pdf')" class="btn btn-danger fw-semibold px-3 py-2" title="Export to PDF">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Stats Row -->
            <div class="row g-3 mb-4">
                <?php if ($user_role === 'student'): ?>
                <div class="col-6 col-md-4">
                    <div class="stat-card bg-white">
                        <div class="stat-icon" style="background:#dbeafe; color:#1d4ed8;"><i class="bi bi-hourglass-split"></i></div>
                        <div>
                            <div class="stat-value" style="color:#1d4ed8;"><?= $total ?></div>
                            <div class="stat-label">Upcoming</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card bg-white">
                        <div class="stat-icon" style="background:#d1fae5; color:#065f46;"><i class="bi bi-check-circle"></i></div>
                        <div>
                            <div class="stat-value" style="color:#059669;"><?= $completed ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="col-6 col-md-3">
                    <div class="stat-card bg-white">
                        <div class="stat-icon" style="background:#ede9fe; color:#7c3aed;"><i class="bi bi-calendar3"></i></div>
                        <div>
                            <div class="stat-value text-dark"><?= $total ?></div>
                            <div class="stat-label">Total Exams</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card bg-white">
                        <div class="stat-icon" style="background:#dbeafe; color:#1d4ed8;"><i class="bi bi-hourglass-split"></i></div>
                        <div>
                            <div class="stat-value" style="color:#1d4ed8;"><?= $scheduled ?></div>
                            <div class="stat-label">Upcoming</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card bg-white">
                        <div class="stat-icon" style="background:#e5e7eb; color:#6b7280;"><i class="bi bi-check-circle"></i></div>
                        <div>
                            <div class="stat-value" style="color:#6b7280;"><?= $completed ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card bg-white">
                        <div class="stat-icon" style="background:#fee2e2; color:#991b1b;"><i class="bi bi-x-circle"></i></div>
                        <div>
                            <div class="stat-value" style="color:#dc2626;"><?= $cancelled ?></div>
                            <div class="stat-label">Cancelled</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Advanced Report Filters (admin/staff only) -->
            <?php if (in_array($user_role, ['admin','staff'])): ?>
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-funnel"></i> Report Filters
                </div>
                <div class="card-body">
                    <form method="GET" action="index.php">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label" style="font-size:13px">From Date</label>
                                <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-size:13px">To Date</label>
                                <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-size:13px">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="Scheduled" <?= $status_filter === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-size:13px">Exam Type</label>
                                <select name="exam_type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="Internal" <?= $type_filter === 'Internal' ? 'selected' : '' ?>>Internal</option>
                                    <option value="External" <?= $type_filter === 'External' ? 'selected' : '' ?>>External</option>
                                    <option value="Practical" <?= $type_filter === 'Practical' ? 'selected' : '' ?>>Practical</option>
                                    <option value="Viva" <?= $type_filter === 'Viva' ? 'selected' : '' ?>>Viva</option>
                                    <option value="Other" <?= $type_filter === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-2 mt-3 d-flex gap-2 w-100">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                                <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- ═══════════════════════════════════
                 TABLE VIEW (desktop/tablet ≥768px)
            ═══════════════════════════════════ -->
            <div class="table-view">
                <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
                    <div class="card-body p-0">
                        <?php if (empty($exams)): ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <h5><?= $user_role === 'student' ? 'No Upcoming Exams' : 'No Exams Scheduled Yet' ?></h5>
                            <p class="mb-0">
                                <?php if (in_array($user_role, ['admin','staff'])): ?>
                                    <a href="add.php" class="btn btn-primary mt-2"><i class="bi bi-plus-circle me-1"></i>Schedule First Exam</a>
                                <?php else: ?>
                                    All exams are completed or none have been scheduled yet.
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table id="examTable" class="table exam-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Exam Title</th>
                                        <th>Subject</th>
                                        <th>Class</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Venue</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <?php if (in_array($user_role, ['admin','staff'])): ?>
                                        <th>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($exams as $i => $e):
                                        $isInactive = in_array($e['status'], ['Completed','Cancelled']);
                                        $typeClass  = 'type-' . strtolower($e['exam_type']);
                                        $statusClass= 'status-' . strtolower($e['status']);
                                        $dateFormatted = $e['exam_date'] ? date('d M Y', strtotime($e['exam_date'])) : '—';
                                        $timeRange = '';
                                        if ($e['start_time']) {
                                            $timeRange = date('g:i A', strtotime($e['start_time']));
                                            if ($e['end_time']) $timeRange .= ' – ' . date('g:i A', strtotime($e['end_time']));
                                        }
                                        // Days left
                                        $daysLeft = '';
                                        if ($e['status'] === 'Scheduled' && $e['exam_date'] >= $today) {
                                            $diff = (int)((strtotime($e['exam_date']) - strtotime($today)) / 86400);
                                            $daysLeft = $diff === 0 ? 'Today' : ($diff === 1 ? 'Tomorrow' : "In $diff days");
                                        }
                                    ?>
                                    <tr class="<?= $isInactive ? 'exam-row-inactive' : '' ?>"
                                        data-status="<?= $e['status'] ?>"
                                        data-type="<?= $e['exam_type'] ?>"
                                        data-date="<?= $e['exam_date'] ?>">
                                        <td><span class="badge bg-light text-secondary"><?= $i + 1 ?></span></td>
                                        <td>
                                            <strong><?= htmlspecialchars($e['exam_title']) ?></strong>
                                            <?php if ($daysLeft): ?>
                                            <div class="mt-1">
                                                <span class="days-left-badge"><?= $daysLeft ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($e['description'] && !$isInactive): ?>
                                            <div class="text-muted" style="font-size:.78rem; margin-top:2px;"><?= htmlspecialchars(mb_substr($e['description'], 0, 50)) ?>…</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $e['subject_name'] ? htmlspecialchars($e['subject_name']) : '<span class="text-muted">—</span>' ?></td>
                                        <td>
                                            <?php if ($e['class_name']): ?>
                                                <?= htmlspecialchars($e['class_name']) ?>
                                                <?php if ($e['section']): ?><span class="text-muted"> (<?= htmlspecialchars($e['section']) ?>)</span><?php endif; ?>
                                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                        </td>
                                        <td><span class="<?= $isInactive ? '' : 'fw-semibold' ?>"><?= $dateFormatted ?></span></td>
                                        <td><?= $timeRange ?: '<span class="text-muted">—</span>' ?></td>
                                        <td><?= $e['venue'] ? htmlspecialchars($e['venue']) : '<span class="text-muted">—</span>' ?></td>
                                        <td><span class="exam-type-badge <?= $typeClass ?>"><?= $e['exam_type'] ?></span></td>
                                        <td><span class="exam-type-badge <?= $statusClass ?>"><?= $e['status'] ?></span></td>
                                        <?php if (in_array($user_role, ['admin','staff'])): ?>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="edit.php?id=<?= $e['id'] ?>" class="btn-action" style="background:#dbeafe; color:#1d4ed8;" title="Edit">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <button class="btn-action btn-delete-exam" style="background:#fee2e2; color:#dc2626;" title="Delete"
                                                        data-id="<?= $e['id'] ?>" data-title="<?= htmlspecialchars($e['exam_title']) ?>">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════
                 CARDS VIEW (mobile <768px)
            ═══════════════════════════════ -->
            <div class="cards-view" style="display:none;">
                <?php if (empty($exams)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <h5><?= $user_role === 'student' ? 'No Upcoming Exams' : 'No Exams Scheduled Yet' ?></h5>
                    <?php if (in_array($user_role, ['admin','staff'])): ?>
                        <a href="add.php" class="btn btn-primary mt-2"><i class="bi bi-plus-circle me-1"></i>Schedule First Exam</a>
                    <?php else: ?>
                        <p class="text-muted">All exams are completed or none scheduled yet.</p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div id="examCards">
                    <?php
                    $prevInactive = false;
                    foreach ($exams as $e):
                        $isInactive  = in_array($e['status'], ['Completed','Cancelled']);
                        $typeClass   = 'type-' . strtolower($e['exam_type']);
                        $statusClass = 'status-' . strtolower($e['status']);
                        $dayStr = $e['exam_date'] ? date('d', strtotime($e['exam_date'])) : '--';
                        $monStr = $e['exam_date'] ? date('M', strtotime($e['exam_date'])) : '--';
                        $timeRange = '';
                        if ($e['start_time']) {
                            $timeRange = date('g:i A', strtotime($e['start_time']));
                            if ($e['end_time']) $timeRange .= ' – ' . date('g:i A', strtotime($e['end_time']));
                        }
                        $daysLeft = '';
                        if ($e['status'] === 'Scheduled' && $e['exam_date'] >= $today) {
                            $diff = (int)((strtotime($e['exam_date']) - strtotime($today)) / 86400);
                            $daysLeft = $diff === 0 ? 'Today' : ($diff === 1 ? 'Tomorrow' : "In $diff days");
                        }
                        // Section divider on first inactive card
                        if ($isInactive && !$prevInactive && in_array($user_role, ['admin','staff'])):
                    ?>
                    <div class="section-divider">Completed &amp; Cancelled</div>
                    <?php
                        endif;
                        $prevInactive = $isInactive;
                    ?>
                    <div class="exam-card mb-3 <?= $isInactive ? 'exam-card-inactive' : '' ?>"
                         data-status="<?= $e['status'] ?>"
                         data-type="<?= $e['exam_type'] ?>"
                         data-date="<?= $e['exam_date'] ?>">
                        <div class="card-top d-flex gap-3">
                            <div class="date-block">
                                <div class="day"><?= $dayStr ?></div>
                                <div class="month"><?= $monStr ?></div>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div class="d-flex flex-wrap gap-1 mb-1">
                                    <span class="exam-type-badge <?= $typeClass ?>"><?= $e['exam_type'] ?></span>
                                    <span class="exam-type-badge <?= $statusClass ?>"><?= $e['status'] ?></span>
                                    <?php if ($daysLeft): ?><span class="days-left-badge"><?= $daysLeft ?></span><?php endif; ?>
                                </div>
                                <div class="fw-bold" style="font-size:.98rem; color:<?= $isInactive ? '#9ca3af' : '#1e293b' ?>;">
                                    <?= htmlspecialchars($e['exam_title']) ?>
                                </div>
                                <?php if ($e['subject_name']): ?>
                                <div class="text-muted" style="font-size:.82rem;"><i class="bi bi-book me-1"></i><?= htmlspecialchars($e['subject_name']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-bottom">
                            <div class="row g-2">
                                <?php if ($timeRange): ?>
                                <div class="col-6"><span class="text-muted" style="font-size:.78rem;"><i class="bi bi-clock me-1"></i><?= $timeRange ?></span></div>
                                <?php endif; ?>
                                <?php if ($e['venue']): ?>
                                <div class="col-6"><span class="text-muted" style="font-size:.78rem;"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($e['venue']) ?></span></div>
                                <?php endif; ?>
                                <?php if ($e['class_name']): ?>
                                <div class="col-6"><span class="text-muted" style="font-size:.78rem;"><i class="bi bi-building me-1"></i><?= htmlspecialchars($e['class_name']) ?><?= $e['section'] ? ' ('.$e['section'].')' : '' ?></span></div>
                                <?php endif; ?>
                            </div>
                            <?php if (in_array($user_role, ['admin','staff'])): ?>
                            <div class="d-flex gap-2 mt-2 justify-content-end">
                                <a href="edit.php?id=<?= $e['id'] ?>" class="btn btn-sm" style="background:#dbeafe; color:#1d4ed8; border-radius:8px; font-size:.8rem; padding:4px 12px;">
                                    <i class="bi bi-pencil-fill me-1"></i>Edit
                                </a>
                                <button class="btn btn-sm btn-delete-exam" style="background:#fee2e2; color:#dc2626; border-radius:8px; font-size:.8rem; padding:4px 12px; border:none;"
                                        data-id="<?= $e['id'] ?>" data-title="<?= htmlspecialchars($e['exam_title']) ?>">
                                    <i class="bi bi-trash-fill me-1"></i>Delete
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /container-fluid -->
    </div><!-- /#main-content -->

    <?php include '../includes/footer.php'; ?>
</div><!-- /#content -->

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#fee2e2,#fecaca); border-radius:16px 16px 0 0; border:none;">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Exam</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-1">Are you sure you want to delete:</p>
                <p class="fw-bold fs-6" id="deleteExamTitle" style="color:#dc2626;"></p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:10px;">Cancel</button>
                <form id="deleteForm" action="delete.php" method="POST" class="d-inline">
                    <input type="hidden" name="exam_id" id="deleteExamId">
                    <button type="submit" class="btn btn-danger" style="border-radius:10px;">
                        <i class="bi bi-trash-fill me-1"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {

    // DataTable (desktop only)
    if ($('#examTable').length) {
        $('#examTable').DataTable({
            pageLength: 10,
            lengthMenu: [[5,10,25,50],[5,10,25,50]],
            order: [[4, 'asc']],
            columnDefs: [{ orderable: false, targets: <?= in_array($user_role, ['admin','staff']) ? 9 : -1 ?> }],
            createdRow: function(row, data, index) {
                var status = $(row).data('status');
                if (status === 'Completed' || status === 'Cancelled') {
                    $(row).addClass('exam-row-inactive');
                }
            }
        });
    }

    // Delete modal
    $(document).on('click', '.btn-delete-exam', function () {
        $('#deleteExamId').val($(this).data('id'));
        $('#deleteExamTitle').text($(this).data('title'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    // Filters (admin/staff only)
    function applyFilters() {
        var status = $('#filterStatus').val();
        var type   = $('#filterType').val();
        var date   = $('#filterDate').val();

        $('#examTable tbody tr').each(function () {
            var show = (!status || $(this).data('status') === status) &&
                       (!type   || $(this).data('type')   === type)   &&
                       (!date   || $(this).data('date')   === date);
            $(this).toggle(show);
        });

        $('#examCards .exam-card').each(function () {
            var show = (!status || $(this).data('status') === status) &&
                       (!type   || $(this).data('type')   === type)   &&
                       (!date   || $(this).data('date')   === date);
            $(this).toggle(show);
        });
    }

    $('#filterStatus, #filterType, #filterDate').on('change', applyFilters);
    $('#clearFilters').on('click', function () {
        $('#filterStatus, #filterType').val('');
        $('#filterDate').val('');
        applyFilters();
    });

    // Auto-dismiss alerts
    setTimeout(function () { $('.alert').fadeOut(500); }, 4000);
});
</script>
