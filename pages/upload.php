<?php
// Handle file upload
$uploadSuccess = false;
$uploadError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $file = $_FILES['document'];
    
    // Check if file was uploaded without errors
    if ($file['error'] === 0) {
        // Check file size
        if ($file['size'] <= MAX_FILE_SIZE) {
            // Check file type
            if (isAllowedFileType($file['type'])) {
                // Create upload directory if it doesn't exist
                if (!file_exists(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0777, true);
                }
                
                // Generate a unique filename
                $filename = generateUniqueFilename($file['name']);
                $uploadPath = UPLOAD_DIR . $filename;
                
                // Move the uploaded file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Store file information in session for next step
                    $_SESSION['upload'] = [
                        'filename' => $filename,
                        'original_filename' => $file['name'],
                        'file_size' => $file['size'],
                        'file_type' => $file['type']
                    ];
                    
                    // Redirect to print options page
                    header("Location: index.php?page=print-options");
                    exit;
                } else {
                    $uploadError = "Failed to upload file. Please try again.";
                }
            } else {
                $uploadError = "File type not allowed. Please upload a PDF, Word document, PowerPoint presentation, or image.";
            }
        } else {
            $uploadError = "File is too large. Maximum size is " . formatFileSize(MAX_FILE_SIZE) . ".";
        }
    } else {
        // Map error code to message
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $uploadError = "File is too large.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $uploadError = "File was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $uploadError = "No file was uploaded.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $uploadError = "Missing a temporary folder.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $uploadError = "Failed to write file to disk.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $uploadError = "File upload stopped by extension.";
                break;
            default:
                $uploadError = "Unknown upload error.";
                break;
        }
    }
}
?>

<h2>Upload Document</h2>

<div class="upload-container">
    <?php if ($uploadError): ?>
        <div class="alert alert-error"><?php echo $uploadError; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form id="upload-form" action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo MAX_FILE_SIZE; ?>">
                
                <div class="upload-zone" id="upload-zone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>Drag & Drop your file here</h3>
                    <p>or click to browse</p>
                    <p class="file-types">Supported formats: PDF, Word, PowerPoint, JPEG, PNG</p>
                    <p class="file-size">Maximum size: <?php echo formatFileSize(MAX_FILE_SIZE); ?></p>
                </div>
                
                <div class="file-preview" style="display: none;">
                    <div class="file-preview-icon"><i class="fas fa-file"></i></div>
                    <div class="file-info">
                        <h4 class="file-name">filename.pdf</h4>
                        <p>
                            <span class="file-size">0 KB</span> | 
                            <span class="file-type">PDF Document</span>
                        </p>
                    </div>
                    <div class="remove-file"><i class="fas fa-times"></i></div>
                </div>
                
                <input type="file" id="upload-input" name="document" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png" style="display: none;">
                
                <div class="form-group text-center" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Continue to Print Options</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="upload-info">
        <div class="info-section">
            <h3><i class="fas fa-info-circle"></i> About Uploading Documents</h3>
            <p>Upload your documents here to print them at one of our campus printing stations. We support a variety of file formats including PDF, Word documents, PowerPoint presentations, and images.</p>
        </div>
        
        <div class="info-section">
            <h3><i class="fas fa-shield-alt"></i> Privacy & Security</h3>
            <p>Your documents are stored securely and are only accessible to you and the print staff. Files are automatically deleted from our system 7 days after printing is completed.</p>
        </div>
        
        <div class="info-section">
            <h3><i class="fas fa-question-circle"></i> Need Help?</h3>
            <p>If you're having issues uploading your file or have any questions about the printing process, please contact the IT help desk at <a href="mailto:helpdesk@university.edu">helpdesk@university.edu</a> or call (555) 123-4567.</p>
        </div>
    </div>
</div>

<style>
.upload-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-8);
}

.upload-info {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.info-section {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: var(--spacing-6);
}

.info-section h3 {
    display: flex;
    align-items: center;
    font-size: 1.25rem;
    margin-bottom: var(--spacing-3);
    color: var(--primary);
}

.info-section h3 i {
    margin-right: var(--spacing-2);
}

.file-types, .file-size {
    color: var(--gray-500);
    font-size: 0.875rem;
    margin-bottom: var(--spacing-1);
}

.text-center {
    text-align: center;
}

@media (max-width: 992px) {
    .upload-container {
        grid-template-columns: 1fr;
    }
}
</style>