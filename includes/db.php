<?php
// TEMPORARY DISABLED DATABASE CONNECTION
// This is a placeholder that prevents database errors while working on the frontend

// Create a mock database class
class MockDatabase {
    // Return mock results for queries and prepared statements
    public function query($sql) {
        return new MockResult();
    }
    
    public function prepare($sql) {
        return new MockStatement();
    }
    
    // Simulate error reporting
    public function error() {
        return "";
    }
    
    public function select_db($dbname) {
        return true;
    }
}

// Mock prepared statement class
class MockStatement {
    public function bind_param($types, ...$params) {
        return true;
    }
    
    public function execute() {
        return true;
    }
    
    public function get_result() {
        return new MockResult();
    }
}

// Mock result set with sample data
class MockResult {
    public function fetch_assoc() {
        // Return sample data based on common queries
        return [
            'id' => 1,
            'user_id' => 1,
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'student_id' => 'STUDENT001',
            'credits' => 100.00,
            'role' => 'admin',
            'password' => '$2y$10$abcdefghijklmnopqrstuv',
            'total' => 42,
            'count' => 5,
            'total_spent' => 75.50,
            'filename' => 'sample_file.pdf',
            'original_filename' => 'Report.pdf',
            'file_size' => 1024000,
            'pages' => 10,
            'copies' => 2,
            'color' => true,
            'status' => 'pending',
            'cost' => 15.00,
            'location' => 'Library',
            'created_at' => date('Y-m-d H:i:s', time() - 86400),
            'job_count' => 8,
            'message' => 'Your print job is ready for pickup'
        ];
    }
    
    public function fetch_all($mode = MYSQLI_ASSOC) {
        // Create a sample array of records
        $results = [];
        $statuses = ['pending', 'printing', 'ready', 'completed'];
        $locations = ['Library', 'IT Lab', 'Student Center', 'Admin Building'];
        
        for ($i = 1; $i <= 5; $i++) {
            $results[] = [
                'id' => $i,
                'user_id' => 1,
                'student_id' => 'STUDENT00' . $i,
                'name' => 'User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'credits' => 100 - ($i * 10),
                'role' => $i === 1 ? 'admin' : 'student',
                'status' => $statuses[$i % 4],
                'count' => $i * 5,
                'filename' => 'file' . $i . '.pdf',
                'original_filename' => 'Document ' . $i . '.pdf',
                'file_size' => $i * 500000,
                'pages' => $i * 5,
                'copies' => $i % 3 + 1,
                'color' => ($i % 2 == 0),
                'cost' => $i * 5.25,
                'location' => $locations[$i % 4],
                'created_at' => date('Y-m-d H:i:s', time() - (86400 * $i)),
                'job_id' => $i,
                'message' => 'Notification message ' . $i
            ];
        }
        
        return $results;
    }
    
    public function num_rows() {
        return 1;
    }
}

// Create the mock database connection
$conn = new MockDatabase();

// Helper functions
function logActivity($userId, $action, $details = "") {
    return true;
}

function createNotification($userId, $message, $jobId = null) {
    return true;
}

// Define error suppression for database-heavy pages
error_reporting(E_ERROR | E_PARSE); // Only show critical errors
?>