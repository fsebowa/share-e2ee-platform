<?php
    require_once __DIR__ . '/includes/config/config_session.inc.php';
    require_once __DIR__ . '/includes/auth/auth_checker.inc.php';

    check_login_otp_status(); 
    
    // Check if clear action is requested
    if (isset($_GET['action']) && $_GET['action'] === 'clear') {
        // Clear hash-related session variables
        unset($_SESSION['hash_results']);
        unset($_SESSION['errors_hash']);
        unset($_SESSION['success_hash']);
        unset($_SESSION['last_key']);
        
        // Redirect to remove the query parameter from URL
        header('Location: /hashing.php');
        exit;
    }

    // Store the key in session for display after form submission
    if (isset($_POST['key']) && !empty($_POST['key'])) {
        $_SESSION['last_key'] = $_POST['key'];
    }
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Calculate File Hashes</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script src="/js/profile-popup.js"></script>
    <script src="/js/form-encryption.js"></script>
    <script src="/js/progress-bar.js"></script>
    <style>
        .hash-text span {
            font-family: monospace;
            word-break: break-all;
            display: inline-block;
        }
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #000;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            margin: 0 auto 15px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="dashboard">
    <?php include __DIR__ . "/includes/templates/dashboard_nav.php"; ?>
    <div class="container dash-flex">
        <div class="left-menu">
            <div class="left-menu-top" style="top: 0;">
                <div class="action-buttons">
                    <div class="btn-container">
                        <button class="action-btn"><a href="/dashboard.php"><i class="fa-regular fa-folder-open"></i> Files</a></button>
                        <button class="action-btn"><i class="fa-solid fa-share-nodes"></i><a href="/shared.php">Shared</a></button>
                    </div>
                </div>
                <div class="action-buttons">
                    <div class="btn-container">   
                    <button class="action-btn"><i class="fa-solid fa-user-lock"></i><a href="/encrypt-decrypt.php">Encrypt/ Decrypt</a></button>
                    <button class="action-btn active-btn"><i class="fa-solid fa-code-compare"></i><a href="/hashing.php">Calculate hashes</a></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-dashboard">
            <div class="dash-files" style="padding:20px 50px; margin: 20px; height: 580px;">
                <!-- Calculate hash file form -->
                <div class="hash-form" id="hash_file">
                    <form action="/includes/file_management/file_hash.inc.php" method="post" id="hash_file_form" enctype="multipart/form-data" class="secure-form">
                        <h2>File Verification</h2>
                        <p class="caption-text">Upload two files to calculate and compare HMAC hashes</p>
                        <div class="form-inputs">
                            <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                            <div class="form-box">
                                <input type="text" id="key" name="key" placeholder="Enter a 256-bit key (64 hex characters)" value="<?php echo isset($_SESSION['last_key']) ? htmlspecialchars($_SESSION['last_key']) : ''; ?>"> <span>or</span>
                                <button type="button" class="btn gray-btn" id="generate_key" style="margin-bottom: 0;">Generate Key</button>
                            </div>
                            <div class="custom-input">
                                <span id="hash-file-name-1" class="file-name truncate" style="max-width: 210px;">
                                    <?php echo isset($_SESSION['hash_results']['file1_name']) ? htmlspecialchars($_SESSION['hash_results']['file1_name']) : 'Select file 1'; ?>
                                </span>
                                <label for="hash_file_input_1" class="custom-file-label">Choose File</label>
                                <input type="file" name="hash_file_input_1" id="hash_file_input_1" class="custom-file-input">
                            </div>
                            <p class="hash-text" id="hash_1">Hash 1: <span><?php echo isset($_SESSION['hash_results']['hash1']) ? htmlspecialchars($_SESSION['hash_results']['hash1']) : 'hash will appear here'; ?></span></p>
                            <div class="custom-input">
                                <span id="hash-file-name-2" class="file-name truncate" style="max-width: 210px;">
                                    <?php echo isset($_SESSION['hash_results']['file2_name']) ? htmlspecialchars($_SESSION['hash_results']['file2_name']) : 'Select file 2'; ?>
                                </span>
                                <label for="hash_file_input_2" class="custom-file-label">Choose File</label>
                                <input type="file" name="hash_file_input_2" id="hash_file_input_2" class="custom-file-input">
                            </div>
                            <p class="hash-text" id="hash_2">Hash 2: <span><?php echo isset($_SESSION['hash_results']['hash2']) ? htmlspecialchars($_SESSION['hash_results']['hash2']) : 'hash will appear here'; ?></span></p>
                        </div>
                        <button class="g-recaptcha btn black-btn" 
                            data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                            data-callback='onSubmitHash' 
                            data-action='submit'>Calculate hashes</button><br>
                        <a href="/hashing.php?action=clear" class="btn white-btn">Clear Form</a>
                    </form>
                    <br><span class="caption-text" style="font-size: 14px;">Verify file integrity with HMAC hashing</span>
                </div>
                
                <?php
                // Display error messages if any
                if (isset($_SESSION['errors_hash'])) {
                    echo '<div class="error-messages" id="errorMessages">';
                    foreach ($_SESSION['errors_hash'] as $error) {
                        echo '<p class="error-danger">' . htmlspecialchars($error) . '</p>';
                    }
                    echo '</div>';
                }
                
                // Display success messages if any
                if (isset($_SESSION['success_hash'])) {
                    echo '<div class="success-messages" id="successMessage">';
                    foreach ($_SESSION['success_hash'] as $success) {
                        echo '<p class="success-message">' . htmlspecialchars($success) . '</p>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Progress backdrops -->
    <div class="progress-backdrop" id="uploadProgressBackdrop">
        <div id="uploadProgressContainer">
            <div class="progress-container">
                <i class="fa-solid fa-code-compare"></i>
                <div class="progress-bar" id="uploadProgressBar"></div>
                <p>Calculating hashes...</p>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay compatible with form-encryption.js -->
    <div id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div id="loading-message">Calculating hashes...</div>
        </div>
    </div>
    
    <script src="/js/hash.js"></script>
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
</body>
</html>