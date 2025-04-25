<?php
    require_once __DIR__ . '/includes/config/config_session.inc.php';
    require_once __DIR__ . '/includes/auth/auth_checker.inc.php';

    check_login_otp_status(); 
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Calculate Hashe Files</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script src="/js/profile-popup.js"></script>
    <script src="/js/form-encryption.js"></script>
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
                    <button class="action-btn  active-btn"><i class="fa-solid fa-code-compare"></i><a href="/hashing.php">Calculate hashes</a></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-dashboard">
            <div class="dash-files" style="padding:20px 50px; margin: 20px; height: 540px;">
                <!-- Calculate hash file form -->
                <div class="hash-form" id="hash_file">
                    <form action="/includes/" method="post" id="hash_file_form" enctype="multipart/form-data" class="secure-form">
                        <h2>File Verification</h2>
                        <p class="caption-text">Upload files to start comparing</p>
                        <div class="form-inputs">
                            <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                            <div class="form-box">
                                <input type="text" id="key" name="key" placeholder="Enter a 256-bit key (64 hex characters)" data-encrypt="true"> <span>or</span>
                                <span class="btn gray-btn" id="generate_key">Generate Key</span>
                            </div>
                            <div class="custom-input">
                                <span id="hash-file-name" class="file-name truncate" style="max-width: 210px;">Select file 1</span>
                                <label for="hash_file_input_1" class="custom-file-label">Choose File</label>
                                <input type="file" name="hash_file_input_1" id="hash_file_input_1" class="custom-file-input">
                            </div>
                            <p class="hash-text" id="hash_1">Hash 1: <span>hash-goes-here</span></p>
                            <div class="custom-input">
                                <span id="hash-file-name" class="file-name truncate" style="max-width: 210px;">Select file 2</span>
                                <label for="hash_file_input_2" class="custom-file-label">Choose File</label>
                                <input type="file" name="hash_file_input_2" id="hash_file_input_2" class="custom-file-input">
                            </div>
                            <p class="hash-text" id="hash_2">Hash 2: <span>hash-goes-here</span></p>
                        </div>
                        <button class="g-recaptcha btn black-btn" 
                            data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                            data-callback='onSubmitHash' 
                            data-action='submit'>Calculate hashes</button><br>
                    </form>
                    <span class="caption-text" style="font-size: 14px;">Your files are end-to-end encrypted</span>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
</body>
</html>