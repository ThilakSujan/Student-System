<?php
require_once '../includes/auth.php';
require_role(['admin', 'staff']);
$page_title = "View Students";
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db.php';

$success = "";
$error = "";

// Handle bulk status update
if (isset($_POST['bulk_status_update'])) {
    $ids = $_POST['student_ids'] ?? [];
    $status = $_POST['new_status'] ?? '';
    
    if (!empty($ids) && in_array($status, ['Active', 'Inactive'])) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "UPDATE students SET status = '$status' WHERE id IN ($placeholders)";
        
        if (mysqli_query($conn, sprintf($query, ...$ids))) {
            $success = "Status updated for " . count($ids) . " student(s).";
        } else {
            $error = "Failed to update status.";
        }
    }
}

// Handle individual delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $query = "DELETE FROM students WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $success = "Student deleted successfully.";
    } else {
        $error = "Failed to delete student.";
    }
}

$query = "SELECT * FROM students";
$result = mysqli_query($conn, $query);
$total_students = mysqli_num_rows($result);
?>

<div id="content">
<?php require '../includes/navbar.php'; ?>
<div id="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Students Management</h4>
            <small class="text-muted">Manage and organize all student records</small>
        </div>
        <a href="index.php" class="btn btn-success btn-sm">
            <i class="bi bi-person-plus"></i> Add New Student
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-people-fill"></i> Students List (<?php echo $total_students; ?>)</strong>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-light" onclick="filterByStatus('All')">All</button>
                <button type="button" class="btn btn-outline-light" onclick="filterByStatus('Active')">Active</button>
                <button type="button" class="btn btn-outline-light" onclick="filterByStatus('Inactive')">Inactive</button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ($total_students == 0): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted mb-2">No student records found.</p>
                    <a href="index.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Add First Student
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="studentsTable" class="table table-bordered table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><input type="checkbox" class="student-checkbox" value="<?php echo $row['id']; ?>"></td>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td>
                                    <?php if ($row['status'] == "Active"): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="students.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Delete this student?')" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Bulk Actions -->
                <div class="card-footer bg-light">
                    <form method="POST" class="row g-2 align-items-center">
                        <div class="col-auto">
                            <select class="form-select form-select-sm" id="bulkStatus">
                                <option value="">-- Select Status --</option>
                                <option value="Active">Mark as Active</option>
                                <option value="Inactive">Mark as Inactive</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" name="bulk_status_update" class="btn btn-primary btn-sm">
                                <i class="bi bi-arrow-repeat"></i> Apply
                            </button>
                        </div>
                        <div class="col-auto">
                            <small class="text-muted">Select students above and choose an action</small>
                        </div>
                        <input type="hidden" id="studentIds" name="student_ids" value="">
                        <input type="hidden" id="newStatus" name="new_status" value="">
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php require '../includes/footer.php'; ?>
</div>

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#studentsTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        columnDefs: [
            { orderable: false, targets: 0 },
            { orderable: false, targets: 7 }
        ],
        order: [[1, 'asc']]
    });
});

function filterByStatus(status) {
    let table = $('#studentsTable').DataTable();
    if (status === 'All') {
        table.column(6).search('').draw();
    } else {
        table.column(6).search(status).draw();
    }
}

$('#selectAll').on('change', function() {
    $('.student-checkbox').prop('checked', this.checked);
});

$('.student-checkbox').on('change', function() {
    $('#selectAll').prop('checked', false);
});

$('#bulkStatus').on('change', function() {
    let status = $(this).val();
    if (status) {
        let ids = $('.student-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (ids.length > 0) {
            $('#studentIds').val(JSON.stringify(ids));
            $('#newStatus').val(status);
        } else {
            alert('Please select at least one student');
            $(this).val('');
        }
    }
});
</script>
