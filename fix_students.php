<?php
$file = 'c:\xampp\htdocs\student_system\students\students.php';
$content = <<<'EOD'
<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin', 'staff']);
$page_title = "View Students";
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db.php';

$success = "";
$error   = "";

// Handle individual delete
if (isset($_GET['delete'])) {
    $id    = (int) $_GET['delete'];
    $query = "DELETE FROM students WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $success = "Student deleted successfully.";
    } else {
        $error = "Failed to delete student.";
    }
}

$from_date  = $_GET['from_date'] ?? '';
$to_date    = $_GET['to_date'] ?? '';
$status     = $_GET['status'] ?? '';
$department = $_GET['department'] ?? '';
$gender     = $_GET['gender'] ?? '';

$where = ["1=1"];
if ($from_date)  $where[] = "dob >= '" . mysqli_real_escape_string($conn, $from_date) . "'";
if ($to_date)    $where[] = "dob <= '" . mysqli_real_escape_string($conn, $to_date) . "'";
if ($status)     $where[] = "status = '" . mysqli_real_escape_string($conn, $status) . "'";
if ($department) $where[] = "department = '" . mysqli_real_escape_string($conn, $department) . "'";
if ($gender)     $where[] = "gender = '" . mysqli_real_escape_string($conn, $gender) . "'";

$query = "SELECT * FROM students WHERE " . implode(' AND ', $where) . " ORDER BY student_name ASC";
$result = mysqli_query($conn, $query);

$deps_query = mysqli_query($conn, "SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != ''");
$deps = [];
while ($d = mysqli_fetch_assoc($deps_query)) $deps[] = $d['department'];

$genders_query = mysqli_query($conn, "SELECT DISTINCT gender FROM students WHERE gender IS NOT NULL AND gender != ''");
$genders = [];
while ($g = mysqli_fetch_assoc($genders_query)) $genders[] = $g['gender'];

$students = [];
$active = $inactive = $male = $female = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
    if ($row['status'] === 'Active') $active++;
    else $inactive++;
    if (strtolower($row['gender']) === 'male') $male++;
    elseif (strtolower($row['gender']) === 'female') $female++;
}
$total_students = count($students);
?>

