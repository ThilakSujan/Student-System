<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';

$page_title = "Add Class";

$error   = '';
$success = '';

// Fetch staff users for teacher dropdown
$staff_res = $mysqli->query("SELECT id, username, email FROM users WHERE role = 'staff' ORDER BY username ASC");
$staff_list = $staff_res ? $staff_res->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_class'])) {
    $class_name       = trim($_POST['class_name']      ?? '');
    $section          = trim($_POST['section']          ?? '');
    $academic_year    = trim($_POST['academic_year']    ?? '');
    $class_teacher_id = intval($_POST['class_teacher_id'] ?? 0);
    $description      = trim($_POST['description']     ?? '');
    $status           = $_POST['status'] === 'Inactive' ? 'Inactive' : 'Active';

    if (empty($class_name)) {
        $error = "Class name is required.";
    } else {
        // Check duplicate class name + section + year
        $check = $mysqli->prepare("SELECT id FROM classes WHERE class_name = ? AND section = ? AND academic_year = ?");
        $check->bind_param('sss', $class_name, $section, $academic_year);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "A class with this name, section, and academic year already exists.";
        } else {
            $teacher_val = $class_teacher_id > 0 ? $class_teacher_id : null;
            $stmt = $mysqli->prepare(
                "INSERT INTO classes (class_name, section, academic_year, class_teacher_id, description, status)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('sssiss', $class_name, $section, $academic_year, $teacher_val, $description, $status);

            if ($stmt->execute()) {
                $success = "Class \"$class_name\" created successfully!";
                // Reset form
                $class_name = $section = $academic_year = $description = '';
                $class_teacher_id = 0;
                $status = 'Active';
            } else {
                $error = "Failed to create class. Please try again.";
            }
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <!-- Heading -->
    <div class="mb-4">
        <h4 class="fw-bold mb-0"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Class</h4>
        <small class="text-muted">Create a new class and assign a class teacher</small>
    </div>

    <div class="row">
        <!-- Form -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white" style="background:linear-gradient(100deg,#4f46e5,#7c3aed)">
                    <h5 class="mb-0"><i class="bi bi-building me-2"></i>Class Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="add.php" method="POST" novalidate>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Class Name <span class="text-danger">*</span></label>
                                <input type="text" name="class_name" class="form-control" required
                                       value="<?= htmlspecialchars($class_name ?? '') ?>"
                                       placeholder="e.g. Grade 10, Class XI Science">
                                <small class="text-muted">A unique descriptive name for this class</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Section</label>
                                <input type="text" name="section" class="form-control"
                                       value="<?= htmlspecialchars($section ?? '') ?>"
                                       placeholder="e.g. A, B, C">
                                <small class="text-muted">Optional section identifier</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Academic Year</label>
                                <input type="text" name="academic_year" class="form-control"
                                       value="<?= htmlspecialchars($academic_year ?? '') ?>"
                                       placeholder="e.g. 2025-2026">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Active" <?= (($status ?? 'Active') === 'Active') ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= (($status ?? '') === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- Class Teacher -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Class Teacher</label>
                            <select name="class_teacher_id" class="form-select">
                                <option value="0">— No Teacher Assigned —</option>
                                <?php foreach ($staff_list as $staff): ?>
                                    <option value="<?= $staff['id'] ?>"
                                        <?= (($class_teacher_id ?? 0) == $staff['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($staff['username']) ?>
                                        (<?= htmlspecialchars($staff['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                <?php if (empty($staff_list)): ?>
                                    <span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>No staff members found.
                                    <a href="../staff/staff_add.php">Add a staff member first.</a></span>
                                <?php else: ?>
                                    Select a staff member to assign as class teacher
                                <?php endif; ?>
                            </small>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Optional notes about this class..."><?= htmlspecialchars($description ?? '') ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="save_class" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Save Class
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- Info panel -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Field Guide</h6>
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Class Name</strong> — Required. Must be unique per section &amp; year.</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Section</strong> — Optional label (A, B, C...).</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Academic Year</strong> — e.g. 2025–2026.</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Class Teacher</strong> — Assign from staff list. Can be changed later.</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Status</strong> — Inactive hides the class from reports.</li>
                    </ul>
                </div>
            </div>

            <?php if (!empty($staff_list)): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-people-fill text-primary me-2"></i>Available Staff (<?= count($staff_list) ?>)</h6>
                    <div style="max-height:200px;overflow-y:auto;">
                        <?php foreach ($staff_list as $s): ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                                <?= strtoupper(substr($s['username'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-size:13px;font-weight:600"><?= htmlspecialchars($s['username']) ?></div>
                                <div style="font-size:11px;color:#94a3b8"><?= htmlspecialchars($s['email']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<?php
if (!empty($success)) echo "<script>window._toastMsg=" . json_encode($success) . ";window._toastType='success';</script>";
if (!empty($error))   echo "<script>window._toastMsg=" . json_encode($error)   . ";window._toastType='danger';</script>";
?>
<?php include '../includes/footer.php'; ?>
</div>
