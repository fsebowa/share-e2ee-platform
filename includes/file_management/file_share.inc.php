<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';
require_once __DIR__ . '/../encryption/encryption_service.inc.php';
require_once __DIR__ . '/../encryption/file_encryption.inc.php';

// Ensure user is logged in and verified
if (!check_login_otp_status()) {
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Initialize encryption service for form data
    $encryptionService = new EncryptionService();
    $encryptedData = $_POST["encrypted_data"] ?? null;
    
    // Get other POST data
    $csrf_token = $_POST["csrf_token"] ?? null;
    $csrf_token_time = $_SESSION["csrf_token_time"] ?? null;
    $recaptcha_response = $_POST["g-recaptcha-response"] ?? null;
    $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';
    
    try {
        require_once __DIR__ . '/../config/dbh.inc.php';
        require_once __DIR__ . '/file_model.inc.php';
        require_once __DIR__ . '/file_contr.inc.php';
        require_once __DIR__ . '/../share/share_model.inc.php';
        require_once __DIR__ . '/../share/share_contr.inc.php';
        
        // Get user ID from session
        $user_id = $_SESSION["user_id"];
        
        // Error handling
        $errors = [];
        $success = [];
        
        // Check CSRF token
        if (csrf_token_expired($csrf_token_time)) {
            $errors["csrf_token_expired"] = "Session token expired. Try again!";
        } elseif ($csrf_token != $_SESSION['csrf_token']) {
            $errors["csrf_token_invalid"] = "Invalid CSRF token";
        } elseif (is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["recaptcha_invalid"] = "The reCAPTCHA verification failed. Please try again!";
        } elseif (!$encryptedData) {
            $errors["encryption_error"] = "Failed to process encrypted data. Please try again!";
        } else {
            // Decrypt the form data
            $decryptedData = $encryptionService->decryptFormData($encryptedData);
            
            if (!$decryptedData) {
                $errors["decryption_error"] = "Error decrypting form data. Please try again!";
            } else {
                // Extract data from decrypted payload
                $file_id = $decryptedData["file_id"] ?? '';
                $recipient = $decryptedData["recipient"] ?? null;
                $expiryDays = $decryptedData["expiry_days"] ?? '7';
                $maxAccess = isset($decryptedData["max_access"]) && !empty($decryptedData["max_access"]) ? $decryptedData["max_access"] : null;
                $keyDelivery = $decryptedData["key_delivery"] ?? 'manual';
                $decryption_key = $decryptedData["decryption_key"] ?? '';
                $sharePassword = $decryptedData["share_password"] ?? null; 
                
                // Validate inputs
                if (empty($file_id) || !is_numeric($file_id)) {
                    $errors["invalid_file"] = "Invalid file selected";
                } elseif (empty($decryption_key)) {
                    $errors["missing_key"] = "Decryption key is required for sharing";
                } else {
                    // Check if file exists and belongs to the user
                    $file = get_file_by_id($pdo, (int)$file_id, $user_id);
                    if (!$file) {
                        $errors["file_not_found"] = "The selected file was not found or does not belong to you";
                    } else {
                        // Initialize encryption service to verify decryption key
                        $encryption_service = new FileEncryptionService();
                        // Convert hex to binary
                        $binary_key = hex2bin($decryption_key);
                        if (!$binary_key) {
                            $errors["key_conversion"] = "Invalid decryption key format";
                        } else {
                            // Create a temporary file to verify the key
                            $temp_dir = sys_get_temp_dir() . '/share_temp_files';
                            if (!is_dir($temp_dir)) {
                                mkdir($temp_dir, 0755, true);
                            }
                            $temp_file = $temp_dir . '/verify_' . $_SESSION["user_id"] . '_' . $file_id . '_' . uniqid();
                            // Attempt to decrypt the file to verify the key
                            $decryption_result = $encryption_service->decrypt_file(
                                $file["file_path"],
                                $binary_key,
                                $temp_file
                            );
                            // Clean up temp file regardless of result
                            if (file_exists($temp_file)) {
                                @unlink($temp_file);
                            }
                            if (!isset($decryption_result['success']) || $decryption_result['success'] !== true) {
                                $error_message = $decryption_result['message'] ?? "Invalid decryption key for this file.";
                                $errors["invalid_key"] = $error_message;
                            } else {
                                // Validate remaining inputs
                                $validationErrors = validate_share_inputs($recipient, $expiryDays, $maxAccess, $keyDelivery, $sharePassword);
                                if (!empty($validationErrors)) {
                                    $errors = array_merge($errors, $validationErrors);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if (empty($errors)) {
            // Generate share token
            $shareToken = generate_share_token();
            // Calculate expiry date
            $expiryDate = calculate_expiry_date((int)$expiryDays);
            // hash password
            $hashedPassword = null;
            if ($sharePassword !== null && !empty($sharePassword)) {
                $hashedPassword = hash_share_password($sharePassword);
            }
            // Create the share in the database
            $shareId = create_file_share(
                $pdo,
                (int)$file_id,
                $user_id,
                $recipient,
                $shareToken,
                $hashedPassword,
                $expiryDate,
                $maxAccess !== null ? (int)$maxAccess : null,
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
                        // Create a simple template if the file doesn't exist
                        $template = '<!DOCTYPE html>
                        <html>
                        <head>
                            <title>File Shared With You</title>
                        </head>
                        <body>
                            <h1>A file has been shared with you</h1>
                            <p><strong>File:</strong> {{file_name}}</p>
                            <p><strong>Shared by:</strong> {{sender_name}}</p>
                            <p><strong>Access link:</strong> <a href="{{share_url}}">{{share_url}}</a></p>
                            <p><strong>Decryption key:</strong> {{decryption_key}}</p>
                            <p><strong>Expires on:</strong> {{expiry_date}}</p>
                            <p>{{access_info}}</p>
                            <p>&copy; Share {{year}}</p>
                        </body>
                        </html>';
                    }
                    
                    // Prepare the email content
                    $template = str_replace('{{file_name}}', htmlspecialchars($file['original_filename']), $template);
                    $template = str_replace('{{share_url}}', $shareUrl, $template);
                    $template = str_replace('{{expiry_date}}', date('d/m/Y H:i', strtotime($expiryDate)), $template);
                    $template = str_replace('{{decryption_key}}', htmlspecialchars($decryption_key), $template);
                    $template = str_replace('{{sender_name}}', $_SESSION['first_name'] . ' ' . $_SESSION['last_name'], $template);
                    $template = str_replace('{{year}}', date('Y'), $template);
                    
                    // Additional info based on share settings
                    $accessInfo = '';
                    if ($maxAccess) {
                        $accessInfo .= "This link can be accessed {$maxAccess} times. ";
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
                        $mail->addCC($_SESSION['email']); // CC the sender
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = $_SESSION['first_name'] . ' shared a file with you';
                        $mail->Body = $template;
                        $mail->AltBody = "A file has been shared with you by " . $_SESSION['first_name'] . 
                                        ". Access it at: " . $shareUrl . ". Decryption key: " . $decryption_key;
                        $mail->send();
                        $success['share_link_sent'] = "File shared successfully and details sent to ". $recipient;
                    } catch (Exception $e) {
                        $errors["email_send_failed"] = "Warning: Share created but email could not be sent: " . $mail->ErrorInfo;
                    }
                } else {
                    $success['share_details_success'] = "File shared successfully! The sharing details are ready above.";
                }
                
                if (empty($errors)) {
                    // Success! Set session variables for the next page
                    $_SESSION['share_success'] = true;
                    $_SESSION['share_url'] = $shareUrl;
                    $_SESSION['share_decryption_key'] = $decryption_key;
                    $_SESSION['share_expiry'] = date('d/m/Y H:i', strtotime($expiryDate));
                    $_SESSION['share_delivery'] = $keyDelivery;
                    $_SESSION['share_recipient'] = $recipient;
                    $_SESSION['share_file_name'] = $file['file_name'];
                    // Redirect back to dashboard with success message and file ID to reopen popup
                    $_SESSION['share_success_message'] = $success;
                    header("Location: /dashboard.php?file_id=" . $file_id . "&message=file_shared_successfully");
                    exit();
                }
            }
        }
        
        // If there were errors
        $_SESSION['share_errors'] = $errors;
        $_SESSION['share_data'] = [
            'file_id' => $file_id ?? '',
            'recipient' => $recipient ?? '',
            'expiry_days' => $expiryDays ?? '7',
            'max_access' => $maxAccess ?? '',
            'key_delivery' => $keyDelivery ?? 'manual'
        ];
        // Redirect back with file_id to reopen the popup
        header("Location: /dashboard.php?file_id=" . ($file_id ?? '') . "&error=share_failed");
        exit();
    } catch (Exception $e) {
        // Log the error
        error_log("Share creation failed: " . $e->getMessage());
        
        // Set generic error message
        $_SESSION['share_errors'] = ["An unexpected error occurred. Please try again later."];
        
        // Redirect back to dashboard
        header("Location: /dashboard.php?error=system_error");
        exit();;
    }
} else {
    // Not a POST request
    header("Location: /dashboard.php");
    exit();
}