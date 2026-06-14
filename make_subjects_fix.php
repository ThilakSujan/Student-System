<?php
$file = 'c:\xampp\htdocs\student_system\subjects\index.php';
$content = file_get_contents($file);

$search_php = <<<'PHP'
// Fetch all subjects
$subjects_result = $mysqli->query("SELECT * FROM subjects ORDER BY created_at DESC");
PHP;

$replace_php = <<<'PHP'
// Fetch all subjects with Filters
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$status = $_GET['status'] ?? '';

$where = ["1=1"];
if ($from_date) $where[] = "DATE(created_at) >= '" . $mysqli->real_escape_string($from_date) . "'";
if ($to_date) $where[] = "DATE(created_at) <= '" . $mysqli->real_escape_string($to_date) . "'";
if ($status) $where[] = "status = '" . $mysqli->real_escape_string($status) . "'";

$query = "SELECT * FROM subjects WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
$subjects_result = $mysqli->query($query);

// Summary Stats
$all_subjects = [];
$total_sub = $active_sub = $inactive_sub = 0;
while ($row = $subjects_result->fetch_assoc()) {
    $all_subjects[] = $row;
    $total_sub++;
    if ($row['status'] === 'Active') $active_sub++;
    else $inactive_sub++;
}
// reset pointer for the table loop or just use the array
PHP;

$content = str_replace($search_php, $replace_php, $content);

// We need to replace `while ($subject = $subjects_result->fetch_assoc()):` with `foreach ($all_subjects as $subject):`
$content = str_replace('while ($subject = $subjects_result->fetch_assoc()):', 'foreach ($all_subjects as $subject):', $content);
$content = str_replace('<?php endwhile; ?>', '<?php endforeach; ?>', $content);
$content = str_replace('if ($subjects_result->num_rows === 0):', 'if (empty($all_subjects)):', $content);

$search_html = <<<'HTML'
        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> alert-dismissible fade show mt-3" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mt-4">
HTML;

$replace_html = <<<'HTML'
        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> alert-dismissible fade show mt-3" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Advanced Report Filters -->
        <div class="card shadow-sm mb-4 mt-4 border-0">
            <div class="card-header bg-light fw-bold">
                <i class="bi bi-funnel"></i> Report Filters
            </div>
            <div class="card-body">
                <form method="GET" action="index.php">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Created From</label>
                            <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Created To</label>
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
                        <div class="col-md-3 mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center text-bg-primary shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-1">Total Subjects</h6>
                        <h3 class="mb-0"><?= $total_sub ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center text-bg-success shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-1">Active</h6>
                        <h3 class="mb-0"><?= $active_sub ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center text-bg-secondary shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-1">Inactive</h6>
                        <h3 class="mb-0"><?= $inactive_sub ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4 border-0 shadow-sm">
HTML;

$content = str_replace($search_html, $replace_html, $content);

file_put_contents('c:\xampp\htdocs\student_system\fix_subjects.php', '<?php file_put_contents("' . addslashes($file) . '", ' . var_export($content, true) . '); echo "Done subjects"; ?>');
