<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change to your MySQL username
define('DB_PASS', ''); // Change to your MySQL password
define('DB_NAME', '');

// Application configuration
define('APP_NAME', 'Forda Print');
define('APP_URL', 'http://127.0.0.1/project');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/jpeg',
    'image/png'
]);

// Print configurations
define('PRINT_LOCATIONS', [
    'library' => 'Main Library',
    'it_lab' => 'IT Lab',
    'admin_building' => 'Admin Building',
    'student_center' => 'Student Center'
]);

define('PRICE_PER_PAGE_BW', 0.05); // $0.05 per black and white page
define('PRICE_PER_PAGE_COLOR', 0.15); // $0.15 per color page
define('DUPLEX_DISCOUNT', 0.02); // $0.02 discount per page for duplex printing
?>