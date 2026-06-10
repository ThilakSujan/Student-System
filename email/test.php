<?php
/**
 * SMTP Test Email Sender
 * Admin only — sends a test email to verify SMTP configuration is working.
 */
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';
require_once '../includes/email_service.php';

$page_title = 'Send Test Email';

$result      = null;   // null = not sent yet, true = success, false = fail
$errorDetail = '';
$testTo      = '';

// ── Handle send ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testTo      = trim($_POST['test_to'] ?? '');
    $testSubject = trim($_POST['test_subject'] ?? 'Test Email from Student Management System');

    if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
        $result      = false;
        $errorDetail = 'Invalid email address format.';
    } else {
        // Build a diagnostic test email body
        $instRes   = $mysqli->query("SELECT * FROM institute_profile LIMIT 1");
        $institute = ($instRes && $instRes->num_rows > 0) ? $instRes->fetch_assoc() : ['institute_name' => 'Student Management System'];

        $timestamp = date('d M Y, h:i:s A');
        $serverIP  = $_SERVER['SERVER_ADDR'] ?? 'localhost';
        $phpVer    = phpversion();
        $smtpHost  = EMAIL_HOST;
        $smtpPort  = EMAIL_PORT;
        $smtpEnc   = strtoupper(EMAIL_ENCRYPTION);

        $htmlBody = <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <tr>
    <td style="background:linear-gradient(135deg,#0f172a,#1e293b);border-radius:12px 12px 0 0;padding:28px 40px;text-align:center;">
      <div style="font-size:36px;margin-bottom:8px;">✅</div>
      <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">SMTP Test Successful</h1>
      <p style="color:rgba(255,255,255,0.55);margin:6px 0 0;font-size:13px;">Your email configuration is working correctly</p>
    </td>
  </tr>
  <tr><td style="height:4px;background:linear-gradient(90deg,#22c55e,#16a34a);"></td></tr>
  <tr>
    <td style="background:#fff;padding:32px 40px;">
      <p style="margin:0 0 20px;color:#374151;font-size:15px;">
        This is a test email sent from <strong>{$institute['institute_name']}</strong> to verify that the email system is configured correctly.
      </p>

      <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;margin-bottom:20px;">
        <tr><td style="padding:16px 20px;">
          <table width="100%" cellpadding="5">
            <tr>
              <td style="color:#6b7280;font-size:12px;width:140px;">Sent At</td>
              <td style="color:#111827;font-weight:600;font-size:13px;">{$timestamp}</td>
            </tr>
            <tr>
              <td style="color:#6b7280;font-size:12px;">Recipient</td>
              <td style="color:#111827;font-weight:600;font-size:13px;">{$testTo}</td>
            </tr>
            <tr>
              <td style="color:#6b7280;font-size:12px;">SMTP Server</td>
              <td style="color:#111827;font-weight:600;font-size:13px;">{$smtpHost}:{$smtpPort} ({$smtpEnc})</td>
            </tr>
            <tr>
              <td style="color:#6b7280;font-size:12px;">PHP Version</td>
              <td style="color:#111827;font-weight:600;font-size:13px;">{$phpVer}</td>
            </tr>
            <tr>
              <td style="color:#6b7280;font-size:12px;">Server</td>
              <td style="color:#111827;font-weight:600;font-size:13px;">{$serverIP}</td>
            </tr>
          </table>
        </td></tr>
      </table>

      <p style="color:#059669;font-size:14px;font-weight:600;margin:0 0 8px;">
        ✅ Everything looks good! Your email system is ready.
      </p>
      <p style="color:#6b7280;font-size:13px;margin:0;">
        All email notifications (attendance alerts, fee invoices, marks results, report cards) 
        will be delivered to recipients correctly.
      </p>
    </td>
  </tr>

  <tr>
    <td style="background:#f9fafb;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;padding:18px 40px;text-align:center;">
      <p style="color:#9ca3af;font-size:12px;margin:0;">
        {$institute['institute_name']} — Student Management System<br>
        This is an automated test message.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body></html>
HTML;

        try {
            $emailSvc = new EmailService($mysqli);
            $sent     = $emailSvc->sendEmail($testTo, $testSubject, $htmlBody, 'custom', 0);
            $result   = $sent;
            if (!$sent) {
                // Fetch the last log entry to get the error
                $logRes = $mysqli->query(
                    "SELECT error_message FROM email_logs WHERE email_type='custom' ORDER BY sent_at DESC LIMIT 1"
                );
                if ($logRes && $logRes->num_rows > 0) {
                    $errorDetail = $logRes->fetch_assoc()['error_message'] ?? 'Unknown error.';
                }
            }
        } catch (Throwable $e) {
            $result      = false;
            $errorDetail = $e->getMessage();
        }
    }
}

