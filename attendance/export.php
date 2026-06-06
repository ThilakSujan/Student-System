<?php
session_start();
require_once '../includes/auth.php';
require_login();
require_once '../config/db.php';

$role = $_SESSION['role'] ?? '';

if ($role !== 'student') {
    die("Only students can download their monthly report.");
}

$sid = (int)($_SESSION['student_id'] ?? 0);

// Determine the month to download
$req_month = $_GET['month'] ?? date('Y-m'); // e.g., '2023-06'
$start_date = $req_month . '-01'; 
$month_name = date('F_Y', strtotime($start_date));

if ($req_month === date('Y-m')) {
    // Current month: up to today's date
    $end_date = date('Y-m-d');
} else {
    // Past/Future month: up to the last day of the month
    $end_date = date('Y-m-t', strtotime($start_date));
}

// Fetch student details for the filename
$sres = $mysqli->query("SELECT student_name, id FROM students WHERE id=$sid LIMIT 1");
$student = $sres ? $sres->fetch_assoc() : ['student_name' => 'Student', 'register_number' => ''];
$student_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $student['student_name']);

// Fetch attendance records for the current month
$query = "SELECT date, status FROM attendance 
          WHERE student_id = $sid 
          AND date BETWEEN '$start_date' AND '$end_date' 
          ORDER BY date DESC";

$ares = $mysqli->query($query);
$records = $ares ? $ares->fetch_all(MYSQLI_ASSOC) : [];

// Set headers to trigger file download
$filename = "Attendance_{$student_name}_{$month_name}.csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open the output stream
$output = fopen('php://output', 'w');

// Write the column headers
fputcsv($output, ['Date', 'Day', 'Status']);

// Write the data rows
if (!empty($records)) {
    foreach ($records as $row) {
        $date = date('d M Y', strtotime($row['date']));
        $day  = date('l', strtotime($row['date']));
        fputcsv($output, [$date, $day, $row['status']]);
    }
} else {
    fputcsv($output, ['No records found for this month.', '', '']);
}

// Close the output stream
fclose($output);
exit();
