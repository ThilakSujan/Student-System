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
