<?php
require_once '../includes/auth.php';
require_login();

$page_title  = "My Profile";
$currentPage = 'profile';
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db_pdo.php';
include '../config/db.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS user_profiles (
    user_id INT PRIMARY KEY,
    full_name VARCHAR(255),
    phone VARCHAR(50),
    profile_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$user       = [];
$is_student = $_SESSION['role'] === 'student';

if ($is_student) {
    $student_id = $_SESSION['student_id'] ?? $_SESSION['user_id'];
    $result     = $mysqli->query("SELECT * FROM students WHERE id=$student_id LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $user = [
            'id'           => $student['id'],
            'username'     => $student['student_name'],
            'email'        => $student['email'],
            'phone'        => $student['phone']       ?? '',
            'role'         => 'student',
            'full_name'    => $student['student_name'],
            'profile_text' => '',
            'department'   => $student['department']  ?? '',
            'gender'       => $student['gender']      ?? '',
            'dob'          => $student['dob']         ?? '',
            'status'       => $student['status']      ?? ''
        ];
    } else {
        header('Location: ../dashboard/dashboard.php'); exit;
    }
} else {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.username, u.email, u.role, u.created_at,
                p.full_name, p.phone, p.profile_text
         FROM users u
         LEFT JOIN user_profiles p ON u.id=p.user_id
         WHERE u.id=:id LIMIT 1"
    );
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) { header('Location: ../dashboard/dashboard.php'); exit; }
}
?>

<style>
.profile-header { animation: slideDown 0.5s ease-out; }
@keyframes slideDown {
    from { opacity:0; transform:translateY(-20px); }
    to   { opacity:1; transform:translateY(0); }
}

.profile-card {
    border:none; border-radius:12px;
    box-shadow:0 2px 12px rgba(0,0,0,.08);
    transition:all 0.3s ease;
    animation:fadeInUp 0.6s ease-out;
}
.profile-card:hover {
    box-shadow:0 4px 20px rgba(0,0,0,.12);
    transform:translateY(-2px);
}
@keyframes fadeInUp {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
}

