<?php
require_once __DIR__ . '/includes/auth/auth_checker.inc.php';
require_once __DIR__ . '/includes/config/config_session.inc.php';

// Ensure user is logged in and verified
if (!check_login_otp_status()) {
    exit();
} 
// Get file ID from URL parameter
$fileId = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;

// Check if file exists and belongs to the user
if ($fileId > 0) {
    try {
        require_once __DIR__ . '/includes/config/dbh.inc.php';
        require_once __DIR__ . '/includes/file_management/file_model.inc.php';
        
        $userId = $_SESSION['user_id'];
        $file = get_file_by_id($pdo, $fileId, $userId);
        
        if (!$file) {
            // File not found or doesn't belong to user
            header("Location: /dashboard.php?error=file_not_found");
            exit();
        }
        
        // File found, proceed with share form
    } catch (PDOException $e) {
        // Database error
        header("Location: /dashboard.php?error=database_error");
        exit();
    }
} else {
    // No file ID provided
    header("Location: /dashboard.php?error=no_file_selected");
    exit();
}

// Check for success message after share creation
$shareCreated = isset($_SESSION['share_success']) && $_SESSION['share_success'] === true;
if ($shareCreated) {
    $shareUrl = $_SESSION['share_url'] ?? '';
    $shareDecryptionKey = $_SESSION['share_decryption_key'] ?? '';
    $shareExpiry = $_SESSION['share_expiry'] ?? '';
    $shareDelivery = $_SESSION['share_delivery'] ?? '';
    $shareRecipient = $_SESSION['share_recipient'] ?? '';
    
    // Clear session data
    unset($_SESSION['share_success']);
    unset($_SESSION['share_url']);
    unset($_SESSION['share_decryption_key']);
    unset($_SESSION['share_expiry']);
    unset($_SESSION['share_delivery']);
    unset($_SESSION['share_recipient']);
}

// Get any errors from previous submission
$errors = $_SESSION['share_errors'] ?? [];
$oldData = $_SESSION['share_data'] ?? [];

// Clear session data
unset($_SESSION['share_errors']);
unset($_SESSION['share_data']);
?>

<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Share File - Share Platform</title>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script>
    function onSubmit(token) {
        document.getElementById("share_form").submit();
    }
    
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        element.select();
        document.execCommand('copy');
        
        // Show copied message
        const messageId = elementId + '_copied';
        const message = document.getElementById(messageId);
        if (message) {
            message.style.display = 'inline';
            setTimeout(() => {
                message.style.display = 'none';
            }, 2000);
        }
    }
    
    // Toggle form fields based on key delivery method
    document.addEventListener('DOMContentLoaded', function() {
        const keyDeliverySelect = document.getElementById('key_delivery');
        const recipientField = document.getElementById('recipient_field');
        
        if (keyDeliverySelect && recipientField) {
            keyDeliverySelect.addEventListener('change', function() {
                if (this.value === 'email') {
                    recipientField.style.display = 'block';
                    document.getElementById('recipient').required = true;
                } else {
                    recipientField.style.display = 'none';
                    document.getElementById('recipient').required = false;
                }
            });
            
            // Initial setup
            if (keyDeliverySelect.value === 'email') {
                recipientField.style.display = 'block';
                document.getElementById('recipient').required = true;
            } else {
                recipientField.style.display = 'none';
                document.getElementById('recipient').required = false;
            }
        }
    });
    </script>
    <style>
    .share-success {
        margin: 20px auto;
        padding: 20px;
        background-color: #f0f9ff;
        border: 1px solid #7C7B7B;
        border-radius: 10px;
    }
    .share-success h3 {
        color: green;
    }
    .copy-field {
        position: relative;
        margin: 15px 0;
    }
    .copy-field input {
        width: 100%;
        padding-right: 40px;
    }
    .copy-button {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
    }
    .copied-message {
        display: none;
        color: green;
        font-size: 12px;
        margin-left: 10px;
    }
    </style>
