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

// FETCH ALL USERS WITH FILTERS
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$role_filter = $_GET['user_role'] ?? '';
$account_status = $_GET['account_status'] ?? '';

$where = ["1=1"];
$params = [];

if ($from_date) {
    $where[] = "DATE(created_at) >= :from_date";
    $params[':from_date'] = $from_date;
}
if ($to_date) {
    $where[] = "DATE(created_at) <= :to_date";
    $params[':to_date'] = $to_date;
}
if ($role_filter) {
    $where[] = "role = :role";
    $params[':role'] = $role_filter;
}
if ($account_status) {
    $where[] = "account_status = :account_status";
    $params[':account_status'] = $account_status;
}

$query = "SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY created_at ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary Stats
$total_users = count($users);
$admin_count = $staff_count = $student_count = 0;
foreach ($users as $u) {
    if ($u['role'] === 'admin') $admin_count++;
    elseif ($u['role'] === 'staff') $staff_count++;
    elseif ($u['role'] === 'student') $student_count++;
}
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
        <div class="d-flex gap-2 align-items-center">
            <?php
            $pendingRes = $pdo->query("SELECT COUNT(*) FROM users WHERE account_status='Pending'");
            $pendingCnt = (int)$pendingRes->fetchColumn();
            if ($pendingCnt > 0):
            ?>
            <a href="approvals.php?status=Pending" class="btn btn-warning btn-sm fw-semibold">
                <i class="bi bi-hourglass-split me-1"></i>
                <?= $pendingCnt ?> Pending Approval<?= $pendingCnt > 1 ? 's' : '' ?>
            </a>
            <?php endif; ?>
            <a href="approvals.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person-check me-1"></i>Approvals Dashboard
            </a>
            <span class="badge bg-primary fs-6"><?php echo count($users); ?> Total Users</span>
        </div>
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

    <!-- Advanced Report Filters -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-light fw-bold">
            <i class="bi bi-funnel"></i> Report Filters
        </div>
        <div class="card-body">
            <form method="GET" action="admin_panel.php">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:13px">Registered From</label>
                        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:13px">Registered To</label>
                        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:13px">User Role</label>
                        <select name="user_role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="staff" <?= $role_filter === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Student</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:13px">Account Status</label>
                        <select name="account_status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Approved" <?= $account_status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Pending" <?= $account_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Suspended" <?= $account_status === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                            <option value="Rejected" <?= $account_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2 mt-3 d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                        <a href="admin_panel.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center text-bg-primary shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Total Users</h6>
                    <h3 class="mb-0"><?= $total_users ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-bg-warning text-dark shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Admins</h6>
                    <h3 class="mb-0"><?= $admin_count ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-bg-info text-dark shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Staff</h6>
                    <h3 class="mb-0"><?= $staff_count ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-bg-success shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Students</h6>
                    <h3 class="mb-0"><?= $student_count ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-people"></i> Registered Users</strong>
            <div class="d-flex gap-2">
                <button onclick="exportTable('table', 'Users Report', 'excel')" class="btn btn-success btn-sm" title="Export to Excel">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
                <button onclick="exportTable('table', 'Users Report', 'pdf')" class="btn btn-danger btn-sm" title="Export to PDF">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Account Status</th>
                        <th>Registered At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No users found.</td>
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
                                <td>
                                    <?php
                                    $astat = $u['account_status'] ?? 'Approved';
                                    $bdg = 'bg-secondary';
                                    if ($astat === 'Approved') $bdg = 'bg-success';
                                    elseif ($astat === 'Pending') $bdg = 'bg-warning text-dark';
                                    elseif ($astat === 'Suspended') $bdg = 'bg-danger';
                                    elseif ($astat === 'Rejected') $bdg = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $bdg ?>"><?= htmlspecialchars($astat) ?></span>
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