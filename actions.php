<?php
require_once 'includes/auth.php';
require_role(['admin', 'staff']);

include 'db.php';


// INSERT STUDENT

if (isset($_POST['save_student'])) {

    $student_name = $_POST['student_name'];
    $email        = $_POST['email'];
    $phone        = $_POST['phone'];
    $gender       = $_POST['gender'];
    $department   = $_POST['department'];
    $skills = isset($_POST['skills'])
    ? implode(",", $_POST['skills'])
    : '';
    $dob          = $_POST['dob'];

    $query = "INSERT INTO students
        (student_name, email, phone, gender, department, skills, dob, status)
        VALUES
        ('$student_name','$email','$phone','$gender','$department','$skills','$dob','Active')";

    mysqli_query($conn, $query);

    header("Location: students.php");
    exit();
}


// UPDATE STUDENT

if (isset($_POST['update_student'])) {

    $id           = $_POST['id'];
    $student_name = $_POST['student_name'];
    $email        = $_POST['email'];
    $phone        = $_POST['phone'];
    $gender       = $_POST['gender'];
    $department   = $_POST['department'];
    $skills       = implode(",", $_POST['skills']);
    $dob          = $_POST['dob'];

    $query = "UPDATE students SET
        student_name='$student_name',
        email='$email',
        phone='$phone',
        gender='$gender',
        department='$department',
        skills='$skills',
        dob='$dob'
        WHERE id='$id'";

    mysqli_query($conn, $query);

    header("Location: students.php");
    exit();
}


// SOFT DELETE

if (isset($_GET['delete'])) {

    $id    = $_GET['delete'];
    $query = "UPDATE students SET status='Inactive' WHERE id='$id'";

    mysqli_query($conn, $query);

    header("Location: students.php");
    exit();
}


// REJOIN

if (isset($_GET['rejoin'])) {

    $id    = $_GET['rejoin'];
    $query = "UPDATE students SET status='Active' WHERE id='$id'";

    mysqli_query($conn, $query);

    header("Location: students.php");
    exit();
}
?>