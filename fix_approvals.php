<?php file_put_contents("c:\\xampp\\htdocs\\student_system\\admin\\approvals.php", '<?php
/**
 * Admin Approval Dashboard
 * Lists all user registrations with filters; allows approve/reject with reason.
 */
require_once \'../includes/auth.php\';
require_role([\'admin\']);

$page_title = \'Registration Approvals\';
require \'../includes/header.php\';
require \'../includes/sidebar.php\';
include \'../config/db_pdo.php\';
include \'../config/db.php\'; // $mysqli for EmailService

$success = \'\';
$error   = \'\';

// ── CSRF token ────────────────────────────────────────────────────────
if (empty($_SESSION[\'csrf_token\'])) {
    $_SESSION[\'csrf_token\'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION[\'csrf_token\'];

function logApprovalEvent(string $event, int $userId, string $performedBy, string $note = \'\'): void {
    $logDir  = __DIR__ . \'/../auth/logs\';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . \'/approval.log\';
    $ts      = date(\'Y-m-d H:i:s\');
    $ip      = $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\';
    file_put_contents($logFile, "[$ts] [$ip] $event | target_user_id=$userId | admin=$performedBy | $note\\n", FILE_APPEND | LOCK_EX);
}

// ── Handle POST actions ──────────────────────────────────────────────
if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    // CSRF validation
    if (!hash_equals($_SESSION[\'csrf_token\'] ?? \'\', $_POST[\'csrf_token\'] ?? \'\')) {
        $error = \'Invalid security token. Please refresh and try again.\';
    } else {
        $action   = $_POST[\'action\']  ?? \'\';
        $targetId = (int)($_POST[\'user_id\'] ?? 0);
        $adminId  = (int)$_SESSION[\'user_id\'];
        $adminName = $_SESSION[\'username\'];

        if ($targetId < 1) {
            $error = \'Invalid user ID.\';
        } elseif ($action === \'approve\') {
            // Fetch user info for email
            $uStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :id AND account_status = \'Pending\' LIMIT 1");
            $uStmt->execute([\':id\' => $targetId]);
            $targetUser = $uStmt->fetch(PDO::FETCH_ASSOC);

            if (!$targetUser) {
                $error = \'User not found or already processed.\';
            } else {
                $pdo->prepare(
                    "UPDATE users SET account_status=\'Approved\', approved_by=:ab, approved_at=NOW(),
                     rejected_by=NULL, rejected_at=NULL, rejection_reason=NULL WHERE id=:id"
                )->execute([\':ab\' => $adminId, \':id\' => $targetId]);

                // Send approval email
                try {
                    require_once \'../includes/email_service.php\';
                    $emailSvc = new EmailService($mysqli);
                    $emailSvc->sendApprovalEmail($targetUser[\'email\'], $targetUser[\'username\']);
                } catch (Throwable $e) {}

                logApprovalEvent(\'APPROVED\', $targetId, $adminName);
                $success = "✅ User <strong>" . htmlspecialchars($targetUser[\'username\']) . "</strong> has been approved. A notification email has been sent.";
            }

        } elseif ($action === \'reject\') {
            $reason = trim($_POST[\'rejection_reason\'] ?? \'\');

            $uStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :id LIMIT 1");
            $uStmt->execute([\':id\' => $targetId]);
            $targetUser = $uStmt->fetch(PDO::FETCH_ASSOC);

            if (!$targetUser) {
                $error = \'User not found.\';
            } else {
                $pdo->prepare(
                    "UPDATE users SET account_status=\'Rejected\', rejected_by=:rb, rejected_at=NOW(),
                     rejection_reason=:reason, approved_by=NULL, approved_at=NULL WHERE id=:id"
                )->execute([\':rb\' => $adminId, \':reason\' => $reason ?: null, \':id\' => $targetId]);

                // Send rejection email
                try {
                    require_once \'../includes/email_service.php\';
                    $emailSvc = new EmailService($mysqli);
                    $emailSvc->sendRejectionEmail($targetUser[\'email\'], $targetUser[\'username\'], $reason);
                } catch (Throwable $e) {}

                logApprovalEvent(\'REJECTED\', $targetId, $adminName, \'reason=\' . ($reason ?: \'none\'));
                $success = "❌ User <strong>" . htmlspecialchars($targetUser[\'username\']) . "</strong> has been rejected.";
            }

        } elseif ($action === \'suspend\') {
            $pdo->prepare("UPDATE users SET account_status=\'Suspended\' WHERE id=:id")
                ->execute([\':id\' => $targetId]);
            logApprovalEvent(\'SUSPENDED\', $targetId, $adminName);
            $success = "User has been suspended.";

        } elseif ($action === \'reactivate\') {
            $pdo->prepare("UPDATE users SET account_status=\'Approved\' WHERE id=:id AND id != :me")
                ->execute([\':id\' => $targetId, \':me\' => $adminId]);
            logApprovalEvent(\'REACTIVATED\', $targetId, $adminName);
            $success = "User reactivated.";
        }
    }
}

// ── Filters ───────────────────────────────────────────────────────────
$filterStatus = trim($_GET[\'status\'] ?? \'Pending\');
$validFilters = [\'Pending\', \'Approved\', \'Rejected\', \'Suspended\', \'all\'];
if (!in_array($filterStatus, $validFilters)) $filterStatus = \'Pending\';

// ── Stats ─────────────────────────────────────────────────────────────
$statsRes = $pdo->query(
    "SELECT
        SUM(account_status=\'Pending\')   AS pending,
        SUM(account_status=\'Approved\')  AS approved,
        SUM(account_status=\'Rejected\')  AS rejected,
        SUM(account_status=\'Suspended\') AS suspended,
        COUNT(*) AS total
     FROM users"
);
$stats = $statsRes->fetch(PDO::FETCH_ASSOC);

// ── Fetch users with filters ──────────────────────────────────────────
$from_date = $_GET[\'from_date\'] ?? \'\';
$to_date = $_GET[\'to_date\'] ?? \'\';
$role_filter = $_GET[\'user_role\'] ?? \'\';

$where = [];
$params = [];
if ($filterStatus !== \'all\') {
    $where[] = "u.account_status = :s";
    $params[\':s\'] = $filterStatus;
}
if ($from_date) {
    $where[] = "DATE(u.created_at) >= :from_date";
    $params[\':from_date\'] = $from_date;
}
if ($to_date) {
    $where[] = "DATE(u.created_at) <= :to_date";
    $params[\':to_date\'] = $to_date;
}
if ($role_filter) {
    $where[] = "u.role = :role";
    $params[\':role\'] = $role_filter;
}

$where_sql = count($where) > 0 ? "WHERE " . implode(\' AND \', $where) : "";

$stmt  = $pdo->prepare("SELECT u.*, ab.username AS approved_by_name, rb.username AS rejected_by_name
                         FROM users u
                         LEFT JOIN users ab ON ab.id = u.approved_by
                         LEFT JOIN users rb ON rb.id = u.rejected_by
                         $where_sql ORDER BY u.created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="content">
<?php require \'../includes/navbar.php\'; ?>
<div id="main-content">
<div class="container-fluid">

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-person-check me-2 text-primary"></i>Registration Approvals</h4>
        <p class="text-muted mb-0" style="font-size:13px;">Review and manage user registration requests</p>
    </div>
    <a href="admin_panel.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-people me-1"></i>User Management
    </a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
        <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <?php
    $statCards = [
        [\'Pending\',   $stats[\'pending\'],   \'bi-hourglass-split\', \'#fffbeb\', \'#92400e\', \'Pending\'],
        [\'Approved\',  $stats[\'approved\'],  \'bi-check-circle\',    \'#f0fdf4\', \'#166534\', \'Approved\'],
        [\'Rejected\',  $stats[\'rejected\'],  \'bi-x-circle\',        \'#fef2f2\', \'#991b1b\', \'Rejected\'],
        [\'Suspended\', $stats[\'suspended\'], \'bi-slash-circle\',    \'#f8fafc\', \'#374151\', \'Suspended\'],
        [\'Total\',     $stats[\'total\'],     \'bi-people-fill\',     \'#eff6ff\', \'#1d4ed8\', \'all\'],
    ];
    foreach ($statCards as [$label, $count, $icon, $bg, $color, $filt]):
    ?>
    <div class="col-6 col-sm-4 col-xl-2">
        <a href="?status=<?= $filt ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 <?= $filterStatus === $filt ? \'border border-primary\' : \'\' ?>"
                 style="background:<?= $bg ?>;">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div style="width:40px;height:40px;border-radius:10px;background:<?= $bg ?>;filter:brightness(0.9);display:flex;align-items:center;justify-content:center;">
                        <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:18px;"></i>
                    </div>
                    <div>
                        <div style="font-size:11px;color:#6c757d;"><?= $label ?></div>
                        <div class="fw-bold fs-5" style="color:<?= $color ?>"><?= (int)$count ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

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
                        <option value="admin" <?= $role_filter === \'admin\' ? \'selected\' : \'\' ?>>Admin</option>
                        <option value="staff" <?= $role_filter === \'staff\' ? \'selected\' : \'\' ?>>Staff</option>
                        <option value="student" <?= $role_filter === \'student\' ? \'selected\' : \'\' ?>>Student</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size:13px">Account Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= $filterStatus === \'all\' ? \'selected\' : \'\' ?>>All Statuses</option>
                        <option value="Approved" <?= $filterStatus === \'Approved\' ? \'selected\' : \'\' ?>>Approved</option>
                        <option value="Pending" <?= $filterStatus === \'Pending\' ? \'selected\' : \'\' ?>>Pending</option>
                        <option value="Suspended" <?= $filterStatus === \'Suspended\' ? \'selected\' : \'\' ?>>Suspended</option>
                        <option value="Rejected" <?= $filterStatus === \'Rejected\' ? \'selected\' : \'\' ?>>Rejected</option>
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

<!-- Users Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-list-ul me-1"></i>
            <?= $filterStatus === \'all\' ? \'All Registrations\' : $filterStatus . \' Registrations\' ?>
        </strong>
        <div class="d-flex gap-2 align-items-center">
            <small class="text-muted"><?= count($users) ?> record(s)</small>
            <button onclick="exportTable(\'#approvalsTable\', \'Registration Approvals Report\', \'excel\')" class="btn btn-success btn-sm" title="Export to Excel">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
            <button onclick="exportTable(\'#approvalsTable\', \'Registration Approvals Report\', \'pdf\')" class="btn btn-danger btn-sm" title="Export to PDF">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                <p class="mb-0">No <?= $filterStatus === \'all\' ? \'\' : strtolower($filterStatus) . \' \' ?>registrations found.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0" style="font-size:13.5px;" id="approvalsTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Reviewed By</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $i => $u):
                    $isSelf = ($u[\'id\'] == $_SESSION[\'user_id\']);
                    $status = $u[\'account_status\'];
                    $statusColors = [
                        \'Pending\'   => [\'bg-warning\',  \'text-dark\'],
                        \'Approved\'  => [\'bg-success\',  \'text-white\'],
                        \'Rejected\'  => [\'bg-danger\',   \'text-white\'],
                        \'Suspended\' => [\'bg-secondary\',\'text-white\'],
                    ];
                    [$sbg, $sfg] = $statusColors[$status] ?? [\'bg-light\', \'text-dark\'];
                ?>
                <tr class="<?= $isSelf ? \'table-warning\' : \'\' ?>">
                    <td class="align-middle text-muted"><?= $i + 1 ?></td>
                    <td class="align-middle fw-semibold">
                        <?= htmlspecialchars($u[\'username\']) ?>
                        <?php if ($isSelf): ?><span class="badge bg-secondary ms-1">You</span><?php endif; ?>
                    </td>
                    <td class="align-middle"><?= htmlspecialchars($u[\'email\']) ?></td>
                    <td class="align-middle">
                        <?php
                        $roleColors = [\'admin\'=>\'warning\',\'staff\'=>\'info\',\'student\'=>\'primary\'];
                        $rc = $roleColors[$u[\'role\']] ?? \'secondary\';
                        ?>
                        <span class="badge bg-<?= $rc ?> text-dark"><?= ucfirst($u[\'role\']) ?></span>
                    </td>
                    <td class="align-middle">
                        <span class="badge <?= $sbg ?> <?= $sfg ?>"><?= $status ?></span>
                        <?php if ($status === \'Rejected\' && !empty($u[\'rejection_reason\'])): ?>
                            <i class="bi bi-info-circle text-muted ms-1"
                               title="<?= htmlspecialchars($u[\'rejection_reason\']) ?>"
                               data-bs-toggle="tooltip"></i>
                        <?php endif; ?>
                    </td>
                    <td class="align-middle text-muted"><?= date(\'d M Y\', strtotime($u[\'created_at\'])) ?></td>
                    <td class="align-middle" style="font-size:12px;">
                        <?php if ($status === \'Approved\' && $u[\'approved_by_name\']): ?>
                            <span class="text-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($u[\'approved_by_name\']) ?></span><br>
                            <span class="text-muted"><?= date(\'d M y, h:i A\', strtotime($u[\'approved_at\'])) ?></span>
                        <?php elseif ($status === \'Rejected\' && $u[\'rejected_by_name\']): ?>
                            <span class="text-danger"><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($u[\'rejected_by_name\']) ?></span><br>
                            <span class="text-muted"><?= date(\'d M y, h:i A\', strtotime($u[\'rejected_at\'])) ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="align-middle text-center">
                        <?php if (!$isSelf): ?>
                            <?php if ($status === \'Pending\'): ?>
                                <!-- Approve -->
                                <form method="POST" class="d-inline" onsubmit="return confirm(\'Approve this user?\')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="action"  value="approve">
                                    <input type="hidden" name="user_id" value="<?= $u[\'id\'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-check2-circle me-1"></i>Approve
                                    </button>
                                </form>
                                <!-- Reject (opens modal) -->
                                <button class="btn btn-danger btn-sm" onclick="openRejectModal(<?= $u[\'id\'] ?>, \'<?= htmlspecialchars($u[\'username\'], ENT_QUOTES) ?>\')">
                                    <i class="bi bi-x-circle me-1"></i>Reject
                                </button>
                            <?php elseif ($status === \'Approved\'): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm(\'Suspend this user?\')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="action"  value="suspend">
                                    <input type="hidden" name="user_id" value="<?= $u[\'id\'] ?>">
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="bi bi-slash-circle me-1"></i>Suspend
                                    </button>
                                </form>
                            <?php elseif ($status === \'Rejected\' || $status === \'Suspended\'): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm(\'Reactivate this account?\')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="action"  value="reactivate">
                                    <input type="hidden" name="user_id" value="<?= $u[\'id\'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-arrow-repeat me-1"></i>Reactivate
                                    </button>
                                </form>
                                <?php if ($status === \'Rejected\'): ?>
                                <button class="btn btn-danger btn-sm" onclick="openRejectModal(<?= $u[\'id\'] ?>, \'<?= htmlspecialchars($u[\'username\'], ENT_QUOTES) ?>\')">
                                    <i class="bi bi-pencil me-1"></i>Update Reason
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted small">— your account —</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>
</div>
<?php require \'../includes/footer.php\'; ?>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
    <div class="modal-content border-0 shadow">
      <div class="modal-header" style="background:linear-gradient(135deg,#1e293b,#334155);color:#fff;border:none;">
        <h5 class="modal-title" id="rejectModalLabel"><i class="bi bi-x-circle me-2"></i>Reject Registration</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" style="background:#f8fafc;">
        <p class="text-muted mb-3" style="font-size:14px;">
            Rejecting <strong id="rejectUsername"></strong>. Optionally provide a reason (will be included in the email to the user).
        </p>
        <form method="POST" id="rejectForm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action"   value="reject">
            <input type="hidden" name="user_id"  id="rejectUserId">
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Rejection Reason (Optional)</label>
                <textarea name="rejection_reason" class="form-control" rows="3"
                          placeholder="e.g. Duplicate account, insufficient information..."></textarea>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" onclick="return confirm(\'Reject this user?\')">
                    <i class="bi bi-x-circle me-1"></i>Confirm Rejection
                </button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Initialize tooltips
document.querySelectorAll(\'[data-bs-toggle="tooltip"]\').forEach(el => new bootstrap.Tooltip(el));

function openRejectModal(userId, username) {
    document.getElementById(\'rejectUserId\').value  = userId;
    document.getElementById(\'rejectUsername\').textContent = username;
    new bootstrap.Modal(document.getElementById(\'rejectModal\')).show();
}
</script>
'); echo "Done approvals"; ?>