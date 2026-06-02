<?php
require_once '../includes/auth.php';
require_role(['admin']);

$page_title  = "Institute Profile";
$currentPage = 'institute';
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db_pdo.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS institute_profile (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institute_name VARCHAR(255),
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(100),
    principal_name VARCHAR(100),
    logo VARCHAR(255),
    other_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$stmt = $pdo->query("SELECT * FROM institute_profile ORDER BY id ASC LIMIT 1");
$inst = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inst) {
    $pdo->prepare("INSERT INTO institute_profile (institute_name,address,phone,email,principal_name,logo,other_details) VALUES ('','','','','','','')")->execute();
    $stmt = $pdo->query("SELECT * FROM institute_profile ORDER BY id ASC LIMIT 1");
    $inst = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<style>
.profile-header { animation: slideDown 0.5s ease-out; }
@keyframes slideDown {
    from { opacity:0; transform:translateY(-20px); }
    to   { opacity:1; transform:translateY(0); }
}

.profile-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    transition: all 0.3s ease;
    animation: fadeInUp 0.6s ease-out;
}
.profile-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,.12);
    transform: translateY(-2px);
}
@keyframes fadeInUp {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
}

.institute-logo {
    width:180px; height:180px;
    background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    transition: all 0.3s ease;
    overflow:hidden;
}
.institute-logo:hover { transform:scale(1.05); }
.institute-logo i   { font-size:5rem; color:#fff; }
.institute-logo img { width:100%; height:100%; object-fit:cover; }

.institute-name {
    font-weight:600; font-size:2rem;
    color:#fff; margin-bottom:0.5rem;
}

.institute-header-section {
    background: linear-gradient(135deg,#1f2937 0%,#111827 100%);
    border-radius:12px; padding:2.5rem; margin-bottom:2rem;
}

.institute-info {
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(250px,1fr));
    gap:1.5rem; margin-top:1.5rem;
}

/* ── Fixed: bright, readable badge colours ── */
.info-badge {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}
.info-badge:hover {
    background: #fef3c7;
    border-color: #f59e0b;
    transform: translateY(-2px);
}
.info-badge-icon {
    font-size: 1.5rem;
    color: #d97706;
    flex-shrink: 0;
    margin-top: 0.25rem;
}
.info-badge-content { flex:1; }
.info-badge-label {
    font-size: 0.8rem;
    color: #92400e;              /* ← dark amber — clearly visible */
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.3rem;
    font-weight: 700;
}
.info-badge-value {
    color: #1f2937;              /* ← dark grey — clearly visible */
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.4;
    word-break: break-word;
}

.other-details-card {
    border: none;
    border-radius: 12px;
    background: rgba(31,41,55,0.5);
    border: 1px solid rgba(251,191,36,0.2);
    transition: all 0.3s ease;
    margin-top: 2rem;
}
.other-details-card:hover { border-color: rgba(251,191,36,0.4); }

.details-title {
    font-size:1.1rem; font-weight:600; color:#fbbf24;
    margin-bottom:1rem;
    display:flex; align-items:center; gap:0.5rem;
}
.details-text { color:#cbd5e0; line-height:1.6; font-size:0.95rem; }

.edit-btn { transition: all 0.3s ease; }
.edit-btn:hover { transform: translateX(2px); }

.institute-status-bar { display:flex; gap:0.5rem; margin-top:1rem; flex-wrap:wrap; }
.status-indicator {
    display:flex; align-items:center; gap:0.5rem;
    padding:0.4rem 0.8rem;
    background:rgba(52,211,153,0.2); border-radius:20px;
    color:#86efac; font-size:0.85rem; font-weight:500;
}

@media(max-width:768px){
    #main-content { padding-top:1.25rem; }
    .institute-header-section { padding:1.5rem; }
    .institute-logo { width:140px; height:140px; margin:0 auto 1rem; }
    .institute-logo i { font-size:3.5rem; }
    .institute-name { font-size:1.5rem; }
    .institute-info { grid-template-columns:1fr; gap:1rem; }
    .profile-card { margin-bottom:1rem; }
}
</style>

<div id="content">
<?php require '../includes/navbar.php'; ?>
<div id="main-content">

    <div class="profile-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-building"></i> Institute Profile</h4>
            <small class="text-muted">Details about your school / institute</small>
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

    <!-- Header section -->
    <div class="institute-header-section">
        <div class="row align-items-center g-4">
            <div class="col-md-3 text-center">
                <div class="institute-logo">
                    <?php if (!empty($inst['logo']) && file_exists(__DIR__.'/../'.$inst['logo'])): ?>
                        <img src="../<?= htmlspecialchars($inst['logo']) ?>" alt="Institute Logo">
                    <?php else: ?>
                        <i class="bi bi-building"></i>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-9">
                <h1 class="institute-name">
                    <?= htmlspecialchars($inst['institute_name'] ?: '— Institute Name Not Set —') ?>
                </h1>
                <p class="text-muted mb-3">
                    <i class="bi bi-geo-alt-fill"></i>
                    <?= nl2br(htmlspecialchars($inst['address'] ?: 'No address provided')) ?>
                </p>
                <div class="institute-status-bar">
                    <span class="status-indicator">
                        <i class="bi bi-check-circle"></i> Active Institute
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Info cards -->
    <div class="card profile-card">
        <div class="card-body">
            <h5 class="mb-3" style="color:#d97706">
                <i class="bi bi-info-circle"></i> Institute Information
            </h5>
            <div class="institute-info">

                <div class="info-badge">
                    <div class="info-badge-icon"><i class="bi bi-person-badge"></i></div>
                    <div class="info-badge-content">
                        <div class="info-badge-label">Principal Name</div>
                        <div class="info-badge-value">
                            <?= htmlspecialchars($inst['principal_name'] ?: '—') ?>
                        </div>
                    </div>
                </div>

                <div class="info-badge">
                    <div class="info-badge-icon"><i class="bi bi-envelope"></i></div>
                    <div class="info-badge-content">
                        <div class="info-badge-label">Email Address</div>
                        <div class="info-badge-value">
                            <?= htmlspecialchars($inst['email'] ?: '—') ?>
                        </div>
                    </div>
                </div>

                <div class="info-badge">
                    <div class="info-badge-icon"><i class="bi bi-telephone"></i></div>
                    <div class="info-badge-content">
                        <div class="info-badge-label">Phone Number</div>
                        <div class="info-badge-value">
                            <?= htmlspecialchars($inst['phone'] ?: '—') ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Other details -->
    <?php if (!empty($inst['other_details'])): ?>
    <div class="other-details-card p-4">
        <div class="details-title">
            <i class="bi bi-card-text"></i> Additional Details
        </div>
        <div class="details-text">
            <?= nl2br(htmlspecialchars($inst['other_details'])) ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /#main-content -->
<?php require '../includes/footer.php'; ?>
</div><!-- /#content -->