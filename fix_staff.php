<?php
$file = 'c:\xampp\htdocs\student_system\staff\staff.php';
$content = <<<'EOD'
<?php
require_once '../includes/auth.php';
require_role(['admin']);

$page_title = "Staff Management";
$currentPage = 'staff';
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db_pdo.php';

$success = "";
$error = "";

// DELETE STAFF
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'staff'");
            $stmt->execute([':id' => $id]);
            $success = "Staff member deleted successfully.";
        } catch (Exception $e) {
            $error = "Failed to delete staff member.";
        }
    }
}

// FILTER LOGIC
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$account_status = $_GET['account_status'] ?? '';

$where = ["role = 'staff'"];
$params = [];

if ($from_date) {
    $where[] = "DATE(created_at) >= :from_date";
    $params[':from_date'] = $from_date;
}
if ($to_date) {
    $where[] = "DATE(created_at) <= :to_date";
    $params[':to_date'] = $to_date;
}
if ($account_status) {
    $where[] = "account_status = :account_status";
    $params[':account_status'] = $account_status;
}

$query = "SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY username ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// SUMMARY CALCULATIONS
$total_staff = count($staff_list);
$approved = $pending = $suspended = 0;
foreach ($staff_list as $s) {
    if ($s['account_status'] === 'Approved') $approved++;
    elseif ($s['account_status'] === 'Pending') $pending++;
    elseif ($s['account_status'] === 'Suspended') $suspended++;
}
?>

<div id="content">

    <?php require '../includes/navbar.php'; ?>

    <div id="main-content">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Staff Management</h4>
                <small class="text-muted">Manage staff users and their credentials</small>
            </div>
            <a href="staff_add.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Add Staff
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Advanced Report Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light fw-bold">
                <i class="bi bi-funnel"></i> Report Filters
            </div>
            <div class="card-body">
                <form method="GET" action="staff.php">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Registered From</label>
                            <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Registered To</label>
                            <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Account Status</label>
                            <select name="account_status" class="form-select">
                                <option value="">All</option>
                                <option value="Approved" <?php echo $account_status === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Pending" <?php echo $account_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Suspended" <?php echo $account_status === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="Rejected" <?php echo $account_status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="staff.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center text-bg-primary shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-1">Total Staff</h6>
                        <h3 class="mb-0"><?php echo $total_staff; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center text-bg-success shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-1">Approved</h6>
                        <h3 class="mb-0"><?php echo $approved; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center text-bg-warning text-dark shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-1">Pending</h6>
                        <h3 class="mb-0"><?php echo $pending; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center text-bg-danger shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-1">Suspended</h6>
                        <h3 class="mb-0"><?php echo $suspended; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-people"></i> Staff Members (<?php echo $total_staff; ?>)</strong>
                <?php if (is_admin()): ?>
                <div class="d-flex gap-2">
                    <button onclick="exportTable('table', 'Staff Report', 'excel')" class="btn btn-success btn-sm" title="Export to Excel">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
                    <button onclick="exportTable('table', 'Staff Report', 'pdf')" class="btn btn-danger btn-sm" title="Export to PDF">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($staff_list)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                        <p class="text-muted mb-0">No staff members found matching criteria.</p>
                    </div>
                <?php else: ?>
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Account Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_list as $s): ?>
                                <tr>
                                    <td><?php echo $s['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($s['username']); ?></strong>
                                        <?php if ($s['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-secondary ms-1">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($s['email']); ?></td>
                                    <td>
                                        <?php
                                        $badge = 'bg-secondary';
                                        if ($s['account_status'] == 'Approved') $badge = 'bg-success';
                                        if ($s['account_status'] == 'Pending') $badge = 'bg-warning text-dark';
                                        if ($s['account_status'] == 'Suspended') $badge = 'bg-danger';
                                        if ($s['account_status'] == 'Rejected') $badge = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($s['account_status']); ?></span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($s['created_at'])); ?></td>
                                    <td>
                                        <a href="staff_edit.php?id=<?php echo $s['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php if ($s['id'] != $_SESSION['user_id']): ?>
                                            <a href="staff.php?delete=<?php echo $s['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Delete this staff member?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

<?php
// Inject toast notification
// Handle redirect success from staff_add
if (isset($_GET['success']) && $_GET['success'] == '1' && empty($success)) {
    $success = "Staff member added successfully.";
}
if (!empty($success)) {
    echo "<script>window._toastMsg=" . json_encode($success) . ";window._toastType='success';</script>";
} elseif (!empty($error)) {
    echo "<script>window._toastMsg=" . json_encode($error) . ";window._toastType='danger';</script>";
}
?>
    <?php require '../includes/footer.php'; ?>

</div>
EOD;

file_put_contents($file, $content);
