<?php
require_once '../includes/auth.php';
require_role(['admin']);

$page_title = "User Management";
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db_pdo.php';

$success = "";
$error   = "";

// DELETE USER
if (isset($_GET['delete_user'])) {
    $uid = (int) $_GET['delete_user'];
    if ($uid == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $uid]);
        $success = "User deleted successfully.";
    }
}

// MAKE ADMIN
if (isset($_GET['make_admin'])) {
    $uid = (int) $_GET['make_admin'];
    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
    $stmt->execute([':id' => $uid]);
    $success = "User promoted to Admin.";
}

// MAKE STAFF
if (isset($_GET['make_staff'])) {
    $uid = (int) $_GET['make_staff'];
    if ($uid == $_SESSION['user_id']) {
        $error = "You cannot change your own role here.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role = 'staff' WHERE id = :id");
        $stmt->execute([':id' => $uid]);
        $success = "User promoted to Staff.";
    }
}

// MAKE STUDENT
if (isset($_GET['make_student'])) {
    $uid = (int) $_GET['make_student'];
    if ($uid == $_SESSION['user_id']) {
        $error = "You cannot change your own role here.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role = 'student' WHERE id = :id");
        $stmt->execute([':id' => $uid]);
        $success = "User set to Student.";
    }
}

// FETCH ALL USERS
$stmt  = $pdo->query("SELECT * FROM users ORDER BY created_at ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Content -->
<div id="content">
<?php require '../includes/navbar.php'; ?>
<div id="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">User Management</h4>
            <small class="text-muted">All registered users in the system</small>
        </div>
        <span class="badge bg-primary fs-6"><?php echo count($users); ?> Total Users</span>
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
        <div class="card-header bg-dark text-white">
            <strong><i class="bi bi-people"></i> Registered Users</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr <?php if ($u['id'] == $_SESSION['user_id']) echo 'class="table-warning"'; ?>>
                                <td><?php echo $u['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($u['username']); ?>
                                    <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-secondary ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php if ($u['role'] == 'admin'): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-shield-fill"></i> Admin
                                        </span>
                                    <?php elseif ($u['role'] == 'staff'): ?>
                                        <span class="badge bg-info text-dark">
                                            <i class="bi bi-people-fill"></i> Staff
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">
                                            <i class="bi bi-person-fill"></i> Student
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <?php if ($u['role'] == 'admin'): ?>
                                            <a href="admin_panel.php?make_staff=<?php echo $u['id']; ?>"
                                               class="btn btn-info btn-sm"
                                               onclick="return confirm('Change role to Staff?')">
                                                <i class="bi bi-people-fill"></i> Make Staff
                                            </a>
                                            <a href="admin_panel.php?make_student=<?php echo $u['id']; ?>"
                                               class="btn btn-secondary btn-sm"
                                               onclick="return confirm('Change role to Student?')">
                                                <i class="bi bi-person-fill"></i> Make Student
                                            </a>
                                        <?php elseif ($u['role'] == 'staff'): ?>
                                            <a href="admin_panel.php?make_admin=<?php echo $u['id']; ?>"
                                               class="btn btn-warning btn-sm"
                                               onclick="return confirm('Promote to Admin?')">
                                                <i class="bi bi-arrow-up-circle"></i> Make Admin
                                            </a>
                                            <a href="admin_panel.php?make_student=<?php echo $u['id']; ?>"
                                               class="btn btn-secondary btn-sm"
                                               onclick="return confirm('Change role to Student?')">
                                                <i class="bi bi-person-fill"></i> Make Student
                                            </a>
                                        <?php elseif ($u['role'] == 'student'): ?>
                                            <a href="admin_panel.php?make_staff=<?php echo $u['id']; ?>"
                                               class="btn btn-info btn-sm"
                                               onclick="return confirm('Promote to Staff?')">
                                                <i class="bi bi-people-fill"></i> Make Staff
                                            </a>
                                            <a href="admin_panel.php?make_admin=<?php echo $u['id']; ?>"
                                               class="btn btn-warning btn-sm"
                                               onclick="return confirm('Promote to Admin?')">
                                                <i class="bi bi-arrow-up-circle"></i> Make Admin
                                            </a>
                                        <?php else: ?>
                                            <a href="admin_panel.php?make_staff=<?php echo $u['id']; ?>"
                                               class="btn btn-info btn-sm"
                                               onclick="return confirm('Change role to Staff?')">
                                                <i class="bi bi-people-fill"></i> Make Staff
                                            </a>
                                            <a href="admin_panel.php?make_admin=<?php echo $u['id']; ?>"
                                               class="btn btn-warning btn-sm"
                                               onclick="return confirm('Promote to Admin?')">
                                                <i class="bi bi-arrow-up-circle"></i> Make Admin
                                            </a>
                                            <a href="admin_panel.php?make_student=<?php echo $u['id']; ?>"
                                               class="btn btn-secondary btn-sm"
                                               onclick="return confirm('Change role to Student?')">
                                                <i class="bi bi-person-fill"></i> Make Student
                                            </a>
                                        <?php endif; ?>
                                        <a href="admin_panel.php?delete_user=<?php echo $u['id']; ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete this user?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">— your account —</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<?php require '../includes/footer.php'; ?>
</div>