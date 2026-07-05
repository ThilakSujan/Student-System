<?php
/**
 * Forgot Password — AJAX Endpoint
 * Handles all 3 steps: send_otp, verify_otp, reset_password
 * For Admin/Staff users only (users table). Students are excluded.
 */

session_start();
header('Content-Type: application/json');

// ── Bootstrap ────────────────────────────────────────────────────────
require_once '../config/db_pdo.php';   // $pdo
require_once '../config/db.php';       // $mysqli (for EmailService)
require_once '../includes/email_service.php';
if (!defined('EMAIL_HOST')) {
    require_once '../config/email_config.php';
}

// ── Helpers ──────────────────────────────────────────────────────────
function json_ok(array $data = []): void {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function json_err(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function log_reset_event(string $event, string $email = '', string $note = ''): void {
    $logDir  = __DIR__ . '/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . '/password_reset.log';
    $ts      = date('Y-m-d H:i:s');
    $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    file_put_contents($logFile, "[$ts] [$ip] $event | email=$email | $note\n", FILE_APPEND | LOCK_EX);
}

// ── Route ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_err('Method not allowed', 405);
}

$action = trim($_POST['action'] ?? '');

match ($action) {
    'send_otp'       => handleSendOtp($pdo, $mysqli),
    'verify_otp'     => handleVerifyOtp($pdo),
    'reset_password' => handleResetPassword($pdo),
    default          => json_err('Invalid action', 400),
};


// ════════════════════════════════════════════════════════════════════
//  STEP 1 — Send OTP
// ════════════════════════════════════════════════════════════════════
function handleSendOtp(PDO $pdo, mysqli $mysqli): void {
    $email = trim($_POST['email'] ?? '');

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_err('Please enter a valid email address.');
    }

    // Always show generic message — prevents user enumeration
    $genericMsg = 'If an account exists for this email, an OTP has been sent.';

    // Lookup user (Admin/Staff only)
    $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no user, still respond generically and log
    if (!$user) {
        log_reset_event('OTP_REQUESTED_NONEXISTENT', $email, 'email not found');
        json_ok(['message' => $genericMsg]);
    }

    // Rate limiting: max 3 OTP sends per 15 minutes
    $stmt2 = $pdo->prepare("SELECT otp_send_count, otp_last_sent FROM users WHERE id = :id");
    $stmt2->execute([':id' => $user['id']]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $lastSent  = $row['otp_last_sent'] ? strtotime($row['otp_last_sent']) : 0;
        $sendCount = (int)$row['otp_send_count'];
        $window    = 15 * 60; // 15 minutes

        if ($lastSent && (time() - $lastSent) < $window && $sendCount >= 3) {
            log_reset_event('OTP_RATE_LIMITED', $email);
            // Still show generic message, not "rate limited" to prevent enumeration
            json_ok(['message' => $genericMsg]);
        }

        // Reset counter if window has passed
        if ($lastSent && (time() - $lastSent) >= $window) {
            $sendCount = 0;
        }
    }

    // Generate 6-digit OTP
    $otp     = (string)random_int(100000, 999999);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $expiry  = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    // Save OTP to DB — invalidates any previous OTP
    $stmt3 = $pdo->prepare(
        "UPDATE users SET
            reset_otp      = :otp_hash,
            otp_expiry     = :expiry,
            otp_verified   = 0,
            otp_attempts   = 0,
            otp_last_sent  = NOW(),
            otp_send_count = otp_send_count + 1
         WHERE id = :id"
    );
    $stmt3->execute([
        ':otp_hash' => $otpHash,
        ':expiry'   => $expiry,
        ':id'       => $user['id'],
    ]);

    // Send email
    $emailSent = sendOtpEmail($mysqli, $email, $otp);

    log_reset_event('OTP_SENT', $email, 'email_sent=' . ($emailSent ? 'yes' : 'no'));

    json_ok(['message' => $genericMsg]);
}


