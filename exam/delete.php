<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

require_role(['admin', 'staff'], '/student_system/exam/index.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;

if (!$exam_id) {
    header('Location: index.php?error=' . urlencode('Invalid exam ID.'));
    exit();
}

$stmt = $mysqli->prepare("DELETE FROM exam_schedule WHERE id = ?");
$stmt->bind_param('i', $exam_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    header('Location: index.php?success=' . urlencode('Exam deleted successfully.'));
} else {
    header('Location: index.php?error=' . urlencode('Failed to delete exam or exam not found.'));
}
exit();
