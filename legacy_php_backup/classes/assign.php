<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin', 'staff']);
require_once '../config/db.php';

$page_title = "Assign Students";

$class_id = intval($_GET['class_id'] ?? 0);
if ($class_id <= 0) { header('Location: index.php'); exit; }

// Fetch class info
$cr = $mysqli->prepare(
    "SELECT c.*, u.username AS teacher_name
     FROM classes c LEFT JOIN users u ON u.id = c.class_teacher_id WHERE c.id = ?"
);
$cr->bind_param('i', $class_id);
$cr->execute();
$cls = $cr->get_result()->fetch_assoc();
if (!$cls) { header('Location: index.php'); exit; }

$success = $error = '';

// ── Handle assign / remove actions ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $student_id = intval($_POST['student_id'] ?? 0);

    if ($action === 'assign' && $student_id > 0) {
        $stmt = $mysqli->prepare(
            "INSERT IGNORE INTO class_students (class_id, student_id) VALUES (?, ?)"
        );
        $stmt->bind_param('ii', $class_id, $student_id);
        $success = $stmt->execute() ? 'Student enrolled successfully!' : 'Failed to enroll student.';
        if (!$stmt->execute() && $mysqli->errno == 0) $success = 'Student enrolled successfully!';

    } elseif ($action === 'remove' && $student_id > 0) {
        $stmt = $mysqli->prepare(
            "DELETE FROM class_students WHERE class_id = ? AND student_id = ?"
        );
        $stmt->bind_param('ii', $class_id, $student_id);
        $success = $stmt->execute() ? 'Student removed from class.' : 'Failed to remove student.';

    } elseif ($action === 'bulk_assign') {
        // Bulk assign: selected[] array
        $ids = array_map('intval', $_POST['selected'] ?? []);
        if (empty($ids)) {
            $error = 'No students selected.';
        } else {
            $stmt = $mysqli->prepare(
                "INSERT IGNORE INTO class_students (class_id, student_id) VALUES (?, ?)"
            );
            $added = 0;
            foreach ($ids as $sid) {
                $stmt->bind_param('ii', $class_id, $sid);
                if ($stmt->execute()) $added++;
            }
            $success = "$added student(s) enrolled successfully!";
        }

    } elseif ($action === 'bulk_remove') {
        $ids = array_map('intval', $_POST['selected_enrolled'] ?? []);
        if (empty($ids)) {
            $error = 'No students selected to remove.';
        } else {
            $removed = 0;
            foreach ($ids as $sid) {
                $stmt = $mysqli->prepare(
                    "DELETE FROM class_students WHERE class_id = ? AND student_id = ?"
                );
                $stmt->bind_param('ii', $class_id, $sid);
                if ($stmt->execute()) $removed++;
            }
            $success = "$removed student(s) removed from class.";
        }
    }
}

// ── Fetch enrolled student IDs ────────────────────────────────
$enrolled_res = $mysqli->prepare(
    "SELECT student_id FROM class_students WHERE class_id = ?"
);
$enrolled_res->bind_param('i', $class_id);
$enrolled_res->execute();
$enrolled_ids = array_column($enrolled_res->get_result()->fetch_all(MYSQLI_ASSOC), 'student_id');

// ── Fetch ALL active students ─────────────────────────────────
$all_res  = $mysqli->query("SELECT id, student_name, email, department, gender FROM students ORDER BY student_name ASC");
$all_students = $all_res ? $all_res->fetch_all(MYSQLI_ASSOC) : [];

