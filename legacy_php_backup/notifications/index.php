<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin', 'staff']);
require_once '../config/db.php';

$page_title = 'Manage Notifications';
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Handle delete/status toggle
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    // Ensure permission
    $perm_check = "";
    if ($role === 'staff') {
        $perm_check = " AND created_by = $user_id";
    }
    
    if ($action === 'delete') {
        $mysqli->query("DELETE FROM notifications WHERE id = $id" . $perm_check);
        $_SESSION['success'] = "Notification deleted successfully.";
    } elseif ($action === 'toggle') {
        $mysqli->query("UPDATE notifications SET status = IF(status='Active', 'Inactive', 'Active') WHERE id = $id" . $perm_check);
        $_SESSION['success'] = "Notification status updated.";
    }
    header("Location: index.php");
    exit();
}

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
$notifications = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<div id="content">
    <?php require '../includes/navbar.php'; ?>
    <div id="main-content">
        <div class="container-fluid">
            
            <div class="content-header mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="bi bi-bell-fill"></i> Manage Notifications</h2>
                    <p class="text-muted mb-0" style="font-size:13px">Create and manage alerts for students and staff.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <?php if ($role === 'admin'): ?>
                    <button onclick="exportTable('table', 'Notifications Report', 'excel')" class="btn btn-success btn-sm" title="Export to Excel">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
                    <button onclick="exportTable('table', 'Notifications Report', 'pdf')" class="btn btn-danger btn-sm" title="Export to PDF">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
                    <?php endif; ?>
                    <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Create Notification</a>
                </div>
            </div>

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
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Message Preview</th>
                                    <th>Target</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                    <?php if ($role === 'admin'): ?>
                                    <th>Created By</th>
                                    <?php endif; ?>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($notifications)): ?>
                                    <tr>
                                        <td colspan="<?= $role === 'admin' ? '7' : '6' ?>" class="text-center py-4 text-muted">
                                            No notifications found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($notifications as $n): ?>
                                    <tr>
                                        <td class="fw-semibold text-truncate" style="max-width: 200px;"><?= htmlspecialchars($n['title']) ?></td>
                                        <td class="text-muted text-truncate" style="max-width: 250px; font-size:13px;">
                                            <?= htmlspecialchars($n['message']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $n['target_audience'] === 'Both' ? 'purple' : ($n['target_audience'] === 'Staff' ? 'info' : 'success') ?>">
                                                <?= $n['target_audience'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= date('d M Y', strtotime($n['expiry_date'])) ?>
                                            <?php if (strtotime($n['expiry_date']) < strtotime(date('Y-m-d'))): ?>
                                                <span class="badge bg-danger ms-1" style="font-size:10px;">Expired</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($n['status'] === 'Active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($role === 'admin'): ?>
                                        <td style="font-size:13px;">
                                            <?= htmlspecialchars($n['creator_name']) ?> <span class="text-muted">(<?= ucfirst($n['creator_role']) ?>)</span>
                                        </td>
                                        <?php endif; ?>
                                        <td class="text-end">
                                            <a href="edit.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                            <a href="index.php?action=toggle&id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-<?= $n['status'] === 'Active' ? 'warning' : 'success' ?>" title="Toggle Status">
                                                <i class="bi bi-power"></i>
                                            </a>
                                            <a href="index.php?action=delete&id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this notification?');" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php require '../includes/footer.php'; ?>
</div>
