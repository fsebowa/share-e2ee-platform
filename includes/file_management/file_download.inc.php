<?php
require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';
require_once __DIR__ . '/../encryption/file_encryption.inc.php';
require_once __DIR__ . '/../encryption/encryption_service.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require __DIR__ . '/../../vendor/autoload.php';

// Verify user is logged in and OTP verified
check_login_otp_status();

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

        $errors = [];
        $success = [];

        // Validate inputs and security checks
        if (csrf_token_expired($csrf_token_time)) {
            $errors["csrf_token_expired"] = "Session token expired. Try again!";
        } elseif (csrf_token_invalid($csrf_token)) {
            $errors["csrf_token_invalid"] = "Invalid CSRF token";
        } elseif(is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["invalid_recaptcha"] = "The reCAPTCHA verification failed. Please try again!";
        } elseif (!$encryptedData) {
            $errors["encryption_error"] = "Failed to process encrypted data. Please try again!";
        } else {
            // Decrypt the form data
            error_log("Attempting to decrypt form data");
            $decryptedData = $encryptionService->decryptFormData($encryptedData);
            
            if (!$decryptedData) {
                $errors["decryption_error"] = "Error decrypting form data. Please try again!";
                error_log("Form data decryption failed");
            } else {
                error_log("Form data decrypted successfully: " . print_r($decryptedData, true));
                
                // Extract data from decrypted payload
                $file_id = $decryptedData["file_id"] ?? null;
                $file_name = $decryptedData["file_name"] ?? null;
                $decryption_key = $decryptedData["key"] ?? null;
                $download_action = isset($decryptedData["download_action"]) ? $decryptedData["download_action"] : "decrypted";
                
                error_log("Download action set to: " . $download_action);
                
                // Validate required fields
                if (empty($file_id)) {
                    $errors["file_id_missing"] = "File ID is required";
                }
                
                // For both types of downloads, we require a valid key for verification
                if (empty($decryption_key)) {
                    $errors["key_missing"] = "Decryption key is required for both download types";
                } elseif (strlen($decryption_key) != 64 || !ctype_xdigit($decryption_key)) {
                    $errors["key_format"] = "Key must be exactly 64 hex characters (256-bit key)";
                }
            }
        }
        
        // If no errors, proceed with file download
        if (empty($errors)) {
            error_log("Validation passed, proceeding with file download. Action: " . $download_action);
            
            // Get file information from database
            $file_info = get_file_by_id($pdo, $file_id, $_SESSION["user_id"]);
            
            if (!$file_info) {
                $errors["file_not_found"] = "File not found.";
                error_log("File ID: " . $file_id . " for user: " . $_SESSION["user_id"] . " not found");
            } else {
                error_log("File found: " . print_r($file_info, true));
                
                // Convert hex key to binary for decryption
                $binary_key = @hex2bin($decryption_key);
                if (!$binary_key) {
                    $errors["key_conversion"] = "Failed to convert hex key to binary format";
                    error_log("Failed to convert hex key to binary: " . $decryption_key);
                } else {
                    error_log("Binary key length: " . strlen($binary_key) . " bytes");
                    
                    // Initialize encryption service
                    $encryption_service = new FileEncryptionService();
                    
                    // Prepare temp directory
                    $temp_dir = sys_get_temp_dir() . '/share_temp_files';
                    if (!is_dir($temp_dir)) {
                        if (!mkdir($temp_dir, 0755, true)) {
                            $errors["temp_dir"] = "Failed to create temp directory";
                            error_log("Failed to create temp directory: " . $temp_dir);
                        }
                    }
                    
                    if (empty($errors)) {
                        // Determine download type and handle appropriately
                        if ($download_action === "encrypted") {
                            error_log("Processing encrypted download");
                            
                            // For encrypted download, still verify the key first
                            $temp_file = $temp_dir . '/verify_' . $_SESSION["user_id"] . '_' . $file_id . '_' . uniqid();
                            
                            try {
                                // Try partial decryption just to verify the key
                                error_log("Verifying key by partial decryption");
                                $verify_result = $encryption_service->decrypt_file(
                                    $file_info["file_path"],
                                    $binary_key,
                                    $temp_file
                                );
                                
                                // Clean up temp file
                                if (file_exists($temp_file)) {
                                    @unlink($temp_file);
                                }
                                
                                if (!isset($verify_result['success']) || $verify_result['success'] !== true) {
                                    $error_message = $verify_result['message'] ?? "Invalid decryption key for this file.";
                                    $errors["invalid_key"] = $error_message;
                                    error_log("Key verification failed: " . $error_message);
                                } else {
                                    // Key is valid, download the encrypted file
                                    error_log("Key verified, proceeding with encrypted download");
                                    logFileDownload($pdo, $file_id, $_SESSION["user_id"], 'encrypted');
                                    
                                    // Record success 
                                    $success["download_encrypted"] = "File verified and downloading encrypted.";
            
                                    // Send email confirmation
                                    sendDownloadConfirmationEmail(
                                        $_SESSION["email"] ?? get_user_email_by_id($pdo, $_SESSION["user_id"]),
                                        $_SESSION["first_name"] . " " . $_SESSION["last_name"],
                                        $file_info,
                                        'encrypted'
                                    );
                                    
                                    error_log("Sending encrypted file: " . $file_info["file_path"]);
                                    
                                    // Check if file exists and is readable
                                    if (!file_exists($file_info["file_path"]) || !is_readable($file_info["file_path"])) {
                                        $errors["file_not_readable"] = "The encrypted file could not be read.";
                                    } else {
                                        // Add a .share extension to help prevent malware detection
                                        $filename_parts = pathinfo($file_info["original_filename"]);
                                        $encrypted_filename = $filename_parts['filename'] . '.share.enc';
                                        
                                        // Set proper headers for file download
                                        header('Content-Description: File Transfer');
                                        header('Content-Type: application/octet-stream');
                                        header('Content-Disposition: attachment; filename="encrypted_' . $encrypted_filename . '"');
                                        header('Expires: 0');
                                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                                        header('Pragma: public');
                                        
                                        // Create a new temp file with metadata header
                                        $temp_download_file = $temp_dir . '/' . uniqid() . '_' . $encrypted_filename;
                                        
                                        // Add a metadata header to indicate this is a Share encrypted file
                                        $metadata = "SHARE-ENCRYPTED-FILE-V1\n" .
                                                    "Original-Filename: " . $file_info["original_filename"] . "\n" .
                                                    "Date-Encrypted: " . $file_info["date_uploaded"] . "\n" .
                                                    "Content-Type: application/octet-stream\n" .
                                                    "---BEGIN-ENCRYPTED-DATA---\n";
                                        
                                        // Write metadata + original content to temp file
                                        file_put_contents($temp_download_file, $metadata);
                                        file_put_contents($temp_download_file, file_get_contents($file_info["file_path"]), FILE_APPEND);
                                        // Update the Content-Length header to match the new file size
                                        header('Content-Length: ' . filesize($temp_download_file));
                                        // Clear any output buffers to prevent corruption
                                        if (ob_get_level()) {
                                            ob_end_clean();
                                        }
                                        // Send the file
                                        readfile($temp_download_file);
                                        // Clean up
                                        if (file_exists($temp_download_file)) {
                                            @unlink($temp_download_file);
                                        }
                                        exit;
                                    }
                                }
                            } catch (Exception $e) {
                                $errors["decrypt_verify_error"] = "Error verifying key: " . $e->getMessage();
                                error_log("Exception during key verification: " . $e->getMessage());
                                error_log("Exception trace: " . $e->getTraceAsString());
                            }
                        } else {
                            error_log("Processing decrypted download");
                            // This is a full decrypt and download request
                            $decrypted_file = $temp_dir . '/' . $file_info["original_filename"];
                            
                            error_log("Attempting to decrypt file to: " . $decrypted_file);
                            
                            // Decrypt the file
                            try {
                                $decryption_result = $encryption_service->decrypt_file(
                                    $file_info["file_path"],
                                    $binary_key,
                                    $decrypted_file
                                );
                                
                                error_log("Decryption result: " . print_r($decryption_result, true));
                                
                                if (!isset($decryption_result['success']) || $decryption_result['success'] !== true) {
                                    // If decryption failed, clean up and return error
                                    if (file_exists($decrypted_file)) {
                                        @unlink($decrypted_file);
                                    }
                                    $error_message = $decryption_result['message'] ?? "Invalid decryption key for this file.";
                                    $errors["decryption_failed"] = $error_message;
                                    error_log("Decryption failed: " . $error_message);
                                } else {
                                    // Log the download
                                    logFileDownload($pdo, $file_id, $_SESSION["user_id"], 'decrypted');
                                    
                                    // Record success
                                    $success["download_decrypted"] = "File decrypted successfully.";
                                    
                                    // Send email confirmation
                                    sendDownloadConfirmationEmail(
                                        $_SESSION["email"] ?? get_user_email_by_id($pdo, $_SESSION["user_id"]),
                                        $_SESSION["first_name"] . " " . $_SESSION["last_name"],
                                        $file_info,
                                        'decrypted'
                                    );
                                    
                                    error_log("File decrypted successfully, sending file: " . $decrypted_file);
                                    
                                    // Verify the file exists and is readable
                                    if (!file_exists($decrypted_file) || !is_readable($decrypted_file)) {
                                        error_log("Decrypted file not found or not readable: " . $decrypted_file);
                                        $errors["decrypted_file_missing"] = "The decrypted file could not be read.";
                                    } else {
                                        // Send the decrypted file
                                        header('Content-Description: File Transfer');
                                        header('Content-Type: application/octet-stream');
                                        $filename_parts = pathinfo($file_info["original_filename"]);
                                        $decrypted_filename = $filename_parts['filename'];
                                        if (isset($filename_parts['extension'])) {
                                            $decrypted_filename .= '.' . $filename_parts['extension'];
                                        }
                                        header('Content-Disposition: attachment; filename="decrypted_' . $decrypted_filename . '"');
                                        header('Expires: 0');
                                        header('Cache-Control: must-revalidate');
                                        header('Pragma: public');
                                        header('Content-Length: ' . filesize($decrypted_file));
                                        readfile($decrypted_file);
                                        
                                        // Clean up the decrypted file
                                        @unlink($decrypted_file);
                                        exit;
                                    }
                                }
                            } catch (Exception $e) {
                                $errors["decryption_exception"] = "Error during decryption: " . $e->getMessage();
                                error_log("Exception during decryption: " . $e->getMessage());
                                error_log("Exception trace: " . $e->getTraceAsString());
                                
                                // Clean up any partial files
                                if (file_exists($decrypted_file)) {
                                    @unlink($decrypted_file);
                                }
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        $errors["system_error"] = "An error occurred: " . $e->getMessage();
        error_log("Exception in file_download.inc.php: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
    }

    // Handle errors and success messages
    if (!empty($errors)) {
        error_log("Download errors: " . print_r($errors, true));
        $_SESSION["errors_file_download"] = $errors;
        header("Location: /dashboard.php");
        exit();
    }
    if (!empty($success)) {
        error_log("Download success: " . print_r($success, true));
        $_SESSION["success_file_download"] = $success;
        header("Location: /dashboard.php");
        exit();
    }
} else {
    header("Location: /dashboard.php");
    exit();
}

function sendDownloadConfirmationEmail($email, $name, $file_info, $download_type) {
    // Check if we should prevent duplicate emails
    if (isset($_SESSION['last_download_email']) && 
        $_SESSION['last_download_email']['file_id'] === $file_info['id'] && 
        $_SESSION['last_download_email']['timestamp'] > time() - 60) {
        error_log("Preventing duplicate download email for file ID {$file_info['id']}");
        return true; // Pretend we sent it successfully
    }
    
    // Skip if email is missing
    if (empty($email)) {
        error_log("Cannot send download confirmation, email is missing");
        return false;
    }
    
    try {
        // Load email template
        $template_path = __DIR__ . '/../templates/file-download-template.html';
        if (!file_exists($template_path)) {
            error_log("Download email template not found: " . $template_path);
            return false;
        }
        
        $template = file_get_contents($template_path);
        
        // Replace template variables
        $download_date = date("Y-m-d H:i:s");
        $template = str_replace('{{file_name}}', $file_info["file_name"], $template);
        $template = str_replace('{{original_filename}}', $file_info["original_filename"], $template);
        $template = str_replace('{{file_size}}', number_format($file_info["file_size"] / 1024 / 1024, 2), $template);
        $template = str_replace('{{upload_date}}', $file_info["date_uploaded"], $template);
        $template = str_replace('{{download_date}}', $download_date, $template);
        $template = str_replace('{{year}}', date('Y'), $template);
        $template = str_replace('{{download_type}}', ucfirst($download_type), $template);

        // Send email
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 465;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->SMTPAuth = true;
        $mail->Username = 'share.e2eeplatform@gmail.com';
        $mail->Password = 'vczc yawy voeo nkbk';
        
        // Recipients
        $mail->setFrom('downloads@share.com', 'Share E2EE Platform');
        $mail->addReplyTo('no-reply@share.com', 'Share E2EE Platform');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = ucfirst($download_type) . ' File Download: ' . $file_info["file_name"];
        $mail->Body = $template;
        $mail->AltBody = 'Your file was downloaded (' . $download_type . '): ' . $file_info["file_name"];
        
        $result = $mail->send();
        
        // Record that we sent an email to prevent duplicates
        $_SESSION['last_download_email'] = [
            'file_id' => $file_info['id'],
            'timestamp' => time()
        ];
        
        return $result;
    } catch (Exception $e) {
        error_log("Error sending download confirmation email: " . $e->getMessage());
        return false;
    }
}