.profile-avatar {
    width:160px; height:160px;
    background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    transition:all 0.3s ease;
}
.profile-avatar:hover { transform:scale(1.05); }
.profile-avatar i { font-size:4rem; color:#fff; }

.profile-name    { font-weight:600; font-size:1.8rem; color:#fff; margin-bottom:0.5rem; }
.profile-subtitle{ font-size:0.95rem; color:#a0aec0; margin-bottom:1.5rem; }

.profile-header-section {
    background:linear-gradient(135deg,#1f2937 0%,#111827 100%);
    border-radius:12px; padding:2rem; margin-bottom:2rem;
}

/* ── Tab nav ── */
.nav-tabs-custom {
    border-bottom:2px solid #e5e7eb;
    margin-bottom:1.5rem;
}
.nav-tabs-custom .nav-link {
    color:#6b7280; border:none;
    border-bottom:3px solid transparent;
    transition:all 0.3s ease;
    padding:0.75rem 1.5rem; font-weight:500;
}
.nav-tabs-custom .nav-link:hover {
    color:#374151;
    border-bottom-color:#d97706;
}
.nav-tabs-custom .nav-link.active {
    color:#d97706;
    border-bottom-color:#d97706;
    background:transparent;
}
.tab-pane { animation:fadeInTab 0.3s ease-out; }
@keyframes fadeInTab {
    from { opacity:0; }
    to   { opacity:1; }
}

/* ── Section title ── */
.section-title {
    font-size:1rem; font-weight:700;
    color:#d97706;                   /* darker amber — readable on white */
    margin-bottom:1rem;
    display:flex; align-items:center; gap:0.5rem;
    padding-bottom:0.5rem;
    border-bottom:1px solid #fde68a;
}

/* ── Info rows ── */
.info-row {
    display:flex; align-items:center;
    padding:0.85rem 0;
    border-bottom:1px solid #f3f4f6;
    transition:all 0.2s ease;
}
.info-row:last-child { border-bottom:none; }
.info-row:hover { background:#fafafa; padding-left:0.5rem; }

.info-label {
    font-weight:600;
    color:#374151;                   /* ← dark grey — clearly visible */
    min-width:160px;
    font-size:0.9rem;
}
.info-value {
    color:#111827;                   /* ← near black — clearly visible */
    flex:1;
    font-size:0.92rem;
    word-break:break-word;
}

/* ── Badges ── */
.badge-custom {
    display:inline-block; padding:0.35rem 0.8rem;
    border-radius:20px; font-weight:600; font-size:0.82rem;
    transition:all 0.2s ease;
}
.badge-custom:hover { transform:scale(1.05); }
.badge-active   { background:#d1fae5; color:#065f46; }
.badge-inactive { background:#fee2e2; color:#991b1b; }
.badge-role     { background:#fef3c7; color:#92400e; }

.edit-btn { transition:all 0.3s ease; }
.edit-btn:hover { transform:translateX(2px); }

@media(max-width:768px){
    #main-content { padding-top:1.25rem; }
    .profile-card { margin-bottom:1rem; }
    .profile-avatar { width:120px; height:120px; margin:0 auto 1rem; }
    .profile-name { font-size:1.4rem; }
    .info-label { min-width:auto; display:block; margin-bottom:0.25rem; }
    .info-row { flex-direction:column; align-items:flex-start; }
    .nav-tabs-custom .nav-link { padding:0.5rem 1rem; font-size:0.9rem; }
}
</style>

<div id="content">
<?php require '../includes/navbar.php'; ?>
<div id="main-content">

    <div class="profile-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-person-badge"></i> My Profile</h4>
            <small class="text-muted">Manage your account details</small>
        </div>
        <a href="edit.php" class="btn btn-warning edit-btn">
            <i class="bi bi-pencil"></i> Edit Profile
        </a>
    </div>

    <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> Profile updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Profile header card -->
    <div class="profile-header-section">
        <div class="row align-items-center g-4">
            <div class="col-md-3 text-center">
                <div class="profile-avatar mx-auto">
                    <i class="bi bi-person-circle"></i>
                </div>
            </div>
            <div class="col-md-9">
                <h2 class="profile-name">
                    <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>
                </h2>
                <p class="profile-subtitle">
                    <i class="bi bi-tag"></i>
                    <?= $is_student ? 'Student' : ucfirst($user['role']) ?>
                    &nbsp;•&nbsp;
                    <strong><?= htmlspecialchars($user['email']) ?></strong>
                </p>
            </div>
        </div>
    </div>

    <!-- Info tabs card -->
    <div class="card profile-card">
        <div class="card-body p-4">

            <?php if ($is_student): ?>
            <!-- ── Student tabs ── -->
            <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#personal" type="button"><i class="bi bi-person-lines-fill me-1"></i>Personal</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#contact"  type="button"><i class="bi bi-telephone-fill me-1"></i>Contact</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#academic" type="button"><i class="bi bi-book-fill me-1"></i>Academic</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="personal">
                    <div class="section-title"><i class="bi bi-person"></i> Personal Information</div>
                    <?php
                    $rows = [
                        ['Full Name',    $user['full_name'] ?: $user['username']],
                        ['Gender',       $user['gender'] ?: '—'],
                        ['Date of Birth',$user['dob'] ? date('d M Y',strtotime($user['dob'])) : '—'],
                    ];
                    foreach ($rows as [$lbl,$val]):
                    ?>
                    <div class="info-row">
                        <span class="info-label"><?= $lbl ?></span>
                        <span class="info-value"><?= htmlspecialchars($val) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="badge-custom <?= $user['status']==='Active'?'badge-active':'badge-inactive' ?>">
                                <i class="bi <?= $user['status']==='Active'?'bi-check-circle':'bi-x-circle' ?> me-1"></i>
                                <?= htmlspecialchars($user['status'] ?: 'Unknown') ?>
                            </span>
                        </span>
                    </div>
                </div>

                <div class="tab-pane fade" id="contact">
                    <div class="section-title"><i class="bi bi-telephone"></i> Contact Information</div>
                    <div class="info-row">
                        <span class="info-label">Email Address</span>
                        <span class="info-value"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone Number</span>
                        <span class="info-value"><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($user['phone'] ?: '—') ?></span>
                    </div>
                </div>

                <div class="tab-pane fade" id="academic">
                    <div class="section-title"><i class="bi bi-book"></i> Academic Information</div>
                    <div class="info-row">
                        <span class="info-label">Department</span>
                        <span class="info-value"><?= htmlspecialchars($user['department'] ?: '—') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Username</span>
                        <span class="info-value"><strong><?= htmlspecialchars($user['username']) ?></strong></span>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- ── Admin/Staff tabs ── -->
            <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#account" type="button"><i class="bi bi-person-badge me-1"></i>Account</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#contact2" type="button"><i class="bi bi-telephone-fill me-1"></i>Contact</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#about" type="button"><i class="bi bi-info-circle-fill me-1"></i>About</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="account">
                    <div class="section-title"><i class="bi bi-lock"></i> Account Information</div>
                    <div class="info-row">
                        <span class="info-label">Username</span>
                        <span class="info-value"><strong><?= htmlspecialchars($user['username']) ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Role</span>
                        <span class="info-value">
                            <span class="badge-custom badge-role"><?= htmlspecialchars(ucfirst($user['role'])) ?></span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Member Since</span>
                        <span class="info-value">
                            <i class="bi bi-calendar-event me-1 text-muted"></i>
                            <?= date('d M Y', strtotime($user['created_at'])) ?>
                        </span>
                    </div>
                </div>

                <div class="tab-pane fade" id="contact2">
                    <div class="section-title"><i class="bi bi-telephone"></i> Contact Information</div>
                    <div class="info-row">
                        <span class="info-label">Email Address</span>
                        <span class="info-value"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone Number</span>
                        <span class="info-value"><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($user['phone'] ?: '—') ?></span>
                    </div>
                </div>

                <div class="tab-pane fade" id="about">
                    <div class="section-title"><i class="bi bi-chat-left-quote"></i> About You</div>
                    <?php if (!empty($user['profile_text'])): ?>
                        <p style="color:#374151;line-height:1.6">
                            <?= nl2br(htmlspecialchars($user['profile_text'])) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">
                            <i class="bi bi-chat-left-text d-block fs-3 mb-2"></i>
                            No additional information added yet.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div><!-- /#main-content -->
<?php require '../includes/footer.php'; ?>
</div><!-- /#content -->