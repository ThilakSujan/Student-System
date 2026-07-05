<?php
/**
 * AJAX endpoint: Email Report Card to student/parent
 * GET params: student_id
 * Returns JSON
 */
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/email_service.php';

header('Content-Type: application/json');

// Admin and staff only
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$studentId = (int)($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
if (!$studentId) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID.']);
    exit;
}

// Verify student exists
$check = $mysqli->query("SELECT id, student_name, email FROM students WHERE id = {$studentId} LIMIT 1");
if (!$check || $check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    exit;
}
$student = $check->fetch_assoc();

try {
    $emailSvc = new EmailService($mysqli);
    $sent     = $emailSvc->sendReportCard($studentId);

    if ($sent) {
        $recipient = !empty($student['parent_email']) ? $student['parent_email'] : $student['email'];
        echo json_encode([
            'success'   => true,
            'message'   => "Report card emailed successfully to {$recipient}.",
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email. Please check SMTP configuration and Email Logs for details.',
        ]);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
