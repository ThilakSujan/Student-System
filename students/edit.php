<?php
require_once '../includes/auth.php';
require_role(['admin', 'staff']);
$page_title = "Edit Student";
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db.php';

$error = "";
$success = "";

if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$id = (int)$_GET['id'];
$query = "SELECT * FROM students WHERE id='$id'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    header("Location: students.php?error=not_found");
    exit();
}

$skills = explode(",", $row['skills']);

// Handle update
if (isset($_POST['update_student'])) {
    $student_name = trim($_POST['student_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'] ?? '';
    $department = $_POST['department'] ?? '';
    $skills_selected = $_POST['skills'] ?? [];
    $dob = $_POST['dob'] ?? '';
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if (empty($student_name) || empty($email) || empty($phone)) {
        $error = "Name, email, and phone are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!preg_match('/^[0-9\-\+\s\(\)]+$/', $phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) {
        $error = "Please enter a valid phone number (minimum 10 digits).";
    } elseif (empty($gender)) {
        $error = "Please select a gender.";
    } elseif (empty($department)) {
        $error = "Please select a department.";
    } elseif (empty($skills_selected)) {
        $error = "Please select at least one skill.";
    } elseif (!empty($dob) && !strtotime($dob)) {
        $error = "Invalid date of birth.";
    } else {
        // Check email uniqueness
        $email_check = "SELECT id FROM students WHERE email='$email' AND id != '$id'";
        $email_result = mysqli_query($conn, $email_check);
        
        if (mysqli_num_rows($email_result) > 0) {
            $error = "Email already exists for another student.";
        } else {
            $skills_str = implode(",", array_map('trim', $skills_selected));
            $query = "UPDATE students SET
                student_name='$student_name',
                email='$email',
                phone='$phone',
                gender='$gender',
                department='$department',
                skills='$skills_str',
                dob='$dob',
                status='$status'
                WHERE id='$id'";

            if (mysqli_query($conn, $query)) {
                $success = "Student updated successfully.";
                // Refresh data
                $result = mysqli_query($conn, "SELECT * FROM students WHERE id='$id'");
                $row = mysqli_fetch_assoc($result);
                $skills = explode(",", $row['skills']);
            } else {
                $error = "Failed to update student.";
            }
        }
    }
}
?>

<div id="content">
<?php require '../includes/navbar.php'; ?>
<div id="main-content">

    <div class="mb-4">
        <h4 class="mb-0">Edit Student</h4>
        <small class="text-muted">Update student information and details</small>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Student Details</h5>
                </div>
                <div class="card-body">
                    <form action="edit.php?id=<?php echo $id; ?>" method="POST">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Student Name *</label>
                                <input type="text" name="student_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($row['student_name']); ?>">
                                <small class="text-muted">Full name of the student</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($row['email']); ?>">
                                <small class="text-muted">Valid email address</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone *</label>
                                <input type="tel" name="phone" class="form-control" required
                                       value="<?php echo htmlspecialchars($row['phone']); ?>"
                                       placeholder="e.g., +1-234-567-8900">
                                <small class="text-muted">Minimum 10 digits</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender *</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">-- Select Gender --</option>
                                    <option value="Male" <?php if ($row['gender'] == "Male") echo "selected"; ?>>Male</option>
                                    <option value="Female" <?php if ($row['gender'] == "Female") echo "selected"; ?>>Female</option>
                                    <option value="Other" <?php if ($row['gender'] == "Other") echo "selected"; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Department *</label>
                                <select name="department" class="form-select" required>
                                    <option value="">-- Select Department --</option>
                                    <option value="CSE" <?php if ($row['department'] == "CSE") echo "selected"; ?>>CSE</option>
                                    <option value="ECE" <?php if ($row['department'] == "ECE") echo "selected"; ?>>ECE</option>
                                    <option value="EEE" <?php if ($row['department'] == "EEE") echo "selected"; ?>>EEE</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control"
                                       value="<?php echo htmlspecialchars($row['dob']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Skills *</label>
                            <div class="border p-3 rounded bg-light">
                                <div class="form-check">
                                    <input type="checkbox" name="skills[]" value="HTML" class="form-check-input"
                                           <?php if (in_array("HTML", $skills)) echo "checked"; ?> id="skill_html">
                                    <label class="form-check-label" for="skill_html">HTML</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="skills[]" value="CSS" class="form-check-input"
                                           <?php if (in_array("CSS", $skills)) echo "checked"; ?> id="skill_css">
                                    <label class="form-check-label" for="skill_css">CSS</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="skills[]" value="PHP" class="form-check-input"
                                           <?php if (in_array("PHP", $skills)) echo "checked"; ?> id="skill_php">
                                    <label class="form-check-label" for="skill_php">PHP</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="skills[]" value="JavaScript" class="form-check-input"
                                           <?php if (in_array("JavaScript", $skills)) echo "checked"; ?> id="skill_js">
                                    <label class="form-check-label" for="skill_js">JavaScript</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="skills[]" value="Java" class="form-check-input"
                                           <?php if (in_array("Java", $skills)) echo "checked"; ?> id="skill_java">
                                    <label class="form-check-label" for="skill_java">Java</label>
                                </div>
                            </div>
                            <small class="text-muted">Select at least one skill</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="Active" <?php if ($row['status'] == "Active") echo "selected"; ?>>Active</option>
                                <option value="Inactive" <?php if ($row['status'] == "Inactive") echo "selected"; ?>>Inactive</option>
                            </select>
                            <small class="text-muted">Mark student as active or inactive</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="update_student" class="btn btn-warning">
                                <i class="bi bi-save"></i> Update Student
                            </button>
                            <a href="students.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-info-circle"></i> Student Info</h5>
                    <div class="mb-3">
                        <small class="text-muted">Student ID</small>
                        <p class="mb-1"><strong><?php echo $row['id']; ?></strong></p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Current Status</small>
                        <p class="mb-1">
                            <?php if ($row['status'] == "Active"): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Inactive</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <hr>
                    <h6 class="mb-2"><i class="bi bi-gear"></i> Field Requirements</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check-circle text-success"></i> Name: Required</li>
                        <li><i class="bi bi-check-circle text-success"></i> Email: Valid & Unique</li>
                        <li><i class="bi bi-check-circle text-success"></i> Phone: Min 10 digits</li>
                        <li><i class="bi bi-check-circle text-success"></i> Gender: Required</li>
                        <li><i class="bi bi-check-circle text-success"></i> Department: Required</li>
                        <li><i class="bi bi-check-circle text-success"></i> Skills: At least 1</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
<?php
// Inject toast notification
if (!empty($success)) {
    echo "<script>window._toastMsg=" . json_encode($success) . ";window._toastType='success';</script>";
} elseif (!empty($error)) {
    echo "<script>window._toastMsg=" . json_encode($error) . ";window._toastType='danger';</script>";
}
?>
<?php require '../includes/footer.php'; ?>
</div>