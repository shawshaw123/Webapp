<?php
/**
 * Authentication functions
 */

/**
 * Attempt to log in a user
 * @param string $studentId Student ID
 * @param string $password Password
 * @return bool True if login successful, false otherwise
 */
function login($studentId, $password) {
    // TEMPORARY: Auto-login for development purposes
    // Set session variables with dummy user data
    $_SESSION['user_id'] = 1;
    $_SESSION['student_id'] = $studentId;
    $_SESSION['name'] = 'Development User';
    $_SESSION['role'] = strtolower($studentId) === 'admin001' ? 'admin' : 'student';
    
    return true;
}

/**
 * Register a new user
 * @param string $studentId Student ID
 * @param string $name Full name
 * @param string $email Email address
 * @param string $password Password
 * @return bool|string True if registration successful, error message otherwise
 */
function register($studentId, $name, $email, $password) {
    global $conn;
    
    // Check if student ID already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE student_id = ?");
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return "Student ID already exists";
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return "Email already exists";
    }
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert the new user
    $stmt = $conn->prepare("INSERT INTO users (student_id, name, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $studentId, $name, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Registration failed: " . $conn->error;
    }
}

/**
 * Log out the current user
 */
function logout() {
    // Log the logout activity if user is logged in
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}

/**
 * Check if a user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the current user is an admin
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

/**
 * Get the current user's ID
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get the current user's name
 * @return string|null User name if logged in, null otherwise
 */
function getCurrentUserName() {
    return isset($_SESSION['name']) ? $_SESSION['name'] : null;
}

/**
 * Get the current user's student ID
 * @return string|null Student ID if logged in, null otherwise
 */
function getCurrentStudentId() {
    return isset($_SESSION['student_id']) ? $_SESSION['student_id'] : null;
}

/**
 * Get user details by ID
 * @param int $userId User ID
 * @return array|null User details if found, null otherwise
 */
function getUserById($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, student_id, name, email, credits, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}
?>