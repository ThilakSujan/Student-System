<?php
require_once '../includes/auth.php';
require_login();

$page_title = "Edit Profile";
$currentPage = 'profile';
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db_pdo.php';
include '../config/db.php';

// For students, redirect to view page (read-only)
if ($_SESSION['role'] === 'student') {
    header('Location: view.php');
    exit;
}

// Ensure user_profiles table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS user_profiles (
    user_id INT PRIMARY KEY,
    full_name VARCHAR(255),
    phone VARCHAR(50),
    profile_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

// Fetch current user and profile
$stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.role, p.full_name, p.phone, p.profile_text
                       FROM users u
                       LEFT JOIN user_profiles p ON u.id = p.user_id
                       WHERE u.id = :id LIMIT 1");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: ../dashboard/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $profile_text = trim($_POST['profile_text'] ?? '');

    if (empty($username) || empty($email)) {
        $error = 'Username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check username/email uniqueness (exclude current user)
        $check = $pdo->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id LIMIT 1");
        $check->execute([':username' => $username, ':email' => $email, ':id' => $_SESSION['user_id']]);
        if ($check->fetch()) {
            $error = 'Username or email already taken by another account.';
        }
    }

    if (empty($error)) {
        // Update users
        $upd = $pdo->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
        $upd->execute([':username' => $username, ':email' => $email, ':id' => $_SESSION['user_id']]);

        // Update or insert profile
        $exists = $pdo->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_id = :id");
        $exists->execute([':id' => $_SESSION['user_id']]);
        if ($exists->fetchColumn() > 0) {
            $up2 = $pdo->prepare("UPDATE user_profiles SET full_name = :full_name, phone = :phone, profile_text = :profile_text WHERE user_id = :id");
            $up2->execute([':full_name' => $full_name, ':phone' => $phone, ':profile_text' => $profile_text, ':id' => $_SESSION['user_id']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO user_profiles (user_id, full_name, phone, profile_text) VALUES (:id, :full_name, :phone, :profile_text)");
            $ins->execute([':id' => $_SESSION['user_id'], ':full_name' => $full_name, ':phone' => $phone, ':profile_text' => $profile_text]);
        }

        // Update session values
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;

        header('Location: view.php?updated=1');
        exit;
    }
}
?>

<div id="content">

    <?php require '../includes/navbar.php'; ?>

    <div id="main-content">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Edit Profile</h4>
                <small class="text-muted">Update your personal information</small>
            </div>
            <a href="view.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">About / Profile</label>
                                <textarea name="profile_text" class="form-control" rows="4"><?php echo htmlspecialchars($user['profile_text']); ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" disabled>
                            </div>

                            <div class="d-grid mt-4">
                                <button class="btn btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <?php require '../includes/footer.php'; ?>

</div>
