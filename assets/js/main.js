/**
 * Main JavaScript file for the University Print System
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const nav = document.querySelector('nav');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
        });
    }
    
    // File upload functionality
    const uploadInput = document.getElementById('upload-input');
    const uploadZone = document.querySelector('.upload-zone');
    const filePreview = document.querySelector('.file-preview');
    const fileNameElement = document.querySelector('.file-name');
    const fileSizeElement = document.querySelector('.file-size');
    const fileTypeElement = document.querySelector('.file-type');
    const removeFileBtn = document.querySelector('.remove-file');
    const uploadForm = document.getElementById('upload-form');
    
    if (uploadZone && uploadInput) {
        // Click on upload zone to trigger file input
        uploadZone.addEventListener('click', function() {
            uploadInput.click();
        });
        
        // Drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadZone.addEventListener(eventName, function() {
                uploadZone.classList.add('dragover');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, function() {
                uploadZone.classList.remove('dragover');
            }, false);
        });
        
        uploadZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length) {
                uploadInput.files = files;
                handleFileUpload(files[0]);
            }
        }, false);
        
        // Handle file selection
        uploadInput.addEventListener('change', function() {
            if (this.files.length) {
                handleFileUpload(this.files[0]);
            }
        });
        
        // Remove file
        if (removeFileBtn) {
            removeFileBtn.addEventListener('click', function() {
                uploadInput.value = '';
                if (filePreview) {
                    filePreview.style.display = 'none';
                }
                if (uploadZone) {
                    uploadZone.style.display = 'block';
                }
            });
        }
        
        function handleFileUpload(file) {
            // Check file type
            const fileType = file.type;
            const fileTypeIcon = getFileTypeIcon(fileType);
            
            // Update file preview
            if (filePreview) {
                document.querySelector('.file-preview-icon').innerHTML = `<i class="${fileTypeIcon}"></i>`;
                fileNameElement.textContent = file.name;
                fileSizeElement.textContent = formatFileSize(file.size);
                fileTypeElement.textContent = getFileTypeName(fileType);
                
                filePreview.style.display = 'flex';
                uploadZone.style.display = 'none';
            }
        }
        
        function getFileTypeIcon(fileType) {
            if (fileType.includes('pdf')) {
                return 'fas fa-file-pdf';
            } else if (fileType.includes('word') || fileType.includes('document')) {
                return 'fas fa-file-word';
            } else if (fileType.includes('powerpoint') || fileType.includes('presentation')) {
                return 'fas fa-file-powerpoint';
            } else if (fileType.includes('image')) {
                return 'fas fa-file-image';
            } else {
                return 'fas fa-file';
            }
        }
        
        function getFileTypeName(fileType) {
            if (fileType.includes('pdf')) {
                return 'PDF Document';
            } else if (fileType.includes('word') || fileType.includes('document')) {
                return 'Word Document';
            } else if (fileType.includes('powerpoint') || fileType.includes('presentation')) {
                return 'PowerPoint Presentation';
            } else if (fileType.includes('image/jpeg')) {
                return 'JPEG Image';
            } else if (fileType.includes('image/png')) {
                return 'PNG Image';
            } else {
                return fileType;
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    }
    
    // Print options cost calculator
    const pageCountInput = document.getElementById('page-count');
    const copiesInput = document.getElementById('copies');
    const colorOption = document.getElementById('color-option');
    const duplexOption = document.getElementById('duplex');
    const costDisplay = document.getElementById('cost-display');
    
    if (pageCountInput && copiesInput && colorOption && costDisplay) {
        // Calculate initial cost
        calculateCost();
        
        // Add event listeners to recalculate cost when options change
        pageCountInput.addEventListener('input', calculateCost);
        copiesInput.addEventListener('input', calculateCost);
        colorOption.addEventListener('change', calculateCost);
        if (duplexOption) {
            duplexOption.addEventListener('change', calculateCost);
        }
        
        function calculateCost() {
            const pages = parseInt(pageCountInput.value) || 0;
            const copies = parseInt(copiesInput.value) || 1;
            const isColor = colorOption.value === 'color';
            const isDuplex = duplexOption ? duplexOption.checked : false;
            
            let pricePerPage = isColor ? 0.15 : 0.05; // Price per page
            
            // Apply duplex discount if applicable
            if (isDuplex) {
                pricePerPage -= 0.02;
            }
            
            const totalCost = (pages * pricePerPage * copies).toFixed(2);
            costDisplay.textContent = '$' + totalCost;
        }
    }
});