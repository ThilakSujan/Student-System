<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

require_role(['admin', 'staff'], '/student_system/exam/index.php');

$error   = '';
$success = '';

// Fetch subjects and classes for dropdowns
$subjects = $mysqli->query("SELECT id, subject_code, subject_name FROM subjects WHERE status='Active' ORDER BY subject_name ASC");
$classes  = $mysqli->query("SELECT id, class_name, section FROM classes WHERE status='Active' ORDER BY class_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_title  = trim($_POST['exam_title']  ?? '');
    $subject_id  = !empty($_POST['subject_id'])  ? (int)$_POST['subject_id']  : null;
    $class_id    = !empty($_POST['class_id'])    ? (int)$_POST['class_id']    : null;
    $exam_date   = trim($_POST['exam_date']   ?? '');
    $start_time  = trim($_POST['start_time']  ?? '') ?: null;
    $end_time    = trim($_POST['end_time']    ?? '') ?: null;
    $venue       = trim($_POST['venue']       ?? '') ?: null;
    $exam_type   = trim($_POST['exam_type']   ?? 'Internal');
    $description = trim($_POST['description'] ?? '') ?: null;
    $status      = trim($_POST['status']      ?? 'Scheduled');
    $created_by  = $_SESSION['user_id'] ?? null;

    if (empty($exam_title)) {
        $error = 'Exam title is required.';
    } elseif (empty($exam_date)) {
        $error = 'Exam date is required.';
    } else {
        $stmt = $mysqli->prepare("
            INSERT INTO exam_schedule
                (exam_title, subject_id, class_id, exam_date, start_time, end_time, venue, exam_type, description, created_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('siissssssss',
            $exam_title, $subject_id, $class_id,
            $exam_date, $start_time, $end_time,
            $venue, $exam_type, $description, $created_by, $status
        );
        if ($stmt->execute()) {
            header('Location: index.php?success=' . urlencode('Exam scheduled successfully!'));
            exit();
        } else {
            $error = 'Failed to schedule exam. Please try again.';
        }
    }
}

$page_title = "Schedule Exam";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
/* ── Form Page Styles ── */
.form-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 26px 30px;
    color: #fff;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.form-hero::before {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 140px; height: 140px;
    border-radius: 50%;
    background: rgba(255,255,255,0.07);
}
.form-hero h2  { font-size: clamp(1.2rem, 3vw, 1.7rem); font-weight: 700; margin-bottom: 4px; }
.form-hero p   { opacity: .85; font-size: .93rem; margin: 0; }

.exam-form-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,.09);
    overflow: hidden;
}
.exam-form-card .card-header {
    background: linear-gradient(90deg, #667eea15, #764ba210);
    border-bottom: 1.5px solid #e8edf5;
    padding: 16px 24px;
    font-weight: 600;
    color: #374151;
    font-size: .95rem;
}
.exam-form-card .card-body {
    padding: 28px 28px;
}
.form-section-title {
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .1em;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 14px;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.form-section-title::after {
    content: '';
    flex: 1;
    height: 1.5px;
    background: linear-gradient(90deg, #667eea40, transparent);
    border-radius: 2px;
}
.form-label {
    font-size: .83rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.form-control, .form-select {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: .9rem;
    transition: border-color .2s, box-shadow .2s;
    background: #fafbff;
}
.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,.15);
    background: #fff;
}
textarea.form-control { resize: vertical; min-height: 90px; }
.btn-submit {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 11px 28px;
    font-weight: 600;
    font-size: .95rem;
    transition: transform .15s, box-shadow .15s, opacity .2s;
}
.btn-submit:hover  { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102,126,234,.4); color: #fff; }
.btn-submit:active { transform: translateY(0); }
.btn-cancel {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 24px;
    font-weight: 500;
    color: #64748b;
    background: #fff;
    transition: border-color .2s, color .2s;
    text-decoration: none;
    display: inline-block;
}
.btn-cancel:hover { border-color: #94a3b8; color: #374151; }

/* Responsive */
@media (max-width: 575px) {
    .exam-form-card .card-body { padding: 18px 16px; }
    .form-hero { padding: 18px 16px; border-radius: 12px; }
    .btn-submit, .btn-cancel { width: 100%; text-align: center; }
}
</style>

<div id="content">
    <?php include '../includes/navbar.php'; ?>

    <div id="main-content">
        <div class="container-fluid">

            <!-- Hero -->
            <div class="form-hero">
                <h2><i class="bi bi-calendar-plus-fill me-2"></i>Schedule New Exam</h2>
                <p>Fill in the details below to add a new examination to the schedule</p>
            </div>

            <!-- Error alert -->
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Form Card -->
            <div class="exam-form-card card">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square" style="color:#667eea;"></i>
                    Exam Details
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="examForm" novalidate>

                        <!-- SECTION: Basic Info -->
                        <div class="form-section-title"><i class="bi bi-info-circle"></i> Basic Information</div>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label" for="exam_title">Exam Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="exam_title" name="exam_title"
                                       placeholder="e.g. Mid-Term Mathematics Examination"
                                       value="<?= htmlspecialchars($_POST['exam_title'] ?? '') ?>" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label" for="subject_id">Subject</label>
                                <select class="form-select" id="subject_id" name="subject_id">
                                    <option value="">— Select Subject —</option>
                                    <?php if ($subjects): while ($sub = $subjects->fetch_assoc()): ?>
                                    <option value="<?= $sub['id'] ?>" <?= (($_POST['subject_id'] ?? '') == $sub['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sub['subject_code'] . ' — ' . $sub['subject_name']) ?>
                                    </option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label" for="class_id">Class / Section</label>
                                <select class="form-select" id="class_id" name="class_id">
                                    <option value="">— Select Class —</option>
                                    <?php if ($classes): while ($cls = $classes->fetch_assoc()): ?>
                                    <option value="<?= $cls['id'] ?>" <?= (($_POST['class_id'] ?? '') == $cls['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cls['class_name'] . ($cls['section'] ? ' – ' . $cls['section'] : '')) ?>
                                    </option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label" for="exam_type">Exam Type</label>
                                <select class="form-select" id="exam_type" name="exam_type">
                                    <?php foreach (['Internal','External','Practical','Viva','Other'] as $t): ?>
                                    <option value="<?= $t ?>" <?= (($_POST['exam_type'] ?? 'Internal') === $t) ? 'selected' : '' ?>><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <?php foreach (['Scheduled','Completed','Cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= (($_POST['status'] ?? 'Scheduled') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- SECTION: Date & Time -->
                        <div class="form-section-title"><i class="bi bi-clock"></i> Date & Time</div>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-sm-4">
                                <label class="form-label" for="exam_date">Exam Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="exam_date" name="exam_date"
                                       value="<?= htmlspecialchars($_POST['exam_date'] ?? '') ?>" required>
                            </div>
                            <div class="col-6 col-sm-4">
                                <label class="form-label" for="start_time">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time"
                                       value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>">
                            </div>
                            <div class="col-6 col-sm-4">
                                <label class="form-label" for="end_time">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time"
                                       value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- SECTION: Location & Notes -->
                        <div class="form-section-title"><i class="bi bi-geo-alt"></i> Location & Notes</div>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-sm-6">
                                <label class="form-label" for="venue">Venue / Room</label>
                                <input type="text" class="form-control" id="venue" name="venue"
                                       placeholder="e.g. Hall A, Room 201"
                                       value="<?= htmlspecialchars($_POST['venue'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="description">Additional Notes</label>
                                <textarea class="form-control" id="description" name="description"
                                          placeholder="Any additional instructions or notes for this exam..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                            <button type="submit" class="btn-submit" id="submitBtn">
                                <i class="bi bi-calendar-check-fill me-2"></i>Schedule Exam
                            </button>
                            <a href="index.php" class="btn-cancel">
                                <i class="bi bi-arrow-left me-1"></i>Back to List
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div><!-- /container-fluid -->
    </div><!-- /#main-content -->

    <?php include '../includes/footer.php'; ?>
</div><!-- /#content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('examForm').addEventListener('submit', function () {
    var btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Scheduling...';
    btn.disabled = true;
});
</script>
