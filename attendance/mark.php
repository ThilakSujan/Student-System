<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin','staff']);
require_once '../config/db.php';
require_once '../includes/email_service.php';

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
        $ok          = true;
        $absentIds   = [];
        foreach ($statuses as $student_id => $status) {
            $student_id = (int)$student_id;
            $status     = in_array($status,['Present','Absent']) ? $status : 'Present';
            $stmt->bind_param('issi', $student_id, $date, $status, $marked_by);
            if (!$stmt->execute()) {
                $ok = false;
            } elseif ($status === 'Absent') {
                $absentIds[] = $student_id;
            }
        }

        // ── Auto email triggers (non-blocking) ───────────────────────
        $alertsSent   = 0;
        $warningsSent = 0;
        if ($ok && !empty($absentIds)) {
            try {
                $emailSvc = new EmailService($mysqli);
                foreach ($absentIds as $absId) {
                    // 1. Absence alert for today
                    if ($emailSvc->sendAttendanceAlert($absId, $date)) {
                        $alertsSent++;
                    }
                    // 2. Low attendance warning if overall % dropped below threshold
                    if ($emailSvc->sendLowAttendanceAlert($absId)) {
                        $warningsSent++;
                    }
                }
            } catch (Throwable $e) {
                error_log('[Attendance Email] ' . $e->getMessage());
            }
        }

        $emailNote = '';
        if ($alertsSent > 0) {
            $emailNote .= " {$alertsSent} absence alert(s) sent.";
        }
        if ($warningsSent > 0) {
            $emailNote .= " {$warningsSent} low attendance warning(s) sent.";
        }

        $success = $ok
            ? "Attendance saved for ".count($statuses)." students on ".date('d M Y',strtotime($date)).".{$emailNote}"
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

<style>
/* ── Attendance Toggle Button ── */
.att-toggle-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
}

.att-toggle {
    position: relative;
    display: inline-flex;
    align-items: center;
    border-radius: 50px;
    width: 160px;
    height: 38px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
    user-select: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}

