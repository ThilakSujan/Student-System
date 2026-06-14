<?php
$file = 'c:\xampp\htdocs\student_system\admin\approvals.php';
$content = file_get_contents($file);

$search_php = <<<'PHP'
// ── Fetch users ───────────────────────────────────────────────────────
if ($filterStatus === 'all') {
    $stmt  = $pdo->query("SELECT u.*, ab.username AS approved_by_name, rb.username AS rejected_by_name
                           FROM users u
                           LEFT JOIN users ab ON ab.id = u.approved_by
                           LEFT JOIN users rb ON rb.id = u.rejected_by
                           ORDER BY u.created_at DESC");
} else {
    $stmt  = $pdo->prepare("SELECT u.*, ab.username AS approved_by_name, rb.username AS rejected_by_name
                             FROM users u
                             LEFT JOIN users ab ON ab.id = u.approved_by
                             LEFT JOIN users rb ON rb.id = u.rejected_by
                             WHERE u.account_status = :s ORDER BY u.created_at DESC");
    $stmt->execute([':s' => $filterStatus]);
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
PHP;

$replace_php = <<<'PHP'
// ── Fetch users with filters ──────────────────────────────────────────
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$role_filter = $_GET['user_role'] ?? '';

$where = [];
$params = [];
if ($filterStatus !== 'all') {
    $where[] = "u.account_status = :s";
    $params[':s'] = $filterStatus;
}
if ($from_date) {
    $where[] = "DATE(u.created_at) >= :from_date";
    $params[':from_date'] = $from_date;
}
if ($to_date) {
    $where[] = "DATE(u.created_at) <= :to_date";
    $params[':to_date'] = $to_date;
}
if ($role_filter) {
    $where[] = "u.role = :role";
    $params[':role'] = $role_filter;
}

$where_sql = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";

$stmt  = $pdo->prepare("SELECT u.*, ab.username AS approved_by_name, rb.username AS rejected_by_name
                         FROM users u
                         LEFT JOIN users ab ON ab.id = u.approved_by
                         LEFT JOIN users rb ON rb.id = u.rejected_by
                         $where_sql ORDER BY u.created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
PHP;

$content = str_replace($search_php, $replace_php, $content);

$search_html = <<<'HTML'
<!-- Status filter tabs -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small fw-semibold me-2">Filter:</span>
            <?php foreach (['Pending','Approved','Rejected','Suspended','all'] as $f): ?>
            <a href="?status=<?= $f ?>"
               class="btn btn-sm <?= $filterStatus === $f ? 'btn-dark' : 'btn-outline-secondary' ?>">
                <?= $f === 'all' ? 'All Users' : $f ?>
                <?php if ($f === 'Pending' && (int)$stats['pending'] > 0): ?>
                    <span class="badge bg-danger ms-1"><?= (int)$stats['pending'] ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
HTML;

$replace_html = <<<'HTML'
<!-- Advanced Report Filters -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-light fw-bold">
        <i class="bi bi-funnel"></i> Report Filters
    </div>
    <div class="card-body">
        <form method="GET" action="approvals.php">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label" style="font-size:13px">Registered From</label>
                    <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:13px">Registered To</label>
                    <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size:13px">User Role</label>
                    <select name="user_role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="staff" <?= $role_filter === 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Student</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size:13px">Account Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="Approved" <?= $filterStatus === 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Pending" <?= $filterStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Suspended" <?= $filterStatus === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="Rejected" <?= $filterStatus === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2 mt-3 d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                    <a href="approvals.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                </div>
            </div>
        </form>
    </div>
</div>
HTML;

$content = str_replace($search_html, $replace_html, $content);

file_put_contents('c:\xampp\htdocs\student_system\fix_approvals.php', '<?php file_put_contents("' . addslashes($file) . '", ' . var_export($content, true) . '); echo "Done approvals"; ?>');
