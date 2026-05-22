<?php
require_once 'includes/auth.php';
require_role(['admin', 'staff']);
$page_title = "Edit Student";
require 'includes/header.php';
require 'includes/sidebar.php';
include 'db.php';

$id     = $_GET['id'];
$query  = "SELECT * FROM students WHERE id='$id'";
$result = mysqli_query($conn, $query);
$row    = mysqli_fetch_assoc($result);
$skills = explode(",", $row['skills']);
?>

<!-- Content -->
<div id="content">
<?php require 'includes/navbar.php'; ?>
<div id="main-content">

    <div class="card shadow-sm">
        <div class="card-header bg-warning">
            <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Student</h5>
        </div>
        <div class="card-body">
            <form action="actions.php" method="POST">

                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                <div class="mb-3">
                    <label class="form-label">Student Name</label>
                    <input type="text" name="student_name" class="form-control"
                        value="<?php echo $row['student_name']; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                        value="<?php echo $row['email']; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                        value="<?php echo $row['phone']; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Gender</label><br>
                    <input type="radio" name="gender" value="Male"
                        <?php if ($row['gender'] == "Male") echo "checked"; ?>> Male &nbsp;
                    <input type="radio" name="gender" value="Female"
                        <?php if ($row['gender'] == "Female") echo "checked"; ?>> Female
                </div>

                <div class="mb-3">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-select">
                        <option <?php if ($row['department'] == "CSE") echo "selected"; ?>>CSE</option>
                        <option <?php if ($row['department'] == "ECE") echo "selected"; ?>>ECE</option>
                        <option <?php if ($row['department'] == "EEE") echo "selected"; ?>>EEE</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Skills</label><br>
                    <input type="checkbox" name="skills[]" value="HTML"
                        <?php if (in_array("HTML", $skills)) echo "checked"; ?>> HTML &nbsp;
                    <input type="checkbox" name="skills[]" value="PHP"
                        <?php if (in_array("PHP", $skills)) echo "checked"; ?>> PHP &nbsp;
                    <input type="checkbox" name="skills[]" value="Java"
                        <?php if (in_array("Java", $skills)) echo "checked"; ?>> Java
                </div>

                <div class="mb-3">
                    <label class="form-label">DOB</label>
                    <input type="date" name="dob" class="form-control"
                        value="<?php echo $row['dob']; ?>">
                </div>

                <button type="submit" name="update_student" class="btn btn-primary">
                    <i class="bi bi-save"></i> Update Student
                </button>
                <a href="students.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>

            </form>
        </div>
    </div>

</div>
<?php require 'includes/footer.php'; ?>
</div>