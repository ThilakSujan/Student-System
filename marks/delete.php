<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Only admin and staff can delete marks
require_role(['admin', 'staff']);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        $response['message'] = 'Invalid mark ID';
    } else {
        // Check if mark exists
        $check = $mysqli->query("SELECT id FROM marks WHERE id = $id");
        if ($check->num_rows === 0) {
            $response['message'] = 'Mark record not found';
        } else {
            // Delete mark
            if ($mysqli->query("DELETE FROM marks WHERE id = $id")) {
                $response['success'] = true;
                $response['message'] = 'Mark deleted successfully';
            } else {
                $response['message'] = 'Error deleting mark: ' . $mysqli->error;
            }
        }
    }
}

echo json_encode($response);
