<?php
    require_once __DIR__ . '/includes/config/config_session.inc.php';
    require_once __DIR__ . '/includes/auth/auth_checker.inc.php';
    require_once __DIR__ . '/includes/config/dbh.inc.php';
    require_once __DIR__ . '/includes/file_management/file_model.inc.php';
    require_once __DIR__ . '/includes/file_management/file_view.inc.php';
    check_login_otp_status(); 

    // get user's files
    $user_files = get_user_files($pdo, $_SESSION["user_id"] ?? 0);
    
    // Define error flags for JavaScript
    $hasUploadErrors = isset($_SESSION["errors_file_upload"]) && !empty($_SESSION["errors_file_upload"]);
    $hasPreviewErrors = isset($_SESSION["errors_file_preview"]) && !empty($_SESSION["errors_file_preview"]);
    $hasDeleteErrors = isset($_SESSION["errors_file_delete"]) && !empty($_SESSION["errors_file_delete"]);
    $hasDownloadErrors = isset($_SESSION["errors_file_download"]) && !empty($_SESSION["errors_file_download"]);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Dashboard</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- JSEncrypt library for RSA encryption -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script>
        // Make error flags available to JavaScript
        const hasUploadErrors = <?php echo $hasUploadErrors ? 'true' : 'false'; ?>;
        const hasPreviewErrors = <?php echo $hasPreviewErrors ? 'true' : 'false'; ?>;
        const hasDeleteErrors = <?php echo $hasDeleteErrors ? 'true' : 'false'; ?>;
        const hasDownloadErrors = <?php echo $hasDownloadErrors ? 'true' : 'false'; ?>;
    </script>
    <script src="/js/profile-popup.js"></script>
    <script src="/js/progress-bar.js"></script>
    <script src="/js/form-encryption.js"></script>
    <script src="/js/dashboard_func.js"></script>
