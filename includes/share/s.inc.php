<?php
require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../encryption/encryption_service.inc.php';

// Initialize variables
$errorMessage = [];
$successMessage = [];
$showPasswordForm = false;
$showDecryptionKeyForm = false;
$passwordVerified = false;
$shareData = null;

// Get share token from URL
$shareToken = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '';
// Check if it's a form submission for password verification
$isPasswordSubmission = isset($_POST['submit_password']) || 
(isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response']));

if (empty($shareToken)) {
    // No token provided, show generic error
    $errorMessage[] = "Invalid or missing share token.";
} else {
    try {
        require_once __DIR__ . '/../config/dbh.inc.php';
        require_once __DIR__ . '/share_model.inc.php';
        require_once __DIR__ . '/share_contr.inc.php';
        require_once __DIR__ . '/../file_management/file_contr.inc.php';

        // Get share information
        $shareData = get_file_share_by_token($pdo, $shareToken);
        
        if (!$shareData) {
            // Share not found
            $errorMessage[] = "This link does not exist or has been removed.";
        } else if (!is_share_valid($shareData)) {
            // Share expired or access limit reached
            $errorMessage[] = "This share link has expired or reached its access limit.";
        } else {
            // Share exists and is valid
            $_SESSION['active_share_token'] = $shareToken;
            // A password is required ONLY if the field exists AND contains a non-empty string value
            $showPasswordForm = isset($shareData['access_password']) && 
                            is_string($shareData['access_password']) && 
                            trim($shareData['access_password']) !== '';
            // If password already verified or not required, show decryption key form
            $passwordVerified = isset($_SESSION['password_verified_' . $shareData['id']]) && 
                            $_SESSION['password_verified_' . $shareData['id']] === true;
            
            // Only show decryption form if no password is required OR password is verified
            $showDecryptionKeyForm = !$showPasswordForm || $passwordVerified;
            
            // If password form was submitted, verify password
            if ($showPasswordForm && $isPasswordSubmission) {
                $encryptionService = new EncryptionService();
                $encryptedData = $_POST["encrypted_data"] ?? null;
                $csrf_token = $_POST["csrf_token"];
                $csrf_token_time = $_SESSION["csrf_token_time"];
                $recaptcha_response = $_POST["g-recaptcha-response"] ?? null; 
                $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';

                 // Check CSRF token and recaptcha
                if (csrf_token_expired($csrf_token_time)) {
                    $errorMessage[] = "Session token expired. Try again!";
                } elseif (csrf_token_invalid($csrf_token)) {
                    $errorMessage[] = "Invalid CSRF token";
                } elseif (is_recaptcha_invalid($secretKey, $recaptcha_response)) {
                    $errorMessage[] = "The reCAPTCHA verification failed. Please try again!";
                } elseif (!$encryptedData) {
                    $errorMessage[] = "Failed to process encrypted data. Please try again!";
                } else {
                    // decrypt the data
                    $decryptedData = $encryptionService->decryptFormData($encryptedData);
                    if (!$decryptedData) {
                        $errorMessage["decryption_error"] = "Error decrypting form data. Please try again!";
                    } else {
                        $enteredPassword = $decryptedData["password"] ?? '';
                        if (verify_share_password($enteredPassword, $shareData['access_password'])) {
                            $_SESSION['password_verified_'.$shareData['id']] = true;
                            $_SESSION['active_share_token'] = $shareToken; // Store token in session
                            header("Location: /s.php?token=".$shareToken);
                            exit();
                        } else {
                            $errorMessage[] = "Incorrect password. Please try again.";
                        }
                    }
                }
            }
        }
    } catch (PDOException $e) {
        $errorMessage[] = "An error occurred while accessing this shared file.";
        $showPasswordForm = false;
        $showDecryptionKeyForm = false;
    }
}

// Only redirect for password submissions with errors
if ($isPasswordSubmission && !empty($errorMessage)) {
    $_SESSION["share_error_messages"] = $errorMessage;
    header("Location: /s.php?token=".$shareToken);
    exit();
}