<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Require admin or staff role
require_role(['admin', 'staff']);

$message = '';
$alert_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $subject_code = trim($_POST['subject_code'] ?? '');
        $subject_name = trim($_POST['subject_name'] ?? '');
        $credit_hours = intval($_POST['credit_hours'] ?? 3);
        
        // Validation
        if (empty($subject_code) || empty($subject_name)) {
            $message = 'Subject Code and Name are required!';
            $alert_type = 'danger';
        } else {
            // Check if subject code already exists
            $check = $mysqli->query("SELECT id FROM subjects WHERE subject_code = '$subject_code'");
            if ($check->num_rows > 0) {
                $message = 'Subject Code already exists!';
                $alert_type = 'danger';
            } else {
                $query = "INSERT INTO subjects (subject_code, subject_name, credit_hours, status) 
                         VALUES ('$subject_code', '$subject_name', $credit_hours, 'Active')";
                if ($mysqli->query($query)) {
                    $message = 'Subject added successfully!';
                    $alert_type = 'success';
                } else {
                    $message = 'Error adding subject: ' . $mysqli->error;
                    $alert_type = 'danger';
                }
            }
        }
    }
}

// Fetch all subjects with Filters
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$status = $_GET['status'] ?? '';

$where = ["1=1"];
if ($from_date) $where[] = "DATE(created_at) >= '" . $mysqli->real_escape_string($from_date) . "'";
if ($to_date) $where[] = "DATE(created_at) <= '" . $mysqli->real_escape_string($to_date) . "'";
if ($status) $where[] = "status = '" . $mysqli->real_escape_string($status) . "'";

$query = "SELECT * FROM subjects WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
$subjects_result = $mysqli->query($query);

// Summary Stats
$all_subjects = [];
$total_sub = $active_sub = $inactive_sub = 0;
while ($row = $subjects_result->fetch_assoc()) {
    $all_subjects[] = $row;
    $total_sub++;
    if ($row['status'] === 'Active') $active_sub++;
    else $inactive_sub++;
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div id="content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="row">
            <div class="col-md-8">
                <h2><i class="bi bi-book"></i> Subject Management</h2>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                    <i class="bi bi-plus-circle"></i> Add Subject
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> alert-dismissible fade show mt-3" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Advanced Report Filters -->
        <div class="card shadow-sm mb-4 mt-4 border-0">
            <div class="card-header bg-light fw-bold">
                <i class="bi bi-funnel"></i> Report Filters
            </div>
            <div class="card-body">
                <form method="GET" action="index.php">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Created From</label>
                            <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Created To</label>
                            <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center text-bg-primary shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-1">Total Subjects</h6>
                        <h3 class="mb-0"><?= $total_sub ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center text-bg-success shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-1">Active</h6>
                        <h3 class="mb-0"><?= $active_sub ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center text-bg-secondary shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-1">Inactive</h6>
                        <h3 class="mb-0"><?= $inactive_sub ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4 border-0 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list"></i> Subjects List</h5>
                <?php if (is_admin()): ?>
                <div class="d-flex gap-2">
                    <button onclick="exportTable('#subjectsTable', 'Subjects Report', 'excel')" class="btn btn-success btn-sm" title="Export to Excel">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
                    <button onclick="exportTable('#subjectsTable', 'Subjects Report', 'pdf')" class="btn btn-danger btn-sm" title="Export to PDF">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <table id="subjectsTable" class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Credit Hours</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_subjects as $subject): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subject['id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($subject['credit_hours']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($subject['status'] === 'Active') ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($subject['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($subject['created_at'])); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" onclick="deleteSubject(<?php echo $subject['id']; ?>)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (empty($all_subjects)): ?>
                    <p class="text-center text-muted mt-3">No subjects found. <a href="#" data-bs-toggle="modal" data-bs-target="#addSubjectModal">Add one now</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Subject</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subject_code" class="form-label">Subject Code *</label>
                        <input type="text" class="form-control" id="subject_code" name="subject_code" placeholder="e.g., CS101" required>
                        <small class="text-muted">Unique identifier for the subject</small>
                    </div>
                    <div class="mb-3">
                        <label for="subject_name" class="form-label">Subject Name *</label>
                        <input type="text" class="form-control" id="subject_name" name="subject_name" placeholder="e.g., Data Structures" required>
                    </div>
                    <div class="mb-3">
                        <label for="credit_hours" class="form-label">Credit Hours</label>
                        <input type="number" class="form-control" id="credit_hours" name="credit_hours" value="3" min="1" max="10">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="action" value="add">
                        <i class="bi bi-check"></i> Add Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#subjectsTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        order: [[5, 'desc']]
    });
});

function deleteSubject(id) {
    if (confirm('Are you sure you want to delete this subject? This action cannot be undone.')) {
        fetch('delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.showToast('Subject deleted successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                window.showToast(data.message || 'Failed to delete subject.', 'danger');
            }
        })
        .catch(() => {
            window.showToast('An unexpected error occurred.', 'danger');
        });
    }
}
</script>
</div>

<?php
// Inject toast notification
if (!empty($message)) {
    echo "<script>window._toastMsg=" . json_encode($message) . ";window._toastType=" . json_encode($alert_type) . ";</script>";
}
?>
<?php include '../includes/footer.php'; ?>
