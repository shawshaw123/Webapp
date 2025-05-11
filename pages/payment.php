<?php
// Check if print job information exists in session
if (!isset($_SESSION['print_job'])) {
    header("Location: index.php?page=upload");
    exit;
}

$printJob = $_SESSION['print_job'];
$userId = getCurrentUserId();
$user = getUserById($userId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    
    // Validate payment method
    if ($paymentMethod !== 'credits' && $paymentMethod !== 'cash') {
        $error = "Invalid payment method selected";
    } else {
        // If paying with credits, check if user has enough
        if ($paymentMethod === 'credits' && $user['credits'] < $printJob['cost']) {
            $error = "Insufficient credits. Please add more credits or select cash payment.";
        } else {
            // Insert print job into database
            $stmt = $conn->prepare("
                INSERT INTO print_jobs 
                (user_id, filename, original_filename, file_size, file_type, pages, copies, color, paper_size, orientation, duplex, cost, payment_method, payment_status, location, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $paymentStatus = $paymentMethod === 'credits' ? 'paid' : 'pending';
            
            $stmt->bind_param(
                "issisissssidsss",
                $userId,
                $printJob['filename'],
                $printJob['original_filename'],
                $printJob['file_size'],
                $printJob['file_type'],
                $printJob['pages'],
                $printJob['copies'],
                $printJob['color'],
                $printJob['paper_size'],
                $printJob['orientation'],
                $printJob['duplex'],
                $printJob['cost'],
                $paymentMethod,
                $paymentStatus,
                $printJob['location'],
                $printJob['notes']
            );
            
            if ($stmt->execute()) {
                $jobId = $conn->insert_id;
                
                // If paying with credits, deduct from user's balance
                if ($paymentMethod === 'credits') {
                    $newBalance = $user['credits'] - $printJob['cost'];
                    $updateCredits = $conn->prepare("UPDATE users SET credits = ? WHERE id = ?");
                    $updateCredits->bind_param("di", $newBalance, $userId);
                    $updateCredits->execute();
                    
                    // Log the transaction
                    logActivity($userId, 'payment', "Paid " . formatPrice($printJob['cost']) . " for print job #$jobId using credits");
                }
                
                // Create notification
                $message = "Your print job has been submitted and is pending processing.";
                createNotification($userId, $message, $jobId);
                
                // Log the activity
                logActivity($userId, 'print_job', "Created print job #$jobId");
                
                // Clear session variables
                unset($_SESSION['upload']);
                unset($_SESSION['print_job']);
                
                // Set success message
                $_SESSION['message'] = "Your print job has been submitted successfully!";
                $_SESSION['message_type'] = "success";
                
                // Redirect to job status page
                header("Location: index.php?page=status&job_id=$jobId");
                exit;
            } else {
                $error = "An error occurred while submitting your print job. Please try again.";
            }
        }
    }
}
?>

<h2>Payment</h2>

<div class="payment-container">
    <div class="payment-main">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Order Summary</h3>
            </div>
            <div class="card-body">
                <div class="order-summary">
                    <div class="summary-item">
                        <span class="item-label">Document:</span>
                        <span class="item-value"><?php echo htmlspecialchars($printJob['original_filename']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Pages:</span>
                        <span class="item-value"><?php echo $printJob['pages']; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Copies:</span>
                        <span class="item-value"><?php echo $printJob['copies']; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Color:</span>
                        <span class="item-value"><?php echo $printJob['color'] === 'color' ? 'Color' : 'Black & White'; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Paper Size:</span>
                        <span class="item-value"><?php echo strtoupper($printJob['paper_size']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Orientation:</span>
                        <span class="item-value"><?php echo ucfirst($printJob['orientation']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Double-sided:</span>
                        <span class="item-value"><?php echo $printJob['duplex'] ? 'Yes' : 'No'; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Pickup Location:</span>
                        <span class="item-value"><?php echo PRINT_LOCATIONS[$printJob['location']]; ?></span>
                    </div>
                    <?php if (!empty($printJob['notes'])): ?>
                    <div class="summary-item">
                        <span class="item-label">Notes:</span>
                        <span class="item-value"><?php echo htmlspecialchars($printJob['notes']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-item total">
                        <span class="item-label">Total Cost:</span>
                        <span class="item-value"><?php echo formatPrice($printJob['cost']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <form action="" method="post">
            <div class="card">
                <div class="card-header">
                    <h3>Payment Method</h3>
                </div>
                <div class="card-body">
                    <div class="payment-methods">
                        <div class="payment-method">
                            <input type="radio" id="credits" name="payment_method" value="credits" <?php echo $user['credits'] >= $printJob['cost'] ? '' : 'disabled'; ?>>
                            <label for="credits" class="<?php echo $user['credits'] >= $printJob['cost'] ? '' : 'disabled'; ?>">
                                <div class="method-icon"><i class="fas fa-coins"></i></div>
                                <div class="method-info">
                                    <h4>Pay with Credits</h4>
                                    <p>Your current balance: <?php echo formatPrice($user['credits']); ?></p>
                                    <?php if ($user['credits'] < $printJob['cost']): ?>
                                        <p class="method-error">Insufficient credits. Please add more credits or select cash payment.</p>
                                    <?php endif; ?>
                                </div>
                            </label>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" id="cash" name="payment_method" value="cash" checked>
                            <label for="cash">
                                <div class="method-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div class="method-info">
                                    <h4>Pay with Cash</h4>
                                    <p>Pay when you pick up your prints</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Confirm and Submit</button>
                    <a href="index.php?page=print-options" class="btn btn-outline">Back to Print Options</a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="payment-sidebar">
        <div class="card">
            <div class="card-header">
                <h3>Payment Information</h3>
            </div>
            <div class="card-body">
                <div class="info-section">
                    <h4><i class="fas fa-coins"></i> Using Credits</h4>
                    <p>Credits are prepaid funds that you can use to pay for print jobs. They can be added to your account by an administrator.</p>
                    <p>Using credits allows for instant payment processing without needing to handle cash at pickup.</p>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-money-bill-wave"></i> Cash on Pickup</h4>
                    <p>When selecting cash payment, you'll need to pay the exact amount when you pick up your print job at your selected location.</p>
                    <p>Please note that print jobs paid with cash may take slightly longer to process.</p>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-question-circle"></i> Need More Credits?</h4>
                    <p>You can request more credits from the IT help desk or library front desk. Credits can be purchased with cash or added to your student account.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-8);
}

.payment-main {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.order-summary {
    padding: var(--spacing-4);
    background-color: var(--gray-100);
    border-radius: var(--border-radius);
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-2) 0;
    border-bottom: 1px solid var(--gray-200);
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-item.total {
    margin-top: var(--spacing-4);
    padding-top: var(--spacing-4);
    border-top: 2px solid var(--gray-300);
    font-weight: 700;
    font-size: 1.25rem;
}

.item-label {
    color: var(--gray-600);
}

.payment-methods {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.payment-method {
    position: relative;
}

.payment-method input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.payment-method label {
    display: flex;
    padding: var(--spacing-4);
    border: 2px solid var(--gray-300);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.2s ease;
}

.payment-method label.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.payment-method input[type="radio"]:checked + label {
    border-color: var(--primary);
    background-color: rgba(59, 130, 246, 0.05);
}

.payment-method input[type="radio"]:focus + label {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
}

.method-icon {
    font-size: 1.5rem;
    color: var(--primary);
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background-color: rgba(59, 130, 246, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: var(--spacing-4);
}

.method-info {
    flex: 1;
}

.method-info h4 {
    margin-bottom: var(--spacing-1);
}

.method-info p {
    color: var(--gray-600);
    margin-bottom: var(--spacing-1);
}

.method-error {
    color: var(--error) !important;
}

.info-section {
    margin-bottom: var(--spacing-6);
}

.info-section h4 {
    display: flex;
    align-items: center;
    color: var(--primary);
    margin-bottom: var(--spacing-2);
}

.info-section h4 i {
    margin-right: var(--spacing-2);
}

.info-section:last-child {
    margin-bottom: 0;
}

@media (max-width: 992px) {
    .payment-container {
        grid-template-columns: 1fr;
    }
}
</style>