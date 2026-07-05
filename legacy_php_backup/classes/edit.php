<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';

$page_title = "Edit Class";
$error = $success = '';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

// Fetch existing class
$res = $mysqli->prepare("SELECT * FROM classes WHERE id = ?");
$res->bind_param('i', $id);
$res->execute();
$cls = $res->get_result()->fetch_assoc();
if (!$cls) { header('Location: index.php'); exit; }

// Fetch staff for teacher dropdown
$staff_res  = $mysqli->query("SELECT id, username, email FROM users WHERE role = 'staff' ORDER BY username ASC");
$staff_list = $staff_res ? $staff_res->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_class'])) {
    $class_name       = trim($_POST['class_name']        ?? '');
    $section          = trim($_POST['section']            ?? '');
    $academic_year    = trim($_POST['academic_year']      ?? '');
    $class_teacher_id = intval($_POST['class_teacher_id'] ?? 0);
    $description      = trim($_POST['description']        ?? '');
    $status           = ($_POST['status'] ?? '') === 'Inactive' ? 'Inactive' : 'Active';

    if (empty($class_name)) {
        $error = "Class name is required.";
    } else {
        // Check duplicate (exclude self)
        $check = $mysqli->prepare(
            "SELECT id FROM classes WHERE class_name = ? AND section = ? AND academic_year = ? AND id != ?"
        );
        $check->bind_param('sssi', $class_name, $section, $academic_year, $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Another class with this name, section, and academic year already exists.";
        } else {
            $teacher_val = $class_teacher_id > 0 ? $class_teacher_id : null;
            $stmt = $mysqli->prepare(
                "UPDATE classes SET class_name=?, section=?, academic_year=?, class_teacher_id=?,
                 description=?, status=?, updated_at=NOW() WHERE id=?"
            );
            $stmt->bind_param('sssissi', $class_name, $section, $academic_year, $teacher_val, $description, $status, $id);

            if ($stmt->execute()) {
                $success = "Class \"$class_name\" updated successfully!";
                // Refresh local data
                $cls = array_merge($cls, compact('class_name','section','academic_year','class_teacher_id','description','status'));
            } else {
                $error = "Failed to update class.";
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
        <h4 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Class</h4>
        <small class="text-muted">Update class details or reassign the class teacher</small>
    </div>

    <div class="row">
        <!-- Form -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white" style="background:linear-gradient(100deg,#f59e0b,#d97706)">
                    <h5 class="mb-0">
                        <i class="bi bi-building me-2"></i>
                        Editing: <?= htmlspecialchars($cls['class_name']) ?>
                        <?php if ($cls['section']): ?><span class="badge bg-light text-dark ms-2"><?= htmlspecialchars($cls['section']) ?></span><?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="edit.php?id=<?= $id ?>" method="POST" novalidate>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Class Name <span class="text-danger">*</span></label>
                                <input type="text" name="class_name" class="form-control" required
                                       value="<?= htmlspecialchars($cls['class_name']) ?>"
                                       placeholder="e.g. Grade 10, Class XI Science">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Section</label>
                                <input type="text" name="section" class="form-control"
                                       value="<?= htmlspecialchars($cls['section']) ?>"
                                       placeholder="e.g. A, B, C">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Academic Year</label>
                                <input type="text" name="academic_year" class="form-control"
                                       value="<?= htmlspecialchars($cls['academic_year']) ?>"
                                       placeholder="e.g. 2025-2026">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Active"   <?= $cls['status'] === 'Active'   ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $cls['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
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
                                        <?= ($cls['class_teacher_id'] == $staff['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($staff['username']) ?> (<?= htmlspecialchars($staff['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($staff_list)): ?>
                                <small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>No staff found. <a href="../staff/staff_add.php">Add staff first.</a></small>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Optional notes..."><?= htmlspecialchars($cls['description']) ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="save_class" class="btn btn-warning">
                                <i class="bi bi-save me-1"></i>Update Class
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Back to List
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Meta panel -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-primary me-2"></i>Record Info</h6>
                    <table class="table table-sm table-borderless mb-0" style="font-size:13px">
                        <tr>
                            <td class="text-muted">ID</td>
                            <td><strong>#<?= $cls['id'] ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Created</td>
                            <td><?= date('d M Y, h:i A', strtotime($cls['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Last Updated</td>
                            <td><?= date('d M Y, h:i A', strtotime($cls['updated_at'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                <span class="badge bg-<?= $cls['status'] === 'Active' ? 'success' : 'danger' ?>">
                                    <?= $cls['status'] ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php
            // Current teacher info
            if ($cls['class_teacher_id']) {
                $tr = $mysqli->prepare("SELECT username, email FROM users WHERE id = ?");
                $tr->bind_param('i', $cls['class_teacher_id']);
                $tr->execute();
                $teacher = $tr->get_result()->fetch_assoc();
            }
            ?>
            <?php if (!empty($teacher)): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-person-check text-success me-2"></i>Current Teacher</h6>
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;">
                            <?= strtoupper(substr($teacher['username'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($teacher['username']) ?></div>
                            <div class="text-muted" style="font-size:12px"><?= htmlspecialchars($teacher['email']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card shadow-sm border-0 border-warning">
                <div class="card-body text-center text-warning">
                    <i class="bi bi-exclamation-triangle fs-4 d-block mb-2"></i>
                    <small>No teacher currently assigned to this class.</small>
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
