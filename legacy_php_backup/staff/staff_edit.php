<?php
require_once '../includes/auth.php';
require_role(['admin']);

$page_title = "Edit Staff";
$currentPage = 'staff';
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db_pdo.php';

$error = "";
$success = "";

if (!isset($_GET['id'])) {
    header("Location: staff.php");
    exit();
}

$id = (int) $_GET['id'];

// Fetch staff member
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'staff'");
$stmt->execute([':id' => $id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    header("Location: staff.php?error=not_found");
    exit();
}

if (isset($_POST['update_staff'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($email)) {
        $error = "Username and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email/username already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (email = :email OR username = :username) AND id != :id");
        $stmt->execute([':email' => $email, ':username' => $username, ':id' => $id]);

        if ($stmt->rowCount() > 0) {
            $error = "Username or email already taken by another user.";
        } else {
            try {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error = "Password must be at least 6 characters.";
                    } else {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, password = :password WHERE id = :id");
                        $stmt->execute([
                            ':username' => $username,
                            ':email' => $email,
                            ':password' => $hashed,
                            ':id' => $id
                        ]);
                        $success = "Staff member updated successfully.";
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
                    $stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':id' => $id
                    ]);
                    $success = "Staff member updated successfully.";
                }

                if ($success) {
                    // Refresh staff data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error = "Failed to update staff member.";
            }
        }
    }
}
?>

<div id="content">

    <?php require '../includes/navbar.php'; ?>

    <div id="main-content">

        <div class="mb-4">
            <h4 class="mb-0">Edit Staff Member</h4>
            <small class="text-muted">Update staff account details and credentials</small>
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

        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Staff Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="staff_edit.php?id=<?php echo $id; ?>" method="POST">

                            <div class="mb-3">
                                <label class="form-label">Staff ID</label>
                                <input type="text" class="form-control" disabled value="<?php echo $staff['id']; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required 
                                       value="<?php echo htmlspecialchars($staff['username']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($staff['email']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" disabled value="Staff">
                                <small class="text-muted">Staff role cannot be changed from this page. Use User Management to change roles.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password (Leave blank to keep current)</label>
                                <input type="password" name="password" class="form-control" minlength="6">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Registered</label>
                                <input type="text" class="form-control" disabled 
                                       value="<?php echo date('d M Y, h:i A', strtotime($staff['created_at'])); ?>">
                            </div>

                            <button type="submit" name="update_staff" class="btn btn-warning">
                                <i class="bi bi-save"></i> Update Staff
                            </button>
                            <a href="staff.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>

                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm bg-light">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-shield-check"></i> Staff Permissions</h5>
                        <p class="text-muted small mb-3">This staff member has access to:</p>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                <strong>Dashboard</strong> - View overview
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                <strong>Student Management</strong> - Add, edit, view students
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                <strong>Marks Module</strong> - View and manage student marks
                            </li>
                            <li class="mb-2 text-muted">
                                <i class="bi bi-x-circle"></i>
                                <strong>Admin Panel</strong> - Not accessible
                            </li>
                            <li class="mb-2 text-muted">
                                <i class="bi bi-x-circle"></i>
                                <strong>User Management</strong> - Not accessible
                            </li>
                        </ul>

                        <hr>

                        <h6 class="card-subtitle mb-3"><i class="bi bi-gear"></i> Management</h6>
                        <p class="text-muted small">To change this staff member's role or delete their account, visit the <a href="admin_panel.php">User Management</a> page.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

<?php
// Inject toast notification
if (!empty($success)) {
    echo "<script>window._toastMsg=" . json_encode($success) . ";window._toastType='success';</script>";
} elseif (!empty($error)) {
    echo "<script>window._toastMsg=" . json_encode($error) . ";window._toastType='danger';</script>";
}
?>
    <?php require '../includes/footer.php'; ?>

</div>
