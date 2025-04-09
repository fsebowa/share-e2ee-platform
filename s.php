<?php
require_once __DIR__ . '/includes/config/config_session.inc.php';

// Get share token from URL
$shareToken = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '';

if (empty($shareToken)) {
    // No token provided, show generic error
    $errorMessage = "Invalid or missing share token.";
    $showPasswordForm = false;
    $showDecryptionKeyForm = false;
} else {
    try {
        require_once __DIR__ . '/includes/config/dbh.inc.php';
        require_once __DIR__ . '/includes/share/share_model.inc.php';
        require_once __DIR__ . '/includes/share/share_contr.inc.php';
        
        // Get share information
        $shareData = get_file_share_by_token($pdo, $shareToken);
        
        if (!$shareData) {
            // Share not found
            $errorMessage = "This share link does not exist or has been removed.";
            $showPasswordForm = false;
            $showDecryptionKeyForm = false;
        } else if (!is_share_valid($shareData)) {
            // Share expired or access limit reached
            $errorMessage = "This share link has expired or reached its access limit.";
            $showPasswordForm = false;
            $showDecryptionKeyForm = false;
        } else {
            // Share exists and is valid
            $errorMessage = '';
            // Check if password is required
            $showPasswordForm = !empty($shareData['access_password']);

            // If password already verified or not required, show decryption key form
            $passwordVerified = isset($_SESSION['password_verified_' . $shareData['id']]) && 
                            $_SESSION['password_verified_' . $shareData['id']] === true;
            
            $showDecryptionKeyForm = !$showPasswordForm || $passwordVerified;
            
            // If password form was submitted, verify password
            if ($showPasswordForm && isset($_POST['submit_password'])) {
                $enteredPassword = $_POST['password'] ?? '';
                
                if (verify_share_password($enteredPassword, $shareData['access_password'])) {
                    // Password correct, mark as verified and show decryption key form
                    $_SESSION['password_verified_' . $shareData['id']] = true;
                    $showPasswordForm = false;
                    $showDecryptionKeyForm = true;
                } else {
                    // Password incorrect
                    $errorMessage = "Incorrect password. Please try again.";
                }
            }
            
            // If decryption key form was submitted, process download
            if ($showDecryptionKeyForm && isset($_POST['submit_download'])) {
                $decryptionKey = $_POST['decryption_key'] ?? '';
                
                if (empty($decryptionKey)) {
                    $errorMessage = "Decryption key is required.";
                } else {
                    // Increment access count
                    increment_share_access_count($pdo, $shareData['id']);
                    
                    // Redirect to download with key
                    $fileId = $shareData['file_id'];
                    $downloadUrl = "/includes/file_management/file_download.inc.php?file_id={$fileId}&key=" . urlencode($decryptionKey) . "&share_token={$shareToken}";
                    header("Location: {$downloadUrl}");
                    exit();
                }
            }
        }
    } catch (PDOException $e) {
        // Database error
        error_log("Share access error: " . $e->getMessage());
        $errorMessage = "An error occurred while accessing this shared file.";
        $showPasswordForm = false;
        $showDecryptionKeyForm = false;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Shared File - Share Platform</title>
    <style>
        .shared-file-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0px 0px 15px -5px rgba(0,0,0,0.3);
        }
        
        .file-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .file-info h2 {
            margin-top: 0;
            word-break: break-word;
        }
        
        .password-form, 
        .decryption-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .form-inputs {
            margin: 15px 0;
        }
        
        .form-inputs input {
            width: 100%;
            padding: 10px;
            margin: 5px 0 15px;
            border: 1px solid #ced4da;
            border-radius: 5px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .black-btn {
            background-color: #000;
            color: #fff;
        }
        
        .black-btn:hover {
            background-color: #333;
        }
    </style>
</head>
<body class="shared-file">
    <div class="header">
        <div class="container">
            <nav>
                <ul>
                    <li class="logo-text"><a href="/index.php">Share</a></li>
                </ul>
            </nav>
        </div>
    </div>
    
    <div class="shared-file-container">
        <h1>Shared File Access</h1>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="error-message">
                <p><?php echo $errorMessage; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($shareData) && empty($errorMessage)): ?>
            <div class="file-info">
                <h2><?php echo htmlspecialchars($shareData['original_name']); ?></h2>
                <p><strong>Shared by:</strong> <?php echo htmlspecialchars($shareData['first_name'] . ' ' . $shareData['last_name']); ?></p>
                <p><strong>File size:</strong> <?php echo round($shareData['file_size'] / 1024 / 1024, 2); ?> MB</p>
                <p><strong>Expires on:</strong> <?php echo date('Y-m-d H:i', strtotime($shareData['expiry_date'])); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($showPasswordForm): ?>
            <div class="password-form">
                <h3>This file is password protected</h3>
                <p>Enter the password provided by the file owner</p>
                
                <form action="/s.php?token=<?php echo $shareToken; ?>" method="post">
                    <div class="form-inputs">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                    </div>
                    <button class="btn black-btn" name="submit_password" type="submit">Submit Password</button>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($showDecryptionKeyForm): ?>
            <div class="decryption-form">
                <h3>Enter Decryption Key</h3>
                <p>Enter the decryption key provided by the file owner</p>
                
                <form action="/s.php?token=<?php echo $shareToken; ?>" method="post">
                    <div class="form-inputs">
                        <label for="decryption_key">Decryption Key:</label>
                        <input type="text" id="decryption_key" name="decryption_key" placeholder="Enter decryption key" required>
                    </div>
                    <button class="btn black-btn" name="submit_download" type="submit">Download File</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
</body>
</html>