<div id="content">
<?php require '../includes/navbar.php'; ?>
<div id="main-content">

    <!-- Page heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Students Management</h4>
            <small class="text-muted">Manage and organize all student records</small>
        </div>
        <a href="index.php" class="btn btn-success btn-sm">
            <i class="bi bi-person-plus"></i> Add New Student
        </a>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Advanced Report Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light fw-bold">
            <i class="bi bi-funnel"></i> Report Filters
        </div>
        <div class="card-body">
            <form method="GET" action="students.php">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">From DOB</label>
                        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To DOB</label>
                        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select">
                            <option value="">All</option>
                            <?php foreach($deps as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $department === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">All</option>
                            <?php foreach($genders as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>" <?= $gender === $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                    <a href="students.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center text-bg-primary shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Total Students</h6>
                    <h3 class="mb-0"><?= $total_students ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-bg-success shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Active</h6>
                    <h3 class="mb-0"><?= $active ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-bg-secondary shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Inactive</h6>
                    <h3 class="mb-0"><?= $inactive ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-bg-info text-white shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Gender (M/F)</h6>
                    <h3 class="mb-0"><?= $male ?> / <?= $female ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-people-fill"></i> Students List (<?= $total_students ?>)</strong>
            <?php if (is_admin()): ?>
            <div class="d-flex gap-2">
                <button onclick="exportTable('#studentsTable', 'Students Report', 'excel')" class="btn btn-success btn-sm" title="Export to Excel">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
                <button onclick="exportTable('#studentsTable', 'Students Report', 'pdf')" class="btn btn-danger btn-sm" title="Export to PDF">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="card-body p-0">
            <?php if ($total_students == 0): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted mb-2">No student records found.</p>
                    <a href="index.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Add First Student
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="studentsTable" class="table table-bordered table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($students as $row): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="student-checkbox form-check-input"
                                           value="<?= $row['id'] ?>">
                                </td>
                                <td><?= $row['id'] ?></td>
                                <td><strong><?= htmlspecialchars($row['student_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td>
                                    <!-- Edit -->
                                    <a href="edit.php?id=<?= $row['id'] ?>"
                                       class="btn btn-warning btn-sm" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <!-- Report Card — opens in same page -->
                                    <a href="report_card.php?student_id=<?= $row['id'] ?>"
                                       class="btn btn-info btn-sm" title="View Report Card">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </a>

                                    <!-- Send Mail (admin & staff only) -->
                                    <button type="button" class="btn btn-primary btn-sm" title="Send Email"
                                            onclick="openSendMail(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['student_name'])) ?>', '<?= htmlspecialchars(addslashes($row['email'])) ?>')">
                                        <i class="bi bi-envelope"></i>
                                    </button>

                                    <!-- Delete -->
                                    <a href="students.php?delete=<?= $row['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete this student permanently?')"
                                       title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /#main-content -->

<script>
$(document).ready(function () {
    $('#studentsTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        columnDefs: [
            { orderable: false, targets: 0 },
            { orderable: false, targets: 6 }
        ],
        order: [[1, 'asc']]
    });
});

// Select all checkboxes
$('#selectAll').on('change', function () {
    $('.student-checkbox').prop('checked', this.checked);
});

// Deselect "select all" if any individual is unchecked
$(document).on('change', '.student-checkbox', function () {
    if (!this.checked) $('#selectAll').prop('checked', false);
    if ($('.student-checkbox:checked').length === $('.student-checkbox').length) {
        $('#selectAll').prop('checked', true);
    }
});
</script>

<?php require '../includes/footer.php'; ?>
</div><!-- /#content -->

<!-- ══ Send Mail Modal ══════════════════════════════════════════════ -->
<div class="modal fade" id="sendMailModal" tabindex="-1" aria-labelledby="sendMailModalLabel">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;">
        <h5 class="modal-title" id="sendMailModalLabel">
          <i class="bi bi-envelope-paper me-2"></i>Send Email
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="mailAlertArea"></div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Student</label>
          <input type="text" id="mailStudentName" class="form-control bg-light" readonly>
          <input type="hidden" id="mailStudentId">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Recipient Email <span class="text-danger">*</span></label>
          <input type="email" id="mailRecipient" class="form-control" placeholder="student@example.com" required>
          <small class="text-muted">Pre-filled with student email. You may change to parent email if needed.</small>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
          <input type="text" id="mailSubject" class="form-control" placeholder="Enter email subject..." required maxlength="500">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
          <textarea id="mailMessage" class="form-control" rows="7" placeholder="Type your message here..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn-primary btn" id="sendMailBtn" onclick="submitSendMail()">
          <i class="bi bi-send me-1"></i>Send Email
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function openSendMail(studentId, studentName, studentEmail) {
    document.getElementById('mailStudentId').value    = studentId;
    document.getElementById('mailStudentName').value  = studentName;
    document.getElementById('mailRecipient').value    = studentEmail;
    document.getElementById('mailSubject').value      = '';
    document.getElementById('mailMessage').value      = '';
    document.getElementById('mailAlertArea').innerHTML= '';
    new bootstrap.Modal(document.getElementById('sendMailModal')).show();
}

function submitSendMail() {
    const recipient = document.getElementById('mailRecipient').value.trim();
    const subject   = document.getElementById('mailSubject').value.trim();
    const message   = document.getElementById('mailMessage').value.trim();
    const studentId = document.getElementById('mailStudentId').value;

    if (!recipient || !subject || !message) {
        showMailAlert('Please fill in all required fields.', 'warning');
        return;
    }

    const btn = document.getElementById('sendMailBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending…';

    const formData = new FormData();
    formData.append('student_id',     studentId);
    formData.append('recipient_email', recipient);
    formData.append('subject',         subject);
    formData.append('message',         message);

    fetch('send_mail.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showMailAlert(d.message, 'success');
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('sendMailModal')).hide();
                    if (window.showToast) window.showToast(d.message, 'success');
                }, 1200);
            } else {
                showMailAlert(d.message || 'Failed to send email.', 'danger');
            }
        })
        .catch(() => showMailAlert('Unexpected error. Please try again.', 'danger'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i>Send Email';
        });
}

function showMailAlert(msg, type) {
    document.getElementById('mailAlertArea').innerHTML =
        `<div class="alert alert-${type} alert-dismissible fade show">
            <i class="bi bi-${type==='success'?'check-circle':'exclamation-circle'} me-1"></i>${msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
         </div>`;
}

<?php
// Inject toast notification
if (!empty($success)) {
    echo "window._toastMsg=" . json_encode($success) . ";window._toastType='success';";
} elseif (!empty($error)) {
    echo "window._toastMsg=" . json_encode($error) . ";window._toastType='danger';";
}
?>
</script>
EOD;

file_put_contents($file, $content);
