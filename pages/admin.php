<?php
// Only admins can access this page
if (!isAdmin()) {
    header("Location: index.php?page=dashboard");
    exit;
}

// Get admin actions
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
$allowedActions = ['dashboard', 'jobs', 'users', 'locations', 'settings'];

if (!in_array($action, $allowedActions)) {
    $action = 'dashboard';
}

// Handle filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$locationFilter = isset($_GET['location']) ? $_GET['location'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$searchTerm = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Pagination
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get print jobs with filters
$whereConditions = [];
$params = [];
$types = '';

if ($statusFilter) {
    $whereConditions[] = "pj.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($locationFilter) {
    $whereConditions[] = "pj.location = ?";
    $params[] = $locationFilter;
    $types .= 's';
}

if ($dateFilter) {
    switch ($dateFilter) {
        case 'today':
            $whereConditions[] = "DATE(pj.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $whereConditions[] = "DATE(pj.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'this_week':
            $whereConditions[] = "YEARWEEK(pj.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'last_week':
            $whereConditions[] = "YEARWEEK(pj.created_at, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)";
            break;
    }
}

if ($searchTerm) {
    $whereConditions[] = "(pj.original_filename LIKE ? OR u.name LIKE ? OR u.student_id LIKE ?)";
    $searchPattern = "%$searchTerm%";
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $types .= 'sss';
}

$whereClause = empty($whereConditions) ? "" : "WHERE " . implode(" AND ", $whereConditions);

// Count total jobs with filters
$countQuery = "
    SELECT COUNT(*) as total
    FROM print_jobs pj
    LEFT JOIN users u ON pj.user_id = u.id
    $whereClause
";

if (!empty($params)) {
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $totalJobs = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $totalJobs = $conn->query($countQuery)->fetch_assoc()['total'];
}

$totalPages = ceil($totalJobs / $perPage);

// Get jobs for current page with filters
$query = "
    SELECT pj.*, u.name as user_name, u.student_id
    FROM print_jobs pj
    LEFT JOIN users u ON pj.user_id = u.id
    $whereClause
    ORDER BY pj.created_at DESC
    LIMIT ?, ?
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $params[] = $offset;
    $params[] = $perPage;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $offset, $perPage);
}
$stmt->execute();
$jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get users if needed
if ($action === 'users') {
    $userQuery = "
        SELECT u.*, 
               COUNT(pj.id) as job_count, 
               SUM(CASE WHEN pj.payment_status = 'paid' THEN pj.cost ELSE 0 END) as total_spent
        FROM users u
        LEFT JOIN print_jobs pj ON u.id = pj.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ";
    $users = $conn->query($userQuery)->fetch_all(MYSQLI_ASSOC);
}

