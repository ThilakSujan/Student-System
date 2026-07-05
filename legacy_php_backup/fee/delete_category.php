<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID.']); exit; }

// check in use
$chk  = $mysqli->query("SELECT COUNT(*) c FROM fee_structures WHERE category_id=$id");
$used = $chk ? (int)$chk->fetch_assoc()['c'] : 0;
if ($used > 0) {
    echo json_encode(['success'=>false,'message'=>"Cannot delete: used in $used fee structure(s)."]);
    exit;
}

if ($mysqli->query("DELETE FROM fee_categories WHERE id=$id")) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$mysqli->error]);
}
