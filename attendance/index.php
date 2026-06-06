<?php
session_start();
require_once '../includes/auth.php';
require_login();
require_once '../config/db.php';

$role = $_SESSION['role'] ?? '';

// ── Student: only see own attendance ─────────────────
if ($role === 'student') {
    $sid  = (int)($_SESSION['student_id'] ?? 0);
    $sres = $mysqli->query("SELECT * FROM students WHERE id=$sid LIMIT 1");
    $student = $sres ? $sres->fetch_assoc() : [];

    $ares = $mysqli->query(
        "SELECT date, status FROM attendance
         WHERE student_id=$sid ORDER BY date DESC"
    );
    $att_list = $ares ? $ares->fetch_all(MYSQLI_ASSOC) : [];

    $total   = count($att_list);
    $present = count(array_filter($att_list, fn($a) => $a['status']==='Present'));
    $absent  = $total - $present;
    $pct     = $total > 0 ? round($present/$total*100,1) : 0;

} else {
    // ── Admin/Staff: see all students' attendance ─────
    // Filter by date range
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    $ares = $mysqli->query(
        "SELECT a.id, a.date, a.status, s.student_name, s.department
         FROM attendance a
         JOIN students s ON s.id=a.student_id
         WHERE a.date BETWEEN '$from' AND '$to'
         ORDER BY a.date DESC, s.student_name ASC"
    );
    $att_list = $ares ? $ares->fetch_all(MYSQLI_ASSOC) : [];

    // Overall stats for date range
    $total   = count($att_list);
    $present = count(array_filter($att_list, fn($a) => $a['status']==='Present'));
    $absent  = $total - $present;
    $pct     = $total > 0 ? round($present/$total*100,1) : 0;
}

$page_title = "Attendance";
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
            <h4 class="fw-bold mb-0"><i class="bi bi-calendar2-check me-2 text-primary"></i>Attendance</h4>
            <p class="text-muted mb-0" style="font-size:13px">
                <?= $role==='student' ? 'Your attendance record' : 'Day-wise student attendance records' ?>
            </p>
        </div>
        <div>
            <?php if ($role !== 'student'): ?>
            <a href="mark.php" class="btn btn-primary btn-sm">
                <i class="bi bi-calendar-check me-1"></i> Mark Attendance
            </a>
            <?php else: ?>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="bi bi-file-earmark-excel me-1"></i> Download Monthly Report
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;border-radius:12px;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                        <i class="bi bi-calendar3 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px">Total Days</div>
                        <div class="fw-bold" style="font-size:26px;line-height:1"><?= $total ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;border-radius:12px;background:#d1fae5;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                        <i class="bi bi-check-circle-fill text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px">Present</div>
                        <div class="fw-bold text-success" style="font-size:26px;line-height:1"><?= $present ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;border-radius:12px;background:#fee2e2;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                        <i class="bi bi-x-circle-fill text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px">Absent</div>
                        <div class="fw-bold text-danger" style="font-size:26px;line-height:1"><?= $absent ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;border-radius:12px;background:#fef3c7;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                        <i class="bi bi-percent text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px">Attendance %</div>
                        <div class="fw-bold <?= $pct>=75?'text-success':($pct>=50?'text-warning':'text-danger') ?>" style="font-size:26px;line-height:1"><?= $pct ?>%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($role !== 'student'): ?>
    <!-- Date filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:13px">From Date</label>
                    <input type="date" name="from" class="form-control"
                           value="<?= htmlspecialchars($from) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:13px">To Date</label>
                    <input type="date" name="to" class="form-control"
                           value="<?= htmlspecialchars($to) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Attendance table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white fw-semibold">
            <i class="bi bi-list-ul me-1"></i>
            <?= $role==='student' ? 'My Attendance Record' : 'Attendance Records' ?>
        </div>
        <div class="card-body p-3">
            <?php if (empty($att_list)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    No attendance records found.
                    <?php if ($role!=='student'): ?>
                        <div class="mt-2">
                            <a href="mark.php" class="btn btn-primary btn-sm">Mark Attendance Now</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table id="attendanceTable" class="table table-hover dt-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <?php if ($role!=='student'): ?>
                        <th>Student Name</th>
                        <th>Department</th>
                        <?php endif; ?>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Status</th>
                        <?php if ($role!=='student'): ?>
                        <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($att_list as $i => $a): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <?php if ($role!=='student'): ?>
                    <td><strong><?= htmlspecialchars($a['student_name']) ?></strong></td>
                    <td><?= htmlspecialchars($a['department']) ?></td>
                    <?php endif; ?>
                    <td><?= date('d M Y', strtotime($a['date'])) ?></td>
                    <td><?= date('l', strtotime($a['date'])) ?></td>
                    <td>
                        <?php if ($a['status']==='Present'): ?>
                            <span class="badge bg-success px-3">
                                <i class="bi bi-check-circle me-1"></i>Present
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger px-3">
                                <i class="bi bi-x-circle me-1"></i>Absent
                            </span>
                        <?php endif; ?>
                    </td>
                    <?php if ($role!=='student'): ?>
                    <td>
                        <a href="edit.php?id=<?= $a['id'] ?>"
                           class="btn btn-warning btn-sm" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>
</div><!-- /#main-content -->

<!-- Export Modal (for students) -->
<?php if ($role === 'student'): ?>
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel"><i class="bi bi-file-earmark-excel text-success me-2"></i>Download Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="export.php" method="GET">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="monthSelect" class="form-label">Select Month</label>
                        <input type="month" class="form-control" id="monthSelect" name="month" value="<?= date('Y-m') ?>" required>
                    </div>
                    <p class="text-muted small mb-0">The report will be downloaded as a CSV file compatible with Microsoft Excel.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" onclick="setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide(), 500);">
                        <i class="bi bi-download me-1"></i> Download
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
</div><!-- /#content -->

<script>
$(function(){
    if($('#attendanceTable').length){
        $('#attendanceTable').DataTable({
            pageLength: 25,
            lengthMenu: [[10,25,50,100],[10,25,50,100]],
            order: [[<?= $role==='student'?'2':'3' ?>,'desc']],
            language:{ search:'Search:', lengthMenu:'Show _MENU_ entries' }
        });
    }
});
</script>