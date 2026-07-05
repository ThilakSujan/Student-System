<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin', 'staff']);
require_once '../config/db.php';

$page_title = 'Create Notification';
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $mysqli->real_escape_string(trim($_POST['title'] ?? ''));
    $message = $mysqli->real_escape_string(trim($_POST['message'] ?? ''));
    $target_audience = $mysqli->real_escape_string($_POST['target_audience'] ?? '');
    $expiry_date = $mysqli->real_escape_string($_POST['expiry_date'] ?? '');
    $status = $mysqli->real_escape_string($_POST['status'] ?? 'Active');

    // Validation
    if (empty($title) || empty($message) || empty($target_audience) || empty($expiry_date)) {
        $error = "All fields are required.";
    } elseif ($role === 'staff' && $target_audience !== 'Student') {
        $error = "Staff members can only target Students.";
    } else {
        $query = "INSERT INTO notifications (title, message, target_audience, expiry_date, status, created_by) 
                  VALUES ('$title', '$message', '$target_audience', '$expiry_date', '$status', $user_id)";
        
        if ($mysqli->query($query)) {
            $_SESSION['success'] = "Notification created successfully.";
            header("Location: index.php");
            exit();
        } else {
            $error = "Error creating notification: " . $mysqli->error;
        }
    }
}

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<div id="content">
    <?php require '../includes/navbar.php'; ?>
    <div id="main-content">
        <div class="container-fluid" style="max-width: 800px; margin: 0 auto;">
            
            <div class="content-header mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="bi bi-bell-fill"></i> Create Notification</h2>
                    <p class="text-muted mb-0" style="font-size:13px">Broadcast a new message to users.</p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Title</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g., Important Exam Update">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Message</label>
                            <textarea name="message" class="form-control" rows="4" required placeholder="Type the notification content here..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Target Audience</label>
                                <?php if ($role === 'admin'): ?>
                                    <select name="target_audience" class="form-select" required>
                                        <option value="">Select Audience...</option>
                                        <option value="Student">Student</option>
                                        <option value="Staff">Staff</option>
                                        <option value="Both">Both (Staff & Student)</option>
                                    </select>
                                <?php else: ?>
                                    <select name="target_audience" class="form-select" required readonly style="background-color: #f8f9fa; pointer-events: none;">
                                        <option value="Student" selected>Student</option>
                                    </select>
                                    <div class="form-text">Staff can only target students.</div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                                <div class="form-text">Notification will hide after this date.</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill me-1"></i> Send Notification</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    <?php require '../includes/footer.php'; ?>
</div>
