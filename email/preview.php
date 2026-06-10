<?php
/**
 * Email Template Previewer
 * Admin only — renders any email template in the browser using real DB data.
 * No emails are sent here; this is purely for visual inspection.
 */
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';
require_once '../includes/email_service.php';

$page_title = 'Email Preview & Test';

// ── Fetch all students for the dropdown ──────────────────────────────
$studentsRes = $mysqli->query(
    "SELECT id, student_name, email, parent_email FROM students ORDER BY student_name ASC"
);
$students = $studentsRes ? $studentsRes->fetch_all(MYSQLI_ASSOC) : [];

// ── Fetch institute info ─────────────────────────────────────────────
$instRes   = $mysqli->query("SELECT * FROM institute_profile LIMIT 1");
$institute = ($instRes && $instRes->num_rows > 0) ? $instRes->fetch_assoc() : ['institute_name' => 'Student Management System'];

$selectedType      = $_GET['type']       ?? 'attendance';
$selectedStudentId = (int)($_GET['student_id'] ?? ($students[0]['id'] ?? 0));

$validTypes = ['attendance', 'fee_invoice', 'marks_published', 'report_card', 'custom'];
if (!in_array($selectedType, $validTypes)) $selectedType = 'attendance';

// ── Build preview HTML ───────────────────────────────────────────────
$previewHtml = '';
$previewError = '';

if ($selectedStudentId) {
    try {
        $emailSvc = new EmailService($mysqli);

        // Get student
        $sRes    = $mysqli->query("SELECT * FROM students WHERE id = {$selectedStudentId} LIMIT 1");
        $student = $sRes ? $sRes->fetch_assoc() : null;

        if (!$student) throw new RuntimeException('Student not found.');

        $tplDir = __DIR__ . '/../includes/email_templates/';

        switch ($selectedType) {

            case 'attendance':
                $date = date('Y-m-d');
                $vars = ['student' => $student, 'date' => $date, 'institute' => $institute];
                break;

            case 'fee_invoice':
                // Fetch latest fee payments for this student
                $fpRes = $mysqli->query(
                    "SELECT fp.amount_paid, fp.payment_mode, fp.receipt_no,
                            fc.name AS cat_name, fs.academic_year
                     FROM fee_payments fp
                     JOIN fee_structures fs ON fs.id = fp.fee_assignment_id
                     JOIN fee_categories fc ON fc.id = fs.category_id
                     WHERE fp.student_id = {$selectedStudentId}
                     ORDER BY fp.created_at DESC LIMIT 5"
                );
                $payments = $fpRes ? $fpRes->fetch_all(MYSQLI_ASSOC) : [];

                // Use dummy data if no real payments exist
                if (empty($payments)) {
                    $payments = [
                        ['amount_paid' => 5000.00, 'payment_mode' => 'Online',        'receipt_no' => 'REC-001', 'cat_name' => 'Tuition Fee',       'academic_year' => date('Y').'-'.(date('Y')+1)],
                        ['amount_paid' => 1500.00, 'payment_mode' => 'Bank Transfer', 'receipt_no' => 'REC-002', 'cat_name' => 'Examination Fee',   'academic_year' => date('Y').'-'.(date('Y')+1)],
                    ];
                }
                $vars = ['student' => $student, 'payments' => $payments, 'institute' => $institute];
                break;

            case 'marks_published':
            case 'report_card':
                $mRes = $mysqli->query(
                    "SELECT m.marks_obtained, m.total_marks, sub.subject_name, sub.subject_code
                     FROM marks m JOIN subjects sub ON sub.id = m.subject_id
                     WHERE m.student_id = {$selectedStudentId} ORDER BY sub.subject_code ASC"
                );
                $marks = $mRes ? $mRes->fetch_all(MYSQLI_ASSOC) : [];

                // Use dummy marks if student has none
                if (empty($marks)) {
                    $marks = [
                        ['marks_obtained' => 85, 'total_marks' => 100, 'subject_name' => 'Mathematics',        'subject_code' => 'MATH101'],
                        ['marks_obtained' => 72, 'total_marks' => 100, 'subject_name' => 'Physics',            'subject_code' => 'PHY101'],
                        ['marks_obtained' => 91, 'total_marks' => 100, 'subject_name' => 'Computer Science',   'subject_code' => 'CS101'],
                        ['marks_obtained' => 68, 'total_marks' => 100, 'subject_name' => 'English',            'subject_code' => 'ENG101'],
                        ['marks_obtained' => 77, 'total_marks' => 100, 'subject_name' => 'Chemistry',          'subject_code' => 'CHE101'],
                    ];
                }
                $totalObtained = array_sum(array_column($marks, 'marks_obtained'));
                $totalMax      = array_sum(array_column($marks, 'total_marks'));
                $percentage    = $totalMax > 0 ? round($totalObtained / $totalMax * 100, 2) : 0;

                $tplFile = ($selectedType === 'report_card') ? 'report_card_email.php' : 'marks_published.php';
                $vars = [
                    'student'        => $student,
                    'marks'          => $marks,
                    'total_obtained' => $totalObtained,
                    'total_max'      => $totalMax,
                    'percentage'     => $percentage,
                    'institute'      => $institute,
                ];
                break;

            case 'custom':
                $vars = [
                    'recipient_email' => $student['email'],
                    'subject'         => 'Sample Custom Email Subject',
                    'message'         => '<p>Dear <strong>' . htmlspecialchars($student['student_name']) . '</strong>,</p>
                                          <p>This is a sample custom email message sent from the Student Management System.</p>
                                          <p>You can type any message content here when using the <strong>Send Mail</strong> feature from the Students list.</p>',
                    'institute'       => $institute,
                ];
                break;

            default:
                $vars = [];
        }

        // Determine template file
        $tplFile = match($selectedType) {
            'attendance'      => 'attendance_alert.php',
            'fee_invoice'     => 'fee_invoice.php',
            'marks_published' => 'marks_published.php',
            'report_card'     => 'report_card_email.php',
            'custom'          => 'custom_email.php',
        };

        $tplPath = $tplDir . $tplFile;
        if (!file_exists($tplPath)) throw new RuntimeException("Template file not found: {$tplFile}");

        extract($vars, EXTR_SKIP);
        ob_start();
        include $tplPath;
        $previewHtml = ob_get_clean();

    } catch (Throwable $e) {
        $previewError = $e->getMessage();
    }
}

