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

$query          = "SELECT * FROM students";
$result         = mysqli_query($conn, $query);
$total_students = mysqli_num_rows($result);
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

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-people-fill"></i> Students List (<?= $total_students ?>)</strong>
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
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
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
                        <?php endwhile; ?>
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
    echo "<script>window._toastMsg=" . json_encode($success) . ";window._toastType='success';</script>";
} elseif (!empty($error)) {
    echo "<script>window._toastMsg=" . json_encode($error) . ";window._toastType='danger';</script>";
}
?>
</script>