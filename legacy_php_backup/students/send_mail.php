<?php
/**
 * AJAX endpoint: Manual Send Mail to a student
 * POST params: student_id, recipient_email, subject, message
 * Returns JSON
 */
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/email_service.php';

header('Content-Type: application/json');

// Only admin and staff can send manual emails
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. You must be admin or staff to send emails.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$recipientEmail = trim($_POST['recipient_email'] ?? '');
$subject        = trim($_POST['subject']         ?? '');
$message        = trim($_POST['message']         ?? '');
$studentId      = (int)($_POST['student_id']     ?? 0);

// ── Validation ────────────────────────────────────────────────────────
if (empty($recipientEmail)) {
    echo json_encode(['success' => false, 'message' => 'Recipient email is required.']);
    exit;
}
if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address format.']);
    exit;
}
if (empty($subject)) {
    echo json_encode(['success' => false, 'message' => 'Subject is required.']);
    exit;
}
if (strlen($subject) > 500) {
    echo json_encode(['success' => false, 'message' => 'Subject is too long (max 500 chars).']);
    exit;
}
if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message body is required.']);
    exit;
}

// Prevent header injection
if (preg_match('/[\r\n]/', $subject)) {
    echo json_encode(['success' => false, 'message' => 'Invalid characters in subject.']);
    exit;
}

// ── Send ──────────────────────────────────────────────────────────────
try {
    $emailSvc = new EmailService($mysqli);
    $sent     = $emailSvc->sendCustomEmail(
        $recipientEmail,
        $subject,
        $message,
        (int)$_SESSION['user_id']
    );

    if ($sent) {
        echo json_encode(['success' => true, 'message' => "Email sent successfully to {$recipientEmail}."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email could not be sent. Check SMTP configuration in config/email_config.php and the Email Logs for details.']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}
