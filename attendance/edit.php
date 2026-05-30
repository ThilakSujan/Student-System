<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin','staff']);
require_once '../config/db.php';

// ── Fetch record ──────────────────────────────────────
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php"); exit();
}

$id  = (int)$_GET['id'];
$res = $mysqli->query(
    "SELECT a.*, s.student_name, s.department
     FROM attendance a JOIN students s ON s.id=a.student_id
     WHERE a.id=$id LIMIT 1"
);
$record = $res ? $res->fetch_assoc() : null;
if (!$record) { header("Location: index.php"); exit(); }

$success = $error = '';

// ── Handle update ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'] ?? '';
    $new_date   = $_POST['date']   ?? '';

    if (!in_array($new_status, ['Present','Absent'])) {
        $error = "Invalid status selected.";
    } elseif (empty($new_date)) {
        $error = "Date is required.";
    } else {
        // Check for duplicate (same student, different record same date)
        $chk = $mysqli->query(
            "SELECT id FROM attendance
             WHERE student_id={$record['student_id']}
             AND date='$new_date' AND id!=$id LIMIT 1"
        );
        if ($chk && $chk->num_rows > 0) {
            $error = "Attendance for this student on $new_date already exists.";
        } else {
            $stmt = $mysqli->prepare(
                "UPDATE attendance SET status=?, date=?, marked_by=? WHERE id=?"
            );
            $uid = (int)$_SESSION['user_id'];
            $stmt->bind_param('ssii', $new_status, $new_date, $uid, $id);
            if ($stmt->execute()) {
                $success = "Attendance updated successfully.";
                // Refresh record
                $res    = $mysqli->query("SELECT a.*, s.student_name, s.department FROM attendance a JOIN students s ON s.id=a.student_id WHERE a.id=$id LIMIT 1");
                $record = $res->fetch_assoc();
            } else {
                $error = "Failed to update. Please try again.";
            }
        }
    }
}

$page_title = "Edit Attendance";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Attendance</h4>
            <p class="text-muted mb-0" style="font-size:13px">Update attendance record</p>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
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

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning fw-semibold">
                    <i class="bi bi-calendar-check me-1"></i> Update Record
                </div>
                <div class="card-body">

                    <!-- Student info (read-only) -->
                    <div class="mb-4 p-3 rounded-3" style="background:#f8f9fa">
                        <div style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;font-weight:600;margin-bottom:4px">Student</div>
                        <div class="fw-bold" style="font-size:16px"><?= htmlspecialchars($record['student_name']) ?></div>
                        <div class="text-muted" style="font-size:13px"><?= htmlspecialchars($record['department']) ?></div>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:13px">Date</label>
                            <input type="date" name="date" class="form-control"
                                   value="<?= htmlspecialchars($record['date']) ?>"
                                   max="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold" style="font-size:13px">Status</label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio"
                                           name="status" value="Present" id="present"
                                           <?= $record['status']==='Present'?'checked':'' ?>>
                                    <label class="form-check-label text-success fw-semibold" for="present">
                                        <i class="bi bi-check-circle-fill me-1"></i>Present
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio"
                                           name="status" value="Absent" id="absent"
                                           <?= $record['status']==='Absent'?'checked':'' ?>>
                                    <label class="form-check-label text-danger fw-semibold" for="absent">
                                        <i class="bi bi-x-circle-fill me-1"></i>Absent
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Update
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

</div>
</div><!-- /#main-content -->
<?php include '../includes/footer.php'; ?>
</div><!-- /#content -->