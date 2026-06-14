<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/email_service.php';

require_login();

$user_role  = $_SESSION['role'];
$publishMsg = '';
$publishErr = '';

// ── Handle Publish / Notify actions (admin/staff only) ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($user_role, ['admin','staff'])) {
    $action    = $_POST['publish_action'] ?? '';
    $pub_sid   = (int)($_POST['publish_student_id'] ?? 0);

    if ($action === 'publish_one' && $pub_sid > 0) {
        // Just publish — no email
        $mysqli->query("UPDATE marks SET published=1, published_at=NOW() WHERE student_id={$pub_sid}");
        $publishMsg = 'Results published. Use the Notify button to send an email to the student.';

    } elseif ($action === 'notify_one' && $pub_sid > 0) {
        // Manual email send
        try {
            $emailSvc = new EmailService($mysqli);
            $sent     = $emailSvc->sendMarksPublished($pub_sid);
            $publishMsg = $sent
                ? 'Notification email sent successfully.'
                : 'Email could not be sent — check SMTP config in config/email_config.php.';
        } catch (Throwable $e) {
            $publishErr = 'Email error: ' . htmlspecialchars($e->getMessage());
        }

    } elseif ($action === 'publish_all') {
        // Just publish all unpublished — no email
        $mysqli->query("UPDATE marks SET published=1, published_at=NOW() WHERE published=0");
        $publishMsg = 'All unpublished results have been published. Use the Notify buttons to email individual students.';
    }
}

// Build query based on role
if ($user_role === 'student') {
    $student_id = null;
    
    // Check if student_id is set in session (new student login system)
    if (isset($_SESSION['student_id'])) {
        $student_id = $_SESSION['student_id'];
    } else {
        // Fall back to email-based lookup (legacy)
        $current_email = $_SESSION['email'];
        $student_result = $mysqli->query("SELECT id FROM students WHERE email = '$current_email'");
        if ($student_result->num_rows > 0) {
            $student = $student_result->fetch_assoc();
            $student_id = $student['id'];
        }
    }
    
    if ($student_id) {
        $marks_query = "SELECT m.*, s.student_name, s.email, sub.subject_code, sub.subject_name
                        FROM marks m
                        JOIN students s ON m.student_id = s.id
                        JOIN subjects sub ON m.subject_id = sub.id
                        WHERE m.student_id = $student_id
                        ORDER BY sub.subject_code ASC";
    } else {
        $marks_query = "SELECT m.*, s.student_name, s.email, sub.subject_code, sub.subject_name
                        FROM marks m
                        JOIN students s ON m.student_id = s.id
                        JOIN subjects sub ON m.subject_id = sub.id
                        WHERE 1=0";
    }
} else {
    $marks_query = "SELECT m.*, s.student_name, s.email, sub.subject_code, sub.subject_name
                    FROM marks m
                    JOIN students s ON m.student_id = s.id
                    JOIN subjects sub ON m.subject_id = sub.id
                    ORDER BY s.student_name ASC";
}

$marks_result = $mysqli->query($marks_query);

// Grade helpers
function getGrade($pct) {
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B';
    if ($pct >= 60) return 'C';
    if ($pct >= 50) return 'D';
    return 'F';
}
function getGradeColor($grade) {
    return match($grade) {
        'A+','A' => 'success',
        'B'      => 'info',
        'C'      => 'warning',
        'D','F'  => 'danger',
        default  => 'secondary'
    };
}

// Group marks by student
$summary_marks = [];
while ($mark = $marks_result->fetch_assoc()) {
    $key = $mark['student_id'];
    if (!isset($summary_marks[$key])) {
        $summary_marks[$key] = [
            'student_id'   => $mark['student_id'],
            'student_name' => $mark['student_name'],
            'email'        => $mark['email'],
            'marks'        => []
        ];
    }
    $summary_marks[$key]['marks'][] = $mark;
}

// Calculate totals, percentage, grade
$student_summaries = [];
foreach ($summary_marks as $sm) {
    $total = array_sum(array_column($sm['marks'], 'marks_obtained'));
    $count = count($sm['marks']);
    $pct   = $count > 0 ? round(($total / ($count * 100)) * 100, 2) : 0;
    $student_summaries[] = [
        'student_id'    => $sm['student_id'],
        'student_name'  => $sm['student_name'],
        'email'         => $sm['email'],
        'total_marks'   => $total,
        'max_marks'     => $count * 100,
        'percentage'    => $pct,
        'grade'         => getGrade($pct),
        'subject_count' => $count,
        'marks'         => $sm['marks']
    ];
}

// Sort by total desc → assign rank
usort($student_summaries, fn($a,$b) => $b['total_marks'] <=> $a['total_marks']);
foreach ($student_summaries as $i => &$s) { $s['rank'] = $i + 1; }
unset($s);

