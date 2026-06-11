<?php
/**
 * Centralized Email Service
 * ─────────────────────────────────────────────────────────────────────
 * Provides a built-in SMTP client (no external library needed) and
 * high-level helpers for each notification type in the system.
 *
 * Usage:
 *   require_once '../includes/email_service.php';
 *   $emailSvc = new EmailService($mysqli);
 *   $emailSvc->sendAttendanceAlert($student_id, $date, 'Absent');
 * ─────────────────────────────────────────────────────────────────────
 */

if (!defined('EMAIL_HOST')) {
    require_once __DIR__ . '/../config/email_config.php';
}

// ══════════════════════════════════════════════════════════════════════
//  SmtpClient — lightweight SMTP sender (no PHPMailer dependency)
// ══════════════════════════════════════════════════════════════════════
class SmtpClient
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $encryption; // 'tls' | 'ssl' | ''
    private        $socket = null;
    private int    $timeout;

    public function __construct(
        string $host,
        int    $port,
        string $username,
        string $password,
        string $encryption = 'tls',
        int    $timeout    = 15
    ) {
        $this->host       = $host;
        $this->port       = $port;
        $this->username   = $username;
        $this->password   = $password;
        $this->encryption = strtolower($encryption);
        $this->timeout    = $timeout;
    }

    /** Connect to SMTP server and authenticate */
    public function connect(): void
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ]);

        $addr = ($this->encryption === 'ssl')
            ? "ssl://{$this->host}:{$this->port}"
            : "{$this->host}:{$this->port}";

        $this->socket = @stream_socket_client(
            $addr, $errno, $errstr, $this->timeout,
            STREAM_CLIENT_CONNECT, $context
        );

        if (!$this->socket) {
            throw new RuntimeException("SMTP connect failed: $errstr ($errno)");
        }
        stream_set_timeout($this->socket, $this->timeout);

        $this->expect(220);                        // Server greeting
        $this->command("EHLO " . gethostname(), 250);

        if ($this->encryption === 'tls') {
            $this->command("STARTTLS", 220);
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->command("EHLO " . gethostname(), 250);
        }

        $this->command("AUTH LOGIN", 334);
        $this->command(base64_encode($this->username), 334);
        $this->command(base64_encode($this->password), 235);
    }

    /** Send a single email */
    public function send(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $htmlBody,
        string $textBody = ''
    ): void {
        if (!$this->socket) {
            $this->connect();
        }

        $toEmail = $this->sanitizeEmail($toEmail);

        $this->command("MAIL FROM:<{$fromEmail}>", 250);
        $this->command("RCPT TO:<{$toEmail}>", [250, 251]);
        $this->command("DATA", 354);

        $boundary = md5(uniqid((string)time(), true));
        $headers  = $this->buildHeaders($fromEmail, $fromName, $toEmail, $subject, $boundary);
        $body     = $this->buildBody($htmlBody, $textBody ?: strip_tags($htmlBody), $boundary);

        // Ensure dots at start of lines are doubled (SMTP dot-stuffing)
        $message = $headers . "\r\n" . $body;
        $message = preg_replace('/^\.$/m', '..', $message);

        fwrite($this->socket, $message . "\r\n.\r\n");
        $this->expect(250);
    }

    /** Close the SMTP connection */
    public function quit(): void
    {
        if ($this->socket) {
            @fwrite($this->socket, "QUIT\r\n");
            fclose($this->socket);
            $this->socket = null;
        }
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function command(string $cmd, $expectedCode): string
    {
        fwrite($this->socket, $cmd . "\r\n");
        return $this->expect($expectedCode);
    }

    private function expect($code): string
    {
        $response = '';
        while ($line = fgets($this->socket, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        $actual = (int)substr($response, 0, 3);
        $codes  = is_array($code) ? $code : [$code];
        if (!in_array($actual, $codes, true)) {
            throw new RuntimeException("SMTP error (expected " . implode('/', $codes) . ", got $actual): $response");
        }
        return $response;
    }

    private function buildHeaders(
        string $from, string $fromName,
        string $to,   string $subject,
        string $boundary
    ): string {
        $fromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $subject  = '=?UTF-8?B?' . base64_encode($subject)  . '?=';
        return implode("\r\n", [
            "From: {$fromName} <{$from}>",
            "To: <{$to}>",
            "Subject: {$subject}",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Mailer: StudentSystem/1.0",
            "Date: " . date('r'),
        ]);
    }

    private function buildBody(string $html, string $text, string $boundary): string
    {
        return "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text)) . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html)) . "\r\n"
            . "--{$boundary}--";
    }

    private function sanitizeEmail(string $email): string
    {
        // Prevent header injection
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
}


// ══════════════════════════════════════════════════════════════════════
//  EmailService — high-level email operations
// ══════════════════════════════════════════════════════════════════════
class EmailService
{
    private mysqli      $db;
    private ?SmtpClient $smtp    = null;
    private string      $tplDir;
    private ?array      $institute = null;

    public function __construct(mysqli $db)
    {
        $this->db     = $db;
        $this->tplDir = __DIR__ . '/email_templates/';
    }

    // ── Public notification methods ───────────────────────────────────

    /**
     * Send attendance absence alert.
     * Prefers parent_email if available, falls back to student email.
     */
    public function sendAttendanceAlert(int $studentId, string $date): bool
    {
        $student = $this->getStudent($studentId);
        if (!$student) return false;

        $recipient = !empty($student['parent_email'])
            ? $student['parent_email']
            : $student['email'];

        if (!$this->isValidEmail($recipient)) return false;

        $institute = $this->getInstitute();
        $html = $this->renderTemplate('attendance_alert.php', [
            'student'   => $student,
            'date'      => $date,
            'institute' => $institute,
        ]);

        $instituteName = $institute['institute_name'] ?? 'Student Management System';
        $subject = "[{$instituteName}] Attendance Alert — {$student['student_name']} Marked Absent";

        return $this->sendEmail(
            $recipient, $subject, $html,
            'attendance', $studentId
        );
    }

    /**
     * Send low attendance warning.
     * Automatically calculates stats from DB; fires when % < EMAIL_LOW_ATTENDANCE_THRESHOLD.
     * Returns false if attendance is still above threshold (no email needed).
     */
    public function sendLowAttendanceAlert(int $studentId): bool
    {
        $student = $this->getStudent($studentId);
        if (!$student) return false;

        $recipient = !empty($student['parent_email'])
            ? $student['parent_email']
            : $student['email'];

        if (!$this->isValidEmail($recipient)) return false;

        // Calculate attendance stats
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*)                         AS total_classes,
                SUM(status = 'Present')          AS present_count,
                SUM(status = 'Absent')           AS absent_count,
                ROUND(SUM(status='Present') / COUNT(*) * 100, 1) AS percentage
             FROM attendance WHERE student_id = ?"
        );
        if (!$stmt) return false;
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || $row['total_classes'] < 1) return false;

        $threshold  = defined('EMAIL_LOW_ATTENDANCE_THRESHOLD') ? EMAIL_LOW_ATTENDANCE_THRESHOLD : 75;
        $percentage = (float)$row['percentage'];

        // Only send if below threshold
        if ($percentage >= $threshold) return false;

        $attendance = [
            'total_classes' => (int)$row['total_classes'],
            'present_count' => (int)$row['present_count'],
            'absent_count'  => (int)$row['absent_count'],
            'percentage'    => $percentage,
            'threshold'     => $threshold,
        ];

        $institute = $this->getInstitute();
        $html = $this->renderTemplate('low_attendance.php', [
            'student'    => $student,
            'attendance' => $attendance,
            'institute'  => $institute,
        ]);

        $instituteName = $institute['institute_name'] ?? 'Student Management System';
        $subject = "[{$instituteName}] ⚠ Low Attendance Warning — {$student['student_name']} ({$percentage}%)";

        return $this->sendEmail(
            $recipient, $subject, $html,
            'attendance', $studentId
        );
    }

    /**
     * Send fee payment invoice email after a successful payment.
     * $paymentData: array with payment details already fetched.
     */
    public function sendFeeInvoice(int $studentId, array $paymentData): bool
    {
        $student = $this->getStudent($studentId);
        if (!$student) return false;

        $recipient = !empty($student['parent_email'])
            ? $student['parent_email']
            : $student['email'];

        if (!$this->isValidEmail($recipient)) return false;

        $institute = $this->getInstitute();
        $html = $this->renderTemplate('fee_invoice.php', [
            'student'     => $student,
            'payments'    => $paymentData,
            'institute'   => $institute,
        ]);

        $total = array_sum(array_column($paymentData, 'amount_paid'));
        $subject = "Fee Payment Confirmation — ₹" . number_format($total, 2) . " Received";

        return $this->sendEmail(
            $recipient, $subject, $html,
            'fee_invoice', $studentId
        );
    }

    /**
     * Send marks published notification to a student.
     */
    public function sendMarksPublished(int $studentId): bool
    {
        $student = $this->getStudent($studentId);
        if (!$student) return false;

        $recipient = $student['email'];
        if (!$this->isValidEmail($recipient)) return false;

        // Fetch marks summary
        $res = $this->db->query(
            "SELECT m.marks_obtained, m.total_marks, sub.subject_name, sub.subject_code
             FROM marks m JOIN subjects sub ON sub.id = m.subject_id
             WHERE m.student_id = {$studentId} ORDER BY sub.subject_code ASC"
        );
        $marks = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

        $totalObtained = array_sum(array_column($marks, 'marks_obtained'));
        $totalMax      = array_sum(array_column($marks, 'total_marks'));
        $percentage    = $totalMax > 0 ? round($totalObtained / $totalMax * 100, 2) : 0;

        $institute = $this->getInstitute();
        $html = $this->renderTemplate('marks_published.php', [
            'student'       => $student,
            'marks'         => $marks,
            'total_obtained'=> $totalObtained,
            'total_max'     => $totalMax,
            'percentage'    => $percentage,
            'institute'     => $institute,
        ]);

        $subject = "Your Results Have Been Published — " . ($institute['institute_name'] ?? 'Student System');

        return $this->sendEmail(
            $recipient, $subject, $html,
            'marks_published', $studentId
        );
    }

    /**
     * Send report card email to a student.
     */
    public function sendReportCard(int $studentId): bool
    {
        $student = $this->getStudent($studentId);
        if (!$student) return false;

        $recipient = !empty($student['parent_email'])
            ? $student['parent_email']
            : $student['email'];

        if (!$this->isValidEmail($recipient)) return false;

        $res = $this->db->query(
            "SELECT m.marks_obtained, m.total_marks, sub.subject_name, sub.subject_code
             FROM marks m JOIN subjects sub ON sub.id = m.subject_id
             WHERE m.student_id = {$studentId} ORDER BY sub.subject_code ASC"
        );
        $marks = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

        $totalObtained = array_sum(array_column($marks, 'marks_obtained'));
        $totalMax      = array_sum(array_column($marks, 'total_marks'));
        $percentage    = $totalMax > 0 ? round($totalObtained / $totalMax * 100, 2) : 0;

        $institute = $this->getInstitute();
        $html = $this->renderTemplate('report_card_email.php', [
            'student'       => $student,
            'marks'         => $marks,
            'total_obtained'=> $totalObtained,
            'total_max'     => $totalMax,
            'percentage'    => $percentage,
            'institute'     => $institute,
        ]);

        $subject = "Report Card — " . $student['student_name'] . " | " . ($institute['institute_name'] ?? 'Student System');

        return $this->sendEmail(
            $recipient, $subject, $html,
            'report_card', $studentId
        );
    }

    /**
     * Send a custom email to any address.
     * Used by the Manual Send Mail feature.
     */
    public function sendCustomEmail(
        string $recipientEmail,
        string $subject,
        string $messageBody,
        int    $sentBy = 0
    ): bool {
        if (!$this->isValidEmail($recipientEmail)) return false;

        // Sanitize subject — prevent header injection
        $subject = preg_replace('/[\r\n]/', '', $subject);
        $subject = htmlspecialchars(strip_tags($subject), ENT_QUOTES, 'UTF-8');

        $institute = $this->getInstitute();
        $html = $this->renderTemplate('custom_email.php', [
            'recipient_email' => $recipientEmail,
            'subject'         => $subject,
            'message'         => nl2br(htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8')),
            'institute'       => $institute,
        ]);

        return $this->sendEmail(
            $recipientEmail, $subject, $html,
            'custom', $sentBy
        );
    }

    // ── Approval Workflow Emails ──────────────────────────────────────

    /**
     * Send "Registration Received – Pending Approval" email to the new user.
     */
    public function sendRegistrationPending(string $toEmail, string $username): bool
    {
        if (!$this->isValidEmail($toEmail)) return false;
        $institute    = $this->getInstitute();
        $instName     = $institute['institute_name'] ?? 'Student Management System';
        $uname        = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
  <tr><td style="background:linear-gradient(135deg,#1e293b,#334155);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
    <div style="font-size:40px;margin-bottom:10px;">📋</div>
    <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">Registration Received</h1>
    <p style="color:rgba(255,255,255,0.55);margin:8px 0 0;font-size:13px;">{$instName}</p>
  </td></tr>
  <tr><td style="height:4px;background:linear-gradient(90deg,#f59e0b,#d97706);"></td></tr>
  <tr><td style="background:#fff;padding:36px 40px;">
    <p style="color:#374151;font-size:15px;margin:0 0 16px;">Dear <strong>{$uname}</strong>,</p>
    <p style="color:#374151;font-size:14px;line-height:1.7;margin:0 0 24px;">
      Thank you for registering on the <strong>{$instName}</strong> portal.<br>
      Your account has been created and is currently <strong>pending administrator review</strong>.
    </p>
    <table width="100%" cellpadding="0" cellspacing="0"><tr><td style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:18px 22px;">
      <p style="margin:0;font-size:14px;color:#92400e;line-height:1.6;">
        ⏳ <strong>What happens next?</strong><br>
        An administrator will review your registration and either approve or reject it.
        You will receive an email notification once a decision has been made.
      </p>
    </td></tr></table>
  </td></tr>
  <tr><td style="background:#f8fafc;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
    <p style="color:#9ca3af;font-size:12px;margin:0;">{$instName} — Student Management System<br>This is an automated message. Please do not reply.</p>
  </td></tr>
</table></td></tr></table>
</body></html>
HTML;
        return $this->sendEmail($toEmail, "Registration Received — {$instName}", $html, 'custom', 0);
    }

    /**
     * Send "Account Approved" email to the user after admin approves.
     */
    public function sendApprovalEmail(string $toEmail, string $username): bool
    {
        if (!$this->isValidEmail($toEmail)) return false;
        $institute    = $this->getInstitute();
        $instName     = $institute['institute_name'] ?? 'Student Management System';
        $uname        = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $loginUrl     = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/student_system/auth/login.php';

        $html = <<<HTML
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
  <tr><td style="background:linear-gradient(135deg,#1e293b,#334155);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
    <div style="font-size:40px;margin-bottom:10px;">✅</div>
    <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">Account Approved!</h1>
    <p style="color:rgba(255,255,255,0.55);margin:8px 0 0;font-size:13px;">{$instName}</p>
  </td></tr>
  <tr><td style="height:4px;background:linear-gradient(90deg,#22c55e,#16a34a);"></td></tr>
  <tr><td style="background:#fff;padding:36px 40px;">
    <p style="color:#374151;font-size:15px;margin:0 0 16px;">Dear <strong>{$uname}</strong>,</p>
    <p style="color:#374151;font-size:14px;line-height:1.7;margin:0 0 24px;">
      Great news! Your registration for <strong>{$instName}</strong> has been <strong style="color:#16a34a;">approved</strong>.<br>
      Your account is now active and you can sign in immediately.
    </p>
    <table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:10px 0 24px;">
      <a href="{$loginUrl}" style="background:#1e293b;color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:600;font-size:15px;display:inline-block;">
        🔐 Sign In Now
      </a>
    </td></tr></table>
    <p style="color:#6b7280;font-size:13px;margin:0;">If the button doesn't work, copy this link: <a href="{$loginUrl}" style="color:#3b82f6;">{$loginUrl}</a></p>
  </td></tr>
  <tr><td style="background:#f8fafc;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
    <p style="color:#9ca3af;font-size:12px;margin:0;">{$instName} — Student Management System<br>This is an automated message. Please do not reply.</p>
  </td></tr>
</table></td></tr></table>
</body></html>
HTML;
        return $this->sendEmail($toEmail, "Account Approved — {$instName}", $html, 'custom', 0);
    }

    /**
     * Send "Registration Rejected" email to the user.
     */
    public function sendRejectionEmail(string $toEmail, string $username, string $reason = ''): bool
    {
        if (!$this->isValidEmail($toEmail)) return false;
        $institute    = $this->getInstitute();
        $instName     = $institute['institute_name'] ?? 'Student Management System';
        $uname        = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $reasonHtml   = $reason
            ? '<p style="color:#374151;font-size:14px;margin:16px 0 0;"><strong>Reason:</strong> ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p>'
            : '';

        $html = <<<HTML
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
  <tr><td style="background:linear-gradient(135deg,#1e293b,#334155);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
    <div style="font-size:40px;margin-bottom:10px;">❌</div>
    <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">Registration Not Approved</h1>
    <p style="color:rgba(255,255,255,0.55);margin:8px 0 0;font-size:13px;">{$instName}</p>
  </td></tr>
  <tr><td style="height:4px;background:linear-gradient(90deg,#ef4444,#dc2626);"></td></tr>
  <tr><td style="background:#fff;padding:36px 40px;">
    <p style="color:#374151;font-size:15px;margin:0 0 16px;">Dear <strong>{$uname}</strong>,</p>
    <p style="color:#374151;font-size:14px;line-height:1.7;margin:0;">
      We regret to inform you that your registration request for <strong>{$instName}</strong> has not been approved at this time.
    </p>
    {$reasonHtml}
    <p style="color:#374151;font-size:14px;line-height:1.7;margin:20px 0 0;">
      If you believe this is an error or need further clarification, please contact the system administrator directly.
    </p>
  </td></tr>
  <tr><td style="background:#f8fafc;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
    <p style="color:#9ca3af;font-size:12px;margin:0;">{$instName} — Student Management System<br>This is an automated message. Please do not reply.</p>
  </td></tr>
</table></td></tr></table>
</body></html>
HTML;
        return $this->sendEmail($toEmail, "Registration Update — {$instName}", $html, 'custom', 0);
    }

    // ── Core send method ──────────────────────────────────────────────

    /**
     * Core email dispatch + logging.
     * Returns true on success, false on failure.
     * NEVER throws — failure is silently logged so it doesn't break app flow.
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $htmlBody,
        string $emailType,
        int    $relatedId = 0
    ): bool {
        if (!defined('EMAIL_ENABLED') || !EMAIL_ENABLED) {
            $this->logEmail($to, $subject, $emailType, 'failed', 'Email sending is disabled.', $relatedId);
            return false;
        }

        try {
            if ($this->smtp === null) {
                $this->smtp = new SmtpClient(
                    EMAIL_HOST,
                    EMAIL_PORT,
                    EMAIL_USERNAME,
                    EMAIL_PASSWORD,
                    EMAIL_ENCRYPTION,
                    EMAIL_TIMEOUT
                );
                $this->smtp->connect();
            }

            $this->smtp->send(
                EMAIL_FROM_EMAIL,
                EMAIL_FROM_NAME,
                $to,
                $subject,
                $htmlBody
            );

            $this->logEmail($to, $subject, $emailType, 'sent', null, $relatedId);
            return true;

        } catch (Throwable $e) {
            $errMsg = $e->getMessage();
            if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
                error_log("[EmailService] Send failed to {$to}: {$errMsg}");
            }
            $this->logEmail($to, $subject, $emailType, 'failed', $errMsg, $relatedId);
            // Force reconnect next time
            try { $this->smtp?->quit(); } catch (Throwable) {}
            $this->smtp = null;
            return false;
        }
    }

    /** Close SMTP connection gracefully */
    public function __destruct()
    {
        try { $this->smtp?->quit(); } catch (Throwable) {}
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function getStudent(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, student_name, email, parent_email, parent_name, department, gender, dob
             FROM students WHERE id = ? LIMIT 1"
        );
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    private function getInstitute(): array
    {
        if ($this->institute === null) {
            $res = $this->db->query("SELECT * FROM institute_profile LIMIT 1");
            $this->institute = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : [];
        }
        return $this->institute;
    }

    private function renderTemplate(string $templateFile, array $vars): string
    {
        $templatePath = $this->tplDir . $templateFile;
        if (!file_exists($templatePath)) {
            throw new RuntimeException("Email template not found: {$templatePath}");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    private function logEmail(
        string  $to,
        string  $subject,
        string  $emailType,
        string  $status,
        ?string $errorMsg,
        int     $relatedId
    ): void {
        if (!defined('EMAIL_LOG_ALL') || !EMAIL_LOG_ALL) return;

        $stmt = $this->db->prepare(
            "INSERT INTO email_logs
                (recipient_email, subject, email_type, status, error_message, related_id, related_type)
             VALUES (?, ?, ?, ?, ?, ?, 'email')"
        );
        if (!$stmt) return;

        $stmt->bind_param(
            'sssssi',
            $to, $subject, $emailType, $status, $errorMsg, $relatedId
        );
        $stmt->execute();
        $stmt->close();
    }

    private function isValidEmail(string $email): bool
    {
        return (bool) filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }
}
