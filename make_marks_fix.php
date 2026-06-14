<?php
$file = 'c:\xampp\htdocs\student_system\marks\index.php';
$content = file_get_contents($file);

$search_php = <<<'PHP'
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
PHP;

$replace_php = <<<'PHP'
// Build query based on role
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$date_filter = "";
if ($from_date) $date_filter .= " AND DATE(m.created_at) >= '" . $mysqli->real_escape_string($from_date) . "'";
if ($to_date) $date_filter .= " AND DATE(m.created_at) <= '" . $mysqli->real_escape_string($to_date) . "'";

if ($user_role === 'student') {
    $student_id = null;
    if (isset($_SESSION['student_id'])) {
        $student_id = $_SESSION['student_id'];
    } else {
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
                        WHERE m.student_id = $student_id $date_filter
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
                    WHERE 1=1 $date_filter
                    ORDER BY s.student_name ASC";
}
PHP;

$content = str_replace($search_php, $replace_php, $content);

$search_html = <<<'HTML'
            <!-- Marks table -->
            <div class="card">
HTML;

$replace_html = <<<'HTML'
            <?php
            $total_students_graded = count($student_summaries);
            $overall_pct = $total_students_graded > 0 ? array_sum(array_column($student_summaries, 'percentage')) / $total_students_graded : 0;
            ?>

            <!-- Advanced Report Filters -->
            <?php if (in_array($user_role, ['admin','staff'])): ?>
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-funnel"></i> Report Filters
                </div>
                <div class="card-body">
                    <form method="GET" action="index.php">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label" style="font-size:13px">Recorded From</label>
                                <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-size:13px">Recorded To</label>
                                <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                            </div>
                            <div class="col-md-4 mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                                <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Report Summary -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card text-center text-bg-primary shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-uppercase mb-1">Students Graded</h6>
                            <h3 class="mb-0"><?= $total_students_graded ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-center text-bg-success shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-uppercase mb-1">Average Percentage</h6>
                            <h3 class="mb-0"><?= number_format($overall_pct, 2) ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Marks table -->
            <div class="card border-0 shadow-sm">
HTML;

$content = str_replace($search_html, $replace_html, $content);

file_put_contents('c:\xampp\htdocs\student_system\fix_marks.php', '<?php file_put_contents("' . addslashes($file) . '", ' . var_export($content, true) . '); echo "Done marks"; ?>');
