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

?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Dashboard</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script>
        // Make error flags available to JavaScript
        const hasUploadErrors = <?php echo $hasUploadErrors ? 'true' : 'false'; ?>;
        const hasPreviewErrors = <?php echo $hasPreviewErrors ? 'true' : 'false'; ?>;
        const hasDeleteErrors = <?php echo $hasDeleteErrors ? 'true' : 'false'; ?>;
    </script>
    <script src="/js/profile-popup.js"></script>
    <script src="/js/progress-bar.js"></script>
    <script src="/js/form-encryption.js"></script>
    <script src="/js/dashboard_func.js"></script>
    <script>
        let closeAllPopups;
    </script>
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
                                    <p class="caption-text"> 
                                        <span><?php echo htmlspecialchars($file['file_type']);?> </span>|
                                        <span> <?php echo number_format($file['file_size'] / 1024 /1024, 2); ?> MB</span>
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
                ?>
            </div>
            
            <!-- Upload progress -->
            <div class="progress-backdrop" id="uploadProgressBackdrop">
                <div id="uploadProgressContainer">
                    <div class="progress-container">
                        <div class="progress-bar" id="uploadProgressBar"></div>
                        <p>Uploading file...</p>
                    </div>
                </div>
            </div>

            <!-- Delete progress -->
            <div class="progress-backdrop delete-progress" id="deleteProgressBackdrop">
                <div id="deleteProgressContainer">
                    <div class="progress-container">
                        <div class="progress-bar" id="deleteProgressBar"></div>
                        <p>Deleting file...</p>
                    </div>
                </div>
            </div>

            <!-- Add new file form -->
            <div class="upload-form" id="uploadForm">
                <form action="/includes/file_management/file_upload.inc.php" method="post" id="file_upload_form" enctype="multipart/form-data">
                    <h2>Upload new File</h2>
                    <p class="caption-text">All files uploaded are encrypted</p>
                    <div class="form-inputs">
                        <input type="text" id="file_name" name="file_name" placeholder="File name" value="<?php echo isset($_SESSION['upload_data']['file_name']) ? htmlspecialchars($_SESSION['upload_data']['file_name']) : ''; ?>">
                        <div class="form-box">
                            <input type="text" id="key" name="key" placeholder="Enter a 256-bit key (64 hex characters)"> <span>or</span>
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
                <form action="/includes/file_management/file_preview.inc.php" method="post" id="open_file_form">
                    <h2></h2> <!-- File name will be inserted here dynamically-->
                    <p class="caption-text">A decryption key is required to open this file</p>
                    <div class="form-inputs">
                        <input type="text" id="decryption_key" name="key" placeholder="Enter decryption key">
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

            <!-- Delete file popup -->
            <div class="file-ellipse-popup" id="deleteFile">
                <form action="/includes/file_management/file_delete.inc.php" method="post" id="delete_file_form">
                    <h2></h2> <!-- File name will be inserted here dynamically-->
                    <p class="error-danger">This will permanently delete the file and cannot be undone.</p>
                    <div class="form-inputs">
                        <input type="text" id="delete_phrase" name="delete_phrase" placeholder="Type “DELETE” to complete the action">
                        <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>"> 
                    </div>
                    <!-- Submit button with reCAPTCHA trigger -->
                    <button class="g-recaptcha btn black-btn" 
                        data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                        data-callback='onSubmitDelete' 
                        data-action='submit'>Delete</button><br>
                </form>
                <span class="caption-text" style="font-size: 14px;">Your files are end-to-end encrypted</span>
            </div>

            <!-- Clear upload data from session after loading -->
            <?php if (isset($_SESSION['upload_data'])) { ?>
                <?php unset($_SESSION['upload_data']); ?>
            <?php } ?>
        </div>
    </div>
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
    <script>
        // File input change handler
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('file');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const fileNameSpan = document.getElementById('file-name');
                    if (fileNameSpan) {
                        fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'No file chosen';
                    }
                });
            }
        });
    </script>
</body>
</html>