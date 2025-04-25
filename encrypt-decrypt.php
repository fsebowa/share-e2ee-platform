<?php
    require_once __DIR__ . '/includes/config/config_session.inc.php';
    require_once __DIR__ . '/includes/auth/auth_checker.inc.php';
    require_once __DIR__ . '/includes/config/dbh.inc.php';
    require_once __DIR__ . '/includes/file_management/file_model.inc.php';
    require_once __DIR__ . '/includes/file_management/file_view.inc.php';

    check_login_otp_status(); 
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Encrypt & Decrypt Files</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script src="/js/profile-popup.js"></script>
    <script src="/js/progress-bar.js"></script>
    <script src="/js/form-encryption.js"></script>
    <script src="/js/dashboard_func.js"></script>
    <script src="/js/encrypt-decrypt.js"></script>
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
                        <button class="action-btn active-btn"><i class="fa-solid fa-user-lock"></i>Encrypt/ Decrypt</button>
                        <button class="action-btn"><i class="fa-solid fa-code-compare"></i>Calculate hashes</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-dashboard">
            <div class="dash-files dash-flex" style="padding: 50px; margin: 20px;">
                <!-- Encrypt file form -->
                <div class="encrypt-decrypt-form" id="encrypt_file">
                    <form action="/includes/file_management/file_encrypt_decrypt.inc.php" method="post" id="encrypt_file_form" enctype="multipart/form-data" class="secure-form">
                        <h2>Encryption</h2>
                        <p class="caption-text">Encrypted files will be downloaded to your device</p>
                        <div class="form-inputs">
                            <input type="hidden" name="operation" value="encrypt" data-encrypt="true">
                            <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                            <div class="form-box">
                                <input type="text" id="encrypt_key" name="key" placeholder="Enter a 256-bit key (64 hex characters)" data-encrypt="true"> <span>or</span>
                                <span class="btn gray-btn" id="encrypt_generate_key">Generate Key</span>
                            </div>
                            <div class="custom-input">
                                <span id="encrypt-file-name" class="file-name truncate" style="max-width: 210px;">Select file</span>
                                <label for="encrypt_file_input" class="custom-file-label">Choose File</label>
                                <input type="file" name="file" id="encrypt_file_input" class="custom-file-input">
                            </div>
                        </div>
                        <button class="g-recaptcha btn black-btn" 
                            data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                            data-callback='onSubmitEncrypted' 
                            data-action='submit'>Encrypt & Download</button><br>
                    </form>
                    <span class="caption-text" style="font-size: 14px;">Your files are end-to-end encrypted</span>
                </div>
                <!-- Decrypt file form -->
                <div class="encrypt-decrypt-form" id="decrypt_file">
                    <form action="/includes/file_management/file_encrypt_decrypt.inc.php" method="post" id="decrypt_file_form" enctype="multipart/form-data" class="secure-form">
                        <h2>Decryption</h2>
                        <p class="caption-text">Upload an encrypted file to decrypt it</p>
                        <div class="form-inputs">
                            <input type="hidden" name="operation" value="decrypt" data-encrypt="true">
                            <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                            <div class="form-box">
                                <input type="text" id="decrypt_key" name="key" placeholder="Enter the decryption key (64 hex characters)" data-encrypt="true" style="width: 100%;">
                            </div>
                            <div class="custom-input">
                                <span id="decrypt-file-name" class="file-name truncate" style="max-width: 210px;">Upload encrypted file</span>
                                <label for="decrypt_file_input" class="custom-file-label">Choose File</label>
                                <input type="file" name="file" id="decrypt_file_input" class="custom-file-input">
                            </div>
                        </div>
                        <button class="g-recaptcha btn black-btn" 
                            data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                            data-callback='onSubmitDecrypted' 
                            data-action='submit'>Decrypt & Download</button><br>
                    </form>
                    <span class="caption-text" style="font-size: 14px;">Your files are end-to-end encrypted</span>
                </div>
            </div>
            <?php
                // Display error messages if any
                if (isset($_SESSION['errors_encrypt_decrypt'])) {
                    echo '<div class="error-messages" id="errorMessages">';
                    foreach ($_SESSION['errors_encrypt_decrypt'] as $error) {
                        echo '<p class="error-danger">' . htmlspecialchars($error) . '</p>';
                    }
                    echo '</div>';
                    unset($_SESSION['errors_encrypt_decrypt']);
                }
            ?>
        </div>
    </div>
    
    <style>
        /* Ensure loading overlay is visible */
        #loadingOverlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: rgba(0, 0, 0, 0.7) !important;
            z-index: 9999 !important;
            display: none;
        }
        
        #uploadProgressContainer {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            background-color: white !important;
            padding: 20px !important;
            border-radius: 10px !important;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5) !important;
            width: 300px !important;
            height: 200px !important;
            z-index: 10000 !important;
        }
    </style>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" style="display: none;">
        <div id="uploadProgressContainer">
            <div class="progress-container">
                <i class="fa-solid fa-shield-halved"></i>
                <div class="progress-bar indeterminate" id="progressBar"></div>
                <p id="loading-message">Processing file...</p>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
</body>
</html>