<?php
$host = "localhost";
$user = "root";
$pass = "";           // default XAMPP password is empty
$db   = "student1_db";

// Create both procedural ($conn) and object-oriented ($mysqli) connections
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create a mysqli object for code that expects $mysqli
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die("MySQLi connection failed: " . $mysqli->connect_error);
}
?>