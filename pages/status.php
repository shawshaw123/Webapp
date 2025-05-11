<?php
$userId = getCurrentUserId();

// Get specific job if ID is provided
$jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : null;
$singleJob = null;

if ($jobId) {
    // Verify the user is authorized to view this job
    if (!isAuthorizedForJob($jobId, $userId) && !isAdmin()) {
        $_SESSION['message'] = "You are not authorized to view this print job.";
        $_SESSION['message_type'] = "error";
        header("Location: index.php?page=status");
        exit;
    }
    
    // Get the job details
    $stmt = $conn->prepare("
        SELECT pj.*, u.name as user_name
        FROM print_jobs pj
        LEFT JOIN users u ON pj.user_id = u.id
        WHERE pj.id = ?
    ");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $singleJob = $stmt->get_result()->fetch_assoc();
    
    // Mark any notifications related to this job as read
    if ($singleJob) {
        $markRead = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND job_id = ?");
        $markRead->bind_param("ii", $userId, $jobId);
        $markRead->execute();
    }
}

// Cancel job if requested
if (isset($_POST['cancel_job']) && isset($_POST['job_id'])) {
    $cancelJobId = intval($_POST['job_id']);
    
    // Verify the user is authorized to cancel this job
    if (isAuthorizedForJob($cancelJobId, $userId)) {
        // Only allow cancellation if the job is pending
        $checkStatus = $conn->prepare("SELECT status FROM print_jobs WHERE id = ?");
        $checkStatus->bind_param("i", $cancelJobId);
        $checkStatus->execute();
        $result = $checkStatus->get_result()->fetch_assoc();
        
        if ($result && $result['status'] === 'pending') {
            // Update the job status
            $updateStatus = $conn->prepare("UPDATE print_jobs SET status = 'cancelled' WHERE id = ?");
            $updateStatus->bind_param("i", $cancelJobId);
            
            if ($updateStatus->execute()) {
                // Log the activity
                logActivity($userId, 'cancel_job', "Cancelled print job #$cancelJobId");
                
                // Create notification
                $message = "Your print job #$cancelJobId has been cancelled.";
                createNotification($userId, $message, $cancelJobId);
                
                $_SESSION['message'] = "Print job cancelled successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to cancel print job.";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Only pending jobs can be cancelled.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "You are not authorized to cancel this print job.";
        $_SESSION['message_type'] = "error";
    }
    
    // Redirect to refresh the page
    header("Location: index.php?page=status" . ($jobId ? "&job_id=$jobId" : ""));
    exit;
}

// Get all jobs if no specific ID is provided
if (!$jobId) {
    // Get all jobs for the current user
    $stmt = $conn->prepare("
        SELECT id, original_filename, pages, copies, color, cost, location, status, created_at, updated_at
        FROM print_jobs
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $allJobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<?php if ($singleJob): ?>
    <h2>Print Job Details</h2>
    
    <div class="job-details-container">
        <div class="job-details-main">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3>Job #<?php echo $singleJob['id']; ?></h3>
                        <p class="job-date">Submitted on <?php echo date('F j, Y \a\t g:i a', strtotime($singleJob['created_at'])); ?></p>
                    </div>
                    <?php 
                        $statusDetails = getStatusDetails($singleJob['status']);
                        echo '<span class="status-badge ' . $statusDetails[1] . '">' . $statusDetails[0] . '</span>';
                    ?>
                </div>
                <div class="card-body">
                    <div class="job-info-grid">
                        <div class="job-info-section">
                            <h4>Document Information</h4>
                            <div class="job-info-rows">
                                <div class="job-info-row">
                                    <span class="info-label">File Name:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($singleJob['original_filename']); ?></span>
                                </div>
                                <div class="job-info-row">
                                    <span class="info-label">File Size:</span>
                                    <span class="info-value"><?php echo formatFileSize($singleJob['file_size']); ?></span>
                                </div>
                                <div class="job-info-row">
                                    <span class="info-label">Pages:</span>
                                    <span class="info-value"><?php echo $singleJob['pages']; ?></span>
                                </div>
                                <div class="job-info-row">
                                    <span class="info-label">Copies:</span>
                                    <span class="info-value"><?php echo $singleJob['copies']; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="job-info-section">
                            <h4>Print Settings</h4>
                            <div class="job-info-rows">
                                <div class="job-info-row">
                                    <span class="info-label">Color:</span>
                                    <span class="info-value"><?php echo $singleJob['color'] === 'color' ? 'Color' : 'Black & White'; ?></span>
                                </div>
                                <div class="job-info-row">
                                    <span class="info-label">Paper Size:</span>
                                    <span class="info-value"><?php echo strtoupper($singleJob['paper_size']); ?></span>
                                </div>
                                <div class="job-info-row">
                                    <span class="info-label">Orientation:</span>
                                    <span class="info-value"><?php echo ucfirst($singleJob['orientation']); ?></span>
                                </div>
                                <div class="job-info-row">
                                    <span class="info-label">Double-sided:</span>
                                    <span class="info-value"><?php echo $singleJob['duplex'] ? 'Yes' : 'No'; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="job-info-section">
                            <h4>Payment Information</h4>
                            <div class="job-info-rows">
                                <div class="job-info-row">
                                    <span class="info-label">Total Cost:</span>
                                    <span class="info-value cost-value"><?php echo formatPrice($singleJob['cost']); ?></span>
                                </div>
                                <div class="job-info-row">
                                    <span class="info-label">Payment Method:</span>
                                    <span class="info-value"><?php echo $singleJob['payment_method'] === 'credits' ? 'Credits' : 'Cash on Pickup'; ?></span>
                                </div>
                                <div class="job-info-row">
                                    <span class="info-label">Payment Status:</span>
                                    <span class="info-value">
                                        <?php if ($singleJob['payment_status'] === 'paid'): ?>
                                            <span class="status-badge status-ready">Paid</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Pending</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="job-info-section">
                            <h4>Pickup Information</h4>
                            <div class="job-info-rows">
                                <div class="job-info-row">
                                    <span class="info-label">Location:</span>
                                    <span class="info-value"><?php echo PRINT_LOCATIONS[$singleJob['location']]; ?></span>
                                </div>
                                <?php if (!empty($singleJob['notes'])): ?>
                                <div class="job-info-row">
                                    <span class="info-label">Notes:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($singleJob['notes']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($singleJob['status'] === 'pending'): ?>
                        <div class="job-actions">
                            <form action="" method="post" onsubmit="return confirm('Are you sure you want to cancel this print job?');">
                                <input type="hidden" name="job_id" value="<?php echo $singleJob['id']; ?>">
                                <button type="submit" name="cancel_job" class="btn btn-danger">Cancel Print Job</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="buttons-row">
                <a href="index.php?page=status" class="btn btn-outline">Back to All Jobs</a>
                <a href="index.php?page=upload" class="btn btn-primary">New Print Job</a>
            </div>
        </div>
        
        <div class="job-details-sidebar">
            <div class="card">
                <div class="card-header">
                    <h3>Status Timeline</h3>
                </div>
                <div class="card-body">
                    <div class="status-timeline">
                        <div class="timeline-item <?php echo $singleJob['status'] !== 'cancelled' ? 'active' : 'cancelled'; ?>">
                            <div class="timeline-badge"><i class="fas fa-file-upload"></i></div>
                            <div class="timeline-content">
                                <h4>Submitted</h4>
                                <p><?php echo date('M j, g:i a', strtotime($singleJob['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="timeline-item <?php echo in_array($singleJob['status'], ['printing', 'ready', 'completed']) ? 'active' : ($singleJob['status'] === 'cancelled' ? 'cancelled' : ''); ?>">
                            <div class="timeline-badge"><i class="fas fa-print"></i></div>
                            <div class="timeline-content">
                                <h4>Printing</h4>
                                <?php if ($singleJob['status'] === 'printing'): ?>
                                    <p>In progress</p>
                                <?php elseif (in_array($singleJob['status'], ['ready', 'completed'])): ?>
                                    <p>Completed</p>
                                <?php elseif ($singleJob['status'] === 'cancelled'): ?>
                                    <p>Cancelled</p>
                                <?php else: ?>
                                    <p>Waiting</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="timeline-item <?php echo in_array($singleJob['status'], ['ready', 'completed']) ? 'active' : ($singleJob['status'] === 'cancelled' ? 'cancelled' : ''); ?>">
                            <div class="timeline-badge"><i class="fas fa-check"></i></div>
                            <div class="timeline-content">
                                <h4>Ready for Pickup</h4>
                                <?php if ($singleJob['status'] === 'ready'): ?>
                                    <p>Available at <?php echo PRINT_LOCATIONS[$singleJob['location']]; ?></p>
                                <?php elseif ($singleJob['status'] === 'completed'): ?>
                                    <p>Picked up</p>
                                <?php elseif ($singleJob['status'] === 'cancelled'): ?>
                                    <p>Cancelled</p>
                                <?php else: ?>
                                    <p>Waiting</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="timeline-item <?php echo $singleJob['status'] === 'completed' ? 'active' : ($singleJob['status'] === 'cancelled' ? 'cancelled' : ''); ?>">
                            <div class="timeline-badge"><i class="fas fa-flag-checkered"></i></div>
                            <div class="timeline-content">
                                <h4>Completed</h4>
                                <?php if ($singleJob['status'] === 'completed'): ?>
                                    <p><?php echo date('M j, g:i a', strtotime($singleJob['updated_at'])); ?></p>
                                <?php elseif ($singleJob['status'] === 'cancelled'): ?>
                                    <p>Cancelled</p>
                                <?php else: ?>
                                    <p>Waiting</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Pickup Location</h3>
                </div>
                <div class="card-body">
                    <div class="location-info">
                        <h4><?php echo PRINT_LOCATIONS[$singleJob['location']]; ?></h4>
                        <?php 
                            $locationHours = '';
                            switch($singleJob['location']) {
                                case 'library':
                                    $locationHours = '7:00 AM - 10:00 PM';
                                    break;
                                case 'it_lab':
                                    $locationHours = '8:00 AM - 8:00 PM';
                                    break;
                                case 'admin_building':
                                    $locationHours = '9:00 AM - 5:00 PM';
                                    break;
                                case 'student_center':
                                    $locationHours = '7:00 AM - 7:00 PM';
                                    break;
                            }
                        ?>
                        <p><i class="fas fa-clock"></i> Open <?php echo $locationHours; ?></p>
                        <?php if ($singleJob['status'] === 'ready'): ?>
                            <p class="pickup-note"><i class="fas fa-info-circle"></i> You will need to show your student ID when picking up your print job.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <h2>Print Job Status</h2>
    
    <div class="job-list-container">
        <div class="card">
            <div class="card-header">
                <h3>Your Print Jobs</h3>
                <a href="index.php?page=upload" class="btn btn-primary">New Print Job</a>
            </div>
            <div class="card-body">
                <?php if (isset($allJobs) && count($allJobs) > 0): ?>
                    <div class="tabs">
                        <div class="tab active" data-tab="all">All</div>
                        <div class="tab" data-tab="pending">Pending</div>
                        <div class="tab" data-tab="processing">Processing</div>
                        <div class="tab" data-tab="completed">Completed</div>
                    </div>
                    
                    <div class="job-list">
                        <?php foreach ($allJobs as $job): ?>
                            <?php $statusDetails = getStatusDetails($job['status']); ?>
                            <div class="job-card" data-status="<?php echo $job['status']; ?>">
                                <div class="job-status <?php echo $job['status']; ?>"></div>
                                <div class="job-content">
                                    <div class="job-header">
                                        <div>
                                            <div class="job-title"><?php echo htmlspecialchars($job['original_filename']); ?></div>
                                            <div class="job-id">Job #<?php echo $job['id']; ?> • <?php echo date('M j, Y', strtotime($job['created_at'])); ?></div>
                                        </div>
                                        <span class="status-badge <?php echo $statusDetails[1]; ?>"><?php echo $statusDetails[0]; ?></span>
                                    </div>
                                    <div class="job-details">
                                        <div class="job-detail">
                                            <i class="fas fa-copy"></i>
                                            <?php echo $job['pages']; ?> pages × <?php echo $job['copies']; ?> <?php echo $job['copies'] > 1 ? 'copies' : 'copy'; ?>
                                        </div>
                                        <div class="job-detail">
                                            <i class="fas fa-palette"></i>
                                            <?php echo $job['color'] == 'color' ? 'Color' : 'Black & White'; ?>
                                        </div>
                                        <div class="job-detail">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo PRINT_LOCATIONS[$job['location']]; ?>
                                        </div>
                                        <div class="job-detail">
                                            <i class="fas fa-dollar-sign"></i>
                                            <?php echo formatPrice($job['cost']); ?>
                                        </div>
                                    </div>
                                    <div class="job-actions">
                                        <a href="index.php?page=status&job_id=<?php echo $job['id']; ?>" class="btn btn-outline btn-sm">View Details</a>
                                        <?php if ($job['status'] === 'pending'): ?>
                                            <form action="" method="post" class="inline-form" onsubmit="return confirm('Are you sure you want to cancel this print job?');">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <button type="submit" name="cancel_job" class="btn btn-danger btn-sm">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-data">
                        <i class="fas fa-print"></i>
                        <p>You haven't submitted any print jobs yet.</p>
                        <a href="index.php?page=upload" class="btn btn-primary">Create New Print Job</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const jobCards = document.querySelectorAll('.job-card');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabType = this.dataset.tab;
                    
                    // Update active tab
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show/hide job cards based on tab
                    jobCards.forEach(card => {
                        const status = card.dataset.status;
                        
                        if (tabType === 'all') {
                            card.style.display = 'flex';
                        } else if (tabType === 'pending' && status === 'pending') {
                            card.style.display = 'flex';
                        } else if (tabType === 'processing' && (status === 'printing' || status === 'ready')) {
                            card.style.display = 'flex';
                        } else if (tabType === 'completed' && (status === 'completed' || status === 'cancelled')) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
<?php endif; ?>

<style>
.job-details-container {
    display: grid;
    grid-template-columns: 3fr 2fr;
    gap: var(--spacing-8);
}

.job-details-main {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.job-date {
    color: var(--gray-500);
    margin-top: var(--spacing-1);
    font-size: 0.875rem;
}

.job-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-6);
}

.job-info-section {
    margin-bottom: var(--spacing-6);
}

.job-info-section h4 {
    margin-bottom: var(--spacing-4);
    padding-bottom: var(--spacing-2);
    border-bottom: 1px solid var(--gray-200);
    color: var(--gray-700);
}

.job-info-rows {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-3);
}

.job-info-row {
    display: flex;
    justify-content: space-between;
}

.info-label {
    color: var(--gray-500);
}

.cost-value {
    font-weight: 700;
    color: var(--primary);
}

.buttons-row {
    display: flex;
    gap: var(--spacing-4);
}

.status-timeline {
    position: relative;
    margin-left: var(--spacing-6);
}

.status-timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 12px;
    width: 2px;
    background-color: var(--gray-300);
    transform: translateX(-50%);
}

.timeline-item {
    position: relative;
    padding-bottom: var(--spacing-6);
    padding-left: var(--spacing-8);
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-badge {
    position: absolute;
    top: 0;
    left: 0;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: var(--gray-300);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}

.timeline-item.active .timeline-badge {
    background-color: var(--primary);
}

.timeline-item.cancelled .timeline-badge {
    background-color: var(--error);
}

.timeline-content h4 {
    margin-bottom: var(--spacing-1);
}

.timeline-content p {
    color: var(--gray-500);
    margin-bottom: 0;
    font-size: 0.875rem;
}

.location-info h4 {
    margin-bottom: var(--spacing-2);
}

.location-info p {
    display: flex;
    align-items: center;
    margin-bottom: var(--spacing-2);
}

.location-info i {
    margin-right: var(--spacing-2);
    color: var(--primary);
}

.pickup-note {
    background-color: var(--gray-100);
    padding: var(--spacing-3);
    border-radius: var(--border-radius);
    margin-top: var(--spacing-4);
}

.pickup-note i {
    color: var(--primary);
}

.job-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--spacing-6);
    padding-top: var(--spacing-4);
    border-top: 1px solid var(--gray-200);
}

.inline-form {
    display: inline;
}

.job-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

@media (max-width: 992px) {
    .job-details-container {
        grid-template-columns: 1fr;
    }
    
    .job-info-grid {
        grid-template-columns: 1fr;
    }
}
</style>