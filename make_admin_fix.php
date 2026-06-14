<?php
$file = 'c:\xampp\htdocs\student_system\admin\admin_panel.php';
$content = file_get_contents($file);

$search_php = <<<'PHP'
// FETCH ALL USERS
$stmt  = $pdo->query("SELECT * FROM users ORDER BY created_at ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
PHP;

$replace_php = <<<'PHP'
// FETCH ALL USERS WITH FILTERS
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$role_filter = $_GET['user_role'] ?? '';
$account_status = $_GET['account_status'] ?? '';

$where = ["1=1"];
$params = [];

if ($from_date) {
    $where[] = "DATE(created_at) >= :from_date";
    $params[':from_date'] = $from_date;
}
if ($to_date) {
    $where[] = "DATE(created_at) <= :to_date";
    $params[':to_date'] = $to_date;
}
if ($role_filter) {
    $where[] = "role = :role";
    $params[':role'] = $role_filter;
}
if ($account_status) {
    $where[] = "account_status = :account_status";
    $params[':account_status'] = $account_status;
}

$query = "SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY created_at ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary Stats
$total_users = count($users);
$admin_count = $staff_count = $student_count = 0;
foreach ($users as $u) {
    if ($u['role'] === 'admin') $admin_count++;
    elseif ($u['role'] === 'staff') $staff_count++;
    elseif ($u['role'] === 'student') $student_count++;
}
PHP;

$content = str_replace($search_php, $replace_php, $content);

// Update table headers to include Account Status
$content = str_replace('<th>Registered At</th>', "<th>Account Status</th>\n                        <th>Registered At</th>", $content);

// Update table body to include Account Status
$search_row = <<<'HTML'
                                <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
HTML;

$replace_row = <<<'HTML'
                                <td>
                                    <?php
                                    $astat = $u['account_status'] ?? 'Approved'; // Default if null
                                    $bdg = 'bg-secondary';
                                    if ($astat === 'Approved') $bdg = 'bg-success';
                                    elseif ($astat === 'Pending') $bdg = 'bg-warning text-dark';
                                    elseif ($astat === 'Suspended') $bdg = 'bg-danger';
                                    elseif ($astat === 'Rejected') $bdg = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $bdg ?>"><?= htmlspecialchars($astat) ?></span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
HTML;
$content = str_replace($search_row, $replace_row, $content);

// Now the filters and summary
$search_html = <<<'HTML'
    <div class="card shadow-sm">
HTML;

$replace_html = <<<'HTML'
    <!-- Advanced Report Filters -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-light fw-bold">
            <i class="bi bi-funnel"></i> Report Filters
        </div>
        <div class="card-body">
            <form method="GET" action="admin_panel.php">
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
                        <select name="account_status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Approved" <?= $account_status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Pending" <?= $account_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Suspended" <?= $account_status === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                            <option value="Rejected" <?= $account_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2 mt-3 d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                        <a href="admin_panel.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center text-bg-primary shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Total Users</h6>
                    <h3 class="mb-0"><?= $total_users ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-bg-warning text-dark shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Admins</h6>
                    <h3 class="mb-0"><?= $admin_count ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-bg-info text-dark shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Staff</h6>
                    <h3 class="mb-0"><?= $staff_count ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-bg-success shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Students</h6>
                    <h3 class="mb-0"><?= $student_count ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
HTML;

// Only replace first occurrence
$content = preg_replace('/' . preg_quote($search_html, '/') . '/', $replace_html, $content, 1);

file_put_contents('c:\xampp\htdocs\student_system\fix_admin.php', '<?php file_put_contents("' . addslashes($file) . '", ' . var_export($content, true) . '); echo "Done admin"; ?>');
