# Student Login Implementation Guide

## Overview
Students can now login to the system using their student name and date of birth (DOB) as credentials.

## Login Credentials Format

### Username (Student Name Field)
- Use the exact student name as registered in the system
- Example: "John Doe"
- Case-sensitive match with database `students.student_name`

### Password (DOB Field)
- Format: **YYYYMMDD** (continuous, no special characters)
- Example: 
  - DOB in DB: 2005-01-15
  - Login password: 20050115
- The field accepts 8 digits only (validated with pattern="\d{8}")

## Login Flow

### Step 1: Access Login Page
- Navigate to `auth/login.php`
- Click on the **"Student"** tab

### Step 2: Enter Credentials
- **Full Name (as in records):** Enter student name exactly as registered
- **DOB (YYYYMMDD format):** Enter date of birth in YYYYMMDD format
  - Example: 20050115 for January 15, 2005

### Step 3: Submit
- Click "Sign In" button
- System will validate credentials against `students` table

### Step 4: Redirected to Dashboard
- On successful login, redirected to dashboard
- Student dashboard shows:
  - Welcome message with student name
  - Total marks obtained
  - Overall percentage
  - Grade (A+, A, B, C, D, F)
  - Number of subjects
  - Detailed marks per subject

## Student Access Permissions

### ✅ Allowed Pages
- **Dashboard** - Personal academic summary
- **Marks** - View personal marks and grades
- **My Profile** - View and edit personal profile information

### ❌ Restricted Pages (Auto-redirect to Dashboard)
- Add Student
- View Students (other students' records)
- Subject Management
- Add Marks
- Staff Management
- User Management
- Institute Profile
- Any student edit/delete operations

## Session Variables Created on Login

```php
$_SESSION['user_id']     // Student ID (from students.id)
$_SESSION['username']    // Student name
$_SESSION['email']       // Student email
$_SESSION['role']        // 'student'
$_SESSION['student_id']  // Student ID (duplicate for clarity)
```

## Database Query

### Login Query
```sql
SELECT * FROM students 
WHERE student_name = :name 
AND status = 'Active'
```

### DOB Validation
- Fetched DOB is formatted as YYYYMMDD
- Compared with user input
- If match → Login successful
- If no match or student not found → Login failed

## Error Handling

### Possible Error Messages
- **"Invalid student name or date of birth."**
  - Student name not found in database
  - DOB doesn't match the student's record
  - Student status is not 'Active'
  - Incorrect DOB format
- **"Please fill in all fields."**
  - Missing student name or DOB
  - Empty input fields

## Important Notes

1. **Student Status**: Only students with status='Active' can login
2. **DOB Format**: Must be exactly YYYYMMDD (e.g., 20050115)
3. **Case Sensitivity**: Student name must match exactly as in database
4. **No Edit Operations**: Students cannot edit their own or other students' records
5. **Profile Edits**: Students can edit their profile in "My Profile" section, but not student record data
6. **Marks Viewing**: Students can only view their own marks, not other students' marks
7. **Report Card**: Students can generate their own report card but cannot view others

## Testing Checklist

- [ ] Student can login with correct name and DOB
- [ ] Student cannot login with incorrect DOB
- [ ] Student cannot login with non-existent name
- [ ] Student can view their personal dashboard
- [ ] Student can view their marks
- [ ] Student can access "My Profile"
- [ ] Student cannot access "View Students"
- [ ] Student cannot edit student records
- [ ] Student cannot add/edit/delete marks
- [ ] Student sidebar shows only allowed options
- [ ] Logout works and returns to login page
- [ ] Redirect occurs when accessing unauthorized pages
- [ ] Report card shows only current student's data

## Example Test Credentials

Assuming a student in database:
- `student_name`: "Alice Johnson"
- `email`: "alice@example.com"
- `dob`: 2004-05-20
- `status`: "Active"

**Login Details:**
- Full Name: Alice Johnson
- DOB: 20040520

## Troubleshooting

### Student cannot login even with correct credentials
1. Check if student status is 'Active' in database
2. Verify DOB format is YYYYMMDD
3. Check student name spelling (must match exactly)
4. Verify student exists in students table

### Student sees error "Invalid student name or date of birth"
1. Confirm student name matches database exactly (case-sensitive)
2. Verify DOB is in correct format (YYYYMMDD)
3. Check student is not marked as 'Inactive' in database

### Student redirected to dashboard when accessing other pages
- This is intentional - students have restricted access
- Only Dashboard, Marks, and Profile are accessible to students

## Database Changes Required

Ensure the `students` table has:
- `student_name` (VARCHAR) - Exact name for login
- `dob` (DATE) - In format YYYY-MM-DD
- `email` (VARCHAR) - Student email
- `status` (VARCHAR) - Set to 'Active' for login capability

No schema changes were needed - the implementation uses existing fields.
