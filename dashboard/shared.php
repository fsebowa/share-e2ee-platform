<?php
    require_once __DIR__ . '/../includes/config/config_session.inc.php';
    require_once __DIR__ . '/../includes/auth/auth_checker.inc.php';
    require_once __DIR__ . '/../includes/config/dbh.inc.php';
    require_once __DIR__ . '/../includes/file_management/file_model.inc.php';
    require_once __DIR__ . '/../includes/file_management/file_view.inc.php';
    require_once __DIR__ . '/../includes/share/share_model.inc.php';

    check_login_otp_status(); 

    // get user's files
    $shared_files = get_all_user_shared_files($pdo, $_SESSION["user_id"] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/../includes/templates/header.php"; ?>
    <title>Shared Files</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- JSEncrypt library for RSA encryption -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script src="/js/profile-popup.js"></script>
    <script src="/js/progress-bar.js"></script>
    <script src="/js/form-encryption.js"></script>
    <script src="/js/dashboard_func.js"></script>
    <script src="/js/share.js"></script>
</head>
<body class="dashboard">
    <?php include __DIR__ . "/../includes/templates/dashboard_nav.php"; ?>
    <div class="container dash-flex">
        <div class="left-menu">
            <button id="add_file"><span style="font-size: 18px;">+</span> New</button>
            <div class="left-menu-top">
                <div class="action-buttons">
                    <div class="btn-container">
                        <button class="action-btn active-btn"><a href="/dashboard.php"><i class="fa-regular fa-folder-open"></i> Files</a></button>
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
                <!-- Query shared files -->
                
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
                    <h2>Share File</h2> <!-- Filename will be inserted here dynamically -->
                    <p class="caption-text">Share this file securely</p>
                    <div class="form-inputs">
                        <input type="hidden" name="file_id" data-encrypt="true">
                        <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                        <div class="form-box">
                            <label for="key_delivery">Key delivery method:</label>
                            <select class="btn-form btn1" id="key_delivery" name="key_delivery" data-encrypt="true">
                                <option value="email">Send via email</option>
                                <option value="manual">Share key manually</option>
                            </select>
                        </div>
                        <div class="form-box" style="justify-content: space-between;">
                            <div class="form-box">
                                <label for="expiry_days">Link expires after:</label>
                                <select class="btn-form btn1" id="expiry_days" name="expiry_days" data-encrypt="true">
                                    <option value="1" selected>1 day</option>
                                    <option value="3">3 days</option>
                                    <option value="7">7 days</option>
                                    <option value="14">14 days</option>
                                    <option value="30">30 days</option>
                                </select>
                            </div>
                            <div class="form-box">
                                <label for="password_protect">Add Password?</label>
                                <input style="width: 15px;" type="checkbox" id="password_protect" class="share-checkbox">
                            </div>
                        </div>
                        <div class="form-box">
                            <input type="number" id="max_access" name="max_access" placeholder="Max downloads (optional)" min="1" max="100" data-encrypt="true">
                            <div id="password_field" style="display: none; width: 100%;">
                                <input type="password" id="share_password" name="share_password" placeholder="Enter a password" data-encrypt="true">
                            </div>
                        </div>
                        
                        <div id="recipient_field">
                            <input type="email" id="recipient" name="recipient" placeholder="Recipient's email address" data-encrypt="true">
                        </div>
                        <!-- <input type="number" id="max_access" name="max_access" placeholder="Maximum downloads (optional)" min="1" max="100" data-encrypt="true"> -->
                        <input type="text" id="decryption_key" name="decryption_key" placeholder="Enter file's decryption key" data-encrypt="true" required>
                    </div>
                    <!-- Submit button with reCAPTCHA trigger -->
                    <button class="g-recaptcha btn black-btn" 
                        data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                        data-callback='onSubmitShare' 
                        data-action='submit'>Create Share Link</button><br>
                </form>
                <!-- Success message area (hidden by default) -->
                <div class="share-success">
                    <h3>File shared successfully!</h3>
                    <div class="copy-field">
                        <p>Share this link with your recipient:</p>
                        <input type="text" id="share_url" readonly>
                        <button class="copy-button" onclick="copyToClipboard('share_url')">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                        <span id="share_url_copied" class="copied-message">Copied!</span>
                    </div>
                    <div class="copy-field">
                        <p>Decryption key (required to access the file):</p>
                        <input type="text" id="share_key" readonly>
                        <button class="copy-button" onclick="copyToClipboard('share_key')">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                        <span id="share_key_copied" class="copied-message">Copied!</span>
                    </div>
                    
                    <p>The share link will expire on: <span id="share_expiry"></span></p>
                    <div id="share_recipient_info"></div>
                </div>
                <span class="caption-text" style="font-size: 14px;">Files are shared with end-to-end encryption</span>
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
            </div>
        </div>
    </div>
    <?php include __DIR__ . "/../includes/templates/footer.php"; ?>
</body>
</html>