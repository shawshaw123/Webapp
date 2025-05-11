<?php
// Get user data
$userId = getCurrentUserId();
$user = getUserById($userId);

// Get recent print jobs
$stmt = $conn->prepare("
    SELECT id, filename, original_filename, file_size, pages, copies, color, cost, location, status, created_at
    FROM print_jobs
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentJobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count jobs by status
$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count
    FROM print_jobs
    WHERE user_id = ?
    GROUP BY status
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$statusCounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Convert to associative array for easier access
$jobsByStatus = [];
foreach ($statusCounts as $status) {
    $jobsByStatus[$status['status']] = $status['count'];
}

// Get total spent
$stmt = $conn->prepare("
    SELECT SUM(cost) as total_spent
    FROM print_jobs
    WHERE user_id = ? AND status != 'cancelled'
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$totalSpent = $result['total_spent'] ?? 0;

// Get unread notifications
$stmt = $conn->prepare("
    SELECT id, message, job_id, created_at
    FROM notifications
    WHERE user_id = ? AND is_read = 0
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h2>Dashboard</h2>

<div class="dashboard-overview">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-content">
                <h3>Your Credits</h3>
                <p><?php echo formatPrice($user['credits']); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-print"></i></div>
            <div class="stat-content">
                <h3>Total Print Jobs</h3>
                <p><?php echo array_sum($jobsByStatus); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <h3>Pending Jobs</h3>
                <p><?php echo isset($jobsByStatus['pending']) ? $jobsByStatus['pending'] : 0; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-content">
                <h3>Total Spent</h3>
                <p><?php echo formatPrice($totalSpent); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-content">
    <div class="dashboard-row">
        <div class="dashboard-column">
            <div class="card">
                <div class="card-header">
                    <h3>Recent Print Jobs</h3>
                    <a href="index.php?page=status" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($recentJobs) > 0): ?>
                        <?php foreach ($recentJobs as $job): ?>
                            <?php $statusDetails = getStatusDetails($job['status']); ?>
                            <div class="job-card">
                                <div class="job-status <?php echo $job['status']; ?>"></div>
                                <div class="job-content">
                                    <div class="job-header">
                                        <div>
                                            <div class="job-title"><?php echo htmlspecialchars($job['original_filename']); ?></div>
                                            <div class="job-id">Job #<?php echo $job['id']; ?></div>
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
                                            <i class="fas fa-dollar-sign"></i>
                                            <?php echo formatPrice($job['cost']); ?>
                                        </div>
                                    </div>
                                    <div class="job-actions">
                                        <a href="index.php?page=status&job_id=<?php echo $job['id']; ?>" class="btn btn-outline btn-sm">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
        
        <div class="dashboard-column-sidebar">
            <div class="card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="index.php?page=upload" class="quick-action">
                            <i class="fas fa-plus"></i>
                            <span>New Print Job</span>
                        </a>
                        <a href="index.php?page=status" class="quick-action">
                            <i class="fas fa-tasks"></i>
                            <span>View All Jobs</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Notifications</h3>
                </div>
                <div class="card-body">
                    <?php if (count($notifications) > 0): ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification">
                                    <div class="notification-content">
                                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <small class="notification-time"><?php echo date('M j, g:i a', strtotime($notification['created_at'])); ?></small>
                                    </div>
                                    <?php if ($notification['job_id']): ?>
                                        <a href="index.php?page=status&job_id=<?php echo $notification['job_id']; ?>" class="notification-link">
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">You have no new notifications.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.dashboard-overview {
    margin-bottom: var(--spacing-8);
}

.dashboard-content {
    margin-bottom: var(--spacing-8);
}

.dashboard-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-6);
}

.btn-sm {
    padding: var(--spacing-2) var(--spacing-4);
    font-size: 0.875rem;
}

.empty-data {
    text-align: center;
    padding: var(--spacing-8) 0;
}

.empty-data i {
    font-size: 3rem;
    color: #a3a3a3; /* neutral muted icon */
    margin-bottom: var(--spacing-4);
}

.quick-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-4);
}

.quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-4);
    background-color: #d1fae5; /* light green */
    border-radius: var(--border-radius);
    transition: all 0.2s ease;
    color: #065f46;
}

.quick-action:hover {
    background-color: #10b981; /* strong green */
    color: #ffffff;
    transform: translateY(-2px);
}

.quick-action i {
    font-size: 1.5rem;
    margin-bottom: var(--spacing-2);
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.notification {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-3);
    border-radius: var(--border-radius);
    background-color: #d1fae5; /* light green */
    transition: background-color 0.2s ease;
    color: #065f46;
}

.notification:hover {
    background-color: #bbf7d0;
}

.notification-content {
    flex: 1;
}

.notification-content p {
    margin-bottom: var(--spacing-1);
    font-size: 0.875rem;
}

.notification-time {
    color: #6b7280; /* gray-500 */
    font-size: 0.75rem;
}

.notification-link {
    color: #059669; /* primary action link */
    padding: var(--spacing-2);
}

.notification-link:hover {
    color: #34d399; /* lighter green */
}

.text-muted {
    color: #6b7280;
    text-align: center;
    padding: var(--spacing-4) 0;
}

@media (max-width: 992px) {
    .dashboard-row {
        grid-template-columns: 1fr;
    }
}
</style>
