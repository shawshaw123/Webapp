<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Process AJAX requests
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'update_status':
            updateJobStatus();
            break;
            
        case 'add_credits':
            addCredits();
            break;
            
        case 'update_pricing':
            updatePricing();
            break;
            
        case 'update_settings':
            updateSettings();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

/**
 * Update print job status
 */
function updateJobStatus() {
    global $conn;
    
    $jobId = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $newStatus = isset($_POST['status']) ? $_POST['status'] : '';
    $paymentStatus = isset($_POST['payment_status']) ? $_POST['payment_status'] : '';
    $notify = isset($_POST['notify']) ? (bool)$_POST['notify'] : false;
    
    // Validate inputs
    if (!$jobId || !in_array($newStatus, ['pending', 'printing', 'ready', 'completed', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }
    
    if (!in_array($paymentStatus, ['pending', 'paid'])) {
        $paymentStatus = 'pending';
    }
    
    // Get the current job status and user ID
    $stmt = $conn->prepare("SELECT status, user_id FROM print_jobs WHERE id = ?");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        return;
    }
    
    $job = $result->fetch_assoc();
    $currentStatus = $job['status'];
    $userId = $job['user_id'];
    
    // Update the job status
    $stmt = $conn->prepare("UPDATE print_jobs SET status = ?, payment_status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $newStatus, $paymentStatus, $jobId);
    
    if ($stmt->execute()) {
        // Log the activity
        $details = "Updated job #$jobId status from $currentStatus to $newStatus";
        logActivity(getCurrentUserId(), 'update_job', $details);
        
        // Create notification if requested
        if ($notify) {
            $message = '';
            
            switch ($newStatus) {
                case 'printing':
                    $message = "Your print job #$jobId is now being printed.";
                    break;
                case 'ready':
                    $message = "Your print job #$jobId is ready for pickup.";
                    break;
                case 'completed':
                    $message = "Your print job #$jobId has been marked as completed.";
                    break;
                case 'cancelled':
                    $message = "Your print job #$jobId has been cancelled by an administrator.";
                    break;
                default:
                    $message = "Your print job #$jobId status has been updated to: " . ucfirst($newStatus);
                    break;
            }
            
            createNotification($userId, $message, $jobId);
        }
        
        echo json_encode(['success' => true, 'message' => 'Job status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update job status: ' . $conn->error]);
    }
}

/**
 * Add credits to a user
 */
function addCredits() {
    global $conn;
    
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $note = isset($_POST['note']) ? sanitize($_POST['note']) : '';
    
    // Validate inputs
    if (!$userId || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }
    
    // Get current user credits
    $stmt = $conn->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    $currentCredits = $result->fetch_assoc()['credits'];
    $newBalance = $currentCredits + $amount;
    
    // Update user credits
    $stmt = $conn->prepare("UPDATE users SET credits = ? WHERE id = ?");
    $stmt->bind_param("di", $newBalance, $userId);
    
    if ($stmt->execute()) {
        // Log the activity
        $details = "Added $amount credits to user #$userId" . ($note ? " (Note: $note)" : "");
        logActivity(getCurrentUserId(), 'add_credits', $details);
        
        // Create notification
        $message = "Your account has been credited with " . formatPrice($amount) . ".";
        if ($note) {
            $message .= " Note: $note";
        }
        createNotification($userId, $message);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Credits added successfully',
            'newBalance' => number_format($newBalance, 2)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add credits: ' . $conn->error]);
    }
}

/**
 * Update pricing settings
 */
function updatePricing() {
    global $conn;
    
    $priceBW = isset($_POST['price_bw']) ? floatval($_POST['price_bw']) : 0;
    $priceColor = isset($_POST['price_color']) ? floatval($_POST['price_color']) : 0;
    $duplexDiscount = isset($_POST['duplex_discount']) ? floatval($_POST['duplex_discount']) : 0;
    $bulkThreshold = isset($_POST['bulk_threshold']) ? intval($_POST['bulk_threshold']) : 0;
    $bulkDiscount = isset($_POST['bulk_discount']) ? intval($_POST['bulk_discount']) : 0;
    
    // Validate inputs
    if ($priceBW <= 0 || $priceColor <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid pricing values']);
        return;
    }
    
    // This would normally update configuration settings
    // For demo purposes, we'll just return success
    echo json_encode(['success' => true, 'message' => 'Pricing settings updated successfully']);
}

/**
 * Update system settings
 */
function updateSettings() {
    global $conn;
    
    $maxFileSize = isset($_POST['max_file_size']) ? intval($_POST['max_file_size']) : 10;
    $fileRetention = isset($_POST['file_retention']) ? intval($_POST['file_retention']) : 7;
    $siteName = isset($_POST['site_name']) ? sanitize($_POST['site_name']) : 'University Print System';
    $adminEmail = isset($_POST['admin_email']) ? sanitize($_POST['admin_email']) : '';
    $enableNotifications = isset($_POST['enable_notifications']) ? (bool)$_POST['enable_notifications'] : false;
    
    // This would normally update configuration settings
    // For demo purposes, we'll just return success
    echo json_encode(['success' => true, 'message' => 'System settings updated successfully']);
}
?>