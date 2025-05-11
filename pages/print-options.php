<?php
// Check if upload information exists in session
if (!isset($_SESSION['upload'])) {
    header("Location: index.php?page=upload");
    exit;
}

$upload = $_SESSION['upload'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $pages = isset($_POST['pages']) ? intval($_POST['pages']) : 1;
    $copies = isset($_POST['copies']) ? intval($_POST['copies']) : 1;
    $colorOption = isset($_POST['color_option']) ? $_POST['color_option'] : 'bw';
    $paperSize = isset($_POST['paper_size']) ? $_POST['paper_size'] : 'a4';
    $orientation = isset($_POST['orientation']) ? $_POST['orientation'] : 'portrait';
    $duplex = isset($_POST['duplex']) ? 1 : 0;
    $location = isset($_POST['location']) ? $_POST['location'] : '';
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';
    
    // Validate inputs
    $errors = [];
    
    if ($pages < 1) {
        $errors[] = "Page count must be at least 1";
    }
    
    if ($copies < 1) {
        $errors[] = "Number of copies must be at least 1";
    }
    
    if (!array_key_exists($location, PRINT_LOCATIONS)) {
        $errors[] = "Invalid print location selected";
    }
    
    // Calculate cost
    $cost = calculatePrintCost($pages, $colorOption, $copies, $duplex);
    
    // If no errors, store print job info in session and redirect to payment page
    if (empty($errors)) {
        $_SESSION['print_job'] = [
            'filename' => $upload['filename'],
            'original_filename' => $upload['original_filename'],
            'file_size' => $upload['file_size'],
            'file_type' => $upload['file_type'],
            'pages' => $pages,
            'copies' => $copies,
            'color' => $colorOption,
            'paper_size' => $paperSize,
            'orientation' => $orientation,
            'duplex' => $duplex,
            'location' => $location,
            'notes' => $notes,
            'cost' => $cost
        ];
        
        header("Location: index.php?page=payment");
        exit;
    }
}

// Get file type icon
function getFileTypeIcon($fileType) {
    if (strpos($fileType, 'pdf') !== false) {
        return 'fas fa-file-pdf';
    } elseif (strpos($fileType, 'word') !== false || strpos($fileType, 'document') !== false) {
        return 'fas fa-file-word';
    } elseif (strpos($fileType, 'powerpoint') !== false || strpos($fileType, 'presentation') !== false) {
        return 'fas fa-file-powerpoint';
    } elseif (strpos($fileType, 'image') !== false) {
        return 'fas fa-file-image';
    } else {
        return 'fas fa-file';
    }
}
?>

<h2>Print Options</h2>

<div class="options-container">
    <div class="options-main">
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <?php echo $error; ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Selected Document</h3>
            </div>
            <div class="card-body">
                <div class="file-preview">
                    <div class="file-preview-icon"><i class="<?php echo getFileTypeIcon($upload['file_type']); ?>"></i></div>
                    <div class="file-info">
                        <h4 class="file-name"><?php echo htmlspecialchars($upload['original_filename']); ?></h4>
                        <p>
                            <span class="file-size"><?php echo formatFileSize($upload['file_size']); ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <form action="" method="post">
            <div class="card">
                <div class="card-header">
                    <h3>Print Options</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="pages">Number of Pages</label>
                                <input type="number" id="page-count" name="pages" min="1" value="1" required>
                                <small>Estimated page count in your document</small>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="copies">Number of Copies</label>
                                <input type="number" id="copies" name="copies" min="1" value="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="color_option">Color Options</label>
                                <select id="color-option" name="color_option">
                                    <option value="bw">Black & White</option>
                                    <option value="color">Color</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="paper_size">Paper Size</label>
                                <select name="paper_size">
                                    <option value="a4">A4</option>
                                    <option value="letter">Letter</option>
                                    <option value="legal">Legal</option>
                                    <option value="a3">A3</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="orientation">Orientation</label>
                                <select name="orientation">
                                    <option value="portrait">Portrait</option>
                                    <option value="landscape">Landscape</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="duplex" name="duplex" value="1">
                                    <span>Double-sided printing</span>
                                </label>
                                <small>Save paper and get a discount on the price per page</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Pickup Location</label>
                        <select name="location" required>
                            <option value="">Select a location</option>
                            <?php foreach (PRINT_LOCATIONS as $key => $name): ?>
                                <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Additional Notes (Optional)</label>
                        <textarea name="notes" rows="3" placeholder="Any special instructions for the print staff"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Cost Summary</h3>
                </div>
                <div class="card-body">
                    <div class="cost-summary">
                        <div class="cost-row">
                            <span>Estimated Cost:</span>
                            <span id="cost-display">$0.05</span>
                        </div>
                        <small>Final cost is based on actual page count, color usage, and options selected</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Continue to Payment</button>
                    <a href="index.php?page=upload" class="btn btn-outline">Back to Upload</a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="options-sidebar">
        <div class="card">
            <div class="card-header">
                <h3>Print Options Help</h3>
            </div>
            <div class="card-body">
                <div class="help-section">
                    <h4><i class="fas fa-palette"></i> Color Options</h4>
                    <p>Choose black & white for text documents and reports. Use color for presentations, images, and diagrams.</p>
                </div>
                
                <div class="help-section">
                    <h4><i class="fas fa-copy"></i> Double-sided Printing</h4>
                    <p>Save paper by printing on both sides of each sheet. This option provides a small discount on the per-page price.</p>
                </div>
                
                <div class="help-section">
                    <h4><i class="fas fa-map-marker-alt"></i> Pickup Locations</h4>
                    <p>Select where you'd like to pick up your print job:</p>
                    <ul class="location-list">
                        <li><span>Main Library</span> - Open 7:00 AM - 10:00 PM</li>
                        <li><span>IT Lab</span> - Open 8:00 AM - 8:00 PM</li>
                        <li><span>Admin Building</span> - Open 9:00 AM - 5:00 PM</li>
                        <li><span>Student Center</span> - Open 7:00 AM - 7:00 PM</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.options-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-8);
}

.options-main {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.cost-summary {
    padding: var(--spacing-4);
    background-color: var(--gray-100);
    border-radius: var(--border-radius);
}

.cost-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: var(--spacing-2);
}

.checkbox-group {
    display: flex;
    flex-direction: column;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.checkbox-label input {
    margin-right: var(--spacing-2);
}

.help-section {
    margin-bottom: var(--spacing-6);
}

.help-section h4 {
    display: flex;
    align-items: center;
    color: var(--primary);
    margin-bottom: var(--spacing-2);
}

.help-section h4 i {
    margin-right: var(--spacing-2);
}

.help-section:last-child {
    margin-bottom: 0;
}

.location-list {
    list-style: none;
    margin-top: var(--spacing-2);
}

.location-list li {
    margin-bottom: var(--spacing-2);
}

.location-list span {
    font-weight: 600;
}

@media (max-width: 992px) {
    .options-container {
        grid-template-columns: 1fr;
    }
}
</style>