// ════════════════════════════════════════════════════════════════════
//  STEP 2 — Verify OTP
// ════════════════════════════════════════════════════════════════════
function handleVerifyOtp(PDO $pdo): void {
    $email = trim($_POST['email'] ?? '');
    $otp   = trim($_POST['otp'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($otp)) {
        json_err('Invalid request.');
    }

    $stmt = $pdo->prepare(
        "SELECT id, reset_otp, otp_expiry, otp_attempts, otp_verified
         FROM users WHERE email = :email LIMIT 1"
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['reset_otp']) {
        log_reset_event('OTP_VERIFY_FAILED', $email, 'no otp on record');
        json_err('Invalid or expired OTP. Please request a new one.');
    }

    // Max 5 attempts
    if ((int)$user['otp_attempts'] >= 5) {
        log_reset_event('OTP_VERIFY_BLOCKED', $email, 'max attempts reached');
        json_err('Too many failed attempts. Please request a new OTP.');
    }

    // Check expiry
    if (!$user['otp_expiry'] || strtotime($user['otp_expiry']) < time()) {
        log_reset_event('OTP_VERIFY_EXPIRED', $email);
        json_err('OTP has expired. Please request a new one.');
    }

    // Verify OTP
    if (!password_verify($otp, $user['reset_otp'])) {
        // Increment failed attempt counter
        $pdo->prepare("UPDATE users SET otp_attempts = otp_attempts + 1 WHERE id = :id")
            ->execute([':id' => $user['id']]);
        log_reset_event('OTP_VERIFY_WRONG', $email, 'attempt=' . ((int)$user['otp_attempts'] + 1));
        json_err('Incorrect OTP. Please try again.');
    }

    // Mark as verified
    $pdo->prepare("UPDATE users SET otp_verified = 1 WHERE id = :id")
        ->execute([':id' => $user['id']]);

    // Store a session token to authorize the reset step
    $_SESSION['fp_user_id']    = $user['id'];
    $_SESSION['fp_email']      = $email;
    $_SESSION['fp_authorized'] = true;
    $_SESSION['fp_granted_at'] = time();

    log_reset_event('OTP_VERIFIED', $email);
    json_ok(['message' => 'OTP verified successfully.']);
}


// ════════════════════════════════════════════════════════════════════
//  STEP 3 — Reset Password
// ════════════════════════════════════════════════════════════════════
function handleResetPassword(PDO $pdo): void {
    // Verify session authorization
    if (
        empty($_SESSION['fp_authorized']) ||
        empty($_SESSION['fp_user_id'])    ||
        empty($_SESSION['fp_granted_at']) ||
        (time() - (int)$_SESSION['fp_granted_at']) > 900 // 15-min window
    ) {
        log_reset_event('RESET_UNAUTHORIZED', $_SESSION['fp_email'] ?? '');
        json_err('Session expired. Please start the process again.', 403);
    }

    $userId = (int)$_SESSION['fp_user_id'];
    $email  = $_SESSION['fp_email'];

    $password        = $_POST['password']         ?? '';
    $confirmPassword = $_POST['confirm_password']  ?? '';

    // Validation
    if (strlen($password) < 8) {
        json_err('Password must be at least 8 characters long.');
    }
    if ($password !== $confirmPassword) {
        json_err('Passwords do not match.');
    }

    // Double-check OTP was verified in DB
    $stmt = $pdo->prepare("SELECT otp_verified FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !(int)$user['otp_verified']) {
        log_reset_event('RESET_OTP_NOT_VERIFIED', $email);
        json_err('OTP verification required.', 403);
    }

    // Hash and update password, clear OTP fields
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt2  = $pdo->prepare(
        "UPDATE users SET
            password       = :password,
            reset_otp      = NULL,
            otp_expiry     = NULL,
            otp_verified   = 0,
            otp_attempts   = 0,
            otp_send_count = 0,
            otp_last_sent  = NULL
         WHERE id = :id"
    );
    $stmt2->execute([':password' => $hashed, ':id' => $userId]);

    // Clear session tokens
    unset($_SESSION['fp_user_id'], $_SESSION['fp_email'], $_SESSION['fp_authorized'], $_SESSION['fp_granted_at']);

    log_reset_event('PASSWORD_RESET_SUCCESS', $email);
    json_ok(['message' => 'Password has been reset successfully. Please login with your new password.']);
}


// ════════════════════════════════════════════════════════════════════
//  OTP Email Sender
// ════════════════════════════════════════════════════════════════════
function sendOtpEmail(mysqli $mysqli, string $toEmail, string $otp): bool {
    try {
        // Fetch institute name for branding
        $res       = $mysqli->query("SELECT institute_name FROM institute_profile LIMIT 1");
        $institute = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : [];
        $instName  = $institute['institute_name'] ?? 'Student Management System';

        $expiry = date('h:i A', time() + 600);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr>
    <td style="background:linear-gradient(135deg,#1e293b,#334155);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
      <div style="font-size:40px;margin-bottom:10px;">🔐</div>
      <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">Password Reset OTP</h1>
      <p style="color:rgba(255,255,255,0.55);margin:8px 0 0;font-size:13px;">{$instName}</p>
    </td>
  </tr>
  <tr><td style="height:4px;background:linear-gradient(90deg,#3b82f6,#6366f1);"></td></tr>

  <!-- Body -->
  <tr>
    <td style="background:#fff;padding:36px 40px;">
      <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.6;">
        We received a request to reset the password for your account.
        Use the OTP below to proceed. <strong>Do not share this code with anyone.</strong>
      </p>

      <!-- OTP Box -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
          <td align="center">
            <div style="display:inline-block;background:linear-gradient(135deg,#eff6ff,#dbeafe);border:2px dashed #3b82f6;border-radius:16px;padding:24px 48px;text-align:center;">
              <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.12em;color:#64748b;font-weight:600;margin-bottom:10px;">Your One-Time Password</div>
              <div style="font-size:42px;font-weight:800;letter-spacing:10px;color:#1e293b;font-family:monospace;">{$otp}</div>
              <div style="font-size:12px;color:#6b7280;margin-top:10px;">⏱ Valid until {$expiry} (10 minutes)</div>
            </div>
          </td>
        </tr>
      </table>

      <!-- Warning -->
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px 20px;">
            <p style="margin:0;font-size:13px;color:#991b1b;line-height:1.5;">
              ⚠️ <strong>Security Warning:</strong><br>
              If you did not request a password reset, please ignore this email and ensure your account is secure.
              This OTP will expire automatically after 10 minutes.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td style="background:#f8fafc;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
      <p style="color:#9ca3af;font-size:12px;margin:0;line-height:1.6;">
        {$instName} — Student Management System<br>
        This is an automated message. Please do not reply to this email.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;

        $emailSvc = new EmailService($mysqli);
        return $emailSvc->sendEmail(
            $toEmail,
            'Password Reset OTP — ' . $instName,
            $html,
            'custom',
            0
        );
    } catch (Throwable $e) {
        error_log('[ForgotPassword] Email error: ' . $e->getMessage());
        return false;
    }
}
