<?php
require_once '../includes/auth.php';
require_role(['admin', 'staff']);

include '../config/db.php';

// Legacy support: Redirect delete/rejoin requests to students.php
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    header("Location: students.php?delete=$id");
    exit();
}

if (isset($_GET['rejoin'])) {
    $id = (int)$_GET['rejoin'];
    $query = "UPDATE students SET status='Active' WHERE id='$id'";
    if (mysqli_query($conn, $query)) {
        header("Location: students.php?success=1");
    }
    exit();
}

// Redirect to students list
header("Location: students.php");
exit();