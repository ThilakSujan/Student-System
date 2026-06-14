<?php
$file = 'c:\xampp\htdocs\student_system\attendance\index.php';
$content = file_get_contents($file);

// Add $status and $department variables to PHP header
$search_php = <<<'PHP'
    // Filter by date range
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    $ares = $mysqli->query(
        "SELECT a.id, a.date, a.status, s.student_name, s.department
         FROM attendance a
         JOIN students s ON s.id=a.student_id
         WHERE a.date BETWEEN '$from' AND '$to'
         ORDER BY a.date DESC, s.student_name ASC"
    );
PHP;

$replace_php = <<<'PHP'
    // Filter by date range and fields
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');
    $status_filter = $_GET['status'] ?? '';
    $dept_filter = $_GET['department'] ?? '';

    $where = ["a.date BETWEEN '" . $mysqli->real_escape_string($from) . "' AND '" . $mysqli->real_escape_string($to) . "'"];
    if ($status_filter) $where[] = "a.status = '" . $mysqli->real_escape_string($status_filter) . "'";
    if ($dept_filter) $where[] = "s.department = '" . $mysqli->real_escape_string($dept_filter) . "'";

    $query = "SELECT a.id, a.date, a.status, s.student_name, s.department
         FROM attendance a
         JOIN students s ON s.id=a.student_id
         WHERE " . implode(' AND ', $where) . "
         ORDER BY a.date DESC, s.student_name ASC";
    
    $ares = $mysqli->query($query);
    
    // Fetch departments for filter
    $deps_res = $mysqli->query("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != ''");
    $departments = [];
    if ($deps_res) {
        while ($d = $deps_res->fetch_assoc()) {
            $departments[] = $d['department'];
        }
    }
PHP;

$content = str_replace($search_php, $replace_php, $content);

// Update HTML filter form
$search_html = <<<'HTML'
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:13px">From Date</label>
                    <input type="date" name="from" class="form-control"
                           value="<?= htmlspecialchars($from) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:13px">To Date</label>
                    <input type="date" name="to" class="form-control"
                           value="<?= htmlspecialchars($to) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                </div>
            </form>
HTML;

$replace_html = <<<'HTML'
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold" style="font-size:13px">From Date</label>
                    <input type="date" name="from" class="form-control"
                           value="<?= htmlspecialchars($from) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold" style="font-size:13px">To Date</label>
                    <input type="date" name="to" class="form-control"
                           value="<?= htmlspecialchars($to) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:13px">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="Present" <?= (isset($status_filter) && $status_filter==='Present') ? 'selected' : '' ?>>Present</option>
                        <option value="Absent" <?= (isset($status_filter) && $status_filter==='Absent') ? 'selected' : '' ?>>Absent</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:13px">Department</label>
                    <select name="department" class="form-select">
                        <option value="">All</option>
                        <?php if(isset($departments)) foreach($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d) ?>" <?= (isset($dept_filter) && $dept_filter===$d) ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mt-3 d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Generate Report
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                    </a>
                </div>
            </form>
HTML;

$content = str_replace($search_html, $replace_html, $content);

file_put_contents('c:\xampp\htdocs\student_system\fix_attendance.php', '<?php file_put_contents("' . addslashes($file) . '", ' . var_export($content, true) . '); echo "Done"; ?>');
