<?php
require_once 'config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    target_audience ENUM('Staff', 'Student', 'Both') NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
)";

if ($mysqli->query($sql)) {
    echo "Notifications table created successfully.\n";
} else {
    echo "Error creating table: " . $mysqli->error . "\n";
}