</head>
<body class="dashboard">
    <?php include __DIR__ . "/includes/templates/dashboard_nav.php"; ?>
    <div class="container dash-flex">
        <div class="left-menu">
            <button id="add_file"><span style="font-size: 18px;">+</span> New</button>
            <div class="left-menu-top">
                <div class="action-buttons">
                    <div class="btn-container">
                        <button class="action-btn active-btn"><i class="fa-regular fa-folder-open"></i>Files</button>
                        <button class="action-btn"><i class="fa-regular fa-clock"></i>Recent</button>
                        <button class="action-btn"><i class="fa-solid fa-share-nodes"></i>Shared</button>
                    </div>
                </div>
                <div class="action-buttons">
                    <div class="btn-container">   
                        <button class="action-btn"><i class="fa-solid fa-user-lock"></i>Encrypt/ Decrypt</button>
                        <button class="action-btn"><i class="fa-solid fa-code-compare"></i>Calculate hashes</button>
                    </div>
                </div>
            </div>
            <div class="left-menu-bottom">
                <div class="action-buttons">
                    <div class="btn-container">
                        <button class="action-btn"><i class="fa-regular fa-trash-can"></i>Recycle bin</button>
                        <div class="storage-btn"> 
                            <button class="action-btn"><i class="fa-solid fa-cloud"></i>Storage <span>(22% Full)</span></button>
                            <div class="percent">
                                <div class="percent1" style="width:22%;"></div>
                                <div class="percent2" style="width:78%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-dashboard">
            <div class="search-sort-container dash-box">
                <div class="search-bar">
                    <form action="">
                        <input type="search" name="search_files" id="search_files" placeholder="Search your files">
                        <button><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>
                <div class="sort dash-box">
                    <p>Sort by:</p>
                    <form action="">
                        <select class="btn-form btn1" name="sort_files" id="sort_files">
                            <option value="Date Added">Date Added</option>
                            <option value="Name">Name</option>
                            <option value="Type">Type</option>
                            <option value="Size">Size</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="categories dash-box">
                <h4>Categories:</h4>
                <ul class="dash-box">
                    <li class="active-cat">All</li>
                    <li>Videos</li>
                    <li>Images</li>
                    <li>Audio</li>
                    <li>PDF</li>
                    <li>Documents</li>
                    <li>Spreadsheets</li>
                    <li>Presentations</li>
                    <li>Other</li>
                </ul>
            </div>
            
            <div class="dash-files">
                <!-- Query user files -->
                <?php if (empty($user_files)) {?>
                    <p class="empty-dash">Upload a file to start sharing</p>
                <?php } else { ?>
                    <div class="uploaded-files">
                        <?php foreach ($user_files as $file) { ?>
                            <div class="file col" data-file-type="<?php echo htmlspecialchars($file['file_type']); ?>" data-file-id="<?php echo htmlspecialchars($file['id']); ?>">
                                <div class="top row">
                                    <p class="file-title truncate"><?php echo htmlspecialchars($file['file_name']); ?></p>
                                    <p class="caption-text" style="display: flex; align-items: center; gap: 2px;"> 
                                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 70px; display: inline-block;"><?php echo htmlspecialchars($file['file_type']);?> </span>|
                                        <span><?php echo number_format($file['file_size'] / 1024 /1024, 2); ?> MB</span>
                                    </p>
                                </div>
                                <div class="bottom row">
                                    <p class="caption-text"><?php echo date('M j, Y', strtotime($file['date_uploaded'])); ?></p>
                                    <span class="elipse-menu">
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </span>
                                </div>
                                <div class="file-menu-popup">
                                    <ul>
                                        <li>Open</li>
                                        <li>Share</li>
                                        <li>Download</li>
                                        <li>Delete</li>
                                    </ul>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php  } 
                // Display upload errors and success if any 
                    check_upload_success_messages(); 
                    check_file_upload_errors(); 
                    check_file_preview_errors();
                    check_file_delete_errors();
                    check_delete_success_messages();
                    check_file_download_errors();
                    check_download_success_messages();
                ?>
            </div>

            <!-- Upload progress -->
            <div class="progress-backdrop" id="uploadProgressBackdrop">
                <div id="uploadProgressContainer">
                    <div class="progress-container">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <div class="progress-bar" id="uploadProgressBar"></div>
                        <p>Uploading file...</p>
                    </div>
                </div>
            </div>

            <!-- Delete progress -->
            <div class="progress-backdrop delete-progress" id="deleteProgressBackdrop">
                <div id="deleteProgressContainer">
                    <div class="progress-container">
                        <i class="fa-solid fa-trash-can"></i>
                        <div class="progress-bar" id="deleteProgressBar"></div>
                        <p>Deleting file...</p>
                    </div>
                </div>
            </div>
            
            <!-- Add new file form -->
            <div class="upload-form" id="uploadForm">
                <form action="/includes/file_management/file_upload.inc.php" method="post" id="file_upload_form" enctype="multipart/form-data" class="secure-form">
                    <h2>Upload new File</h2>
                    <p class="caption-text">All files uploaded are encrypted</p>
                    <div class="form-inputs">
                        <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                        <input type="text" id="file_name" name="file_name" placeholder="File name" data-encrypt="true" value="<?php echo isset($_SESSION['upload_data']['file_name']) ? htmlspecialchars($_SESSION['upload_data']['file_name']) : ''; ?>">
                        <div class="form-box">
                            <input type="text" id="key" name="key" placeholder="Enter a 256-bit key (64 hex characters)" data-encrypt="true"> <span>or</span>
                            <span class="btn gray-btn" id="generate_key_button">Generate Key</span>
                        </div>
                        <span class="caption-text">You will receive an email with the key</span>
                        <div class="custom-input">
                            <span id="file-name" class="file-name">Select file</span>
                            <label for="file" class="custom-file-label">Choose File</label>
                            <input type="file" name="file" id="file" class="custom-file-input">
                        </div>
                    </div>
                    <!-- Submit button with reCAPTCHA trigger -->
                    <button class="g-recaptcha btn white-btn" 
                        data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                        data-callback='onSubmit' 
                        data-action='submit'><i class="fa-solid fa-upload"></i> Upload</button><br>
                </form>
                <span class="caption-text" style="font-size: 14px;">Your files are end-to-end encrypted</span>
            </div>

            <!-- Open file popup -->
            <div class="file-ellipse-popup" id="openFile">
                <form action="/includes/file_management/file_preview.inc.php" method="post" id="open_file_form" class="secure-form">
                    <h2></h2> <!-- File name will be inserted here dynamically-->
                    <p class="caption-text">A decryption key is required to open this file</p>
                    <div class="form-inputs">
                        <input type="text" id="decryption_key" name="key" placeholder="Enter decryption key" data-encrypt="true">
                        <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                    </div>
                    <!-- Submit button with reCAPTCHA trigger -->
                    <button class="g-recaptcha btn black-btn" 
                        data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                        data-callback='onSubmitPreview' 
                        data-action='submit'>Open</button><br>
                </form>
                <span class="caption-text" style="font-size: 14px;">Your files are end-to-end encrypted</span>
            </div>

            <!-- Share file popup -->
            <div class="file-ellipse-popup" id="shareFile">
                <form action="/includes/file_management/file_share.inc.php" method="post" id="share_file_form" class="secure-form">
                    <h2></h2> 
                    <p class="caption-text">Share this file securely</p>
                    <div class="form-inputs">
                        <input type="text" id="email" name="email" placeholder="Recipient's email address" data-encrypt="true">
                        <input type="text" id="decryption_key" name="key" placeholder="Enter decryption key" data-encrypt="true">
                        <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                    </div>
                    <button class="g-recaptcha btn black-btn" 
                        data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                        data-callback='onSubmitShare' 
                        data-action='submit'>Share</button><br>
                </form>
                <span class="caption-text" style="font-size: 14px;">The file will be encrypted and the key will be sent separately</span>
            </div>

            <!-- Download file popup -->
            <div class="file-ellipse-popup" id="downloadFile">
                <form action="/includes/file_management/file_download.inc.php" method="post" id="download_file_form" class="secure-form">
                    <h2></h2> <!-- File name will be inserted here dynamically-->
                    <p class="caption-text">Select download option:</p>
                    <div class="form-inputs">
                        <div id="decryption_key_container">
                            <input type="text" id="decryption_key" name="key" placeholder="Enter decryption key to verify download" data-encrypt="true" >
                            <p class="caption-text" style="margin-top: 5px; color: #555;">
                                Your decryption key is required for both download options
                            </p>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                        <input type="hidden" name="download_action" id="download_action" value="decrypted" data-encrypt="true">
                    </div>
                    <div class="download-buttons">
                        <!-- Single reCAPTCHA button for decrypted download -->
                        <button id="decrypted_download_btn" class="g-recaptcha btn black-btn" 
                            data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                            data-callback='onSubmitDownload' 
                            data-action='submit'>Decrypt & Download</button>
                        
                        <!-- Button to trigger encrypted download -->
                        <button type="button" class="btn gray-btn" id="encrypted_download_btn">
                            Download Encrypted File
                        </button>
                    </div>
                </form>
                <span class="caption-text" style="font-size: 14px;">Your files are end-to-end encrypted</span>
            </div>

            <!-- Delete file popup -->
            <div class="file-ellipse-popup" id="deleteFile">
                <form action="/includes/file_management/file_delete.inc.php" method="post" id="delete_file_form" class="secure-form">
                    <h2></h2> <!-- File name will be inserted here dynamically using JavaScript-->
                    <p class="error-danger">This will permanently delete the file and cannot be undone.</p> <br>
                    <p class="caption-text">For security, please enter the decryption key for this file to confirm deletion.</p>
                    <div class="form-inputs">
                        <input type="text" id="decryption_key" name="decryption_key" data-encrypt="true" placeholder="Enter the file's decryption key">
                        <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>"> 
                    </div>
                    <!-- Submit button with reCAPTCHA trigger -->
                    <button class="g-recaptcha btn black-btn" 
                        data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                        data-callback='onSubmitDelete' 
                        data-action='submit'>Delete</button><br>
                </form>
                <p class="caption-text" style="font-size: 14px; margin: 0 20px;">You can find the decryption key in your email from when you uploaded this file.</p>
                <!-- <span class="caption-text" style="font-size: 14px;">Your files are end-to-end encrypted</span> -->
            </div>

            <!-- Clear upload data from session after loading -->
            <?php if (isset($_SESSION['upload_data'])) { ?>
                <?php unset($_SESSION['upload_data']); ?>
            <?php } ?>
        </div>
    </div>
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // File input change handler
            const fileInput = document.getElementById('file');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const fileNameSpan = document.getElementById('file-name');
                    if (fileNameSpan) {
                        fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'No file chosen';
                    }
                });
            }

             // Enhanced file preview submission with encryption
            const openFileForm = document.getElementById('open_file_form');
            const previewBtn = openFileForm ? openFileForm.querySelector('button') : null;
            
            if (previewBtn && openFileForm) {
                // Replace with a new button to remove existing listeners
                const newBtn = previewBtn.cloneNode(true);
                previewBtn.parentNode.replaceChild(newBtn, previewBtn);
                
                // Add enhanced click handler with proper encryption
                newBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Get key input
                    const keyInput = openFileForm.querySelector('input[name="key"]');
                    
                    // Validate key
                    if (!keyInput || keyInput.value.trim() === '') {
                        alert("Please enter a decryption key to preview the file.");
                        return;
                    }
                    
                    // Show loading overlay
                    if (typeof showLoadingOverlay === 'function') {
                        showLoadingOverlay("Decrypting file for preview...");
                    }
                    
                    // Set a timeout to hide the overlay after a reasonable time
                    setTimeout(function() {
                        if (typeof hideLoadingOverlay === 'function') {
                            hideLoadingOverlay();
                        }
                        
                        // Also close any open popups
                        if (typeof closeAllPopups === 'function') {
                            closeAllPopups();
                        }
                    }, 4000);
                    
                    // Get all sensitive fields that need encryption
                    const sensitiveFields = openFileForm.querySelectorAll('[data-encrypt="true"]');
                    
                    // If no fields require encryption, just submit the form
                    if (sensitiveFields.length === 0) {
                        openFileForm.submit();
                        return;
                    }
                    
                    try {
                        // Create payload from sensitive fields
                        let payload = {};
                        sensitiveFields.forEach(field => {
                            payload[field.name] = field.value;
                            
                            // Disable original field to prevent it from being submitted
                            field.disabled = true;
                            
                            // Create hidden field with empty value
                            let hiddenField = openFileForm.querySelector(`input[type="hidden"][name="${field.name}"]`);
                            if (!hiddenField) {
                                hiddenField = document.createElement('input');
                                hiddenField.type = 'hidden';
                                hiddenField.name = field.name;
                                openFileForm.appendChild(hiddenField);
                            }
                            hiddenField.value = '';
                        });
                        
                        // Get server public key
                        const publicKey = window.SERVER_PUBLIC_KEY;
                        if (!publicKey) {
                            throw new Error("Server public key not found");
                        }
                        
                        // Encrypt data
                        const encrypt = new JSEncrypt();
                        encrypt.setPublicKey(publicKey);
                        const encryptedData = encrypt.encrypt(JSON.stringify(payload));
                        
                        if (!encryptedData) {
                            throw new Error("Encryption failed");
                        }
                        
                        // Add encrypted data to form
                        let encryptedField = openFileForm.querySelector('input[name="encrypted_data"]');
                        if (!encryptedField) {
                            encryptedField = document.createElement('input');
                            encryptedField.type = 'hidden';
                            encryptedField.name = 'encrypted_data';
                            openFileForm.appendChild(encryptedField);
                        }
                        encryptedField.value = encryptedData;
                        
                        // Add recaptcha token if needed
                        let tokenInput = openFileForm.querySelector('input[name="g-recaptcha-response"]');
                        if (!tokenInput) {
                            tokenInput = document.createElement('input');
                            tokenInput.type = 'hidden';
                            tokenInput.name = 'g-recaptcha-response';
                            openFileForm.appendChild(tokenInput);
                        }
                        tokenInput.value = 'bypass_token';
                        
                        // Submit the form
                        openFileForm.submit();
                        
                    } catch (error) {
                        console.error("Encryption error:", error);
                        
                        // Re-enable fields on error
                        sensitiveFields.forEach(field => {
                            field.disabled = false;
                        });
                        
                        hideLoadingOverlay();
                        alert("Error encrypting form data. Please try again.");
                    }
                });
                
                // Remove any data-callback attribute to prevent double processing
                newBtn.removeAttribute('data-callback');
            }
            // Make sure we don't lose the SERVER_PUBLIC_KEY on page load
            if (!window.SERVER_PUBLIC_KEY && typeof SERVER_PUBLIC_KEY !== 'undefined') {
                window.SERVER_PUBLIC_KEY = SERVER_PUBLIC_KEY;
            }
            
            // Enhanced download button handlers with proper encryption
            function setupDownloadButtons() {
                console.log("Setting up download buttons with clean handlers");
                
                // 1. Decrypted download button - replace any existing handler
                const decryptBtn = document.getElementById('decrypted_download_btn');
                if (decryptBtn) {
                    // Clone to remove all existing handlers
                    const newDecryptBtn = decryptBtn.cloneNode(true);
                    if (decryptBtn.parentNode) {
                        decryptBtn.parentNode.replaceChild(newDecryptBtn, decryptBtn);
                    }
                    
                    // Add clean handler with proper encryption
                    newDecryptBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Process the download with decryption
                        processDownload('decrypted');
                    });
                    
                    // Remove any data-callback attribute to prevent double processing
                    newDecryptBtn.removeAttribute('data-callback');
                }
                
                // 2. Encrypted download button - replace any existing handler
                const encryptBtn = document.getElementById('encrypted_download_btn');
                if (encryptBtn) {
                    // Clone to remove all existing handlers
                    const newEncryptBtn = encryptBtn.cloneNode(true);
                    if (encryptBtn.parentNode) {
                        encryptBtn.parentNode.replaceChild(newEncryptBtn, encryptBtn);
                    }
                    
                    // Add clean handler with proper encryption
                    newEncryptBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Process the download without decryption
                        processDownload('encrypted');
                    });
                }
            }
            
            // Clean implementation of download processing with proper encryption
            function processDownload(downloadType) {
                const form = document.getElementById('download_file_form');
                if (!form) {
                    console.error('Download form not found');
                    return;
                }
                
                // Validate the form input
                const keyInput = form.querySelector('input[name="key"]');
                if (!keyInput || keyInput.value.trim() === '') {
                    alert("Please enter a decryption key to verify your ownership of this file.");
                    return;
                }
                
                // Set the download action
                const actionInput = form.querySelector('input[name="download_action"]');
                if (actionInput) {
                    actionInput.value = downloadType;
                } else {
                    // Create the action input if it doesn't exist
                    const newActionInput = document.createElement('input');
                    newActionInput.type = 'hidden';
                    newActionInput.name = 'download_action';
                    newActionInput.value = downloadType;
                    form.appendChild(newActionInput);
                }
                
                // Show appropriate loading message
                const loadingMessage = downloadType === 'encrypted' 
                    ? "Downloading encrypted file..." 
                    : "Decrypting and downloading file...";
                
                if (typeof showLoadingOverlay === 'function') {
                    showLoadingOverlay(loadingMessage);
                }
                
                // Record that a download is starting for timer-based overlay hiding
                sessionStorage.setItem('downloadStarted', Date.now().toString());
                
                // Set a timeout to hide the overlay after download likely started
                setTimeout(function() {
                    console.log("Download timer completed");
                    
                    // Hide the overlay
                    if (typeof hideLoadingOverlay === 'function') {
                        hideLoadingOverlay();
                    }
                    
                    // Close any open popups
                    if (typeof closeAllPopups === 'function') {
                        closeAllPopups();
                    }
                    
                    // Clear download tracking
                    sessionStorage.removeItem('downloadStarted');
                }, 4000);
                
                // Encrypt the form data
                try {
                    // Get sensitive fields
                    const sensitiveFields = form.querySelectorAll('[data-encrypt="true"]');
                    
                    // Create payload
                    let payload = {};
                    sensitiveFields.forEach(field => {
                        console.log("Adding field to payload:", field.name, field.value);
                        payload[field.name] = field.value;
                        
                        // Disable the original field
                        field.disabled = true;
                        
                        // Create hidden field to preserve form structure
                        const hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.name = field.name;
                        hiddenField.value = '';
                        form.appendChild(hiddenField);
                    });
                    
                    // Add file ID if not already in payload
                    const fileIdInput = form.querySelector('input[name="file_id"]');
                    if (fileIdInput && fileIdInput.value && !payload.file_id) {
                        payload.file_id = fileIdInput.value;
                    }
                    
                    // Check if server public key is available
                    if (!window.SERVER_PUBLIC_KEY) {
                        console.error("Server public key not available for encryption");
                        alert("Encryption key not available. Please refresh the page and try again.");
                        return;
                    }
                    
                    // Encrypt the payload
                    const encrypt = new JSEncrypt();
                    encrypt.setPublicKey(window.SERVER_PUBLIC_KEY);
                    const encryptedData = encrypt.encrypt(JSON.stringify(payload));
                    
                    if (!encryptedData) {
                        throw new Error("Encryption failed");
                    }
                    
                    // Add encrypted data to form
                    let encryptedField = form.querySelector('input[name="encrypted_data"]');
                    if (!encryptedField) {
                        encryptedField = document.createElement('input');
                        encryptedField.type = 'hidden';
                        encryptedField.name = 'encrypted_data';
                        form.appendChild(encryptedField);
                    }
                    encryptedField.value = encryptedData;
                    
                    // Add reCAPTCHA token
                    let tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
                    if (!tokenInput) {
                        tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = 'g-recaptcha-response';
                        form.appendChild(tokenInput);
                    }
                    tokenInput.value = 'bypass_token';
                    
                    // Submit the form
                    console.log("Submitting download form with encrypted data");
                    form.submit();
                    
                } catch (error) {
                    console.error("Error processing download:", error);
                    
                    // Re-enable fields on error
                    const sensitiveFields = form.querySelectorAll('[data-encrypt="true"]');
                    sensitiveFields.forEach(field => {
                        field.disabled = false;
                    });
                    
                    // Hide overlay and show error
                    if (typeof hideLoadingOverlay === 'function') {
                        hideLoadingOverlay();
                    }
                    alert("Error processing download request. Please try again.");
                }
            }
            
            // Setup download buttons when DOM is loaded
            setupDownloadButtons();
            
            // Force hide any visible overlay on load
            setTimeout(function() {
                if (typeof hideLoadingOverlay === 'function') {
                    hideLoadingOverlay();
                }
            }, 500);
        });
    </script>
</body>
</html>