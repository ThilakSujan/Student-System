<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

require_login();
$user_role = $_SESSION['role'];

$student_id = intval($_GET['student_id'] ?? 0);
if ($student_id <= 0) {
    header('Location: index.php');
    exit();
}

// If current user is student, ensure they can only view their own marks
if ($user_role === 'student') {
    $current_student_id = null;
    
    // Check for new student login system
    if (isset($_SESSION['student_id'])) {
        $current_student_id = $_SESSION['student_id'];
    } else {
        // Fall back to email-based lookup for legacy system
        $current_email = current_user_email();
        $res = $mysqli->query("SELECT id FROM students WHERE email = '" . $mysqli->real_escape_string($current_email) . "' LIMIT 1");
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $current_student_id = (int)$row['id'];
        }
    }
    
    if (!$current_student_id || $current_student_id !== $student_id) {
        header('Location: index.php');
        exit();
    }
}

// Fetch student info
$student_res = $mysqli->query("SELECT id, student_name, email FROM students WHERE id = $student_id LIMIT 1");
if ($student_res->num_rows === 0) {
    header('Location: index.php');
    exit();
}
$student = $student_res->fetch_assoc();

// Fetch marks for this student
$marks_res = $mysqli->query("SELECT m.*, sub.subject_code, sub.subject_name FROM marks m JOIN subjects sub ON m.subject_id = sub.id WHERE m.student_id = $student_id ORDER BY sub.subject_code ASC");

function getGrade($percentage) {
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B';
    if ($percentage >= 60) return 'C';
    if ($percentage >= 50) return 'D';
    return 'F';
}

function getGradeColor($grade) {
    switch ($grade) {
        case 'A+':
        case 'A':
            return 'success';
        case 'B':
            return 'info';
        case 'C':
            return 'warning';
        case 'D':
            return 'danger';
        default:
            return 'danger';
    }
}

$total = 0;
$count = 0;
$marks_list = [];
while ($row = $marks_res->fetch_assoc()) {
    $marks_list[] = $row;
    $total += floatval($row['marks_obtained']);
    $count++;
}

$percentage = $count > 0 ? ($total / ($count * 100)) * 100 : 0;
$grade = getGrade($percentage);

?>

<?php include '../includes/header.php'; ?>
<div id="content">
    <?php include '../includes/navbar.php'; ?>
    <div class="container-fluid p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="bi bi-card-list"></i> Marks for <?php echo htmlspecialchars($student['student_name']); ?></h2>
                <p class="text-muted">Email: <?php echo htmlspecialchars($student['email']); ?></p>
            </div>
            <div class="col-md-4 text-end">
                <a href="index.php" class="btn btn-secondary">Back</a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="mb-3">
                    <strong>Total:</strong> <?php echo $total; ?> / <?php echo ($count * 100); ?>
                    &nbsp;|&nbsp;
                    <strong>Percentage:</strong> <?php echo round($percentage,2); ?>%
                    &nbsp;|&nbsp;
                    <strong>Grade:</strong> <span class="badge bg-<?php echo getGradeColor($grade); ?>"><?php echo $grade; ?></span>
                </div>

                <table id="studentMarksTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Marks Obtained</th>
                            <th>Total</th>
                            <th>Percentage</th>
                            <?php if (in_array($user_role, ['admin','staff'], true)): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($marks_list as $i => $m): ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <td><?php echo htmlspecialchars($m['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($m['subject_name']); ?></td>
                                <td><?php echo $m['marks_obtained']; ?></td>
                                <td><?php echo $m['total_marks']; ?></td>
                                <td><?php echo round(($m['marks_obtained'] / $m['total_marks']) * 100, 2); ?>%</td>
                                <?php if (in_array($user_role, ['admin','staff'], true)): ?>
                                    <td>
                                        <a href="edit.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a>
                                        <button class="btn btn-sm btn-danger" onclick="deleteMark(<?php echo $m['id']; ?>)"><i class="bi bi-trash"></i></button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (empty($marks_list)): ?>
                    <p class="text-muted">No marks recorded for this student.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#studentMarksTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50],[5,10,25,50]]
    });
});

function deleteMark(id) {
    if (!confirm('Delete this mark record?')) return;
    fetch('delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    }).then(r=>r.json()).then(data=>{
        if (data.success) location.reload(); else alert(data.message || 'Error');
    });
}
</script>
</div>

<?php include '../includes/footer.php'; ?>
