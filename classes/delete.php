<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        $response['message'] = 'Invalid class ID.';
    } else {
        // Check exists
        $check = $mysqli->query("SELECT id, class_name FROM classes WHERE id = $id");
        if ($check->num_rows === 0) {
            $response['message'] = 'Class not found.';
        } else {
            $row = $check->fetch_assoc();
            if ($mysqli->query("DELETE FROM classes WHERE id = $id")) {
                $response['success'] = true;
                $response['message'] = 'Class "' . $row['class_name'] . '" deleted successfully.';
            } else {
                $response['message'] = 'Failed to delete class: ' . $mysqli->error;
            }
        }
    }
}

echo json_encode($response);