$page_title = "Marks Management";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
    <?php include '../includes/navbar.php'; ?>

    <div id="main-content">
        <div class="container-fluid">

            <!-- Page heading -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-graph-up"></i> Marks Management</h2>
                <?php if (in_array($user_role, ['admin','staff'])): ?>
                    <div class="d-flex gap-2">
                        <a href="add.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Marks
                        </a>
                        <!-- Publish All Unpublished -->
                        <form method="POST" onsubmit="return confirm('Publish ALL unpublished results? (No email will be sent — use Notify buttons for that)') ">
                            <input type="hidden" name="publish_action" value="publish_all">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check2-all me-1"></i>Publish All Results
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($publishMsg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($publishMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if ($publishErr): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($publishErr) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Marks table -->
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list"></i> Student Marks Summary</h5>
                    <?php if (is_admin()): ?>
                    <div class="d-flex gap-2">
                        <button onclick="exportTable('#marksTable', 'Marks Summary Report', 'excel')" class="btn btn-success btn-sm" title="Export to Excel">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
                        <button onclick="exportTable('#marksTable', 'Marks Summary Report', 'pdf')" class="btn btn-danger btn-sm" title="Export to PDF">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($student_summaries)): ?>
                        <p class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No marks found.
                            <?php if (in_array($user_role, ['admin','staff'])): ?>
                                <a href="add.php">Add marks now</a>
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <table id="marksTable" class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                     <th>Rank</th>
                                     <th>Student Name</th>
                                     <th>Email</th>
                                     <th>Total Marks</th>
                                     <th>Percentage</th>
                                     <th>Grade</th>
                                     <th>Subjects</th>
                                     <?php if (in_array($user_role, ['admin','staff'])): ?>
                                         <th>Actions</th>
                                     <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($student_summaries as $s): ?>
                                <tr>
                                    <td>
                                        <?php $badge = match($s['rank']) {
                                            1 => 'warning', 2 => 'secondary',
                                            3 => 'danger',  default => 'light text-dark'
                                        }; ?>
                                        <span class="badge bg-<?= $badge ?>"><?= $s['rank'] ?></span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($s['student_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><strong><?= $s['total_marks'] ?> / <?= $s['max_marks'] ?></strong></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:6px;min-width:60px">
                                                <div class="progress-bar bg-<?= getGradeColor($s['grade']) ?>"
                                                     style="width:<?= $s['percentage'] ?>%"></div>
                                            </div>
                                            <span><?= $s['percentage'] ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= getGradeColor($s['grade']) ?>">
                                            <?= $s['grade'] ?>
                                        </span>
                                    </td>
                                    <td><?= $s['subject_count'] ?></td>
                                     <?php if (in_array($user_role, ['admin','staff'])): ?>
                                     <td>
                                         <a href="student_marks.php?student_id=<?= $s['student_id'] ?>"
                                            class="btn btn-sm btn-info" title="View Details">
                                             <i class="bi bi-eye"></i>
                                         </a>
                                         <?php
                                         // Check published status
                                         $pubCheck = $mysqli->query("SELECT MAX(published) as pub FROM marks WHERE student_id={$s['student_id']}");
                                         $isPub    = $pubCheck && (bool)($pubCheck->fetch_assoc()['pub'] ?? false);
                                         ?>
                                         <?php if (!$isPub): ?>
                                         <form method="POST" class="d-inline ms-1">
                                             <input type="hidden" name="publish_action" value="publish_one">
                                             <input type="hidden" name="publish_student_id" value="<?= $s['student_id'] ?>">
                                             <button type="submit" class="btn btn-sm btn-success"
                                                 title="Publish Results (no email)"
                                                 onclick="return confirm('Publish results for <?= htmlspecialchars(addslashes($s['student_name'])) ?>?')">
                                                 <i class="bi bi-check-circle"></i>
                                             </button>
                                         </form>
                                         <?php else: ?>
                                         <form method="POST" class="d-inline ms-1">
                                             <input type="hidden" name="publish_action" value="notify_one">
                                             <input type="hidden" name="publish_student_id" value="<?= $s['student_id'] ?>">
                                             <button type="submit" class="btn btn-sm btn-primary"
                                                 title="Send Email Notification"
                                                 onclick="return confirm('Send marks notification email to <?= htmlspecialchars(addslashes($s['student_name'])) ?>?')">
                                                 <i class="bi bi-envelope-arrow-up"></i>
                                             </button>
                                         </form>
                                         <span class="badge bg-success ms-1" title="Results Published"><i class="bi bi-check-circle-fill"></i> Published</span>
                                         <?php endif; ?>
                                     </td>
                                     <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /container-fluid -->
    </div><!-- /#main-content -->

    <?php include '../includes/footer.php'; ?>

</div><!-- /#content -->

<script>
$(document).ready(function () {
    $('#marksTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        order: [[0, 'asc']]
    });
});
</script>