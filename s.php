<?php
    include __DIR__ . '/includes/share/s.inc.php';
    if (isset($_SESSION["share_error_messages"])) {
        $errorMessage = $_SESSION["share_error_messages"];
        unset($_SESSION["share_error_messages"]);
    }
?>

<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Shared File - <?php echo htmlspecialchars($shareData['original_name']); ?></title>
    <!-- JSEncrypt library for RSA encryption -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js"></script>
    <script src="/js/form-encryption.js"></script>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script src="/js/profile-popup.js"></script>
    <script src="/js/dashboard_func.js"></script>
</head>
<script>
// Add download detection to hide overlay
document.addEventListener('DOMContentLoaded', function() {
    const downloadForm = document.getElementById('share_decryption_form');
    if (downloadForm) {
        downloadForm.addEventListener('submit', function() {
            setTimeout(function() {
                const overlay = document.getElementById('loading-overlay');
                if (overlay) {
                    overlay.style.display = 'none';
                }
            }, 3000); // 5 seconds
        });
    }
});
</script>
<body class="shared-file">
    <?php include __DIR__ . "/includes/templates/nav.php"; ?>
    <div class="shared-file-container">
        <h1>Shared File Access</h1>
        <?php 
            if (!empty($errorMessage)) {
                echo '<div class="error-messages" id="errorMessages">';
                if (is_array($errorMessage)) {
                    foreach ($errorMessage as $error) {
                        echo '<p class="error-danger">' . htmlspecialchars($error) . '</p>';
                    }
                } else {
                    echo '<p class="error-danger">' . htmlspecialchars($errorMessage) . '</p>';
                }
                echo '</div>';
            }
        ?>
        
        <?php if (isset($shareData) && !empty($shareData)): ?>
            <div class="file-info share-file-wrapper">
                <h2><?php echo htmlspecialchars($shareData['original_name']); ?></h2>
                <div class="form-box" style="justify-content: center;">
                    <p><strong>Shared by:</strong><br> <span class="caption-text"><?php echo htmlspecialchars($shareData['first_name'] . ' ' . $shareData['last_name']); ?></span> </p>
                    <p><strong>File size:</strong><br> <span class="caption-text"><?php echo round($shareData['file_size'] / 1024 / 1024, 2); ?> MB</span> </p>
                    <p><strong>Expires on:</strong><br> <span class="caption-text"><?php echo date('d/m/Y H:i', strtotime($shareData['expiry_date'])); ?></span> </p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($showPasswordForm && !$passwordVerified ): ?>
            <div class="password-form share-file-wrapper">
                <h3>This file is password protected</h3>
                <p class="caption-text">Enter the password provided by the file owner</p>
                <form action="/includes/share/s.inc.php?token=<?php echo htmlspecialchars($shareToken); ?>" method="post" id="share_access_form" class="secure-form">
                    <div class="form-inputs">
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                        <input type="password" id="password" name="password" placeholder="Enter password" data-encrypt="true">
                    </div>
                    <button class="g-recaptcha btn black-btn" 
                                name="submit_password"
                                data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                                data-callback='onSubmitAccessPassword' 
                                data-action='submit'>Submit Password</button>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($showDecryptionKeyForm && isset($shareData)): ?>
            <div class="decryption-form share-file-wrapper">
                <h3>Enter Decryption Key</h3>
                <p class="caption-text">Enter the decryption key provided by the file owner</p>
                
                <form action="/includes/share/share_download.inc.php" method="post" id="share_decryption_form" class="secure-form">
                    <div class="form-inputs">
                        <input type="hidden" name="share_token" value="<?php echo $shareToken; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                        <input type="text" id="decryption_key" name="decryption_key" placeholder="Enter decryption key" data-encrypt="true" required>
                    </div>
                    <button class="g-recaptcha btn black-btn" 
                            data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                            data-callback='onSubmitShareDownload' 
                            data-action='submit'>Download File</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
</body>
</html>