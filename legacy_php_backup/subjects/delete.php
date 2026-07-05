<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Require admin or staff role
require_role(['admin', 'staff']);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        $response['message'] = 'Invalid subject ID';
    } else {
        // Check if subject exists
        $check = $mysqli->query("SELECT id FROM subjects WHERE id = $id");
        if ($check->num_rows === 0) {
            $response['message'] = 'Subject not found';
        } else {
            // Check if subject has marks associated
            $marks_check = $mysqli->query("SELECT COUNT(*) as count FROM marks WHERE subject_id = $id");
            $marks_row = $marks_check->fetch_assoc();
            
            if ($marks_row['count'] > 0) {
                $response['message'] = 'Cannot delete subject with existing marks. Delete all marks first.';
            } else {
                // Delete subject
                if ($mysqli->query("DELETE FROM subjects WHERE id = $id")) {
                    $response['success'] = true;
                    $response['message'] = 'Subject deleted successfully';
                } else {
                    $response['message'] = 'Error deleting subject: ' . $mysqli->error;
                }
            }
        }
    }
}

echo json_encode($response);
