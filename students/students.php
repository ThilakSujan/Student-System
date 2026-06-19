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

<style>
/* ── Stat cards: compound-class specificity fix (0,2,0) beats .card (0,1,0) ── */
.card.stat-total    { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important; }
.card.stat-active   { background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important; }
.card.stat-inactive { background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%) !important; }
.card.stat-male     { background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%) !important; }
.card.stat-female   { background: linear-gradient(135deg, #be185d 0%, #ec4899 100%) !important; }
/* Descendant (0,1,1) overrides global h3/h6 element rule (0,0,1) */
.card.stat-total    h3, .card.stat-total    h6,
.card.stat-active   h3, .card.stat-active   h6,
.card.stat-inactive h3, .card.stat-inactive h6,
.card.stat-male     h3, .card.stat-male     h6,
.card.stat-female   h3, .card.stat-female   h6 {
    color: #ffffff !important;
}
</style>
    <!-- Page heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Students Management</h4>
            <small class="text-muted">Manage and organize all student records</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="index.php" class="btn btn-success btn-sm">
                <i class="bi bi-person-plus"></i> Add New Student
            </a>
            <?php if (!is_student()): ?>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#csvImportModal" id="btnOpenCsvImport">
                <i class="bi bi-file-earmark-arrow-up"></i> Import Students (CSV)
            </button>
            <?php endif; ?>
        </div>
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
    <div class="row mb-4 g-3">
        <div class="col-6 col-md">
            <div class="card stat-total text-center shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-uppercase mb-1" style="font-size:10px;"><i class="bi bi-people-fill me-1"></i>Total Students</h6>
                    <h3 class="mb-0"><?= $total_students ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card stat-active text-center shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-uppercase mb-1" style="font-size:10px;"><i class="bi bi-check-circle me-1"></i>Active</h6>
                    <h3 class="mb-0"><?= $active ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card stat-inactive text-center shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-uppercase mb-1" style="font-size:10px;"><i class="bi bi-x-circle me-1"></i>Inactive</h6>
                    <h3 class="mb-0"><?= $inactive ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card stat-male text-center shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-uppercase mb-1" style="font-size:10px;"><i class="bi bi-gender-male me-1"></i>Male</h6>
                    <h3 class="mb-0"><?= $male ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card stat-female text-center shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-uppercase mb-1" style="font-size:10px;"><i class="bi bi-gender-female me-1"></i>Female</h6>
                    <h3 class="mb-0"><?= $female ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-people-fill"></i> Students List (<span id="studentCount"><?= $total_students ?></span>)</strong>
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
                                <th>Gender</th>
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
                                    <?php
                                        $g = strtolower(trim($row['gender'] ?? ''));
                                        if ($g === 'male') {
                                            echo '<span class="badge" style="background:#6366f1;"><i class="bi bi-gender-male me-1"></i>Male</span>';
                                        } elseif ($g === 'female') {
                                            echo '<span class="badge" style="background:#ec4899;"><i class="bi bi-gender-female me-1"></i>Female</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">' . htmlspecialchars($row['gender'] ?? '—') . '</span>';
                                        }
                                    ?>
                                </td>
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

<!-- ══ CSV Import Modal ════════════════════════════════════════════════════ -->
<?php if (!is_student()): ?>
<div class="modal fade" id="csvImportModal" tabindex="-1" aria-labelledby="csvImportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px; overflow:hidden;">

      <!-- Header -->
      <div class="modal-header" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;">
        <h5 class="modal-title" id="csvImportModalLabel" style="color:#fff !important;">
          <i class="bi bi-file-earmark-arrow-up me-2"></i>Import Students via CSV
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body p-4">

        <!-- Alert area -->
        <div id="csvAlertArea"></div>

        <!-- Step 1: Upload zone -->
        <div id="csvStep1">

          <!-- Drag-and-drop zone -->
          <div id="csvDropZone"
               style="border:2px dashed #6366f1; border-radius:12px; padding:40px 20px;
                      text-align:center; cursor:pointer; transition:background 0.2s;"
               onclick="document.getElementById('csvFileInput').click()"
               ondragover="csvDragOver(event)" ondragleave="csvDragLeave(event)" ondrop="csvDrop(event)">
            <i class="bi bi-cloud-arrow-up" style="font-size:48px;color:#6366f1;"></i>
            <p class="mt-3 mb-1 fw-semibold" style="font-size:16px;">Drag &amp; Drop your CSV file here</p>
            <p class="text-muted mb-3" style="font-size:13px;">or click to browse</p>
            <span class="badge" style="background:#6366f1; font-size:13px; padding:6px 14px;">.csv files only</span>
            <input type="file" id="csvFileInput" accept=".csv" style="display:none;" onchange="csvFileSelected(this)">
          </div>

          <!-- Selected file indicator -->
          <div id="csvFileInfo" class="mt-3" style="display:none;">
            <div class="alert alert-info d-flex align-items-center gap-2 mb-0" style="border-radius:10px;">
              <i class="bi bi-file-earmark-spreadsheet fs-5"></i>
              <div>
                <strong id="csvFileName">file.csv</strong>
                <span class="text-muted ms-2" id="csvFileSize"></span>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" onclick="csvClearFile()">
                <i class="bi bi-x"></i> Remove
              </button>
            </div>
          </div>

          <!-- Live column-check panel (appears after file is selected) -->
          <div id="csvColumnCheck" style="display:none;" class="mt-3">
            <div class="card" style="border-radius:12px; border:1px solid var(--dash-border,#e2e8f0);">
              <div class="card-body py-3">
                <h6 class="fw-bold mb-3" style="font-size:13px;">
                  <i class="bi bi-list-check me-1"></i>Column Validation
                  <span id="csvColSummaryBadge" class="ms-2"></span>
                </h6>
                <div id="csvColRows" class="d-flex flex-column gap-2"></div>
              </div>
            </div>
          </div>

          <!-- Instructions -->
          <div class="card mt-4" style="border-radius:12px; background:var(--dash-input-bg,#f8fafc);">
            <div class="card-body py-3">
              <h6 class="fw-bold mb-2"><i class="bi bi-info-circle text-primary me-1"></i>CSV Format Requirements</h6>
              <ul class="mb-0 small" style="padding-left:18px;">
                <li>First row must be the header: <code>name,email,phone,department,gender</code></li>
                <li>Do <strong>not</strong> include an <code>id</code> column — IDs are auto-generated</li>
                <li><strong>Gender</strong> accepted values: <code>Male</code>, <code>Female</code>, <code>Other</code> (optional)</li>
                <li>Email must be valid and unique across all students</li>
                <li>Phone must contain at least 7 digits</li>
                <li>Duplicate emails and phone numbers will be skipped automatically</li>
              </ul>
            </div>
          </div>

        </div><!-- /#csvStep1 -->

        <!-- Step 2: Loading indicator -->
        <div id="csvStep2" style="display:none; text-align:center; padding:40px 20px;">
          <div class="spinner-border" style="width:3.5rem;height:3.5rem;color:#6366f1;" role="status"></div>
          <p class="mt-4 fw-semibold" style="font-size:18px;">Importing Students…</p>
          <p class="text-muted">Please wait while we process your CSV file.</p>
          <div class="progress mt-3" style="height:6px;border-radius:3px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated"
                 style="width:100%;background:#6366f1;"></div>
          </div>
        </div>

        <!-- Step 3: Results -->
        <div id="csvStep3" style="display:none;">

          <!-- FORMAT ERROR panel (shown instead of counts on hard format failure) -->
          <div id="csvFormatError" style="display:none;">
            <div class="text-center mb-3">
              <div style="font-size:52px;">🚫</div>
              <h5 class="fw-bold mt-2" style="color:#ef4444;">Invalid CSV Format</h5>
              <p class="text-muted mb-0" id="csvFormatErrorMsg" style="font-size:14px;"></p>
            </div>
            <div class="card mt-3" style="border:2px solid #ef4444;border-radius:12px;">
              <div class="card-body py-3">
                <div class="row g-2 small">
                  <div class="col-12">
                    <span class="fw-bold text-success"><i class="bi bi-check-circle me-1"></i>Expected columns:</span><br>
                    <code id="csvFmtExpected" class="d-block mt-1 p-2 rounded" style="background:#f0fdf4;color:#16a34a;"></code>
                  </div>
                  <div class="col-12 mt-2">
                    <span class="fw-bold text-danger"><i class="bi bi-x-circle me-1"></i>Your file had:</span><br>
                    <code id="csvFmtFound" class="d-block mt-1 p-2 rounded" style="background:#fef2f2;color:#dc2626;"></code>
                  </div>
                </div>
              </div>
            </div>
            <div class="alert alert-warning mt-3 mb-0" style="border-radius:10px;font-size:13px;">
              <i class="bi bi-download me-1"></i>
              Please <a href="sample_students.csv" download class="fw-bold">download the sample CSV</a> and use it as your template.
            </div>
          </div>

          <!-- FORMAT WARNING banner (non-blocking extra columns notice) -->
          <div id="csvFormatWarning" class="alert alert-warning mb-3" style="display:none;border-radius:10px;font-size:13px;">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <span id="csvFormatWarningMsg"></span>
          </div>

          <!-- Normal result view -->
          <div id="csvNormalResult">
            <div class="text-center mb-4">
              <div id="csvResultIcon" style="font-size:56px;">✅</div>
              <h5 class="fw-bold mt-2" id="csvResultTitle">Import Completed Successfully</h5>
            </div>

            <!-- Summary cards -->
            <div class="row g-3 mb-4">
              <div class="col-4">
                <div class="card text-center" style="border-radius:12px;border:2px solid #e2e8f0;">
                  <div class="card-body py-3">
                    <h3 class="mb-0" id="csvTotalCount" style="color:#6366f1;">0</h3>
                    <small class="text-muted fw-semibold">Total Found</small>
                  </div>
                </div>
              </div>
              <div class="col-4">
                <div class="card text-center" style="border-radius:12px;border:2px solid #10b981;">
                  <div class="card-body py-3">
                    <h3 class="mb-0" id="csvImportedCount" style="color:#10b981;">0</h3>
                    <small class="text-muted fw-semibold">Imported</small>
                  </div>
                </div>
              </div>
              <div class="col-4">
                <div class="card text-center" style="border-radius:12px;border:2px solid #f59e0b;">
                  <div class="card-body py-3">
                    <h3 class="mb-0" id="csvSkippedCount" style="color:#f59e0b;">0</h3>
                    <small class="text-muted fw-semibold">Skipped</small>
                  </div>
                </div>
              </div>
            </div>

          <!-- Per-row errors -->
          <div id="csvErrorList" style="display:none;">
            <h6 class="fw-bold"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Skipped Records Detail</h6>
            <div id="csvErrorItems" style="max-height:200px; overflow-y:auto; border:1px solid var(--dash-border,#e2e8f0); border-radius:10px; padding:12px; font-size:13px;"></div>
          </div>

          </div><!-- /#csvNormalResult -->

        </div><!-- /#csvStep3 -->

      </div><!-- /.modal-body -->

      <!-- Footer -->
      <div class="modal-footer" id="csvModalFooter" style="border-top:1px solid var(--dash-border,#e2e8f0);">
        <!-- Initial state -->
        <div id="csvFooterInitial" class="d-flex gap-2 w-100 justify-content-between align-items-center flex-wrap">
          <a href="sample_students.csv" download class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download me-1"></i>Download Sample CSV
          </a>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="csvImportBtn" onclick="startCsvImport()" disabled>
              <i class="bi bi-upload me-1"></i>Import Students
            </button>
          </div>
        </div>
        <!-- Result state -->
        <div id="csvFooterResult" style="display:none;" class="d-flex gap-2 w-100 justify-content-end">
          <button type="button" class="btn btn-outline-primary" onclick="csvReset()">
            <i class="bi bi-arrow-repeat me-1"></i>Import Another File
          </button>
          <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="location.reload()">
            <i class="bi bi-check-circle me-1"></i>Done
          </button>
        </div>
      </div>

    </div>
  </div>
</div><!-- /#csvImportModal -->
<?php endif; ?>

<script>
var studentsTable;
$(document).ready(function () {
    studentsTable = $('#studentsTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        columnDefs: [
            { orderable: false, targets: 0 },
            { orderable: false, targets: 7 }
        ],
        order: [[1, 'asc']]
    });

    // Reset modal state when closed
    document.getElementById('csvImportModal')?.addEventListener('hidden.bs.modal', function () {
        csvReset();
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

// ═══════════════════════════════════════════════════
//  CSV IMPORT — JavaScript
// ═══════════════════════════════════════════════════

/** Currently selected File object */
let csvSelectedFile = null;

/** Show/hide steps */
function csvShowStep(step) {
    document.getElementById('csvStep1').style.display = (step === 1) ? '' : 'none';
    document.getElementById('csvStep2').style.display = (step === 2) ? '' : 'none';
    document.getElementById('csvStep3').style.display = (step === 3) ? '' : 'none';
    document.getElementById('csvFooterInitial').style.display = (step === 1) ? '' : 'none';
    document.getElementById('csvFooterResult').style.display  = (step === 3) ? '' : 'none';
}

/** Update alert area */
function csvAlert(msg, type) {
    document.getElementById('csvAlertArea').innerHTML =
        `<div class="alert alert-${type} alert-dismissible fade show" style="border-radius:10px;" role="alert">
            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'} me-1"></i>${msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
         </div>`;
}

/** Handle file selected via input */
function csvFileSelected(input) {
    if (input.files && input.files[0]) {
        csvSetFile(input.files[0]);
    }
}

// Required columns that must be present (matches sample_students.csv)
const CSV_REQUIRED_COLS = ['name', 'email', 'phone', 'department'];
const CSV_OPTIONAL_COLS = ['gender'];

/** Parse first row of file, validate columns, update UI */
function csvSetFile(file) {
    // Extension check
    const ext = file.name.split('.').pop().toLowerCase();
    if (ext !== 'csv') {
        csvAlert('Please select a .csv file.', 'danger');
        return;
    }

    // Show file info bar immediately
    csvSelectedFile = file;
    document.getElementById('csvFileName').textContent = file.name;
    document.getElementById('csvFileSize').textContent = `(${(file.size / 1024).toFixed(1)} KB)`;
    document.getElementById('csvFileInfo').style.display = '';
    document.getElementById('csvDropZone').style.borderColor = '#6366f1'; // neutral until checked
    document.getElementById('csvImportBtn').disabled = true;               // lock until validated
    document.getElementById('csvAlertArea').innerHTML = '';

    // Read only the first line (header) via FileReader
    const reader = new FileReader();
    reader.onload = function (e) {
        const text = e.target.result;

        // Strip UTF-8 BOM if present
        const cleaned = text.replace(/^\uFEFF/, '');

        // Extract header row (first non-empty line)
        const firstLine = cleaned.split(/\r?\n/).find(l => l.trim() !== '');
        if (!firstLine) {
            csvShowColumnError(['The CSV file appears to be empty — no header row found.']);
            return;
        }

        // Parse header columns (trim + lowercase for comparison)
        const rawCols  = firstLine.split(',').map(c => c.trim());
        const normCols = rawCols.map(c => c.toLowerCase().replace(/["']/g, ''));

        // Build per-column result
        const missing  = [];
        const colItems = [];

        // Required columns
        CSV_REQUIRED_COLS.forEach(col => {
            const present = normCols.includes(col);
            if (!present) missing.push(col);
            colItems.push({ col, present, required: true });
        });

        // Optional columns
        CSV_OPTIONAL_COLS.forEach(col => {
            const present = normCols.includes(col);
            colItems.push({ col, present, required: false });
        });

        // Render column check panel
        renderColumnCheck(colItems, missing);

        if (missing.length === 0) {
            // All required columns present
            document.getElementById('csvDropZone').style.borderColor = '#10b981';
            document.getElementById('csvImportBtn').disabled = false;
        } else {
            // Missing columns — keep button locked
            document.getElementById('csvDropZone').style.borderColor = '#ef4444';
            document.getElementById('csvImportBtn').disabled = true;
            csvSelectedFile = null; // prevent accidental submit
        }
    };

    // Read only the first 2 KB — enough to get the header without loading huge files
    const slice = file.slice(0, 2048);
    reader.readAsText(slice, 'UTF-8');
}

/** Render the column-check grid inside #csvColumnCheck */
function renderColumnCheck(colItems, missing) {
    const panel   = document.getElementById('csvColumnCheck');
    const rowsEl  = document.getElementById('csvColRows');
    const badgeEl = document.getElementById('csvColSummaryBadge');

    rowsEl.innerHTML = colItems.map(({ col, present, required }) => {
        if (present) {
            return `<div class="d-flex align-items-center gap-2" style="font-size:13px;">
                      <i class="bi bi-check-circle-fill" style="color:#10b981;font-size:16px;"></i>
                      <code style="background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:6px;">${escHtml(col)}</code>
                      <span class="text-muted">${required ? 'Required — found ✓' : 'Optional — found ✓'}</span>
                    </div>`;
        } else if (required) {
            return `<div class="d-flex align-items-start gap-2" style="font-size:13px;">
                      <i class="bi bi-x-circle-fill" style="color:#ef4444;font-size:16px;flex-shrink:0;margin-top:1px;"></i>
                      <div>
                        <code style="background:#fef2f2;color:#dc2626;padding:2px 8px;border-radius:6px;">${escHtml(col)}</code>
                        <span class="text-danger fw-semibold ms-2">The column &ldquo;${escHtml(col)}&rdquo; is missing.</span>
                      </div>
                    </div>`;
        } else {
            return `<div class="d-flex align-items-center gap-2" style="font-size:13px;">
                      <i class="bi bi-dash-circle" style="color:#94a3b8;font-size:16px;"></i>
                      <code style="background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:6px;">${escHtml(col)}</code>
                      <span class="text-muted">Optional — not found (will be left blank)</span>
                    </div>`;
        }
    }).join('');

    // Summary badge
    if (missing.length === 0) {
        badgeEl.innerHTML = '<span class="badge" style="background:#10b981;">All columns valid ✔</span>';
    } else {
        badgeEl.innerHTML = `<span class="badge bg-danger">${missing.length} column${missing.length > 1 ? 's' : ''} missing</span>`;
    }

    panel.style.display = '';
}

/** Show a fatal error inside the column-check panel */
function csvShowColumnError(messages) {
    const panel  = document.getElementById('csvColumnCheck');
    const rowsEl = document.getElementById('csvColRows');
    const badge  = document.getElementById('csvColSummaryBadge');

    rowsEl.innerHTML = messages.map(m =>
        `<div class="d-flex align-items-center gap-2 text-danger" style="font-size:13px;">
           <i class="bi bi-exclamation-triangle-fill" style="font-size:16px;"></i>
           <span>${escHtml(m)}</span>
         </div>`
    ).join('');
    badge.innerHTML = '<span class="badge bg-danger">Error</span>';
    panel.style.display = '';
}

/** Clear the column check panel */
function csvClearColumnCheck() {
    document.getElementById('csvColumnCheck').style.display = 'none';
    document.getElementById('csvColRows').innerHTML = '';
    document.getElementById('csvColSummaryBadge').innerHTML = '';
}

/** Remove selected file */
function csvClearFile() {
    csvSelectedFile = null;
    document.getElementById('csvFileInput').value = '';
    document.getElementById('csvFileInfo').style.display = 'none';
    document.getElementById('csvDropZone').style.borderColor = '#6366f1';
    document.getElementById('csvImportBtn').disabled = true;
    csvClearColumnCheck();
}

/** Reset modal to initial state */
function csvReset() {
    csvClearFile();
    csvShowStep(1);
    document.getElementById('csvAlertArea').innerHTML    = '';
    document.getElementById('csvTotalCount').textContent    = '0';
    document.getElementById('csvImportedCount').textContent = '0';
    document.getElementById('csvSkippedCount').textContent  = '0';
    document.getElementById('csvErrorList').style.display   = 'none';
    document.getElementById('csvErrorItems').innerHTML      = '';
    document.getElementById('csvResultIcon').textContent    = '✅';
    document.getElementById('csvResultTitle').textContent   = 'Import Completed Successfully';
    // Reset format-error / warning panels
    document.getElementById('csvFormatError').style.display   = 'none';
    document.getElementById('csvFormatWarning').style.display = 'none';
    document.getElementById('csvNormalResult').style.display  = '';
    document.getElementById('csvFormatErrorMsg').textContent  = '';
    document.getElementById('csvFmtExpected').textContent     = '';
    document.getElementById('csvFmtFound').textContent        = '';
    document.getElementById('csvFormatWarningMsg').textContent = '';
    csvClearColumnCheck();
}

/** Drag-and-drop event handlers */
function csvDragOver(e) {
    e.preventDefault();
    document.getElementById('csvDropZone').style.background = 'rgba(99,102,241,0.08)';
}
function csvDragLeave(e) {
    document.getElementById('csvDropZone').style.background = '';
}
function csvDrop(e) {
    e.preventDefault();
    document.getElementById('csvDropZone').style.background = '';
    if (e.dataTransfer.files.length > 0) {
        csvSetFile(e.dataTransfer.files[0]);
    }
}

/** Submit CSV for processing */
function startCsvImport() {
    if (!csvSelectedFile) {
        csvAlert('Please select a CSV file first.', 'warning');
        return;
    }

    // Show loading step
    csvShowStep(2);

    const formData = new FormData();
    formData.append('csv_file', csvSelectedFile);

    fetch('import_csv.php', { method: 'POST', body: formData })
        .then(response => {
            if (!response.ok) throw new Error('Server error: ' + response.status);
            return response.json();
        })
        .then(data => {

            // ── Hard format error: wrong CSV structure ────────────────────────
            if (data.format_error) {
                document.getElementById('csvFormatErrorMsg').textContent = data.message || 'Your CSV does not match the required format.';
                document.getElementById('csvFmtExpected').textContent    = data.expected || 'name, email, phone, department [, gender]';
                document.getElementById('csvFmtFound').textContent       = data.found    || '(unknown)';
                document.getElementById('csvFormatError').style.display  = '';
                document.getElementById('csvNormalResult').style.display = 'none';
                document.getElementById('csvFormatWarning').style.display = 'none';
                csvShowStep(3);
                return;
            }

            // ── Normal result ─────────────────────────────────────────────────
            document.getElementById('csvFormatError').style.display  = 'none';
            document.getElementById('csvNormalResult').style.display = '';

            // Non-blocking format warnings (e.g. unknown extra columns)
            if (data.format_warnings && data.format_warnings.length > 0) {
                document.getElementById('csvFormatWarningMsg').textContent = data.format_warnings.join(' ');
                document.getElementById('csvFormatWarning').style.display = '';
            } else {
                document.getElementById('csvFormatWarning').style.display = 'none';
            }

            // Populate count cards
            document.getElementById('csvTotalCount').textContent    = data.total    ?? 0;
            document.getElementById('csvImportedCount').textContent = data.imported ?? 0;
            document.getElementById('csvSkippedCount').textContent  = data.skipped  ?? 0;

            // Icon / title
            if (data.skipped > 0 && data.imported === 0) {
                document.getElementById('csvResultIcon').textContent  = '❌';
                document.getElementById('csvResultTitle').textContent = 'Import Failed — No Records Imported';
            } else if (data.skipped > 0) {
                document.getElementById('csvResultIcon').textContent  = '⚠️';
                document.getElementById('csvResultTitle').textContent = 'Import Completed with Warnings';
            } else {
                document.getElementById('csvResultIcon').textContent  = '✅';
                document.getElementById('csvResultTitle').textContent = 'Import Completed Successfully';
            }

            // Per-row errors
            if (data.errors && data.errors.length > 0) {
                const errorContainer = document.getElementById('csvErrorItems');
                errorContainer.innerHTML = data.errors
                    .map(e => `<div class="py-1 border-bottom" style="border-color:var(--dash-border,#e2e8f0)!important;">
                                  <i class="bi bi-exclamation-circle text-warning me-1"></i>${escHtml(e)}
                               </div>`)
                    .join('');
                document.getElementById('csvErrorList').style.display = '';
            } else {
                document.getElementById('csvErrorList').style.display = 'none';
            }

            // Show result step
            csvShowStep(3);
        })
        .catch(err => {
            csvShowStep(1);
            csvAlert('Unexpected error: ' + err.message + '. Please try again.', 'danger');
        });
}

/** Escape HTML helper */
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
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