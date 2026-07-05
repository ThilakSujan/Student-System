-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.32-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.17.0.7270
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for student1_db
CREATE DATABASE IF NOT EXISTS `student1_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `student1_db`;

-- Dumping structure for table student1_db.attendance
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent') NOT NULL DEFAULT 'Present',
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_date` (`student_id`,`date`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.attendance: ~9 rows (approximately)
INSERT INTO `attendance` (`id`, `student_id`, `date`, `status`, `marked_by`, `created_at`, `updated_at`) VALUES
	(1, 1, '2026-05-30', 'Present', 1, '2026-05-30 16:27:16', '2026-05-30 16:27:16'),
	(2, 2, '2026-05-30', 'Absent', 1, '2026-05-30 16:27:16', '2026-05-30 16:27:16'),
	(3, 1, '2026-06-02', 'Absent', 1, '2026-06-02 04:33:04', '2026-06-02 13:46:33'),
	(4, 2, '2026-06-02', 'Absent', 1, '2026-06-02 04:33:04', '2026-06-02 13:46:22'),
	(7, 1, '2026-05-31', 'Absent', 1, '2026-06-02 13:10:07', '2026-06-02 13:10:07'),
	(8, 2, '2026-05-31', 'Absent', 1, '2026-06-02 13:10:07', '2026-06-02 13:10:07'),
	(13, 3, '2026-06-02', 'Present', 1, '2026-06-02 14:06:27', '2026-06-02 14:06:27'),
	(17, 1, '2026-06-10', 'Present', 1, '2026-06-10 18:50:37', '2026-06-10 18:50:37'),
	(18, 2, '2026-06-10', 'Present', 1, '2026-06-10 18:50:37', '2026-06-10 18:50:37'),
	(19, 3, '2026-06-10', 'Present', 1, '2026-06-10 18:50:37', '2026-06-10 18:50:37');

-- Dumping structure for table student1_db.class_students
CREATE TABLE IF NOT EXISTS `class_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_class_student` (`class_id`,`student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `class_students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.class_students: ~2 rows (approximately)
INSERT INTO `class_students` (`id`, `class_id`, `student_id`, `assigned_at`) VALUES
	(1, 1, 1, '2026-06-03 15:13:30'),
	(2, 1, 2, '2026-06-03 15:13:30'),
	(4, 3, 3, '2026-06-04 04:33:30');

-- Dumping structure for table student1_db.classes
CREATE TABLE IF NOT EXISTS `classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_name` varchar(100) NOT NULL,
  `section` varchar(20) DEFAULT '',
  `academic_year` varchar(20) DEFAULT '',
  `class_teacher_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `class_teacher_id` (`class_teacher_id`),
  CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`class_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.classes: ~1 rows (approximately)
INSERT INTO `classes` (`id`, `class_name`, `section`, `academic_year`, `class_teacher_id`, `description`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'Class X', 'A', '2025-2026', 2, '', 'Active', '2026-06-03 15:06:00', '2026-06-03 15:06:00'),
	(3, 'Class X', 'B', '2025-2026', 3, '', 'Active', '2026-06-04 04:33:18', '2026-06-04 04:33:18');

-- Dumping structure for table student1_db.email_logs
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `email_type` varchar(50) NOT NULL COMMENT 'attendance|fee_invoice|marks_published|report_card|custom',
  `status` enum('sent','failed') NOT NULL DEFAULT 'failed',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `error_message` text DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL COMMENT 'e.g. student_id, payment_id',
  `related_type` varchar(50) DEFAULT NULL COMMENT 'student|payment|mark',
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.email_logs: ~10 rows (approximately)
INSERT INTO `email_logs` (`id`, `recipient_email`, `subject`, `email_type`, `status`, `sent_at`, `error_message`, `related_id`, `related_type`, `created_by`) VALUES
	(1, 'ashok@gmail.com', 'Student Report Card', 'custom', 'failed', '2026-06-10 18:02:08', 'SMTP error (expected 235, got 535): 535-5.7.8 Username and Password not accepted. For more information, go to\r\n535 5.7.8  https://support.google.com/mail/?p=BadCredentials d2e1a72fcca58-842822222ccsm30363112b3a.3 - gsmtp\r\n', 1, 'email', NULL),
	(2, 'balaji@gmail.com', 'Your Results Have Been Published — Tech Vision Educational Institute', 'marks_published', 'sent', '2026-06-10 18:41:55', NULL, 2, 'email', NULL),
	(3, 'techv10sion@gmail.com', 'Test Email from Student Management System', 'custom', 'sent', '2026-06-10 18:49:21', NULL, 0, 'email', NULL),
	(4, 'balaji@gmail.com', 'Fee Payment Confirmation — ₹7,000.00 Received', 'fee_invoice', 'sent', '2026-06-10 18:59:05', NULL, 2, 'email', NULL),
	(5, 'techv10sion@gmail.com', 'Test Email from Student Management System', 'custom', 'sent', '2026-06-11 04:35:49', NULL, 0, 'email', NULL),
	(6, 'ksthilaksujan2006@gmail.com', 'Registration Received — Tech Vision Educational Institute', 'custom', 'sent', '2026-06-11 16:25:40', NULL, 0, 'email', NULL),
	(7, 'ksthilaksujan2006@gmail.com', 'Account Approved — Tech Vision Educational Institute', 'custom', 'sent', '2026-06-11 16:26:09', NULL, 0, 'email', NULL),
	(8, 'ksthilaksujan2006@gmail.com', 'Password Reset OTP — Tech Vision Educational Institute', 'custom', 'sent', '2026-06-11 16:27:07', NULL, 0, 'email', NULL),
	(9, 'ksthilaksujan2006@gmail.com', 'Registration Received — Tech Vision Educational Institute', 'custom', 'sent', '2026-06-12 04:35:13', NULL, 0, 'email', NULL),
	(10, 'ksthilaksujan2006@gmail.com', 'Account Approved — Tech Vision Educational Institute', 'custom', 'sent', '2026-06-12 04:35:47', NULL, 0, 'email', NULL),
	(11, 'ksthilaksujan2006@gmail.com', 'Password Reset OTP — Tech Vision Educational Institute', 'custom', 'sent', '2026-06-12 04:36:13', NULL, 0, 'email', NULL);

-- Dumping structure for table student1_db.exam_schedule
CREATE TABLE IF NOT EXISTS `exam_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_title` varchar(200) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `exam_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(200) DEFAULT NULL,
  `exam_type` enum('Internal','External','Practical','Viva','Other') DEFAULT 'Internal',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `class_id` (`class_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `exam_schedule_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_schedule_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_schedule_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.exam_schedule: ~0 rows (approximately)
INSERT INTO `exam_schedule` (`id`, `exam_title`, `subject_id`, `class_id`, `exam_date`, `start_time`, `end_time`, `venue`, `exam_type`, `description`, `created_by`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'Mid-Term', 1, 1, '2026-06-05', '09:00:00', '10:00:00', 'Hall A', 'Internal', NULL, 1, 'Completed', '2026-06-05 04:26:25', '2026-06-06 04:02:19');

-- Dumping structure for table student1_db.fee_assignments
CREATE TABLE IF NOT EXISTS `fee_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fee_category_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fee_category_id` (`fee_category_id`),
  KEY `class_id` (`class_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fee_assignments_ibfk_1` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_assignments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fee_assignments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.fee_assignments: ~0 rows (approximately)

-- Dumping structure for table student1_db.fee_categories
CREATE TABLE IF NOT EXISTS `fee_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_permanent` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.fee_categories: ~6 rows (approximately)
INSERT INTO `fee_categories` (`id`, `name`, `description`, `is_permanent`, `status`, `created_at`, `updated_at`, `created_by`) VALUES
	(1, 'Tuition Fee', 'Core academic term / semester fee', 0, 'Active', '2026-06-05 07:57:02', '2026-06-05 07:57:02', NULL),
	(2, 'Admission Fee', 'One-time fee collected at first enrolment', 0, 'Active', '2026-06-05 07:57:02', '2026-06-05 11:42:03', NULL),
	(7, 'Transport Fee', 'School bus / daily commute charge', 0, 'Active', '2026-06-05 07:57:02', '2026-06-05 07:57:02', NULL),
	(8, 'Uniform Fee', 'Uniform purchase and maintenance charge', 0, 'Active', '2026-06-05 07:57:02', '2026-06-05 07:57:02', NULL),
	(10, 'Miscellaneous', 'Any other charge not covered above', 0, 'Active', '2026-06-05 07:57:02', '2026-06-05 07:57:02', NULL),
	(14, 'Book Fee', 'Purchasing Books', 0, 'Active', '0000-00-00 00:00:00', '2026-06-05 13:35:56', NULL);

-- Dumping structure for table student1_db.fee_payments
CREATE TABLE IF NOT EXISTS `fee_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `fee_assignment_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_mode` enum('Cash','Cheque','Online','Bank Transfer','Other') DEFAULT 'Cash',
  `receipt_no` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `fee_payments_ibfk_2` (`fee_assignment_id`),
  CONSTRAINT `fee_payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_payments_ibfk_2` FOREIGN KEY (`fee_assignment_id`) REFERENCES `fee_structures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_payments_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.fee_payments: ~4 rows (approximately)
INSERT INTO `fee_payments` (`id`, `student_id`, `fee_assignment_id`, `amount_paid`, `payment_date`, `payment_mode`, `receipt_no`, `remarks`, `recorded_by`, `created_at`, `updated_at`) VALUES
	(4, 1, 1, 3000.00, '2026-06-05', 'Cash', '', '', 1, '2026-06-05 13:41:31', '2026-06-05 13:41:31'),
	(5, 2, 2, 2000.00, '2026-06-08', 'Cash', '', '', 1, '2026-06-08 04:31:43', '2026-06-08 04:31:43'),
	(10, 3, 2, 3000.00, '2026-06-08', 'Cash', '', '', 1, '2026-06-08 13:32:26', '2026-06-08 13:32:26'),
	(11, 3, 1, 3000.00, '2026-06-08', 'Cash', '', '', 1, '2026-06-08 13:32:26', '2026-06-08 13:32:26'),
	(12, 2, 1, 5000.00, '2026-06-10', 'Cash', '', '', 1, '2026-06-10 18:58:56', '2026-06-10 18:58:56');

-- Dumping structure for table student1_db.fee_structures
CREATE TABLE IF NOT EXISTS `fee_structures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `class_id` (`class_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fee_structures_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `fee_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fee_structures_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.fee_structures: ~0 rows (approximately)
INSERT INTO `fee_structures` (`id`, `category_id`, `class_id`, `academic_year`, `amount`, `due_date`, `description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
	(1, 2, NULL, '2026-2027', 5000.00, '2026-06-10', '', 'Active', 1, '2026-06-05 13:38:41', '2026-06-05 13:38:41'),
	(2, 14, NULL, '2026-2027', 3000.00, '2026-06-10', '', 'Active', 1, '2026-06-08 04:31:20', '2026-06-08 04:31:20');

-- Dumping structure for table student1_db.institute_profile
CREATE TABLE IF NOT EXISTS `institute_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institute_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `principal_name` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `other_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table student1_db.institute_profile: ~0 rows (approximately)
INSERT INTO `institute_profile` (`id`, `institute_name`, `address`, `phone`, `email`, `principal_name`, `logo`, `other_details`, `created_at`, `updated_at`) VALUES
	(1, 'Tech Vision Educational Institute', 'Coimbatore,641103', '8563547892', 'prince@gmail.com', 'Principal', 'uploads/institute/logo_1779785281.jpeg', '', '2026-05-25 14:31:15', '2026-05-26 09:05:56');

-- Dumping structure for table student1_db.marks
CREATE TABLE IF NOT EXISTS `marks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `total_marks` decimal(5,2) DEFAULT 100.00,
  `status` varchar(20) DEFAULT 'Active',
  `published` tinyint(1) DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `published_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_subject` (`student_id`,`subject_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.marks: ~16 rows (approximately)
INSERT INTO `marks` (`id`, `student_id`, `subject_id`, `marks_obtained`, `total_marks`, `status`, `published`, `published_at`, `published_by`, `created_at`, `updated_at`) VALUES
	(4, 2, 1, 90.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-05-28 09:28:23', '2026-06-10 18:41:45'),
	(5, 2, 2, 90.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-05-28 09:28:23', '2026-06-10 18:41:45'),
	(6, 2, 3, 79.99, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-05-28 09:28:23', '2026-06-10 18:41:45'),
	(7, 2, 4, 80.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-05-28 09:28:23', '2026-06-10 18:41:45'),
	(8, 2, 5, 85.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-05-28 09:28:23', '2026-06-10 18:41:45'),
	(9, 1, 1, 85.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-05-28 09:29:55', '2026-06-10 18:41:45'),
	(10, 1, 2, 100.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-05-28 09:29:55', '2026-06-10 18:41:45'),
	(11, 1, 3, 80.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-05-28 09:29:55', '2026-06-10 18:41:45'),
	(12, 1, 4, 68.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-05-28 09:29:55', '2026-06-10 18:41:45'),
	(13, 1, 5, 87.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-05-28 09:29:55', '2026-06-10 18:41:45'),
	(14, 3, 1, 50.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-06-02 13:48:50', '2026-06-10 18:41:45'),
	(15, 3, 2, 90.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-06-02 13:48:50', '2026-06-10 18:41:45'),
	(16, 3, 3, 90.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-06-02 13:48:50', '2026-06-10 18:41:45'),
	(17, 3, 4, 100.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-06-02 13:48:50', '2026-06-10 18:41:45'),
	(18, 3, 5, 75.00, 100.00, 'Active', 1, '2026-06-10 18:41:45', NULL, '2026-06-02 13:48:50', '2026-06-10 18:41:45');

-- Dumping structure for table student1_db.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `target_audience` enum('Staff','Student','Both') NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.notifications: ~2 rows (approximately)
INSERT INTO `notifications` (`id`, `title`, `message`, `target_audience`, `expiry_date`, `status`, `created_by`, `created_at`) VALUES
	(1, 'Exam', 'The mid exams are nearning', 'Both', '2026-06-10', 'Active', 1, '2026-06-08 14:15:18'),
	(2, 'Fee', 'Pay your fees as soon as possible', 'Student', '2026-06-10', 'Active', 2, '2026-06-08 14:16:50'),
	(3, 'Fees', 'Fee due extended for 2 days', 'Both', '2026-06-12', 'Active', 1, '2026-06-09 04:34:19');

-- Dumping structure for table student1_db.students
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `parent_email` varchar(100) DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `skills` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.students: ~8 rows (approximately)
INSERT INTO `students` (`id`, `student_name`, `email`, `parent_email`, `parent_name`, `phone`, `gender`, `department`, `skills`, `dob`, `status`, `created_at`) VALUES
	(1, 'Ashok', 'ashok@gmail.com', NULL, NULL, '9056723148', 'Male', 'CSE', 'HTML,CSS', '2005-03-20', 'Active', '2026-05-15 03:30:00'),
	(2, 'Balaji', 'balaji@gmail.com', NULL, NULL, '5628904536', 'Male', 'ECE', 'PHP,JavaScript', '2005-05-10', 'Active', '2026-05-17 04:39:28'),
	(3, 'Devi', 'devi@gmail.com', NULL, NULL, '8769032541', 'Female', 'EEE', 'CSS,PHP,JavaScript', '2006-05-05', 'Active', '2026-05-25 07:50:00'),
	(4, 'Rohith', 'rohith@gmail.com', '', '', '9842370274', 'Male', 'ECE', 'HTML,CSS,JavaScript,Java', '2006-05-12', 'Active', '2026-06-15 09:36:23'),
	(5, 'Thilak', 'thilak@gmail.com', '', '', '9344697820', 'Male', 'ECE', 'HTML,CSS,PHP,JavaScript', '2006-12-10', 'Active', '2026-06-15 09:40:21'),
	(6, 'Barath', 'barath@gmail.com', '', '', '6383495499', 'Male', 'EEE', 'HTML,CSS,Java', '2006-01-01', 'Active', '2026-06-15 10:20:20'),
	(8, 'Divya', 'divya@gmail.com', NULL, NULL, '9032672413', 'Female', 'CSE', NULL, NULL, 'Active', '2026-06-19 04:45:47'),
	(9, 'Preethi', 'preethi@gmail.com', NULL, NULL, '7562319584', 'Female', 'ECE', NULL, NULL, 'Active', '2026-06-19 04:45:47');

-- Dumping structure for table student1_db.subjects
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `credit_hours` int(11) DEFAULT 3,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_code` (`subject_code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.subjects: ~6 rows (approximately)
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `credit_hours`, `status`, `created_at`) VALUES
	(1, 'CS101', 'Data Structure', 3, 'Active', '2026-05-23 03:28:20'),
	(2, 'CS102', 'Database Management System', 4, 'Active', '2026-05-23 03:28:20'),
	(3, 'CS103', 'Web Development', 3, 'Active', '2026-05-23 03:28:20'),
	(4, 'CS104', 'Operating Systems', 4, 'Active', '2026-05-23 03:28:20'),
	(5, 'CS105', 'Software Engineering', 3, 'Active', '2026-05-23 03:28:20');

-- Dumping structure for table student1_db.user_profiles
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `profile_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table student1_db.user_profiles: ~0 rows (approximately)
INSERT INTO `user_profiles` (`user_id`, `full_name`, `phone`, `profile_text`, `created_at`, `updated_at`) VALUES
	(1, '', '9344697820', '', '2026-06-02 13:33:13', '2026-06-02 13:50:12');

-- Dumping structure for table student1_db.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('admin','staff','student') NOT NULL DEFAULT 'student',
  `reset_otp` varchar(255) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `otp_verified` tinyint(1) DEFAULT 0,
  `otp_attempts` int(11) DEFAULT 0,
  `otp_last_sent` datetime DEFAULT NULL,
  `otp_send_count` int(11) DEFAULT 0,
  `account_status` enum('Pending','Approved','Rejected','Suspended') NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table student1_db.users: ~4 rows (approximately)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `role`, `reset_otp`, `otp_expiry`, `otp_verified`, `otp_attempts`, `otp_last_sent`, `otp_send_count`, `account_status`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `rejection_reason`) VALUES
	(1, 'abc', 'abc@gmail.com', '$2y$10$2basS/ZCd88fP9FhQAYxZegREZaMAPcqiCyidQIYCz/JqqbJI9rQy', '2026-05-19 18:18:14', 'admin', NULL, NULL, 0, 0, NULL, 0, 'Approved', NULL, NULL, NULL, NULL, NULL),
	(2, 'Arjun', 'arjun@gmail.com', '$2y$10$xsS7i12TB.kDqY0nBsZKi.JRBnUnhg0npt.9GOmzQrkWHOH3xC78G', '2026-05-21 14:31:43', 'staff', NULL, NULL, 0, 0, NULL, 0, 'Approved', NULL, NULL, NULL, NULL, NULL),
	(3, 'Manoj', 'manoj@gmail.com', '$2y$10$7LfUaxv.dmO/uOZ5ljiw/.7v4Sriu3zaYWSbvGn6fHLW/FuHxEtsC', '2026-06-04 04:26:18', 'staff', NULL, NULL, 0, 0, NULL, 0, 'Approved', NULL, NULL, NULL, NULL, NULL),
	(6, 'Thilak', 'ksthilaksujan2006@gmail.com', '$2y$10$CX.gQrfFBSJX7P2pav3WnuwUQwUvt7CDUYO3HKp.uF7N/bsS3z8yO', '2026-06-12 04:35:09', 'staff', '$2y$10$u9jmEAr1Jhthyo4wNTLTy.8ZW6kcUcFX983F9GXTD8y0mZE1g.yA.', '2026-06-12 06:46:09', 1, 0, '2026-06-12 10:06:09', 1, 'Approved', 1, '2026-06-12 10:05:42', NULL, NULL, NULL);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
