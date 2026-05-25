<?php
require_once '../includes/auth.php';
require_role(['admin']);

$page_title = "Edit Institute Profile";
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

// Fetch existing record
$stmt = $pdo->query("SELECT * FROM institute_profile ORDER BY id ASC LIMIT 1");
$inst = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inst) {
    $insert = $pdo->prepare("INSERT INTO institute_profile (institute_name, address, phone, email, principal_name, logo, other_details) VALUES ('', '', '', '', '', '', '')");
    $insert->execute();
    $stmt = $pdo->query("SELECT * FROM institute_profile ORDER BY id ASC LIMIT 1");
    $inst = $stmt->fetch(PDO::FETCH_ASSOC);
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['institute_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $principal = trim($_POST['principal_name'] ?? '');
    $other = trim($_POST['other_details'] ?? '');

    $logoPath = $inst['logo'];

    // Handle logo upload if provided
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['logo'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (!in_array($ext, $allowed, true)) {
            $error = 'Invalid logo file type. Allowed: jpg, jpeg, png, gif.';
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $error = 'Logo file is too large (max 2MB).';
        } else {
            $uploadDir = __DIR__ . '/../uploads/institute/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newName = 'logo_' . time() . '.' . $ext;
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($f['tmp_name'], $dest)) {
                // remove old logo file if exists
                if (!empty($logoPath) && file_exists(__DIR__ . '/../' . $logoPath)) {
                    @unlink(__DIR__ . '/../' . $logoPath);
                }
                $logoPath = 'uploads/institute/' . $newName;
            } else {
                $error = 'Failed to move uploaded logo file.';
            }
        }
    }

    if (empty($error)) {
        $update = $pdo->prepare("UPDATE institute_profile SET institute_name = :name, address = :address, phone = :phone, email = :email, principal_name = :principal, other_details = :other, logo = :logo WHERE id = :id");
        $update->execute([
            ':name' => $name,
            ':address' => $address,
            ':phone' => $phone,
            ':email' => $email,
            ':principal' => $principal,
            ':other' => $other,
            ':logo' => $logoPath,
            ':id' => $inst['id']
        ]);

        header('Location: index.php?updated=1');
        exit;
    }
}

?>

<div id="content">

    <?php require '../includes/navbar.php'; ?>

    <div id="main-content">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Edit Institute Profile</h4>
                <small class="text-muted">Update institute details and logo</small>
            </div>
            <a href="index.php" class="btn btn-secondary">
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
                <form method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Institute Name</label>
                                <input type="text" name="institute_name" class="form-control" value="<?php echo htmlspecialchars($inst['institute_name']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Principal Name</label>
                                <input type="text" name="principal_name" class="form-control" value="<?php echo htmlspecialchars($inst['principal_name']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($inst['email']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($inst['phone']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($inst['address']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Other Details</label>
                                <textarea name="other_details" class="form-control" rows="4"><?php echo htmlspecialchars($inst['other_details']); ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Logo (optional, max 2MB)</label>
                                <input type="file" name="logo" class="form-control">
                            </div>

                            <?php if (!empty($inst['logo']) && file_exists(__DIR__ . '/../' . $inst['logo'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current Logo</label>
                                    <div>
                                        <img src="../<?php echo htmlspecialchars($inst['logo']); ?>" alt="Logo" class="img-fluid border rounded">
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="d-grid">
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
