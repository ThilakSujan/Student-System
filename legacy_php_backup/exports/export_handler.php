<?php
/**
 * Universal Dynamic Export Handler
 * ─────────────────────────────────────────────────────
 * Receives JSON payloads directly from the frontend JS, ensuring
 * that the exported PDF or Excel perfectly matches the user's
 * current view (including active searches, filters, and sorts).
 *
 * Endpoint: /exports/export_handler.php (POST)
 */

session_start();
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin']);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ExportService.php';

// Ensure it's a POST request with the required payload
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['export_payload'])) {
    http_response_code(400);
    die('Invalid export request. Payload missing.');
}

// Decode JSON payload
$data = json_decode($_POST['export_payload'], true);

if (!is_array($data) || empty($data['headers']) || empty($data['rows'])) {
    http_response_code(400);
    die('Invalid payload format. Missing headers or rows.');
}

$title   = $data['title'] ?? 'Exported Report';
$format  = $data['format'] ?? 'pdf';
$headers = $data['headers'];
$rows    = $data['rows'];

// Prepare metadata (Institute Name)
$instName = 'Student Management System';
$ir = @$mysqli->query("SELECT institute_name FROM institute_profile LIMIT 1");
if ($ir && $row = $ir->fetch_assoc()) {
    $instName = $row['institute_name'] ?: $instName;
}
$meta = [
    'institute' => $instName,
    'subtitle'  => 'Total Records: ' . count($rows)
];

// Generate safe filename
$date = date('Y-m-d');
$safeTitle = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($title));
$baseFilename = $safeTitle . '_' . $date;

// Generate Export
if ($format === 'pdf') {
    ExportService::pdf($title, $headers, $rows, $baseFilename . '.pdf', $meta);
} else {
    // For Excel, we need an associative array of headers with data types
    $headersWithTypes = [];
    foreach ($headers as $h) {
        $headersWithTypes[$h] = 'string'; // Default to string to preserve formatting
    }
    ExportService::excel($headersWithTypes, $rows, $baseFilename . '.xlsx');
}
