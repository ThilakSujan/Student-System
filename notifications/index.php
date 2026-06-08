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

// Fetch notifications
$query = "SELECT n.*, u.username as creator_name, u.role as creator_role FROM notifications n JOIN users u ON n.created_by = u.id";
if ($role === 'staff') {
    $query .= " WHERE n.created_by = $user_id";
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
                <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Create Notification</a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="card">
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
