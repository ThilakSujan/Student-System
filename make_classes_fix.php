<?php
$file = 'c:\xampp\htdocs\student_system\classes\index.php';
$content = file_get_contents($file);

$search_php = <<<'PHP'
// ── Fetch all classes with enrolled count ───────────────
$result = $mysqli->query(
    "SELECT c.*, u.username AS teacher_name,
            COUNT(cs.student_id) AS student_count
     FROM classes c
     LEFT JOIN users u ON u.id = c.class_teacher_id
     LEFT JOIN class_students cs ON cs.class_id = c.id
     GROUP BY c.id
     ORDER BY c.created_at DESC"
);
$classes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
PHP;

$replace_php = <<<'PHP'
// ── Fetch all classes with enrolled count & Filters ─────
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$status = $_GET['status'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';

$where = ["1=1"];
if ($from_date) $where[] = "DATE(c.created_at) >= '" . $mysqli->real_escape_string($from_date) . "'";
if ($to_date) $where[] = "DATE(c.created_at) <= '" . $mysqli->real_escape_string($to_date) . "'";
if ($status) $where[] = "c.status = '" . $mysqli->real_escape_string($status) . "'";
if ($academic_year) $where[] = "c.academic_year = '" . $mysqli->real_escape_string($academic_year) . "'";

$result = $mysqli->query(
    "SELECT c.*, u.username AS teacher_name,
            COUNT(cs.student_id) AS student_count
     FROM classes c
     LEFT JOIN users u ON u.id = c.class_teacher_id
     LEFT JOIN class_students cs ON cs.class_id = c.id
     WHERE " . implode(' AND ', $where) . "
     GROUP BY c.id
     ORDER BY c.created_at DESC"
);
$classes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$ay_res = $mysqli->query("SELECT DISTINCT academic_year FROM classes WHERE academic_year IS NOT NULL AND academic_year != ''");
$academic_years = [];
if ($ay_res) while($r = $ay_res->fetch_assoc()) $academic_years[] = $r['academic_year'];
PHP;

$content = str_replace($search_php, $replace_php, $content);

$search_html = <<<'HTML'
    <!-- Classes table -->
    <div class="card shadow-sm border-0">
HTML;

$replace_html = <<<'HTML'
    <!-- Advanced Report Filters -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-light fw-bold">
            <i class="bi bi-funnel"></i> Report Filters
        </div>
        <div class="card-body">
            <form method="GET" action="index.php">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:13px">Created From</label>
                        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:13px">Created To</label>
                        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:13px">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:13px">Academic Year</label>
                        <select name="academic_year" class="form-select">
                            <option value="">All</option>
                            <?php foreach($academic_years as $ay): ?>
                            <option value="<?= htmlspecialchars($ay) ?>" <?= $academic_year === $ay ? 'selected' : '' ?>><?= htmlspecialchars($ay) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2 w-100 mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Classes table -->
    <div class="card shadow-sm border-0">
HTML;

$content = str_replace($search_html, $replace_html, $content);

file_put_contents('c:\xampp\htdocs\student_system\fix_classes.php', '<?php file_put_contents("' . addslashes($file) . '", ' . var_export($content, true) . '); echo "Done classes"; ?>');
