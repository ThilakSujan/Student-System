<?php
session_start();
require_once '../includes/auth.php';
require_login();
require_once '../config/db.php';

$page_title = 'My Notifications';
$role = $_SESSION['role'];

// Only Students and Staff see "My Notifications" in this way (Admin sees "Manage Notifications" dashboard)
if ($role === 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch active notifications
$target = $role === 'student' ? "'Student','Both'" : "'Staff','Both'";
$query = "SELECT n.*, u.role as sender_role, u.username as sender_name 
          FROM notifications n 
          JOIN users u ON n.created_by = u.id 
          WHERE n.status='Active' 
          AND n.expiry_date >= CURDATE() 
          AND n.target_audience IN ($target) 
          ORDER BY n.created_at DESC";

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

<style>
/* Notification Cards */
.notif-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left: 4px solid transparent;
}
.notif-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important;
}
.notif-card-admin {
    border-left-color: #6366f1; /* Indigo for Management */
}
.notif-card-staff {
    border-left-color: #10b981; /* Emerald for Staff */
}
.notif-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.notif-icon-admin {
    background: rgba(99,102,241,0.1);
    color: #6366f1;
}
.notif-icon-staff {
    background: rgba(16,185,129,0.1);
    color: #10b981;
}
</style>

<div id="content">
    <?php require '../includes/navbar.php'; ?>
    <div id="main-content">
        <div class="container-fluid" style="max-width: 900px; margin: 0 auto;">
            
            <div class="content-header mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="bi bi-bell-fill" style="color: #6366f1;"></i> My Notifications</h2>
                    <p class="text-muted mb-0" style="font-size:14px">Stay updated with the latest announcements.</p>
                </div>
            </div>

            <?php if (empty($notifications)): ?>
                <div class="card shadow-sm border-0 py-5 text-center">
                    <div class="card-body">
                        <div style="width:80px;height:80px;border-radius:50%;background:#f8fafc;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                            <i class="bi bi-bell-slash" style="font-size:36px;color:#cbd5e1;"></i>
                        </div>
                        <h4 class="text-muted mb-2">All Caught Up!</h4>
                        <p class="text-muted mb-0">You have no new notifications at the moment.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($notifications as $n): 
                        $is_admin = $n['sender_role'] === 'admin';
                        $sender_display = $is_admin ? 'Management' : 'Staff - ' . htmlspecialchars($n['sender_name']);
                        $card_class = $is_admin ? 'notif-card-admin' : 'notif-card-staff';
                        $icon_class = $is_admin ? 'notif-icon-admin' : 'notif-icon-staff';
                        $icon = $is_admin ? 'bi-shield-fill-check' : 'bi-person-fill';
                    ?>
                        <div class="card shadow-sm border-0 notif-card <?= $card_class ?>">
                            <div class="card-body p-4 d-flex gap-4">
                                <div class="<?= $icon_class ?> notif-icon-wrap">
                                    <i class="bi <?= $icon ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="mb-0 fw-bold text-dark" style="font-size: 1.1rem;"><?= htmlspecialchars($n['title']) ?></h5>
                                        <span class="text-muted" style="font-size: 12px; font-weight: 500;">
                                            <i class="bi bi-clock me-1"></i><?= date('d M Y', strtotime($n['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div class="text-muted mb-3" style="font-size: 14.5px; line-height: 1.6;">
                                        <?= nl2br(htmlspecialchars($n['message'])) ?>
                                    </div>
                                    <div class="d-flex align-items-center" style="font-size: 13px;">
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            <i class="bi bi-person-fill me-1"></i>From: <?= $sender_display ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <?php require '../includes/footer.php'; ?>
</div>
