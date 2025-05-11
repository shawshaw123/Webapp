<?php
/**
 * Utility functions for the University Print System
 */

/**
 * Sanitize user input
 * @param string $data The data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate a unique filename for uploaded files
 * @param string $originalName Original filename
 * @return string New unique filename
 */
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Check if a file type is allowed
 * @param string $fileType MIME type of the file
 * @return bool True if allowed, false otherwise
 */
function isAllowedFileType($fileType) {
    return in_array($fileType, ALLOWED_FILE_TYPES);
}

/**
 * Format a price with currency symbol
 * @param float $price The price to format
 * @return string Formatted price
 */
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

/**
 * Calculate the cost of a print job
 * @param int $pages Number of pages
 * @param string $colorOption Color or black and white
 * @param int $copies Number of copies
 * @param bool $duplex Double-sided printing
 * @return float Total cost
 */
function calculatePrintCost($pages, $colorOption, $copies, $duplex) {
    $pricePerPage = ($colorOption == 'color') ? PRICE_PER_PAGE_COLOR : PRICE_PER_PAGE_BW;
    
    // Apply duplex discount if applicable
    if ($duplex) {
        $pricePerPage -= DUPLEX_DISCOUNT;
    }
    
    return $pages * $pricePerPage * $copies;
}

/**
 * Get human readable file size
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Get the status label with appropriate CSS class
 * @param string $status Status of the print job
 * @return array Array with label and class
 */
function getStatusDetails($status) {
    switch ($status) {
        case 'pending':
            return ['Pending', 'status-pending'];
        case 'printing':
            return ['Printing', 'status-printing'];
        case 'ready':
            return ['Ready for Pickup', 'status-ready'];
        case 'completed':
            return ['Completed', 'status-completed'];
        case 'cancelled':
            return ['Cancelled', 'status-cancelled'];
        default:
            return ['Unknown', 'status-unknown'];
    }
}

/**
 * Check if the current user is authorized to view a print job
 * @param int $jobId The print job ID
 * @param int $userId The user ID
 * @return bool True if authorized, false otherwise
 */
function isAuthorizedForJob($jobId, $userId) {
    global $conn;
    
    // Admins can view all jobs
    if (isAdmin()) {
        return true;
    }
    
    $stmt = $conn->prepare("SELECT id FROM print_jobs WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $jobId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}
?>