<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

require_role(['admin', 'staff']);

$message    = '';
$alert_type = '';

// Fetch students into array first (so we can reuse in form)
$students_res = $mysqli->query("SELECT id, student_name, email FROM students WHERE status='Active' ORDER BY student_name ASC");
$students = [];
while ($row = $students_res->fetch_assoc()) { $students[] = $row; }

// Fetch subjects into array
$subjects_res = $mysqli->query("SELECT id, subject_code, subject_name FROM subjects WHERE status='Active' ORDER BY subject_code ASC");
$available_subjects = [];
while ($s = $subjects_res->fetch_assoc()) { $available_subjects[] = $s; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id  = intval($_POST['student_id'] ?? 0);
    $subject_ids = $_POST['subject_ids'] ?? [];
    $marks_input = $_POST['marks'] ?? [];

    if ($student_id <= 0 || count($subject_ids) < 1) {
        $message    = 'Please select a student and provide marks for all subjects.';
        $alert_type = 'danger';
    } else {
        $stmt_check  = $mysqli->prepare("SELECT id FROM marks WHERE student_id = ? AND subject_id = ?");
        $stmt_insert = $mysqli->prepare("INSERT INTO marks (student_id, subject_id, marks_obtained, total_marks, status) VALUES (?, ?, ?, 100, 'Active')");
        $stmt_update = $mysqli->prepare("UPDATE marks SET marks_obtained = ?, updated_at = NOW() WHERE id = ?");

        $errors = 0;
        foreach ($subject_ids as $index => $sub_id) {
            $sub_id    = intval($sub_id);
            $mark_val  = floatval($marks_input[$index] ?? 0);
            if ($sub_id <= 0) continue;

            $stmt_check->bind_param('ii', $student_id, $sub_id);
            $stmt_check->execute();
            $res = $stmt_check->get_result();

            if ($res && $res->num_rows > 0) {
                $existing_id = (int)$res->fetch_assoc()['id'];
                $stmt_update->bind_param('di', $mark_val, $existing_id);
                if (!$stmt_update->execute()) $errors++;
            } else {
                $stmt_insert->bind_param('iid', $student_id, $sub_id, $mark_val);
                if (!$stmt_insert->execute()) $errors++;
            }
        }

        if ($errors === 0) {
            $message    = 'Marks saved successfully!';
            $alert_type = 'success';
        } else {
            $message    = 'Some marks could not be saved. Please try again.';
            $alert_type = 'warning';
        }
    }
}

$page_title = "Add Marks";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
    <?php include '../includes/navbar.php'; ?>

    <div id="main-content">
        <div class="container-fluid">

            <!-- Page heading -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-plus-circle"></i> Add / Update Marks</h2>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Marks
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($alert_type) ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $alert_type==='success'?'check-circle':'exclamation-circle' ?> me-1"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($available_subjects)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    No active subjects found. Please <a href="../subjects/index.php">add subjects</a> first.
                </div>
            <?php elseif (empty($students)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    No active students found. Please <a href="../students/index.php">add students</a> first.
                </div>
            <?php else: ?>

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Enter Marks</h5>
                </div>
                <div class="card-body">
                    <form method="POST">

                        <!-- Student selector -->
                        <div class="mb-4">
                            <label for="student_id" class="form-label fw-semibold">
                                Select Student <span class="text-danger">*</span>
                            </label>
                            <select name="student_id" id="student_id" class="form-select" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>"
                                        <?= (isset($_POST['student_id']) && $_POST['student_id'] == $student['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($student['student_name']) ?>
                                        (<?= htmlspecialchars($student['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <p class="text-muted mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Enter marks for each subject (0 – 100). Existing marks will be updated.
                        </p>

                        <!-- Subject mark rows -->
                        <?php foreach ($available_subjects as $i => $sub): ?>
                        <div class="row g-3 align-items-end mb-3">

                            <!-- Subject (read-only display + hidden input) -->
                            <div class="col-md-5">
                                <?php if ($i === 0): ?>
                                    <label class="form-label fw-semibold">Subject</label>
                                <?php endif; ?>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-book"></i>
                                    </span>
                                    <input type="text" class="form-control bg-light"
                                        value="<?= htmlspecialchars($sub['subject_code']) ?> — <?= htmlspecialchars($sub['subject_name']) ?>"
                                        readonly>
                                    <input type="hidden" name="subject_ids[]" value="<?= $sub['id'] ?>">
                                </div>
                            </div>

                            <!-- Marks obtained -->
                            <div class="col-md-4">
                                <?php if ($i === 0): ?>
                                    <label class="form-label fw-semibold">Marks Obtained</label>
                                <?php endif; ?>
                                <div class="input-group">
                                    <input type="number" step="1" min="0" max="100"
                                        name="marks[]" class="form-control"
                                        placeholder="0 – 100"
                                        value="<?= isset($_POST['marks'][$i]) ? htmlspecialchars($_POST['marks'][$i]) : '' ?>"
                                        required>
                                    <span class="input-group-text">/ 100</span>
                                </div>
                            </div>

                            <!-- Max marks badge -->
                            <div class="col-md-3">
                                <?php if ($i === 0): ?>
                                    <label class="form-label fw-semibold">Max Marks</label>
                                <?php endif; ?>
                                <div class="form-control bg-light text-center fw-bold text-muted">100</div>
                            </div>

                        </div>
                        <?php endforeach; ?>

                        <hr class="my-4">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Marks
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>

            <?php endif; ?>

        </div><!-- /container-fluid -->
    </div><!-- /#main-content -->

    <?php include '../includes/footer.php'; ?>

</div><!-- /#content -->