// Get dashboard stats
if ($action === 'dashboard') {
    // Total jobs by status
    $statusQuery = "
        SELECT status, COUNT(*) as count
        FROM print_jobs
        GROUP BY status
    ";
    $statusResults = $conn->query($statusQuery)->fetch_all(MYSQLI_ASSOC);
    
    $jobsByStatus = [];
    foreach ($statusResults as $result) {
        $jobsByStatus[$result['status']] = $result['count'];
    }
    
    // Jobs created today
    $todayJobsQuery = "SELECT COUNT(*) as count FROM print_jobs WHERE DATE(created_at) = CURDATE()";
    $todayJobs = $conn->query($todayJobsQuery)->fetch_assoc()['count'];
    
    // Jobs by location
    $locationQuery = "
        SELECT location, COUNT(*) as count
        FROM print_jobs
        GROUP BY location
    ";
    $locationResults = $conn->query($locationQuery)->fetch_all(MYSQLI_ASSOC);
    
    $jobsByLocation = [];
    foreach ($locationResults as $result) {
        $jobsByLocation[$result['location']] = $result['count'];
    }
    
    // Total revenue
    $revenueQuery = "SELECT SUM(cost) as total FROM print_jobs WHERE payment_status = 'paid'";
    $totalRevenue = $conn->query($revenueQuery)->fetch_assoc()['total'] ?? 0;
    
    // Revenue today
    $todayRevenueQuery = "SELECT SUM(cost) as total FROM print_jobs WHERE payment_status = 'paid' AND DATE(created_at) = CURDATE()";
    $todayRevenue = $conn->query($todayRevenueQuery)->fetch_assoc()['total'] ?? 0;
    
    // Total users
    $usersQuery = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
    $totalUsers = $conn->query($usersQuery)->fetch_assoc()['count'];
    
    // Recent jobs
    $recentJobsQuery = "
        SELECT pj.*, u.name as user_name
        FROM print_jobs pj
        LEFT JOIN users u ON pj.user_id = u.id
        ORDER BY pj.created_at DESC
        LIMIT 5
    ";
    $recentJobs = $conn->query($recentJobsQuery)->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="admin-layout">
    <div class="admin-sidebar">
        <h3>Admin Panel</h3>
        <ul class="admin-menu">
            <li><a href="index.php?page=admin&action=dashboard" class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="index.php?page=admin&action=jobs" class="<?php echo $action === 'jobs' ? 'active' : ''; ?>"><i class="fas fa-print"></i> Print Jobs</a></li>
            <li><a href="index.php?page=admin&action=users" class="<?php echo $action === 'users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="index.php?page=admin&action=locations" class="<?php echo $action === 'locations' ? 'active' : ''; ?>"><i class="fas fa-map-marker-alt"></i> Locations</a></li>
            <li><a href="index.php?page=admin&action=settings" class="<?php echo $action === 'settings' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </div>
    
    <div class="admin-content">
        <?php if ($action === 'dashboard'): ?>
            <div class="admin-header">
                <h2>Admin Dashboard</h2>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-print"></i></div>
                    <div class="stat-content">
                        <h3>Total Print Jobs</h3>
                        <p><?php echo $totalJobs; ?></p>
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
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-content">
                        <h3>Jobs Today</h3>
                        <p><?php echo $todayJobs; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-content">
                        <h3>Total Revenue</h3>
                        <p><?php echo formatPrice($totalRevenue); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="admin-row">
                <div class="admin-column">
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Print Jobs</h3>
                            <a href="index.php?page=admin&action=jobs" class="btn btn-outline btn-sm">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($recentJobs) > 0): ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>File</th>
                                            <th>User</th>
                                            <th>Status</th>
                                            <th>Cost</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentJobs as $job): ?>
                                            <?php $statusDetails = getStatusDetails($job['status']); ?>
                                            <tr>
                                                <td>#<?php echo $job['id']; ?></td>
                                                <td><?php echo htmlspecialchars(substr($job['original_filename'], 0, 20) . (strlen($job['original_filename']) > 20 ? '...' : '')); ?></td>
                                                <td><?php echo htmlspecialchars($job['user_name']); ?></td>
                                                <td><span class="status-badge <?php echo $statusDetails[1]; ?>"><?php echo $statusDetails[0]; ?></span></td>
                                                <td><?php echo formatPrice($job['cost']); ?></td>
                                                <td><?php echo date('M j, g:i a', strtotime($job['created_at'])); ?></td>
                                                <td class="actions">
                                                    <a href="index.php?page=status&job_id=<?php echo $job['id']; ?>" class="btn btn-outline btn-sm">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-data">
                                    <i class="fas fa-print"></i>
                                    <p>No print jobs found.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="admin-column-sidebar">
                    <div class="card">
                        <div class="card-header">
                            <h3>System Stats</h3>
                        </div>
                        <div class="card-body">
                            <div class="user-stats">
                                <div class="user-stat">
                                    <div class="user-stat-header">
                                        <h4>Total Users</h4>
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="user-stat-value"><?php echo $totalUsers; ?></div>
                                    <p class="user-stat-description">Registered student accounts</p>
                                </div>
                                <div class="user-stat">
                                    <div class="user-stat-header">
                                        <h4>Revenue Today</h4>
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                    <div class="user-stat-value"><?php echo formatPrice($todayRevenue); ?></div>
                                    <p class="user-stat-description">From paid print jobs</p>
                                </div>
                            </div>
                            
                            <h4>Jobs by Location</h4>
                            <div class="location-stats">
                                <?php foreach (PRINT_LOCATIONS as $key => $name): ?>
                                    <div class="location-stat">
                                        <span class="location-name"><?php echo $name; ?></span>
                                        <span class="location-count"><?php echo isset($jobsByLocation[$key]) ? $jobsByLocation[$key] : 0; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Admin Credentials</h3>
                        </div>
                        <div class="card-body">
                            <div class="credentials-box">
                                <h4>Default Admin Login</h4>
                                <p>Student ID:</p>
                                <code>ADMIN001</code>
                                <p>Password:</p>
                                <code>admin123</code>
                                <p class="warning-text">Change these credentials for production use!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
        <?php elseif ($action === 'jobs'): ?>
            <div class="admin-header">
                <h2>Manage Print Jobs</h2>
            </div>
            
            <div class="filter-bar">
                <form id="filter-form" action="" method="get">
                    <input type="hidden" name="page" value="admin">
                    <input type="hidden" name="action" value="jobs">
                    
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="">All</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="printing" <?php echo $statusFilter === 'printing' ? 'selected' : ''; ?>>Printing</option>
                            <option value="ready" <?php echo $statusFilter === 'ready' ? 'selected' : ''; ?>>Ready for Pickup</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="location">Location:</label>
                        <select name="location" id="location">
                            <option value="">All</option>
                            <?php foreach (PRINT_LOCATIONS as $key => $name): ?>
                                <option value="<?php echo $key; ?>" <?php echo $locationFilter === $key ? 'selected' : ''; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date">Date:</label>
                        <select name="date" id="date">
                            <option value="">All Time</option>
                            <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="yesterday" <?php echo $dateFilter === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                            <option value="this_week" <?php echo $dateFilter === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                            <option value="last_week" <?php echo $dateFilter === 'last_week' ? 'selected' : ''; ?>>Last Week</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="File name, user...">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <a href="index.php?page=admin&action=jobs" class="btn btn-outline btn-sm">Reset</a>
                </form>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <?php if (count($jobs) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>File</th>
                                    <th>User</th>
                                    <th>Details</th>
                                    <th>Location</th>
                                    <th class="job-status-cell">Status</th>
                                    <th>Cost</th>
                                    <th>Payment</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jobs as $job): ?>
                                    <?php $statusDetails = getStatusDetails($job['status']); ?>
                                    <tr id="job-<?php echo $job['id']; ?>">
                                        <td>#<?php echo $job['id']; ?></td>
                                        <td title="<?php echo htmlspecialchars($job['original_filename']); ?>">
                                            <?php echo htmlspecialchars(substr($job['original_filename'], 0, 15) . (strlen($job['original_filename']) > 15 ? '...' : '')); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($job['user_name']); ?> (<?php echo $job['student_id']; ?>)</td>
                                        <td>
                                            <?php echo $job['pages']; ?> pages × <?php echo $job['copies']; ?> copies<br>
                                            <?php echo $job['color'] === 'color' ? 'Color' : 'B&W'; ?>, 
                                            <?php echo strtoupper($job['paper_size']); ?>, 
                                            <?php echo ucfirst($job['orientation']); ?>
                                        </td>
                                        <td><?php echo PRINT_LOCATIONS[$job['location']]; ?></td>
                                        <td class="job-status-cell">
                                            <span class="status-badge <?php echo $statusDetails[1]; ?>"><?php echo $statusDetails[0]; ?></span>
                                        </td>
                                        <td><?php echo formatPrice($job['cost']); ?></td>
                                        <td>
                                            <?php if ($job['payment_method'] === 'credits'): ?>
                                                Credits
                                            <?php else: ?>
                                                Cash
                                            <?php endif; ?>
                                            <br>
                                            <span class="status-badge <?php echo $job['payment_status'] === 'paid' ? 'status-ready' : 'status-pending'; ?>">
                                                <?php echo ucfirst($job['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, g:i a', strtotime($job['created_at'])); ?></td>
                                        <td class="actions">
                                            <a href="index.php?page=status&job_id=<?php echo $job['id']; ?>" class="btn btn-outline btn-sm">View</a>
                                            <?php if ($job['status'] !== 'completed' && $job['status'] !== 'cancelled'): ?>
                                                <button class="btn btn-primary btn-sm" data-modal="update-status-modal-<?php echo $job['id']; ?>">Update</button>
                                                
                                                <!-- Status update modal -->
                                                <div id="update-status-modal-<?php echo $job['id']; ?>" class="modal-backdrop" style="display: none;">
                                                    <div class="modal">
                                                        <div class="modal-header">
                                                            <h3>Update Job Status</h3>
                                                            <button type="button" class="modal-close">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form class="update-status-form" action="admin_actions.php" method="post">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                                
                                                                <div class="form-group">
                                                                    <label for="status-<?php echo $job['id']; ?>">New Status:</label>
                                                                    <select name="status" id="status-<?php echo $job['id']; ?>">
                                                                        <option value="pending" <?php echo $job['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="printing" <?php echo $job['status'] === 'printing' ? 'selected' : ''; ?>>Printing</option>
                                                                        <option value="ready" <?php echo $job['status'] === 'ready' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                                                        <option value="completed" <?php echo $job['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                        <option value="cancelled" <?php echo $job['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label for="payment-status-<?php echo $job['id']; ?>">Payment Status:</label>
                                                                    <select name="payment_status" id="payment-status-<?php echo $job['id']; ?>">
                                                                        <option value="pending" <?php echo $job['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="paid" <?php echo $job['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label for="notify-<?php echo $job['id']; ?>">
                                                                        <input type="checkbox" id="notify-<?php echo $job['id']; ?>" name="notify" value="1" checked>
                                                                        Notify Student
                                                                    </label>
                                                                </div>
                                                                
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-outline modal-cancel">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary">Update Status</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li>
                                        <a href="index.php?page=admin&action=jobs&p=<?php echo $i; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?><?php echo $locationFilter ? '&location=' . $locationFilter : ''; ?><?php echo $dateFilter ? '&date=' . $dateFilter : ''; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-data">
                            <i class="fas fa-print"></i>
                            <p>No print jobs found matching your criteria.</p>
                            <a href="index.php?page=admin&action=jobs" class="btn btn-primary">Reset Filters</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        
        <?php elseif ($action === 'users'): ?>
            <div class="admin-header">
                <h2>Manage Users</h2>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <?php if (isset($users) && count($users) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Credits</th>
                                    <th>Print Jobs</th>
                                    <th>Total Spent</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>#<?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td id="user-credits-<?php echo $user['id']; ?>"><?php echo formatPrice($user['credits']); ?></td>
                                        <td><?php echo $user['job_count']; ?></td>
                                        <td><?php echo formatPrice($user['total_spent'] ?? 0); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td class="actions">
                                            <button class="btn btn-primary btn-sm" data-modal="add-credits-modal-<?php echo $user['id']; ?>">Add Credits</button>
                                            <a href="#" class="btn btn-outline btn-sm">View Jobs</a>
                                            
                                            <!-- Add credits modal -->
                                            <div id="add-credits-modal-<?php echo $user['id']; ?>" class="modal-backdrop" style="display: none;">
                                                <div class="modal">
                                                    <div class="modal-header">
                                                        <h3>Add Credits for <?php echo htmlspecialchars($user['name']); ?></h3>
                                                        <button type="button" class="modal-close">&times;</button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form id="add-credits-form" action="admin_actions.php" method="post">
                                                            <input type="hidden" name="action" value="add_credits">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            
                                                            <div class="form-group">
                                                                <label for="current-credits-<?php echo $user['id']; ?>">Current Credits:</label>
                                                                <input type="text" id="current-credits-<?php echo $user['id']; ?>" value="<?php echo formatPrice($user['credits']); ?>" readonly>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="amount-<?php echo $user['id']; ?>">Amount to Add:</label>
                                                                <input type="number" id="amount-<?php echo $user['id']; ?>" name="amount" min="0.01" step="0.01" required>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="note-<?php echo $user['id']; ?>">Note (Optional):</label>
                                                                <textarea id="note-<?php echo $user['id']; ?>" name="note" rows="2"></textarea>
                                                            </div>
                                                            
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-outline modal-cancel">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Add Credits</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-data">
                            <i class="fas fa-users"></i>
                            <p>No users found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        
        <?php elseif ($action === 'locations'): ?>
            <div class="admin-header">
                <h2>Manage Locations</h2>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Location Name</th>
                                <th>Status</th>
                                <th>Hours</th>
                                <th>Current Queue</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (PRINT_LOCATIONS as $key => $name): ?>
                                <tr>
                                    <td><?php echo $key; ?></td>
                                    <td><?php echo $name; ?></td>
                                    <td><span class="status-badge status-ready">Active</span></td>
                                    <td>
                                        <?php 
                                            switch($key) {
                                                case 'library':
                                                    echo '7:00 AM - 10:00 PM';
                                                    break;
                                                case 'it_lab':
                                                    echo '8:00 AM - 8:00 PM';
                                                    break;
                                                case 'admin_building':
                                                    echo '9:00 AM - 5:00 PM';
                                                    break;
                                                case 'student_center':
                                                    echo '7:00 AM - 7:00 PM';
                                                    break;
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            // Count pending jobs at this location
                                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM print_jobs WHERE location = ? AND status IN ('pending', 'printing')");
                                            $stmt->bind_param("s", $key);
                                            $stmt->execute();
                                            $queueCount = $stmt->get_result()->fetch_assoc()['count'];
                                            echo $queueCount . ' job' . ($queueCount !== 1 ? 's' : '');
                                        ?>
                                    </td>
                                    <td class="actions">
                                        <button class="btn btn-outline btn-sm" disabled>Edit</button>
                                        <a href="index.php?page=admin&action=jobs&location=<?php echo $key; ?>" class="btn btn-primary btn-sm">View Jobs</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        
        <?php elseif ($action === 'settings'): ?>
            <div class="admin-header">
                <h2>System Settings</h2>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Pricing Settings</h3>
                </div>
                <div class="card-body">
                    <form action="admin_actions.php" method="post">
                        <input type="hidden" name="action" value="update_pricing">
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="price_bw">Black & White Price (per page)</label>
                                    <input type="number" id="price_bw" name="price_bw" min="0.01" step="0.01" value="0.05">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="price_color">Color Price (per page)</label>
                                    <input type="number" id="price_color" name="price_color" min="0.01" step="0.01" value="0.15">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="duplex_discount">Duplex Discount (per page)</label>
                                    <input type="number" id="duplex_discount" name="duplex_discount" min="0" step="0.01" value="0.02">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="bulk_threshold">Bulk Discount Threshold (pages)</label>
                                    <input type="number" id="bulk_threshold" name="bulk_threshold" min="0" value="50">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="bulk_discount">Bulk Discount Percentage</label>
                            <input type="number" id="bulk_discount" name="bulk_discount" min="0" max="100" value="10">
                            <small>Applied when page count exceeds the threshold</small>
                        </div>
                        
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" disabled>Save Changes</button>
                            <button type="reset" class="btn btn-outline" disabled>Reset</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>System Settings</h3>
                </div>
                <div class="card-body">
                    <form action="admin_actions.php" method="post">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="form-group">
                            <label for="max_file_size">Maximum File Size (MB)</label>
                            <input type="number" id="max_file_size" name="max_file_size" min="1" value="10">
                        </div>
                        
                        <div class="form-group">
                            <label for="file_retention">File Retention Period (days)</label>
                            <input type="number" id="file_retention" name="file_retention" min="1" value="7">
                            <small>Number of days to keep completed print job files</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_name">Site Name</label>
                            <input type="text" id="site_name" name="site_name" value="University Print System">
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">Admin Email</label>
                            <input type="email" id="admin_email" name="admin_email" value="admin@university.edu">
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="enable_notifications" name="enable_notifications" value="1" checked>
                                <span>Enable Email Notifications</span>
                            </label>
                        </div>
                        
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" disabled>Save Changes</button>
                            <button type="reset" class="btn btn-outline" disabled>Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.admin-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.admin-column, .admin-column-sidebar {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.location-stats {
    margin-top: var(--spacing-4);
}

.location-stat {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-2) 0;
    border-bottom: 1px solid var(--gray-200);
}

.location-stat:last-child {
    border-bottom: none;
}

.location-name {
    color: var(--gray-700);
}

.location-count {
    font-weight: 600;
    color: var(--primary);
}

.warning-text {
    color: var(--error);
    font-weight: 600;
}

@media (max-width: 1200px) {
    .admin-row {
        grid-template-columns: 1fr;
    }
}
</style>