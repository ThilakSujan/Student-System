<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Only admin and staff can edit marks
require_role(['admin', 'staff']);

$message = '';
$alert_type = '';
$mark = null;

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit();
}

// Fetch mark record
$result = $mysqli->query("SELECT m.*, s.student_name, s.email, sub.subject_code, sub.subject_name FROM marks m 
                         JOIN students s ON m.student_id = s.id 
                         JOIN subjects sub ON m.subject_id = sub.id 
                         WHERE m.id = $id LIMIT 1");
if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}
$mark = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marks_obtained = floatval($_POST['marks_obtained'] ?? 0);
    $total_marks = floatval($_POST['total_marks'] ?? 100);
    $status = trim($_POST['status'] ?? 'Active');

    // Validation
    if ($marks_obtained < 0 || $total_marks <= 0 || $marks_obtained > $total_marks) {
        $message = 'Invalid marks values!';
        $alert_type = 'danger';
    } else {
        $query = "UPDATE marks SET marks_obtained = $marks_obtained, total_marks = $total_marks, status = '$status', updated_at = NOW() WHERE id = $id";
        if ($mysqli->query($query)) {
            $message = 'Marks updated successfully!';
            $alert_type = 'success';
            // Refresh data
            $result = $mysqli->query("SELECT m.*, s.student_name, s.email, sub.subject_code, sub.subject_name FROM marks m 
                                     JOIN students s ON m.student_id = s.id 
                                     JOIN subjects sub ON m.subject_id = sub.id 
                                     WHERE m.id = $id LIMIT 1");
            $mark = $result->fetch_assoc();
        } else {
            $message = 'Error updating marks: ' . $mysqli->error;
            $alert_type = 'danger';
        }
    }
}

function getGrade($percentage) {
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B';
    if ($percentage >= 60) return 'C';
    if ($percentage >= 50) return 'D';
    return 'F';
}

function getGradeColor($grade) {
    switch ($grade) {
        case 'A+':
        case 'A':
            return 'success';
        case 'B':
            return 'info';
        case 'C':
            return 'warning';
        case 'D':
            return 'danger';
        default:
            return 'danger';
    }
}

$percentage = ($mark['marks_obtained'] / $mark['total_marks']) * 100;
$grade = getGrade($percentage);
?>

<?php include '../includes/header.php'; ?>

<div id="content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="bi bi-pencil"></i> Edit Mark Record</h2>
            </div>
            <div class="col-md-4 text-end">
                <a href="student_marks.php?student_id=<?php echo $mark['student_id']; ?>" class="btn btn-secondary">Back</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> alert-dismissible fade show mt-3" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Mark Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Student Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($mark['student_name']); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($mark['email']); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($mark['subject_code'] . ' - ' . $mark['subject_name']); ?>" readonly>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="marks_obtained" class="form-label">Marks Obtained *</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="marks_obtained" name="marks_obtained" 
                                               value="<?php echo htmlspecialchars($mark['marks_obtained']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="total_marks" class="form-label">Total Marks *</label>
                                        <input type="number" step="0.01" min="1" class="form-control" id="total_marks" name="total_marks" 
                                               value="<?php echo htmlspecialchars($mark['total_marks']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="Active" <?php echo ($mark['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo ($mark['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check"></i> Update Mark
                                </button>
                                <a href="student_marks.php?student_id=<?php echo $mark['student_id']; ?>" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Mark Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Mark ID:</strong> <?php echo htmlspecialchars($mark['id']); ?></p>
                        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($mark['student_id']); ?></p>
                        <p><strong>Subject ID:</strong> <?php echo htmlspecialchars($mark['subject_id']); ?></p>
                        <hr>
                        <p><strong>Current Percentage:</strong> <?php echo round($percentage, 2); ?>%</p>
                        <p><strong>Grade:</strong> <span class="badge bg-<?php echo getGradeColor($grade); ?>"><?php echo htmlspecialchars($grade); ?></span></p>
                        <hr>
                        <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($mark['created_at'])); ?></p>
                        <p><strong>Updated:</strong> <?php echo date('M d, Y H:i', strtotime($mark['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
