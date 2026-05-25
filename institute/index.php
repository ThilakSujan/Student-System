<?php
require_once '../includes/auth.php';
require_role(['admin']);

$page_title = "Institute Profile";
$currentPage = 'institute';
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db_pdo.php';

// Ensure table exists
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

// Fetch single institute record (only one maintained)
$stmt = $pdo->query("SELECT * FROM institute_profile ORDER BY id ASC LIMIT 1");
$inst = $stmt->fetch(PDO::FETCH_ASSOC);

// If no record exists, insert an empty default record
if (!$inst) {
    $insert = $pdo->prepare("INSERT INTO institute_profile (institute_name, address, phone, email, principal_name, logo, other_details) VALUES ('', '', '', '', '', '', '')");
    $insert->execute();
    $stmt = $pdo->query("SELECT * FROM institute_profile ORDER BY id ASC LIMIT 1");
    $inst = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<div id="content">

    <?php require '../includes/navbar.php'; ?>

    <div id="main-content">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Institute Profile</h4>
                <small class="text-muted">Details about your school / institute</small>
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
                        <?php if (!empty($inst['logo']) && file_exists(__DIR__ . '/../' . $inst['logo'])): ?>
                            <img src="../<?php echo htmlspecialchars($inst['logo']); ?>" alt="Logo" class="img-fluid rounded" style="max-height:160px;">
                        <?php else: ?>
                            <div class="border rounded d-flex align-items-center justify-content-center" style="height:160px;">
                                <i class="bi bi-building fs-1 text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-9">
                        <h3><?php echo htmlspecialchars($inst['institute_name'] ?: '— Institute Name Not Set —'); ?></h3>
                        <p class="text-muted mb-1"><strong>Principal:</strong> <?php echo htmlspecialchars($inst['principal_name'] ?: '—'); ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($inst['email'] ?: '—'); ?></p>
                        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($inst['phone'] ?: '—'); ?></p>
                        <p class="mb-2"><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($inst['address'] ?: '—')); ?></p>
                        <?php if (!empty($inst['other_details'])): ?>
                            <div class="mt-3">
                                <h6>Other Details</h6>
                                <p class="small text-muted mb-0"><?php echo nl2br(htmlspecialchars($inst['other_details'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php require '../includes/footer.php'; ?>

</div>

