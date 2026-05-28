<?php
require_once '../includes/auth.php';
require_login();

$page_title = "My Profile";
$currentPage = 'profile';
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db_pdo.php';
include '../config/db.php';

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

// Initialize user data
$user = [];
$is_student = $_SESSION['role'] === 'student';

if ($is_student) {
    // For students, fetch from students table
    $student_id = $_SESSION['student_id'] ?? $_SESSION['user_id'];
    $result = $mysqli->query("SELECT * FROM students WHERE id = $student_id LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $user = [
            'id' => $student['id'],
            'username' => $student['student_name'],
            'email' => $student['email'],
            'phone' => $student['phone'] ?? '',
            'role' => 'student',
            'full_name' => $student['student_name'],
            'profile_text' => '',
            'department' => $student['department'] ?? '',
            'gender' => $student['gender'] ?? '',
            'dob' => $student['dob'] ?? '',
            'status' => $student['status'] ?? ''
        ];
    } else {
        header('Location: ../dashboard/dashboard.php');
        exit;
    }
} else {
    // For admin/staff, fetch from users and user_profiles tables
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.role, u.created_at, p.full_name, p.phone, p.profile_text
                           FROM users u
                           LEFT JOIN user_profiles p ON u.id = p.user_id
                           WHERE u.id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: ../dashboard/dashboard.php');
        exit;
    }
}
?>

<div id="content">

    <?php require '../includes/navbar.php'; ?>

    <div id="main-content">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">My Profile</h4>
                <small class="text-muted">Manage your account details</small>
            </div>
            <a href="edit.php" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> Profile updated successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="border rounded d-flex align-items-center justify-content-center" style="height:140px;">
                            <i class="bi bi-person-circle fs-1 text-muted"></i>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h3><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h3>
                        
                        <?php if ($is_student): ?>
                            <!-- Student Profile Details -->
                            <p class="text-muted mb-1"><strong>Student Name:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: '—'); ?></p>
                            <p class="mb-1"><strong>Department:</strong> <?php echo htmlspecialchars($user['department'] ?: '—'); ?></p>
                            <p class="mb-1"><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender'] ?: '—'); ?></p>
                            <p class="mb-1"><strong>Date of Birth:</strong> <?php echo htmlspecialchars($user['dob'] ? date('d M Y', strtotime($user['dob'])) : '—'); ?></p>
                            <p class="mb-1">
                                <strong>Status:</strong> 
                                <span class="badge <?php echo $user['status'] === 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo htmlspecialchars($user['status'] ?: 'Unknown'); ?>
                                </span>
                            </p>
                        <?php else: ?>
                            <!-- Admin/Staff Profile Details -->
                            <p class="text-muted mb-1"><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: '—'); ?></p>
                            <p class="mb-1"><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
                            <?php if (!empty($user['profile_text'])): ?>
                                <div class="mt-3">
                                    <h6>About</h6>
                                    <p class="small text-muted mb-0"><?php echo nl2br(htmlspecialchars($user['profile_text'])); ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php require '../includes/footer.php'; ?>

</div>
