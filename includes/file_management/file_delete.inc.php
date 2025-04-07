<?php

require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';
require_once __DIR__ . '/../encryption/file_encryption.inc.php';
require_once __DIR__ . '/../encryption/encryption_service.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require __DIR__ . '/../../vendor/autoload.php';

check_login_otp_status();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Initialize encryption service
    $encryptionService = new EncryptionService();
    $encryptedData = $_POST["encrypted_data"] ?? null;
    // get data remaining data from POST
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

        // validate inputs and error handling
        if (csrf_token_expired($csrf_token_time)) {
            $errors["csrf_token_expired"] = "Session token expired. Try again!";
        } elseif (csrf_token_invalid($csrf_token)) {
            $errors["csrf_token_invalid"] = "Invalid CSRF token";
        } elseif(is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["invalid_recaptcha"] = "The reCAPTCHA verification failed. Please try again!";
        } elseif (!$encryptedData) {
            $errors["encryption_error"] = "Failed to process encrypted data. Please try again!";
        } else {
            // decrypt the data
            $decryptedData = $encryptionService->decryptFormData($encryptedData);
            if (!$decryptedData) {
                $errors["decryption_error"] = "Error decrypting form data. Please try again!";
            } else {
                $file_id = $decryptedData["file_id"] ?? null;
                $file_name = $decryptedData["file_name"] ?? null;
                $decryption_key = $decryptedData["decryption_key"] ?? null;
                
                if (empty($decryption_key) || empty($file_id) || empty($file_name)) {
                    $errors["empty_inputs"] = "One or more fields are empty";
                } elseif (!empty($decryption_key) && (strlen($decryption_key) != 64 || !ctype_xdigit($decryption_key))) {
                    $errors["wrong_key_format"] = "Key must be exactly 64 hex characters (256-bit key)";
                }
            }
        }
        // if no errors, proceed with file deletion 
        if (empty($errors)) {
            $file_info = get_file_by_id($pdo, $file_id, $_SESSION["user_id"]);
            if (!$file_info) {
                $errors["file_not_found"] = "File not found.";
            } else {
                // Initialize encryption service to verify decryption key
                $encryption_service = new FileEncryptionService();

                // Convert hex to binary
                $binary_key = hex2bin($decryption_key);
                
                // Create a temporary file to verify the key
                $temp_dir = sys_get_temp_dir() . '/share_temp_files';
                if (!is_dir($temp_dir)) {
                    mkdir($temp_dir, 0755, true);
                }
                $temp_file = $temp_dir . '/verify_' . $_SESSION["user_id"] . '_' . $file_id . '_' . uniqid();

                // Attempt to decrypt the file to verify the key
                $decryption_result = $encryption_service->decrypt_file(
                    $file_info["file_path"],
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
                } else { // Key is valid, proceed with deletion
                    
                    // Get original filename, upload date and file-size before deletion
                    $original_filename = $file_info["original_filename"] ?? "Unknown";
                    $file_size = number_format($file_info["file_size"] / 1024 /1024, 2) ?? "Unknown";
                    $upload_date = $file_info["date_uploaded"] ?? "Unknown";

                    // perform file deletion
                    $deleteFile = delete_file($pdo, $file_id, $_SESSION["user_id"]);
                    $delete_date = date("Y-m-d H:i:s");

                    if (!$deleteFile) {
                        $errors["delete_failed"] = "An error occurred while deleting the file. Please try again!";
                    } else {                        
                        // load email template
                        $file_email_delete_template = __DIR__ . '/../templates/file-delete-template.html';
                        
                        if (is_file_email_template_missing($file_email_delete_template)) {
                            $errors["file_template_not_found"] = "Email template not found.";
                        } else {
                            $file_template = file_get_contents($file_email_delete_template);
                            
                            // create email template for delete confirmation
                            $file_template = str_replace('{{file_name}}', $file_name, $file_template);
                            $file_template = str_replace('{{original_filename}}', $original_filename, $file_template);
                            $file_template = str_replace('{{file_size}}', $file_size, $file_template);
                            $file_template = str_replace('{{upload_date}}', $upload_date, $file_template);
                            $file_template = str_replace('{{delete_date}}', $delete_date, $file_template);
                            $file_template = str_replace('{{year}}', date('Y'), $file_template);
                            
                            // Send deletion confirmation email
                            $mail = new PHPMailer(true);
                            try {
                                // server settings
                                $mail->isSMTP();
                                $mail->Host = 'smtp.gmail.com';
                                $mail->Port = 465;
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                                $mail->SMTPAuth = true;
                                $mail->Username = 'share.e2eeplatform@gmail.com';
                                $mail->Password = 'vczc yawy voeo nkbk';
                                // Recipients
                                $mail->setFrom('otp-verification@share.com', 'Share E2EE Platform');
                                $mail->addReplyTo('no-reply@share.com', 'Share E2EE Platform');
                                $mail->addAddress($_SESSION["email"] ?? get_user_email_by_id($pdo, $_SESSION["user_id"]), $_SESSION["user_data"]["first_name"] . " " . $_SESSION["user_data"]["last_name"]);
                                // Content
                                $mail->isHTML(true);
                                $mail->Subject = 'File Deletion Confirmation: ' . $file_name;
                                $mail->Body = $file_template;
                                $mail->AltBody = 'Your file was deleted successfully: ' . $file_name;
                                
                                if (!$mail->send()) {
                                    $errors["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
                                } else {
                                    $success["deleted_successfully"] = "File Deleted Successfully";
                                    $_SESSION["success_file_delete"] = $success;
                                    header("Location: /dashboard.php?message=file_delete_success");
                                    exit();
                                }
                            } catch (Exception $e) {
                                $errors["send_email_error"] = "Error sending delete confirmation email.<br>Try again!";
                                $errors["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        $errors["system_error"] = "An error occurred: " . $e->getMessage();
        error_log("Exception in file_delete.inc.php: " . $e->getMessage());
    }

    // redirect back to dashboard if error or success messages
    if (!empty($errors)) {
        $_SESSION["errors_file_delete"] = $errors;
        header("Location: /dashboard.php");
        exit();
    }
    if (!empty($success)) {
        $_SESSION["success_file_delete"] = $success;
        header("Location: /dashboard.php");
        exit();
    }
} else {
    header("Location: /dashboard.php");
    exit();
}