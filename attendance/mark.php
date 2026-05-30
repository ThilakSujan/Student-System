<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin','staff']);
require_once '../config/db.php';

// ── Handle bulk form submission ───────────────────────
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date       = $_POST['date'] ?? '';
    $statuses   = $_POST['status'] ?? [];
    $marked_by  = (int)$_SESSION['user_id'];

    if (empty($date)) {
        $error = "Please select a date.";
    } elseif (empty($statuses)) {
        $error = "No students found to mark.";
    } else {
        $stmt = $mysqli->prepare(
            "INSERT INTO attendance (student_id, date, status, marked_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status=VALUES(status), marked_by=VALUES(marked_by)"
        );
        $ok = true;
        foreach ($statuses as $student_id => $status) {
            $student_id = (int)$student_id;
            $status     = in_array($status,['Present','Absent']) ? $status : 'Present';
            $stmt->bind_param('issi', $student_id, $date, $status, $marked_by);
            if (!$stmt->execute()) { $ok = false; }
        }
        $success = $ok ? "Attendance saved for ".count($statuses)." students on ".date('d M Y',strtotime($date))."."
                       : "Some records could not be saved.";
    }
}

// ── Fetch selected date's existing attendance ─────────
$selected_date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
$students_res  = $mysqli->query(
    "SELECT s.id, s.student_name, s.department,
            a.status AS existing_status
     FROM students s
     LEFT JOIN attendance a ON a.student_id=s.id AND a.date='$selected_date'
     WHERE s.status='Active'
     ORDER BY s.student_name ASC"
);
$students = $students_res ? $students_res->fetch_all(MYSQLI_ASSOC) : [];

// ── Count summary for selected date ──────────────────
$present = count(array_filter($students, fn($s) => $s['existing_status']==='Present'));
$absent  = count(array_filter($students, fn($s) => $s['existing_status']==='Absent'));
$unmarked= count(array_filter($students, fn($s) => is_null($s['existing_status'])));

$page_title = "Mark Attendance";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-primary"></i>Mark Attendance</h4>
            <p class="text-muted mb-0" style="font-size:13px">Mark day-wise attendance for all students</p>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-list-ul me-1"></i> View Attendance
        </a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST">

        <!-- Date selector -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:13px">
                            <i class="bi bi-calendar3 me-1 text-primary"></i>Select Date
                        </label>
                        <input type="date" name="date" id="dateInput"
                               class="form-control"
                               value="<?= htmlspecialchars($selected_date) ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="button" onclick="loadDate()" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search me-1"></i> Load Students
                        </button>
                    </div>
                    <!-- Summary badges -->
                    <div class="col-md-5 d-flex gap-2 justify-content-end flex-wrap">
                        <span class="badge bg-success px-3 py-2" style="font-size:13px">
                            <i class="bi bi-check-circle me-1"></i>Present: <?= $present ?>
                        </span>
                        <span class="badge bg-danger px-3 py-2" style="font-size:13px">
                            <i class="bi bi-x-circle me-1"></i>Absent: <?= $absent ?>
                        </span>
                        <span class="badge bg-secondary px-3 py-2" style="font-size:13px">
                            <i class="bi bi-dash-circle me-1"></i>Unmarked: <?= $unmarked ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students attendance table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-people me-1"></i>
                    Students — <?= date('d M Y', strtotime($selected_date)) ?>
                </span>
                <div class="d-flex gap-2">
                    <button type="button" onclick="markAll('Present')"
                            class="btn btn-success btn-sm">
                        <i class="bi bi-check-all me-1"></i>All Present
                    </button>
                    <button type="button" onclick="markAll('Absent')"
                            class="btn btn-danger btn-sm">
                        <i class="bi bi-x-lg me-1"></i>All Absent
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($students)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>No active students found.
                    </div>
                <?php else: ?>
                <table class="table table-hover mb-0" style="font-size:13px">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th class="text-center">
                                <i class="bi bi-check-circle-fill text-success me-1"></i>Present
                            </th>
                            <th class="text-center">
                                <i class="bi bi-x-circle-fill text-danger me-1"></i>Absent
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($students as $i => $s):
                        $existing = $s['existing_status'];
                        $is_present = $existing === 'Present';
                        $is_absent  = $existing === 'Absent';
                        // Default to Present if unmarked
                        $default_present = is_null($existing) ? true : $is_present;
                    ?>
                    <tr id="row-<?= $s['id'] ?>" class="<?= $is_absent ? 'table-danger' : ($is_present ? 'table-success' : '') ?>" style="opacity:<?= is_null($existing)?'0.7':'1' ?>">
                        <td class="text-muted"><?= $i+1 ?></td>
                        <td><strong><?= htmlspecialchars($s['student_name']) ?></strong></td>
                        <td><?= htmlspecialchars($s['department']) ?></td>
                        <td class="text-center">
                            <input type="radio"
                                   name="status[<?= $s['id'] ?>]"
                                   value="Present"
                                   class="form-check-input attendance-radio"
                                   data-id="<?= $s['id'] ?>"
                                   <?= $default_present ? 'checked' : '' ?>
                                   style="width:20px;height:20px;cursor:pointer;accent-color:#198754">
                        </td>
                        <td class="text-center">
                            <input type="radio"
                                   name="status[<?= $s['id'] ?>]"
                                   value="Absent"
                                   class="form-check-input attendance-radio"
                                   data-id="<?= $s['id'] ?>"
                                   <?= $is_absent ? 'checked' : '' ?>
                                   style="width:20px;height:20px;cursor:pointer;accent-color:#dc3545">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php if (!empty($students)): ?>
            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= count($students) ?> students — <?= date('l, d M Y', strtotime($selected_date)) ?>
                </small>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Save Attendance
                </button>
            </div>
            <?php endif; ?>
        </div>

        <input type="hidden" name="date" value="<?= htmlspecialchars($selected_date) ?>">
    </form>

</div>
</div><!-- /#main-content -->
<?php include '../includes/footer.php'; ?>
</div><!-- /#content -->

<script>
// Load different date
function loadDate() {
    const d = document.getElementById('dateInput').value;
    if (d) window.location.href = 'mark.php?date=' + d;
}

// Mark all students Present or Absent
function markAll(status) {
    document.querySelectorAll('.attendance-radio').forEach(r => {
        if (r.value === status) {
            r.checked = true;
            updateRowColor(r.dataset.id, status);
        }
    });
}

// Update row colour on radio change
document.querySelectorAll('.attendance-radio').forEach(r => {
    r.addEventListener('change', function () {
        updateRowColor(this.dataset.id, this.value);
    });
});

function updateRowColor(id, status) {
    const row = document.getElementById('row-' + id);
    if (!row) return;
    row.classList.remove('table-success','table-danger');
    row.style.opacity = '1';
    row.classList.add(status === 'Present' ? 'table-success' : 'table-danger');
}
</script>