<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Require admin or staff role
require_role(['admin', 'staff']);

$message = '';
$alert_type = '';
$subject = null;

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch subject
$result = $mysqli->query("SELECT * FROM subjects WHERE id = $id");
if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}
$subject = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_code = trim($_POST['subject_code'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');
    $credit_hours = intval($_POST['credit_hours'] ?? 3);
    $status = trim($_POST['status'] ?? 'Active');
    
    // Validation
    if (empty($subject_code) || empty($subject_name)) {
        $message = 'Subject Code and Name are required!';
        $alert_type = 'danger';
    } else {
        // Check if subject code exists (excluding current subject)
        $check = $mysqli->query("SELECT id FROM subjects WHERE subject_code = '$subject_code' AND id != $id");
        if ($check->num_rows > 0) {
            $message = 'Subject Code already exists!';
            $alert_type = 'danger';
        } else {
            $query = "UPDATE subjects SET subject_code = '$subject_code', subject_name = '$subject_name', 
                     credit_hours = $credit_hours, status = '$status' WHERE id = $id";
            if ($mysqli->query($query)) {
                $message = 'Subject updated successfully!';
                $alert_type = 'success';
                // Refresh subject data
                $result = $mysqli->query("SELECT * FROM subjects WHERE id = $id");
                $subject = $result->fetch_assoc();
            } else {
                $message = 'Error updating subject: ' . $mysqli->error;
                $alert_type = 'danger';
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div id="content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="bi bi-pencil"></i> Edit Subject</h2>
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
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Subject Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="subject_code" class="form-label">Subject Code *</label>
                                <input type="text" class="form-control" id="subject_code" name="subject_code" 
                                       value="<?php echo htmlspecialchars($subject['subject_code']); ?>" required>
                                <small class="text-muted">Unique identifier for the subject</small>
                            </div>

                            <div class="mb-3">
                                <label for="subject_name" class="form-label">Subject Name *</label>
                                <input type="text" class="form-control" id="subject_name" name="subject_name" 
                                       value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="credit_hours" class="form-label">Credit Hours</label>
                                <input type="number" class="form-control" id="credit_hours" name="credit_hours" 
                                       value="<?php echo htmlspecialchars($subject['credit_hours']); ?>" min="1" max="10">
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="Active" <?php echo ($subject['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo ($subject['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check"></i> Update Subject
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Back
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Subject Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Subject ID:</strong> <?php echo htmlspecialchars($subject['id']); ?></p>
                        <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($subject['created_at'])); ?></p>
                        <p><strong>Current Status:</strong></p>
                        <span class="badge bg-<?php echo ($subject['status'] === 'Active') ? 'success' : 'danger'; ?>">
                            <?php echo htmlspecialchars($subject['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</div>

<?php
// Inject toast notification
if (!empty($message)) {
    echo "<script>window._toastMsg=" . json_encode($message) . ";window._toastType=" . json_encode($alert_type) . ";</script>";
}
?>

