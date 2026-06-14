<?php
require_once '../includes/auth.php';
require_role(['admin']);

$page_title = "Staff Management";
$currentPage = 'staff';
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db_pdo.php';

$success = "";
$error = "";

// DELETE STAFF
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'staff'");
            $stmt->execute([':id' => $id]);
            $success = "Staff member deleted successfully.";
        } catch (Exception $e) {
            $error = "Failed to delete staff member.";
        }
    }
}

// FETCH ALL STAFF
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'staff' ORDER BY username ASC");
$staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="content">

    <?php require '../includes/navbar.php'; ?>

    <div id="main-content">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Staff Management</h4>
                <small class="text-muted">Manage staff users and their credentials</small>
            </div>
            <a href="staff_add.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Add Staff
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-people"></i> Staff Members</strong>
                <?php if (is_admin()): ?>
                <div class="d-flex gap-2">
                    <button onclick="exportTable('table', 'Staff Report', 'excel')" class="btn btn-success btn-sm" title="Export to Excel">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
                    <button onclick="exportTable('table', 'Staff Report', 'pdf')" class="btn btn-danger btn-sm" title="Export to PDF">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($staff_list)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                        <p class="text-muted mb-0">No staff members yet. <a href="staff_add.php">Add the first staff member</a></p>
                    </div>
                <?php else: ?>
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_list as $s): ?>
                                <tr>
                                    <td><?php echo $s['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($s['username']); ?></strong>
                                        <?php if ($s['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-secondary ms-1">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($s['email']); ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <i class="bi bi-people-fill"></i> Staff
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($s['created_at'])); ?></td>
                                    <td>
                                        <a href="staff_edit.php?id=<?php echo $s['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php if ($s['id'] != $_SESSION['user_id']): ?>
                                            <a href="staff.php?delete=<?php echo $s['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Delete this staff member?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

<?php
// Inject toast notification
// Handle redirect success from staff_add
if (isset($_GET['success']) && $_GET['success'] == '1' && empty($success)) {
    $success = "Staff member added successfully.";
}
if (!empty($success)) {
    echo "<script>window._toastMsg=" . json_encode($success) . ";window._toastType='success';</script>";
} elseif (!empty($error)) {
    echo "<script>window._toastMsg=" . json_encode($error) . ";window._toastType='danger';</script>";
}
?>
    <?php require '../includes/footer.php'; ?>

</div>