// Split into enrolled / not-enrolled
$enrolled     = array_filter($all_students, fn($s) => in_array($s['id'], $enrolled_ids));
$not_enrolled = array_filter($all_students, fn($s) => !in_array($s['id'], $enrolled_ids));

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
.student-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: border-color .18s, box-shadow .18s, background .18s;
    cursor: pointer;
    background: #fff;
    margin-bottom: 8px;
}
.student-card:hover { border-color: #6366f1; box-shadow: 0 2px 10px rgba(99,102,241,.12); }
.student-card.selected-card { border-color: #6366f1; background: #eef2ff; box-shadow: 0 2px 10px rgba(99,102,241,.18); }
.student-card.enrolled-card { border-color: #e2e8f0; }
.student-card.enrolled-card:hover { border-color: #ef4444; box-shadow: 0 2px 10px rgba(239,68,68,.12); }
.student-card.selected-enrolled { border-color: #ef4444; background: #fef2f2; }
.student-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg,#6366f1,#a78bfa);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; color: #fff; flex-shrink: 0;
}
.enrolled-avatar { background: linear-gradient(135deg,#10b981,#34d399); }
.panel-box {
    height: 420px;
    overflow-y: auto;
    padding: 12px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    background: #f8fafc;
}
.panel-box::-webkit-scrollbar { width: 4px; }
.panel-box::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 4px; }
.panel-search {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 7px 12px;
    font-size: 13px;
    width: 100%;
    margin-bottom: 10px;
    outline: none;
    transition: border-color .18s, box-shadow .18s;
}
.panel-search:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
</style>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0" style="font-size:13px">
            <li class="breadcrumb-item"><a href="index.php">Classes</a></li>
            <li class="breadcrumb-item"><a href="students.php?class_id=<?= $class_id ?>">
                <?= htmlspecialchars($cls['class_name']) ?><?= $cls['section'] ? ' — '.$cls['section'] : '' ?>
            </a></li>
            <li class="breadcrumb-item active">Assign Students</li>
        </ol>
    </nav>

    <!-- Class banner -->
    <div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(110deg,#4f46e5,#7c3aed);color:#fff">
        <div class="card-body p-3 d-flex align-items-center gap-3">
            <div style="width:46px;height:46px;border-radius:12px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:22px;">
                <i class="bi bi-building-fill"></i>
            </div>
            <div>
                <div class="fw-bold" style="font-size:16px">
                    <?= htmlspecialchars($cls['class_name']) ?>
                    <?php if ($cls['section']): ?><span class="badge ms-1" style="background:rgba(255,255,255,.2)"><?= htmlspecialchars($cls['section']) ?></span><?php endif; ?>
                </div>
                <div style="font-size:12px;opacity:.8">
                    <?= $cls['academic_year'] ? $cls['academic_year'].' · ' : '' ?>
                    <?= count($enrolled) ?> enrolled · <?= count($not_enrolled) ?> available
                </div>
            </div>
            <a href="students.php?class_id=<?= $class_id ?>" class="btn btn-light btn-sm ms-auto">
                <i class="bi bi-eye me-1"></i>View Roster
            </a>
        </div>
    </div>

    <!-- Dual panel layout -->
    <div class="row g-4">

        <!-- LEFT: Available (not enrolled) -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <strong><i class="bi bi-person-add me-1 text-primary"></i>Available Students</strong>
                        <span class="badge bg-primary ms-1" id="available-count"><?= count($not_enrolled) ?></span>
                    </div>
                    <form method="POST" id="assignForm">
                        <input type="hidden" name="action" value="bulk_assign">
                        <button type="submit" class="btn btn-primary btn-sm" id="assignBtn" disabled>
                            <i class="bi bi-arrow-right-circle me-1"></i>Enroll Selected
                        </button>
                    </form>
                </div>
                <div class="card-body p-3">
                    <input class="panel-search" type="text" id="searchAvailable" placeholder="🔍 Search students...">
                    <div class="panel-box" id="availablePanel">
                        <?php if (empty($not_enrolled)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                                All students are enrolled in this class!
                            </div>
                        <?php else: ?>
                        <?php foreach ($not_enrolled as $s): ?>
                        <div class="student-card" data-id="<?= $s['id'] ?>" data-name="<?= strtolower(htmlspecialchars($s['student_name'])) ?>"
                             onclick="toggleSelect(this, 'available')">
                            <input type="checkbox" name="selected[]" value="<?= $s['id'] ?>" form="assignForm"
                                   class="available-cb" style="display:none">
                            <div class="student-avatar"><?= strtoupper(substr($s['student_name'],0,1)) ?></div>
                            <div style="min-width:0;flex:1">
                                <div class="fw-semibold" style="font-size:13px"><?= htmlspecialchars($s['student_name']) ?></div>
                                <div class="text-muted" style="font-size:11px"><?= htmlspecialchars($s['email']) ?> <?= $s['department'] ? '· '.$s['department'] : '' ?></div>
                            </div>
                            <i class="bi bi-plus-circle text-primary select-icon"></i>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Enrolled -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <strong><i class="bi bi-people-fill me-1 text-success"></i>Enrolled Students</strong>
                        <span class="badge bg-success ms-1" id="enrolled-count"><?= count($enrolled) ?></span>
                    </div>
                    <form method="POST" id="removeForm">
                        <input type="hidden" name="action" value="bulk_remove">
                        <button type="submit" class="btn btn-danger btn-sm" id="removeBtn" disabled>
                            <i class="bi bi-person-dash me-1"></i>Remove Selected
                        </button>
                    </form>
                </div>
                <div class="card-body p-3">
                    <input class="panel-search" type="text" id="searchEnrolled" placeholder="🔍 Search enrolled...">
                    <div class="panel-box" id="enrolledPanel">
                        <?php if (empty($enrolled)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No students enrolled yet.<br>
                                <small>Select from the left panel to enroll.</small>
                            </div>
                        <?php else: ?>
                        <?php foreach ($enrolled as $s): ?>
                        <div class="student-card enrolled-card" data-id="<?= $s['id'] ?>" data-name="<?= strtolower(htmlspecialchars($s['student_name'])) ?>"
                             onclick="toggleSelect(this, 'enrolled')">
                            <input type="checkbox" name="selected_enrolled[]" value="<?= $s['id'] ?>" form="removeForm"
                                   class="enrolled-cb" style="display:none">
                            <div class="student-avatar enrolled-avatar"><?= strtoupper(substr($s['student_name'],0,1)) ?></div>
                            <div style="min-width:0;flex:1">
                                <div class="fw-semibold" style="font-size:13px"><?= htmlspecialchars($s['student_name']) ?></div>
                                <div class="text-muted" style="font-size:11px"><?= htmlspecialchars($s['email']) ?> <?= $s['department'] ? '· '.$s['department'] : '' ?></div>
                            </div>
                            <i class="bi bi-dash-circle text-danger select-icon"></i>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tips -->
    <div class="alert alert-info d-flex gap-2 align-items-start mt-4 border-0 shadow-sm" style="font-size:13px">
        <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
        <div>
            <strong>How to use:</strong> Click a student card to select it (cards highlight), then click
            <strong>Enroll Selected</strong> or <strong>Remove Selected</strong>. Use the search boxes to find students quickly.
        </div>
    </div>

</div>
</div>

<?php
if (!empty($success)) echo "<script>window._toastMsg=" . json_encode($success) . ";window._toastType='success';</script>";
if (!empty($error))   echo "<script>window._toastMsg=" . json_encode($error)   . ";window._toastType='danger';</script>";
?>
<?php include '../includes/footer.php'; ?>
</div>

<script>
// ── Toggle card selection ─────────────────────────────
function toggleSelect(card, type) {
    const isAvailable = type === 'available';
    const cb = card.querySelector(isAvailable ? '.available-cb' : '.enrolled-cb');
    const icon = card.querySelector('.select-icon');

    if (isAvailable) {
        card.classList.toggle('selected-card');
        cb.checked = card.classList.contains('selected-card');
        icon.className = card.classList.contains('selected-card')
            ? 'bi bi-check-circle-fill text-primary select-icon'
            : 'bi bi-plus-circle text-primary select-icon';
    } else {
        card.classList.toggle('selected-enrolled');
        cb.checked = card.classList.contains('selected-enrolled');
        icon.className = card.classList.contains('selected-enrolled')
            ? 'bi bi-dash-circle-fill text-danger select-icon'
            : 'bi bi-dash-circle text-danger select-icon';
    }
    updateButtons();
}

function updateButtons() {
    const assignBtn = document.getElementById('assignBtn');
    const removeBtn = document.getElementById('removeBtn');
    const selectedAvailable = document.querySelectorAll('.available-cb:checked').length;
    const selectedEnrolled  = document.querySelectorAll('.enrolled-cb:checked').length;
    assignBtn.disabled = selectedAvailable === 0;
    removeBtn.disabled = selectedEnrolled  === 0;
    if (selectedAvailable > 0) {
        assignBtn.innerHTML = `<i class="bi bi-arrow-right-circle me-1"></i>Enroll ${selectedAvailable} Student(s)`;
    } else {
        assignBtn.innerHTML = `<i class="bi bi-arrow-right-circle me-1"></i>Enroll Selected`;
    }
    if (selectedEnrolled > 0) {
        removeBtn.innerHTML = `<i class="bi bi-person-dash me-1"></i>Remove ${selectedEnrolled} Student(s)`;
    } else {
        removeBtn.innerHTML = `<i class="bi bi-person-dash me-1"></i>Remove Selected`;
    }
}

// ── Live search ──────────────────────────────────────
function filterCards(inputId, panelId) {
    const q = document.getElementById(inputId).value.toLowerCase().trim();
    document.querySelectorAll('#' + panelId + ' .student-card').forEach(card => {
        card.style.display = card.dataset.name.includes(q) ? '' : 'none';
    });
}

document.getElementById('searchAvailable').addEventListener('input', () =>
    filterCards('searchAvailable', 'availablePanel'));
document.getElementById('searchEnrolled').addEventListener('input', () =>
    filterCards('searchEnrolled', 'enrolledPanel'));

// Toast on form submit feedback
document.getElementById('assignForm').addEventListener('submit', function() {
    const n = document.querySelectorAll('.available-cb:checked').length;
    if (n === 0) { window.showToast('Please select at least one student.', 'warning'); return false; }
});
document.getElementById('removeForm').addEventListener('submit', function() {
    const n = document.querySelectorAll('.enrolled-cb:checked').length;
    if (n === 0) { window.showToast('Please select students to remove.', 'warning'); return false; }
    return confirm('Remove ' + n + ' student(s) from this class?');
});
</script>
