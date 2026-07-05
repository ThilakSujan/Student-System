<?php
// ══════════════════════════════════════════════════════
//  ALL PHP logic FIRST — before any HTML output
// ══════════════════════════════════════════════════════
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
include '../config/db_pdo.php';

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS institute_profile (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    institute_name VARCHAR(255),
    address        TEXT,
    phone          VARCHAR(50),
    email          VARCHAR(100),
    principal_name VARCHAR(100),
    logo           VARCHAR(255),
    other_details  TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

// Fetch or create default record
$stmt = $pdo->query("SELECT * FROM institute_profile ORDER BY id ASC LIMIT 1");
$inst = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inst) {
    $pdo->exec("INSERT INTO institute_profile
        (institute_name, address, phone, email, principal_name, logo, other_details)
        VALUES ('', '', '', '', '', '', '')");
    $stmt = $pdo->query("SELECT * FROM institute_profile ORDER BY id ASC LIMIT 1");
    $inst = $stmt->fetch(PDO::FETCH_ASSOC);
}

$error = '';

// ── Handle form submission ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['institute_name']  ?? '');
    $address   = trim($_POST['address']         ?? '');
    $phone     = trim($_POST['phone']           ?? '');
    $email     = trim($_POST['email']           ?? '');
    $principal = trim($_POST['principal_name']  ?? '');
    $other     = trim($_POST['other_details']   ?? '');
    $logoPath  = $inst['logo'];

    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $f       = $_FILES['logo'];
        $ext     = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed, true)) {
            $error = 'Invalid file type. Allowed: jpg, jpeg, png, gif.';
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $error = 'Logo file is too large (max 2MB).';
        } else {
            $uploadDir = __DIR__ . '/../uploads/institute/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

            $newName = 'logo_' . time() . '.' . $ext;
            $dest    = $uploadDir . $newName;

            if (move_uploaded_file($f['tmp_name'], $dest)) {
                // Delete old logo
                if (!empty($logoPath) && file_exists(__DIR__ . '/../' . $logoPath)) {
                    @unlink(__DIR__ . '/../' . $logoPath);
                }
                $logoPath = 'uploads/institute/' . $newName;
            } else {
                $error = 'Failed to upload logo. Check folder permissions.';
            }
        }
    }

    // Save if no errors
    if (empty($error)) {
        $update = $pdo->prepare(
            "UPDATE institute_profile SET
                institute_name = :name,
                address        = :address,
                phone          = :phone,
                email          = :email,
                principal_name = :principal,
                other_details  = :other,
                logo           = :logo
             WHERE id = :id"
        );
        $update->execute([
            ':name'      => $name,
            ':address'   => $address,
            ':phone'     => $phone,
            ':email'     => $email,
            ':principal' => $principal,
            ':other'     => $other,
            ':logo'      => $logoPath,
            ':id'        => $inst['id']
        ]);

        // ✅ Safe to redirect — no HTML sent yet
        header('Location: index.php?updated=1');
        exit();
    }
}

// ══════════════════════════════════════════════════════
//  HTML output starts here — after all logic is done
// ══════════════════════════════════════════════════════
$page_title = "Edit Institute Profile";
require '../includes/header.php';
require '../includes/sidebar.php';
?>

<div id="content">
<?php require '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <!-- Page heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Institute Profile
            </h4>
            <small class="text-muted">Update your institute details and logo</small>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <!-- Error alert -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-1"></i>
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Form card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white fw-semibold">
            <i class="bi bi-building me-1"></i> Institute Details
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4">

                    <!-- Left column — text fields -->
                    <div class="col-md-8">

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:13px">
                                Institute Name
                            </label>
                            <input type="text" name="institute_name" class="form-control"
                                placeholder="e.g. St. Joseph's School"
                                value="<?= htmlspecialchars($inst['institute_name']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:13px">
                                Principal Name
                            </label>
                            <input type="text" name="principal_name" class="form-control"
                                placeholder="e.g. Dr. A. Kumar"
                                value="<?= htmlspecialchars($inst['principal_name']) ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" style="font-size:13px">
                                    Email
                                </label>
                                <input type="email" name="email" class="form-control"
                                    placeholder="info@institute.com"
                                    value="<?= htmlspecialchars($inst['email']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" style="font-size:13px">
                                    Phone
                                </label>
                                <input type="text" name="phone" class="form-control"
                                    placeholder="+91 9876543210"
                                    value="<?= htmlspecialchars($inst['phone']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:13px">
                                Address
                            </label>
                            <textarea name="address" class="form-control" rows="3"
                                placeholder="Full address of the institute"><?= htmlspecialchars($inst['address']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:13px">
                                Other Details
                            </label>
                            <textarea name="other_details" class="form-control" rows="3"
                                placeholder="Any additional information..."><?= htmlspecialchars($inst['other_details']) ?></textarea>
                        </div>

                    </div>

                    <!-- Right column — logo -->
                    <div class="col-md-4">

                        <!-- Current logo preview -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:13px">
                                Current Logo
                            </label>
                            <div style="width:120px;height:120px;border-radius:12px;border:2px dashed #dee2e6;overflow:hidden;background:#f8f9fa;display:flex;align-items:center;justify-content:center">
                                <?php
                                $logo_path = '../' . $inst['logo'];
                                if (!empty($inst['logo']) && file_exists(__DIR__ . '/../' . $inst['logo'])):
                                ?>
                                    <img src="<?= htmlspecialchars($logo_path) ?>"
                                         alt="Logo" style="width:100%;height:100%;object-fit:cover">
                                <?php else: ?>
                                    <div style="text-align:center;color:#adb5bd">
                                        <i class="bi bi-image" style="font-size:32px;display:block"></i>
                                        <span style="font-size:11px">No logo</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Upload new logo -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold" style="font-size:13px">
                                Upload New Logo
                            </label>
                            <input type="file" name="logo" class="form-control form-control-sm"
                                accept=".jpg,.jpeg,.png,.gif">
                            <div class="form-text">Max 2MB. JPG, PNG or GIF.</div>
                        </div>

                        <!-- Preview on file select -->
                        <div id="previewWrap" class="mb-3" style="display:none">
                            <label class="form-label fw-semibold" style="font-size:13px">Preview</label>
                            <div style="width:120px;height:120px;border-radius:12px;border:2px solid #0d6efd;overflow:hidden">
                                <img id="logoPreview" src="" alt="Preview"
                                     style="width:100%;height:100%;object-fit:cover">
                            </div>
                        </div>

                        <!-- Save button -->
                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary fw-semibold">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>

</div><!-- /container-fluid -->
</div><!-- /#main-content -->
<?php require '../includes/footer.php'; ?>
</div><!-- /#content -->

<script>
// Live logo preview
document.querySelector('input[name="logo"]').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('logoPreview').src = e.target.result;
        document.getElementById('previewWrap').style.display = 'block';
    };
    reader.readAsDataURL(file);
});
</script>