<?php
$file = 'c:\xampp\htdocs\student_system\notifications\index.php';
$content = file_get_contents($file);

$search_php = <<<'PHP'
// Fetch notifications
$query = "SELECT n.*, u.username as creator_name, u.role as creator_role FROM notifications n JOIN users u ON n.created_by = u.id";
if ($role === 'staff') {
    $query .= " WHERE n.created_by = $user_id";
}
$query .= " ORDER BY n.created_at DESC";

$result = $mysqli->query($query);
PHP;

$replace_php = <<<'PHP'
// Fetch notifications with filters
$query = "SELECT n.*, u.username as creator_name, u.role as creator_role FROM notifications n JOIN users u ON n.created_by = u.id";

$where = [];
if ($role === 'staff') {
    $where[] = "n.created_by = $user_id";
}

$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$status = $_GET['status'] ?? '';
$target_audience = $_GET['target_audience'] ?? '';

if ($from_date) $where[] = "DATE(n.created_at) >= '" . $mysqli->real_escape_string($from_date) . "'";
if ($to_date) $where[] = "DATE(n.created_at) <= '" . $mysqli->real_escape_string($to_date) . "'";
if ($status) $where[] = "n.status = '" . $mysqli->real_escape_string($status) . "'";
if ($target_audience) $where[] = "n.target_audience = '" . $mysqli->real_escape_string($target_audience) . "'";

if (count($where) > 0) {
    $query .= " WHERE " . implode(' AND ', $where);
}
$query .= " ORDER BY n.created_at DESC";

$result = $mysqli->query($query);
PHP;

$content = str_replace($search_php, $replace_php, $content);

$search_html = <<<'HTML'
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="card">
HTML;

$replace_html = <<<'HTML'
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

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
                                    <option value="">All Statuses</option>
                                    <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-size:13px">Target Audience</label>
                                <select name="target_audience" class="form-select">
                                    <option value="">All</option>
                                    <option value="Students" <?= $target_audience === 'Students' ? 'selected' : '' ?>>Students</option>
                                    <option value="Staff" <?= $target_audience === 'Staff' ? 'selected' : '' ?>>Staff</option>
                                    <option value="Both" <?= $target_audience === 'Both' ? 'selected' : '' ?>>Both</option>
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

            <?php
            $total_notifs = count($notifications);
            $active_notifs = count(array_filter($notifications, fn($n) => $n['status'] === 'Active'));
            $expired_notifs = count(array_filter($notifications, fn($n) => strtotime($n['expiry_date']) < strtotime(date('Y-m-d'))));
            ?>

            <!-- Report Summary -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-center text-bg-primary shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-uppercase mb-1">Total Notifications</h6>
                            <h3 class="mb-0"><?= $total_notifs ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center text-bg-success shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-uppercase mb-1">Active</h6>
                            <h3 class="mb-0"><?= $active_notifs ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center text-bg-danger shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-uppercase mb-1">Expired</h6>
                            <h3 class="mb-0"><?= $expired_notifs ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
HTML;

$content = str_replace($search_html, $replace_html, $content);

file_put_contents('c:\xampp\htdocs\student_system\fix_notifications.php', '<?php file_put_contents("' . addslashes($file) . '", ' . var_export($content, true) . '); echo "Done notifications"; ?>');
