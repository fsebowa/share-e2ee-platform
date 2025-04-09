<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';

// Ensure user is logged in and verified
if (!check_login_otp_status()) {
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrf_token = $_POST["csrf_token"] ?? '';
    $csrf_token_time = $_SESSION["csrf_token_time"] ?? 0;
    $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';
    $recaptcha_response = $_POST["g-recaptcha-response"] ?? '';
    
    // Get share inputs
    $fileId = $_POST["file_id"] ?? '';
    $recipient = $_POST["recipient"] ?? null;
    $expiryDays = $_POST["expiry_days"] ?? '7';
    $maxAccess = isset($_POST["max_access"]) && !empty($_POST["max_access"]) ? $_POST["max_access"] : null;
    $keyDelivery = $_POST["key_delivery"] ?? 'manual';
    $password = isset($_POST["password"]) && !empty($_POST["password"]) ? $_POST["password"] : null;
    $decryptionKey = $_POST["decryption_key"] ?? '';
    
    try {
        require_once __DIR__ . '/../config/dbh.inc.php';
        require_once __DIR__ . '/share_model.inc.php';
        require_once __DIR__ . '/share_contr.inc.php';
        require_once __DIR__ . '/../file_management/file_model.inc.php';
        require_once __DIR__ . '/../file_management/file_contr.inc.php';
        
        // Get user ID from session
        $userId = $_SESSION["user_id"];
        
        // Error handling
        $errors = [];
        
        // Check CSRF token
        if (csrf_token_expired($csrf_token_time)) {
            $errors["csrf_token_expired"] = "Session token expired. Try again!";
        } elseif ($csrf_token != $_SESSION['csrf_token']) {
            $errors["csrf_token_invalid"] = "Invalid CSRF token";
        } elseif (is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["recaptcha_invalid"] = "The reCAPTCHA verification failed. Please try again!";
        } elseif (empty($fileId) || !is_numeric($fileId)) {
            $errors["invalid_file"] = "Invalid file selected";
        } elseif (empty($decryptionKey)) {
            $errors["missing_key"] = "Decryption key is required for sharing";
        } else {
            // Check if file exists and belongs to the user
            $file = get_file_by_id($pdo, (int)$fileId, $userId);
            if (!$file) {
                $errors["file_not_found"] = "The selected file was not found or does not belong to you";
            } else {
                // Validate remaining inputs
                $validationErrors = validate_share_inputs($recipient, $expiryDays, $maxAccess, $keyDelivery, $password);
                if (!empty($validationErrors)) {
                    $errors = array_merge($errors, $validationErrors);
                }
            }
        }
        
        if (empty($errors)) {
            // Generate share token
            $shareToken = generate_share_token();
            
            // Calculate expiry date
            $expiryDate = calculate_expiry_date((int)$expiryDays);
            
            // Hash password if provided
            $hashedPassword = $password ? hash_share_password($password) : null;
            
            // Convert max access to integer if provided
            $maxAccessCount = $maxAccess !== null ? (int)$maxAccess : null;
            
            // Create the share in the database
            $shareId = create_file_share(
                $pdo,
                (int)$fileId,
                $userId,
                $recipient,
                $shareToken,
                $hashedPassword,
                $expiryDate,
                $maxAccessCount,
                $keyDelivery
            );
            
            if (!$shareId) {
                $errors["share_creation_failed"] = "Failed to create share. Please try again.";
            } else {
                // Generate the share URL
                $shareUrl = generate_share_url($shareToken);
                
                // Send email to recipient if key delivery is by email
                if ($keyDelivery === 'email' && $recipient) {
                    if (file_exists(__DIR__ . '/../templates/file-share-template.html')) {
                        $template = file_get_contents(__DIR__ . '/../templates/file-share-template.html');
                    } else {
                        $errors["email_template_not_found"] = "Email template not found.";
                    }
                    
                    if (empty($errors)) {
                        // Prepare the email content
                        $template = str_replace('{{file_name}}', htmlspecialchars($file['original_name']), $template);
                        $template = str_replace('{{share_url}}', $shareUrl, $template);
                        $template = str_replace('{{expiry_date}}', date('Y-m-d H:i', strtotime($expiryDate)), $template);
                        $template = str_replace('{{decryption_key}}', htmlspecialchars($decryptionKey), $template);
                        $template = str_replace('{{sender_name}}', $_SESSION['first_name'] . ' ' . $_SESSION['last_name'], $template);
                        $template = str_replace('{{year}}', date('Y'), $template);
                        
                        // Additional info based on share settings
                        $accessInfo = '';
                        if ($maxAccessCount) {
                            $accessInfo .= "This link can be accessed {$maxAccessCount} times. ";
                        }
                        if ($password) {
                            $accessInfo .= "This link is password-protected.";
                        }
                        $template = str_replace('{{access_info}}', $accessInfo, $template);
                        
                        // Send email
                        $mail = new PHPMailer(true);
                        try {
                            // Server settings
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->Port = 465;
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            $mail->SMTPAuth = true;
                            $mail->Username = 'share.e2eeplatform@gmail.com';
                            $mail->Password = 'vczc yawy voeo nkbk';
                            
                            // Recipients
                            $mail->setFrom('no-reply@sharee2ee.com', 'Share E2EE Platform');
                            $mail->addReplyTo('no-reply@sharee2ee.com', 'Share E2EE Platform');
                            $mail->addAddress($recipient);
                            $mail->addCC($_SESSION['email']); // copy sender in email
                            
                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = $_SESSION['first_name'] . ' shared a file with you';
                            $mail->Body = $template;
                            $mail->AltBody = "A file has been shared with you by " . $_SESSION['first_name'] . 
                                            ". Access it at: " . $shareUrl . ". Decryption key: " . $decryptionKey;
                            
                            if (!$mail->send()) {
                                $errors["email_send_failed"] = "Failed to send email: " . $mail->ErrorInfo;
                            }
                        } catch (Exception $e) {
                            $errors["email_exception"] = "Error sending email: " . $e->getMessage();
                        }
                    }
                }
                
                if (empty($errors)) {
                    // Success! Set session variables for the next page
                    $_SESSION['share_success'] = true;
                    $_SESSION['share_url'] = $shareUrl;
                    $_SESSION['share_decryption_key'] = $decryptionKey;
                    $_SESSION['share_expiry'] = date('Y-m-d H:i', strtotime($expiryDate));
                    $_SESSION['share_delivery'] = $keyDelivery;
                    $_SESSION['share_recipient'] = $recipient;
                    $_SESSION['share_file_name'] = $file['file_name']; // Add filename for display
                    
                    // Redirect back to dashboard with success message and file ID to reopen popup
                    header("Location: /dashboard.php?file_id=" . $fileId . "&message=file_shared_successfully");
                    exit();
                }
            }
        }
        
        // If we reached here, there were errors
        $_SESSION['share_errors'] = $errors;
        $_SESSION['share_data'] = [
            'file_id' => $fileId,
            'recipient' => $recipient,
            'expiry_days' => $expiryDays,
            'max_access' => $maxAccess,
            'key_delivery' => $keyDelivery,
            'decryption_key' => $decryptionKey
        ];
        
        // Redirect back to dashboard with the file_id to reopen the share popup
        header("Location: /dashboard.php?file_id=" . $fileId . "&error=share_failed");
        exit();
        
    } catch (PDOException $e) {
        // Log error and show generic message to user
        error_log("Share submission failed: " . $e->getMessage());
        $_SESSION['share_errors'] = ["An unexpected error occurred. Please try again later."];
        header("Location: /dashboard.php?error=database_error");
        exit();
    }
} else {
    // Not a POST request, redirect to dashboard
    header("Location: /dashboard.php");
    exit();
}