.att-toggle.present {
    background: linear-gradient(135deg, #198754, #28a745);
    border-color: #146c43;
    box-shadow: 0 3px 12px rgba(25,135,84,0.35);
}

.att-toggle.absent {
    background: linear-gradient(135deg, #dc3545, #c0392b);
    border-color: #b02a37;
    box-shadow: 0 3px 12px rgba(220,53,69,0.35);
}

.att-toggle.unmarked {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    border-color: #545b62;
    box-shadow: 0 3px 12px rgba(108,117,125,0.25);
}

/* Sliding knob */
.att-toggle .knob {
    position: absolute;
    top: 3px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.25);
    transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;
}

.att-toggle.present .knob  { left: calc(100% - 31px); }
.att-toggle.absent .knob   { left: 3px; }
.att-toggle.unmarked .knob { left: calc(50% - 14px); }

/* Label text */
.att-toggle .toggle-label {
    position: absolute;
    width: 100%;
    text-align: center;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: #fff;
    text-transform: uppercase;
    z-index: 1;
    transition: opacity 0.2s ease;
    pointer-events: none;
}

/* Icons inside toggle */
.att-toggle .toggle-icon {
    font-size: 14px;
    margin-right: 4px;
}

/* Row highlight */
tr.status-present { background-color: rgba(25,135,84,0.07) !important; }
tr.status-absent  { background-color: rgba(220,53,69,0.07) !important; }

/* Pulse animation on toggle */
@keyframes togglePulse {
    0%   { transform: scale(1); }
    40%  { transform: scale(0.95); }
    70%  { transform: scale(1.04); }
    100% { transform: scale(1); }
}
.att-toggle.pulse { animation: togglePulse 0.3s ease; }

/* Card header gradient */
.att-card-header {
    background: linear-gradient(135deg, #0d6efd, #0b5ed7);
}

/* Summary badge */
.summary-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
}

/* Table font */
.att-table { font-size: 13.5px; }
.att-table thead th { background: #f1f3f5; font-weight: 600; border-bottom: 2px solid #dee2e6; }

/* Search box */
#studentSearch { max-width: 240px; font-size: 13px; }
</style>

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

    <form method="POST" id="attendanceForm">

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
                        <span class="summary-badge bg-success text-white" id="badge-present">
                            <i class="bi bi-check-circle-fill"></i>Present: <span id="cnt-present"><?= $present ?></span>
                        </span>
                        <span class="summary-badge bg-danger text-white" id="badge-absent">
                            <i class="bi bi-x-circle-fill"></i>Absent: <span id="cnt-absent"><?= $absent ?></span>
                        </span>
                        <span class="summary-badge bg-secondary text-white" id="badge-unmarked">
                            <i class="bi bi-dash-circle-fill"></i>Unmarked: <span id="cnt-unmarked"><?= $unmarked ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students attendance table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header att-card-header text-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="bi bi-people me-1"></i>
                    Students — <?= date('d M Y', strtotime($selected_date)) ?>
                </span>
                <div class="d-flex gap-2 align-items-center">
                    <input type="text" id="studentSearch" class="form-control form-control-sm"
                           style="width:180px;" placeholder="🔍 Search student…" oninput="filterStudents(this.value)">
                    <button type="button" onclick="markAll('Present')"
                            class="btn btn-success btn-sm text-nowrap">
                        <i class="bi bi-check-all me-1"></i>All Present
                    </button>
                    <button type="button" onclick="markAll('Absent')"
                            class="btn btn-danger btn-sm text-nowrap">
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
                <table class="table att-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th class="text-center">Attendance</th>
                        </tr>
                    </thead>
                    <tbody id="studentsBody">
                    <?php foreach ($students as $i => $s):
                        $existing = $s['existing_status'];
                        // Default unmarked → Present
                        $initial  = is_null($existing) ? 'Present' : $existing;
                        $rowClass = $initial === 'Present' ? 'status-present' : 'status-absent';
                        $toggleClass = strtolower($initial);
                        $icon  = $initial === 'Present' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                        $label = $initial === 'Present' ? 'Present' : 'Absent';
                    ?>
                    <tr id="row-<?= $s['id'] ?>" class="<?= $rowClass ?>" data-name="<?= strtolower(htmlspecialchars($s['student_name'])) ?>">
                        <td class="text-muted align-middle"><?= $i+1 ?></td>
                        <td class="align-middle"><strong><?= htmlspecialchars($s['student_name']) ?></strong></td>
                        <td class="align-middle text-muted"><?= htmlspecialchars($s['department']) ?></td>
                        <td class="text-center align-middle py-2">
                            <!-- Hidden input carries the value on submit -->
                            <input type="hidden"
                                   name="status[<?= $s['id'] ?>]"
                                   id="status-<?= $s['id'] ?>"
                                   value="<?= htmlspecialchars($initial) ?>">

                            <!-- Toggle pill -->
                            <div class="att-toggle-wrap">
                                <div class="att-toggle <?= $toggleClass ?>"
                                     id="toggle-<?= $s['id'] ?>"
                                     data-id="<?= $s['id'] ?>"
                                     data-state="<?= $initial ?>"
                                     onclick="toggleAttendance(this)"
                                     title="Click to toggle">
                                    <span class="knob"></span>
                                    <span class="toggle-label" id="lbl-<?= $s['id'] ?>">
                                        <i class="bi <?= $icon ?> toggle-icon"></i><?= $label ?>
                                    </span>
                                </div>
                            </div>
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
// ── Toggle a single student's attendance ──────────────
function toggleAttendance(el) {
    const id       = el.dataset.id;
    const current  = el.dataset.state;           // 'Present' | 'Absent'
    const next     = current === 'Present' ? 'Absent' : 'Present';

    // Update hidden input
    document.getElementById('status-' + id).value = next;

    // Update toggle visual
    el.dataset.state = next;
    el.classList.remove('present', 'absent', 'unmarked');
    el.classList.add(next.toLowerCase());

    // Update label + icon
    const lbl = document.getElementById('lbl-' + id);
    if (next === 'Present') {
        lbl.innerHTML = '<i class="bi bi-check-circle-fill toggle-icon"></i>Present';
    } else {
        lbl.innerHTML = '<i class="bi bi-x-circle-fill toggle-icon"></i>Absent';
    }

    // Pulse animation
    el.classList.add('pulse');
    el.addEventListener('animationend', () => el.classList.remove('pulse'), { once: true });

    // Update row colour
    const row = document.getElementById('row-' + id);
    row.classList.remove('status-present', 'status-absent');
    row.classList.add(next === 'Present' ? 'status-present' : 'status-absent');

    updateCounts();
}

// ── Mark all students Present or Absent ───────────────
function markAll(status) {
    document.querySelectorAll('.att-toggle').forEach(el => {
        if (el.dataset.state !== status) toggleAttendance(el);
    });
}

// ── Recount summary badges ────────────────────────────
function updateCounts() {
    let present = 0, absent = 0, unmarked = 0;
    document.querySelectorAll('.att-toggle').forEach(el => {
        const s = el.dataset.state;
        if (s === 'Present') present++;
        else if (s === 'Absent') absent++;
        else unmarked++;
    });
    document.getElementById('cnt-present').textContent  = present;
    document.getElementById('cnt-absent').textContent   = absent;
    document.getElementById('cnt-unmarked').textContent = unmarked;
}

// ── Load different date ───────────────────────────────
function loadDate() {
    const d = document.getElementById('dateInput').value;
    if (d) window.location.href = 'mark.php?date=' + d;
}

// ── Filter students by name ───────────────────────────
function filterStudents(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('#studentsBody tr').forEach(row => {
        const name = row.dataset.name || '';
        row.style.display = name.includes(q) ? '' : 'none';
    });
}
</script>