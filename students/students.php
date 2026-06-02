<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin', 'staff']);
$page_title = "View Students";
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db.php';

$success = "";
$error   = "";

// Handle individual delete
if (isset($_GET['delete'])) {
    $id    = (int) $_GET['delete'];
    $query = "DELETE FROM students WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $success = "Student deleted successfully.";
    } else {
        $error = "Failed to delete student.";
    }
}

$query          = "SELECT * FROM students";
$result         = mysqli_query($conn, $query);
$total_students = mysqli_num_rows($result);
?>

<div id="content">
<?php require '../includes/navbar.php'; ?>
<div id="main-content">

    <!-- Page heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Students Management</h4>
            <small class="text-muted">Manage and organize all student records</small>
        </div>
        <a href="index.php" class="btn btn-success btn-sm">
            <i class="bi bi-person-plus"></i> Add New Student
        </a>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-people-fill"></i> Students List (<?= $total_students ?>)</strong>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="student-checkbox form-check-input"
                                           value="<?= $row['id'] ?>">
                                </td>
                                <td><?= $row['id'] ?></td>
                                <td><strong><?= htmlspecialchars($row['student_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td>
                                    <!-- Edit -->
                                    <a href="edit.php?id=<?= $row['id'] ?>"
                                       class="btn btn-warning btn-sm" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <!-- Report Card — opens in same page -->
                                    <a href="report_card.php?student_id=<?= $row['id'] ?>"
                                       class="btn btn-info btn-sm" title="View Report Card">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </a>

                                    <!-- Delete -->
                                    <a href="students.php?delete=<?= $row['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete this student permanently?')"
                                       title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>


            <?php endif; ?>
        </div>
    </div>

</div><!-- /#main-content -->
<?php require '../includes/footer.php'; ?>
</div><!-- /#content -->

<script>
$(document).ready(function () {
    $('#studentsTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        columnDefs: [
            { orderable: false, targets: 0 },
            { orderable: false, targets: 6 }
        ],
        order: [[1, 'asc']]
    });
});

// Select all checkboxes
$('#selectAll').on('change', function () {
    $('.student-checkbox').prop('checked', this.checked);
});

// Deselect "select all" if any individual is unchecked
$(document).on('change', '.student-checkbox', function () {
    if (!this.checked) $('#selectAll').prop('checked', false);
    if ($('.student-checkbox:checked').length === $('.student-checkbox').length) {
        $('#selectAll').prop('checked', true);
    }
});


<?php
// Inject toast notification
if (!empty($success)) {
    echo "<script>window._toastMsg=" . json_encode($success) . ";window._toastType='success';</script>";
} elseif (!empty($error)) {
    echo "<script>window._toastMsg=" . json_encode($error) . ";window._toastType='danger';</script>";
}
?>
</script>