// ── Read current config (mask password) ──────────────────────────────
$configStatus = [
    'HOST'       => EMAIL_HOST,
    'PORT'       => EMAIL_PORT,
    'ENCRYPTION' => strtoupper(EMAIL_ENCRYPTION),
    'USERNAME'   => EMAIL_USERNAME,
    'PASSWORD'   => str_repeat('●', max(0, strlen(EMAIL_PASSWORD) - 4)) . substr(EMAIL_PASSWORD, -4),
    'FROM_NAME'  => EMAIL_FROM_NAME,
    'ENABLED'    => EMAIL_ENABLED ? 'Yes' : 'No',
];

$isConfigured = (
    EMAIL_USERNAME !== 'your_email@gmail.com' &&
    EMAIL_PASSWORD !== 'your_app_password_here' &&
    !empty(EMAIL_USERNAME) && !empty(EMAIL_PASSWORD)
);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-send-check me-2 text-warning"></i>Send Test Email</h4>
            <p class="text-muted mb-0" style="font-size:13px;">Verify your SMTP configuration is working correctly</p>
        </div>
        <div class="d-flex gap-2">
            <a href="preview.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye me-1"></i>Preview Templates
            </a>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-clock-history me-1"></i>Email Logs
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- Left: Form -->
        <div class="col-lg-6">

            <!-- Config status card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header fw-semibold d-flex align-items-center gap-2"
                     style="background:<?= $isConfigured ? '#f0fdf4' : '#fef2f2' ?>;">
                    <i class="bi bi-gear-fill text-<?= $isConfigured ? 'success' : 'danger' ?>"></i>
                    SMTP Configuration
                    <span class="ms-auto badge bg-<?= $isConfigured ? 'success' : 'danger' ?>">
                        <?= $isConfigured ? 'Configured' : 'Not Configured' ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless mb-0" style="font-size:13px;">
                        <?php foreach ($configStatus as $key => $val): ?>
                        <tr class="border-bottom">
                            <td class="ps-3 text-muted fw-semibold" style="width:130px;">EMAIL_<?= $key ?></td>
                            <td class="font-monospace text-dark"><?= htmlspecialchars($val) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>

                    <?php if (!$isConfigured): ?>
                    <div class="alert alert-warning m-3 mb-2" style="font-size:13px;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Credentials not configured.</strong>
                        Edit <a href="#" onclick="alert('Path: c:\\\\xampp\\\\htdocs\\\\student_system\\\\config\\\\email_config.php')">
                            config/email_config.php
                        </a> with your real SMTP credentials before testing.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Send form -->
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-semibold" style="background:linear-gradient(135deg,#1e293b,#334155);color:#fff;">
                    <i class="bi bi-envelope-paper me-2"></i>Send Test Email
                </div>
                <div class="card-body">

                    <?php if ($result === true): ?>
                    <div class="alert alert-success">
                        <div class="d-flex align-items-center gap-3">
                            <div style="font-size:32px;">✅</div>
                            <div>
                                <strong>Email sent successfully!</strong><br>
                                <small>Check <strong><?= htmlspecialchars($testTo) ?></strong>'s inbox (and spam folder).</small>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($result === false): ?>
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-start gap-3">
                            <div style="font-size:28px;">❌</div>
                            <div>
                                <strong>Failed to send email.</strong><br>
                                <?php if ($errorDetail): ?>
                                <div class="mt-2 p-2 bg-white rounded border" style="font-family:monospace;font-size:12px;word-break:break-all;">
                                    <?= htmlspecialchars($errorDetail) ?>
                                </div>
                                <?php endif; ?>
                                <div class="mt-2" style="font-size:13px;">
                                    Common causes:
                                    <ul class="mb-0 mt-1">
                                        <li>Wrong Gmail App Password (must be 16 chars, no spaces)</li>
                                        <li>2-Step Verification not enabled on Google Account</li>
                                        <li>Firewall blocking port <?= EMAIL_PORT ?></li>
                                        <li>EMAIL_ENABLED set to false in config</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Send Test Email To <span class="text-danger">*</span></label>
                            <input type="email" name="test_to" class="form-control"
                                   value="<?= htmlspecialchars($testTo ?: (EMAIL_USERNAME !== 'your_email@gmail.com' ? EMAIL_USERNAME : '')) ?>"
                                   placeholder="recipient@gmail.com" required>
                            <small class="text-muted">The test email will be delivered to this address.</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Subject</label>
                            <input type="text" name="test_subject" class="form-control"
                                   value="Test Email from Student Management System">
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-semibold" <?= !$isConfigured ? 'title="Configure SMTP credentials first"' : '' ?>>
                            <i class="bi bi-send me-2"></i>Send Test Email Now
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <!-- Right: Guide -->
        <div class="col-lg-6">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header fw-semibold" style="background:#f8fafc;">
                    <i class="bi bi-book me-2 text-primary"></i>Gmail Setup Guide
                </div>
                <div class="card-body" style="font-size:13px;">
                    <div class="d-flex flex-column gap-3">

                        <div class="d-flex gap-3">
                            <div style="width:28px;height:28px;background:#4f46e5;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0;font-size:13px;">1</div>
                            <div>
                                <strong>Enable 2-Step Verification</strong><br>
                                <span class="text-muted">Go to: <a href="https://myaccount.google.com/security" target="_blank">myaccount.google.com/security</a><br>
                                Turn on "2-Step Verification"</span>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <div style="width:28px;height:28px;background:#4f46e5;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0;font-size:13px;">2</div>
                            <div>
                                <strong>Generate App Password</strong><br>
                                <span class="text-muted">Still in Security → scroll down to "App Passwords"<br>
                                App: Mail → Device: Windows Computer → Generate<br>
                                <strong>Copy the 16-character code shown.</strong></span>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <div style="width:28px;height:28px;background:#4f46e5;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0;font-size:13px;">3</div>
                            <div>
                                <strong>Edit config/email_config.php</strong><br>
                                <div class="bg-dark text-success rounded p-2 mt-1 font-monospace" style="font-size:12px;">
                                    define('EMAIL_USERNAME', '<span class="text-warning">you@gmail.com</span>');<br>
                                    define('EMAIL_PASSWORD', '<span class="text-warning">abcd efgh ijkl mnop</span>');<br>
                                    define('EMAIL_FROM_EMAIL', '<span class="text-warning">you@gmail.com</span>');
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <div style="width:28px;height:28px;background:#22c55e;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0;font-size:13px;">4</div>
                            <div>
                                <strong>Come back and click "Send Test Email"</strong><br>
                                <span class="text-muted">If you see ✅, everything works. Check your inbox.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other SMTP providers -->
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-semibold" style="background:#f8fafc;font-size:13px;">
                    <i class="bi bi-server me-2 text-secondary"></i>Other SMTP Providers
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" style="font-size:12px;">
                        <thead class="table-light"><tr>
                            <th>Provider</th><th>Host</th><th>Port</th><th>Encryption</th>
                        </tr></thead>
                        <tbody>
                            <tr><td><strong>Gmail</strong></td><td class="font-monospace">smtp.gmail.com</td><td>587</td><td>TLS</td></tr>
                            <tr><td><strong>Outlook</strong></td><td class="font-monospace">smtp.office365.com</td><td>587</td><td>TLS</td></tr>
                            <tr><td><strong>Yahoo</strong></td><td class="font-monospace">smtp.mail.yahoo.com</td><td>587</td><td>TLS</td></tr>
                            <tr><td><strong>Mailtrap</strong> <span class="badge bg-info" style="font-size:10px;">Testing</span></td><td class="font-monospace">sandbox.smtp.mailtrap.io</td><td>2525</td><td>TLS</td></tr>
                        </tbody>
                    </table>
                    <div class="p-3 pt-2" style="font-size:12px;color:#6b7280;">
                        <i class="bi bi-lightbulb text-warning me-1"></i>
                        <strong>Tip:</strong> Use <a href="https://mailtrap.io" target="_blank">Mailtrap.io</a> (free) during development — 
                        it captures all emails without delivering them to real inboxes, perfect for safe testing.
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
</div>
<?php include '../includes/footer.php'; ?>
</div>
