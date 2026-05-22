<?php
require_once '../includes/auth.php';
require_role(['admin']);

$page_title = "Add Staff";
$currentPage = 'staff';
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db_pdo.php';

$error = "";
$success = "";

if (isset($_POST['add_staff'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email or username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $stmt->execute([':email' => $email, ':username' => $username]);

        if ($stmt->rowCount() > 0) {
            $error = "Username or email already exists.";
        } else {
            try {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashed,
                    ':role' => 'staff'
                ]);
                header("Location: staff.php?success=1");
                exit();
            } catch (Exception $e) {
                $error = "Failed to add staff member. " . $e->getMessage();
            }
        }
    }
}
?>

<div id="content">

    <?php require '../includes/navbar.php'; ?>

    <div id="main-content">

        <div class="mb-4">
            <h4 class="mb-0">Add New Staff</h4>
            <small class="text-muted">Create a new staff account with login credentials</small>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-plus"></i> Staff Registration</h5>
                    </div>
                    <div class="card-body">
                        <form action="staff_add.php" method="POST">

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                <small class="text-muted">Must be unique</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                <small class="text-muted">Used for login</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>

                            <button type="submit" name="add_staff" class="btn btn-success">
                                <i class="bi bi-save"></i> Create Staff Account
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
                        <h5 class="card-title"><i class="bi bi-info-circle"></i> Staff Account Info</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>Role:</strong> Staff
                            </li>
                            <li class="mb-2">
                                <strong>Access:</strong> Student & Marks modules
                            </li>
                            <li class="mb-2">
                                <strong>Permissions:</strong>
                                <ul>
                                    <li>View students</li>
                                    <li>Add new students</li>
                                    <li>Edit student records</li>
                                    <li>View and manage marks</li>
                                </ul>
                            </li>
                            <li class="mb-2">
                                <strong>Cannot:</strong>
                                <ul>
                                    <li>Access admin panel</li>
                                    <li>Manage users</li>
                                    <li>Delete users</li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php require '../includes/footer.php'; ?>

</div>
