<?php
$file = 'c:\xampp\htdocs\student_system\exam\index.php';
$content = file_get_contents($file);

$search_php = <<<'PHP'
// ── Fetch exams with joins (role-aware) ──
if ($user_role === 'student') {
    // Students only see upcoming/scheduled exams (today or future, not cancelled)
    $query = "
        SELECT es.*,
               sub.subject_name, sub.subject_code,
               c.class_name, c.section
        FROM exam_schedule es
        LEFT JOIN subjects sub ON es.subject_id = sub.id
        LEFT JOIN classes  c   ON es.class_id   = c.id
        WHERE es.status = 'Scheduled'
          AND es.exam_date >= '$today'
        ORDER BY es.exam_date ASC, es.start_time ASC
    ";
} else {
    // Admin / Staff see all exams
    $query = "
        SELECT es.*,
               sub.subject_name, sub.subject_code,
               c.class_name, c.section,
               u.username AS created_by_name
        FROM exam_schedule es
        LEFT JOIN subjects sub ON es.subject_id = sub.id
        LEFT JOIN classes  c   ON es.class_id   = c.id
        LEFT JOIN users    u   ON es.created_by = u.id
        ORDER BY es.exam_date ASC, es.start_time ASC
    ";
}
PHP;

$replace_php = <<<'PHP'
// ── Fetch exams with joins (role-aware) ──
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['exam_type'] ?? '';

$where = [];
if ($from_date) $where[] = "es.exam_date >= '" . $mysqli->real_escape_string($from_date) . "'";
if ($to_date) $where[] = "es.exam_date <= '" . $mysqli->real_escape_string($to_date) . "'";
if ($status_filter) $where[] = "es.status = '" . $mysqli->real_escape_string($status_filter) . "'";
if ($type_filter) $where[] = "es.exam_type = '" . $mysqli->real_escape_string($type_filter) . "'";

if ($user_role === 'student') {
    $where[] = "es.status = 'Scheduled'";
    $where[] = "es.exam_date >= '$today'";
    
    $where_sql = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";
    // Students only see upcoming/scheduled exams (today or future, not cancelled)
    $query = "
        SELECT es.*,
               sub.subject_name, sub.subject_code,
               c.class_name, c.section
        FROM exam_schedule es
        LEFT JOIN subjects sub ON es.subject_id = sub.id
        LEFT JOIN classes  c   ON es.class_id   = c.id
        $where_sql
        ORDER BY es.exam_date ASC, es.start_time ASC
    ";
} else {
    $where_sql = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";
    // Admin / Staff see all exams
    $query = "
        SELECT es.*,
               sub.subject_name, sub.subject_code,
               c.class_name, c.section,
               u.username AS created_by_name
        FROM exam_schedule es
        LEFT JOIN subjects sub ON es.subject_id = sub.id
        LEFT JOIN classes  c   ON es.class_id   = c.id
        LEFT JOIN users    u   ON es.created_by = u.id
        $where_sql
        ORDER BY es.exam_date ASC, es.start_time ASC
    ";
}
PHP;

$content = str_replace($search_php, $replace_php, $content);

$search_html = <<<'HTML'
            <!-- Filter Bar (admin/staff only) -->
            <?php if (in_array($user_role, ['admin','staff'])): ?>
            <div class="filter-bar">
                <span class="filter-label d-none d-sm-block"><i class="bi bi-funnel"></i> Filter:</span>
                <select id="filterStatus">
                    <option value="">All Statuses</option>
                    <option value="Scheduled">Scheduled</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <select id="filterType">
                    <option value="">All Types</option>
                    <option value="Internal">Internal</option>
                    <option value="External">External</option>
                    <option value="Practical">Practical</option>
                    <option value="Viva">Viva</option>
                    <option value="Other">Other</option>
                </select>
                <input type="date" id="filterDate" title="Filter by date">
                <button class="btn btn-sm btn-outline-secondary ms-auto" id="clearFilters" style="border-radius:8px;">
                    <i class="bi bi-arrow-counterclockwise"></i> Clear
                </button>
            </div>
            <?php endif; ?>
HTML;

$replace_html = <<<'HTML'
            <!-- Advanced Report Filters (admin/staff only) -->
            <?php if (in_array($user_role, ['admin','staff'])): ?>
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-funnel"></i> Report Filters
                </div>
                <div class="card-body">
                    <form method="GET" action="index.php">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label" style="font-size:13px">From Date</label>
                                <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-size:13px">To Date</label>
                                <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-size:13px">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="Scheduled" <?= $status_filter === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-size:13px">Exam Type</label>
                                <select name="exam_type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="Internal" <?= $type_filter === 'Internal' ? 'selected' : '' ?>>Internal</option>
                                    <option value="External" <?= $type_filter === 'External' ? 'selected' : '' ?>>External</option>
                                    <option value="Practical" <?= $type_filter === 'Practical' ? 'selected' : '' ?>>Practical</option>
                                    <option value="Viva" <?= $type_filter === 'Viva' ? 'selected' : '' ?>>Viva</option>
                                    <option value="Other" <?= $type_filter === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-2 mt-3 d-flex gap-2 w-100">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                                <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
HTML;

$content = str_replace($search_html, $replace_html, $content);

file_put_contents('c:\xampp\htdocs\student_system\fix_exam.php', '<?php file_put_contents("' . addslashes($file) . '", ' . var_export($content, true) . '); echo "Done exam"; ?>');
