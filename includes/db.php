<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "print";


// Create connection
$conn = new mysqli("127.0.0.1", "root", "", "your_database");


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);

// Create tables if they don't exist
$tables = [
    // Users table
    "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        credits DECIMAL(10,2) DEFAULT 0.00,
    )",
    
    // Print jobs table
    "CREATE TABLE IF NOT EXISTS print_jobs (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        file_size INT(11) NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        pages INT(11) NOT NULL,
        copies INT(11) DEFAULT 1,
    )",
    
    
    
    
];

// Execute each table creation query
foreach ($tables as $sql) {
    if ($conn->query($sql) === FALSE) {
        die("Error creating table: " . $conn->error);
    }
}

// Create default admin user if not exists
$adminCheck = $conn->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
if ($adminCheck->num_rows == 0) {
    $adminPass = password_hash("admin123", PASSWORD_DEFAULT);
    $insertAdmin = $conn->prepare("INSERT INTO users (student_id, name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $adminId = "ADMIN001";
    $adminName = "System Administrator";
    $adminEmail = "admin@university.edu";
    $adminRole = "admin";
    $insertAdmin->bind_param("sssss", $adminId, $adminName, $adminEmail, $adminPass, $adminRole);
    $insertAdmin->execute();
}

/**
 * Log user activity
 * @param int $userId User ID
 * @param string $action The action performed
 * @param string $details Additional details
 * @return bool True on success, false on failure
 */
function logActivity($userId, $action, $details = "") {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("isss", $userId, $action, $details, $ipAddress);
    
    return $stmt->execute();
}

/**
 * Create a notification for a user
 * @param int $userId User ID
 * @param string $message Notification message
 * @param int $jobId Optional job ID related to the notification
 * @return bool True on success, false on failure
 */
function createNotification($userId, $message, $jobId = null) {
    global $conn;
    
    if ($jobId === null) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $message);
    } else {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, job_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $userId, $jobId, $message);
    }
    
    return $stmt->execute();
}
?>