</head>
<body class="share-file">
    <?php include __DIR__ . "/includes/templates/dashboard_nav.php"; ?>
    
    <div class="container">
        <h1>Share File</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($shareCreated) && $shareCreated): ?>
            <div class="share-success">
                <h3>File Shared Successfully!</h3>
                
                <p>Your file has been shared. Here are the details:</p>
                
                <div class="copy-field">
                    <label for="share_url">Share URL:</label>
                    <input type="text" id="share_url" value="<?php echo htmlspecialchars($shareUrl); ?>" readonly>
                    <button type="button" class="copy-button" onclick="copyToClipboard('share_url')">
                        <i class="fas fa-copy"></i>
                    </button>
                    <span id="share_url_copied" class="copied-message">Copied!</span>
                </div>
                
                <div class="copy-field">
                    <label for="decryption_key">Decryption Key:</label>
                    <input type="text" id="decryption_key" value="<?php echo htmlspecialchars($shareDecryptionKey); ?>" readonly>
                    <button type="button" class="copy-button" onclick="copyToClipboard('decryption_key')">
                        <i class="fas fa-copy"></i>
                    </button>
                    <span id="decryption_key_copied" class="copied-message">Copied!</span>
                </div>
                
                <p><strong>Expires On:</strong> <?php echo htmlspecialchars($shareExpiry); ?></p>
                
                <?php if ($shareDelivery === 'email' && !empty($shareRecipient)): ?>
                    <p>An email with the share link and decryption key has been sent to: <?php echo htmlspecialchars($shareRecipient); ?></p>
                <?php elseif ($shareDelivery === 'manual'): ?>
                    <p>Please securely share the URL and decryption key with the recipient.</p>
                <?php endif; ?>
                
                <a href="/dashboard.php" class="btn black-btn">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <div class="file-details">
                <h3>File Details</h3>
                <p><strong>File Name:</strong> <?php echo htmlspecialchars($file['original_filename']); ?></p>
                <p><strong>File Size:</strong> <?php echo round($file['file_size'] / 1024 / 1024, 2); ?> MB</p>
                <p><strong>Uploaded On:</strong> <?php echo date('d/m/Y H:i', strtotime($file['date_uploaded'])); ?></p>
            </div>
            
            <form id="share_form" action="/includes/share/share.inc.php" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                <input type="hidden" name="file_id" value="<?php echo $fileId; ?>">
                
                <div class="form-inputs">
                    <div id="recipient_field" class="form-group">
                        <label for="recipient">Recipient Email:</label>
                        <input type="email" id="recipient" name="recipient" placeholder="Recipient Email" 
                                value="<?php echo htmlspecialchars($oldData['recipient'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="expiry_days">Expire After:</label>
                        <select id="expiry_days" name="expiry_days">
                            <option value="1" <?php echo (($oldData['expiry_days'] ?? '7') == '1') ? 'selected' : ''; ?>>1 day</option>
                            <option value="3" <?php echo (($oldData['expiry_days'] ?? '7') == '3') ? 'selected' : ''; ?>>3 days</option>
                            <option value="7" <?php echo (($oldData['expiry_days'] ?? '7') == '7') ? 'selected' : ''; ?>>7 days</option>
                            <option value="14" <?php echo (($oldData['expiry_days'] ?? '7') == '14') ? 'selected' : ''; ?>>14 days</option>
                            <option value="30" <?php echo (($oldData['expiry_days'] ?? '7') == '30') ? 'selected' : ''; ?>>30 days</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_access">Maximum Downloads (optional):</label>
                        <input type="number" id="max_access" name="max_access" placeholder="Leave empty for unlimited" min="1" 
                                value="<?php echo htmlspecialchars($oldData['max_access'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="key_delivery">Decryption Key Delivery:</label>
                        <select id="key_delivery" name="key_delivery">
                            <option value="manual" <?php echo (($oldData['key_delivery'] ?? 'manual') == 'manual') ? 'selected' : ''; ?>>Provide key manually</option>
                            <option value="email" <?php echo (($oldData['key_delivery'] ?? 'manual') == 'email') ? 'selected' : ''; ?>>Send key via email</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Access Password (optional):</label>
                        <input type="password" id="password" name="password" placeholder="Add a password for extra security">
                        <p class="caption-text">Leave empty for no password protection</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="decryption_key">File Decryption Key:</label>
                        <input type="text" id="decryption_key" name="decryption_key" placeholder="Enter your file's decryption key" required 
                                value="<?php echo htmlspecialchars($oldData['decryption_key'] ?? ''); ?>">
                        <p class="caption-text">This is required to share your encrypted file</p>
                    </div>
                </div>
                
                <button class="g-recaptcha btn black-btn" 
                        data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                        data-callback='onSubmit' 
                        data-action='submit'>Share File</button>
            </form>
            
            <a href="/dashboard.php" class="btn white-btn" style="margin-top: 20px;">Cancel</a>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
</body>
</html>