// ── Type meta ────────────────────────────────────────────────────────
$typeMeta = [
    'attendance'      => ['Attendance Absence Alert',   'danger',  'bi-person-x-fill'],
    'fee_invoice'     => ['Fee Payment Invoice',         'success', 'bi-receipt-cutoff'],
    'marks_published' => ['Marks Published Notification','primary', 'bi-graph-up-arrow'],
    'report_card'     => ['Report Card Email',           'info',    'bi-file-earmark-text-fill'],
    'custom'          => ['Custom / Manual Email',       'secondary','bi-envelope-paper-fill'],
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-eye me-2 text-primary"></i>Email Preview & Test</h4>
            <p class="text-muted mb-0" style="font-size:13px;">Preview email templates and test your SMTP configuration</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-clock-history me-1"></i>Email Logs
            </a>
            <a href="test.php" class="btn btn-warning btn-sm">
                <i class="bi bi-send-check me-1"></i>Send Test Email
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- Left: Controls -->
        <div class="col-lg-4 col-xl-3">

            <!-- Template Selector -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header fw-semibold" style="background:linear-gradient(135deg,#1e293b,#334155);color:#fff;">
                    <i class="bi bi-palette me-2"></i>Preview Options
                </div>
                <div class="card-body">
                    <form method="GET" id="previewForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:13px;">Email Template</label>
                            <?php foreach ($typeMeta as $typeKey => [$typeLabel, $typeBadge, $typeIcon]): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="type"
                                       id="type_<?= $typeKey ?>" value="<?= $typeKey ?>"
                                       <?= $selectedType === $typeKey ? 'checked' : '' ?>
                                       onchange="document.getElementById('previewForm').submit()">
                                <label class="form-check-label d-flex align-items-center gap-2" for="type_<?= $typeKey ?>">
                                    <span class="badge bg-<?= $typeBadge ?>" style="font-size:11px;">
                                        <i class="bi <?= $typeIcon ?>"></i>
                                    </span>
                                    <span style="font-size:13px;"><?= $typeLabel ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:13px;">Preview as Student</label>
                            <select name="student_id" class="form-select form-select-sm"
                                    onchange="document.getElementById('previewForm').submit()">
                                <?php foreach ($students as $st): ?>
                                    <option value="<?= $st['id'] ?>" <?= $selectedStudentId == $st['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($st['student_name']) ?>
                                        <?= $st['email'] ? '(' . htmlspecialchars($st['email']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Uses real data from DB. Falls back to dummy data if none exists.</small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Selected Student Info -->
            <?php
            $selStudent = null;
            foreach ($students as $st) {
                if ($st['id'] == $selectedStudentId) { $selStudent = $st; break; }
            }
            ?>
            <?php if ($selStudent): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header fw-semibold" style="font-size:13px;background:#f8fafc;">
                    <i class="bi bi-person-circle me-1 text-primary"></i>Student Details
                </div>
                <div class="card-body py-2" style="font-size:13px;">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Name</td><td class="fw-semibold"><?= htmlspecialchars($selStudent['student_name']) ?></td></tr>
                        <tr><td class="text-muted">Email</td>
                            <td>
                                <?php if ($selStudent['email']): ?>
                                    <span class="text-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($selStudent['email']) ?></span>
                                <?php else: ?>
                                    <span class="text-danger"><i class="bi bi-x-circle me-1"></i>No email</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr><td class="text-muted">Parent Email</td>
                            <td>
                                <?php if (!empty($selStudent['parent_email'])): ?>
                                    <span class="text-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($selStudent['parent_email']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Not set</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Legend -->
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3" style="font-size:12px;color:#6b7280;">
                    <p class="mb-2 fw-semibold text-dark">📧 Recipient Logic</p>
                    <ul class="mb-0 ps-3">
                        <li><strong>Attendance, Fee, Report Card</strong><br>Parent email → Student email</li>
                        <li class="mt-1"><strong>Marks Published</strong><br>Student email only</li>
                        <li class="mt-1"><strong>Custom</strong><br>Whatever you type in the modal</li>
                    </ul>
                </div>
            </div>

        </div>

        <!-- Right: Preview -->
        <div class="col-lg-8 col-xl-9">
            <!-- Template badge -->
            <?php [$typeLabel, $typeBadge, $typeIcon] = $typeMeta[$selectedType]; ?>
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="badge bg-<?= $typeBadge ?> px-3 py-2" style="font-size:13px;">
                    <i class="bi <?= $typeIcon ?> me-1"></i><?= $typeLabel ?>
                </span>
                <span class="text-muted" style="font-size:13px;">
                    Previewing template using
                    <strong><?= htmlspecialchars($selStudent['student_name'] ?? 'sample') ?></strong>'s data
                    <?= $selectedType === 'attendance' ? '— Date: <strong>'.date('d M Y').'</strong>' : '' ?>
                </span>
            </div>

            <?php if ($previewError): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Template Error:</strong> <?= htmlspecialchars($previewError) ?>
                </div>
            <?php elseif ($previewHtml): ?>
                <!-- Toolbar -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>This is exactly how the email will appear in the recipient's inbox.</small>
                    <div class="d-flex gap-2">
                        <button onclick="document.getElementById('previewFrame').contentWindow.print()"
                                class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-printer me-1"></i>Print Preview
                        </button>
                        <button onclick="copyHtml()" class="btn btn-sm btn-outline-dark">
                            <i class="bi bi-clipboard me-1"></i>Copy HTML
                        </button>
                    </div>
                </div>

                <!-- Iframe preview — isolated from page styles -->
                <div class="card border-0 shadow" style="border-radius:12px;overflow:hidden;">
                    <iframe id="previewFrame"
                            srcdoc="<?= htmlspecialchars($previewHtml, ENT_QUOTES) ?>"
                            style="width:100%;border:none;min-height:700px;background:#f4f6f9;"
                            onload="autoResize(this)">
                    </iframe>
                </div>

                <!-- Hidden textarea for copy -->
                <textarea id="htmlSource" style="position:absolute;left:-9999px;" readonly><?= htmlspecialchars($previewHtml) ?></textarea>

            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-envelope-open fs-1 d-block mb-3"></i>
                        <p>Select a template type and student above to preview the email.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>
</div>
<?php include '../includes/footer.php'; ?>
</div>

<script>
function autoResize(iframe) {
    try {
        const doc = iframe.contentDocument || iframe.contentWindow.document;
        iframe.style.height = (doc.documentElement.scrollHeight + 40) + 'px';
    } catch(e) {}
}

function copyHtml() {
    const ta = document.getElementById('htmlSource');
    ta.select();
    document.execCommand('copy');
    const btn = event.target.closest('button');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check me-1"></i>Copied!';
    btn.classList.replace('btn-outline-dark', 'btn-success');
    setTimeout(() => {
        btn.innerHTML = orig;
        btn.classList.replace('btn-success', 'btn-outline-dark');
    }, 2000);
}
</script>
