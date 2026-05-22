<?php
session_start();
include '../config/db_pdo.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: students.php");
    exit();
}

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    $stmt = $pdo->prepare("UPDATE users SET role='admin' WHERE id=:id");

    $stmt->execute([
        ':id' => $id
    ]);

    header("Location: admin_panel.php");
    exit();
}
?>