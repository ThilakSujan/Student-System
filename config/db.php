<?php
$host = "localhost";
$user = "root";
$pass = "";           // default XAMPP password is empty
$db   = "student1_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>