<?php
require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';
require_once __DIR__ . '/../encryption/file_encryption.inc.php';
require_once __DIR__ . '/file_contr.inc.php';
require_once __DIR__ . '/../config/dbh.inc.php';
require_once __DIR__ . '/file_model.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require __DIR__ . '/../../vendor/autoload.php';

check_login_otp_status();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // get data from POST
    $file_id = $_POST["file_id"] ?? null;
    $file_name = $_POST["file_name"] ?? null;
    $decryption_key = $_POST["key"] ?? null;
    $download_action = $_POST["download_action"] ?? "decrypted"; // Default to decrypted
    $csrf_token = $_POST["csrf_token"] ?? null;
    $csrf_token_time = $_SESSION["csrf_token_time"] ?? null;
    $recaptcha_response = $_POST["g-recaptcha-response"] ?? null;
    $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';
    
    try {
        $errors = [];
        $success = [];

        // validation for both download types
        if (csrf_token_expired($csrf_token_time)) {
            $errors["csrf_token_expired"] = "Session token expired. Try again!";
        } elseif (csrf_token_invalid($csrf_token)) {
            $errors["csrf_token_invalid"] = "Invalid CSRF token";
        } elseif(is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["invalid_recaptcha"] = "The reCAPTCHA verification failed. Please try again!";
        } elseif (empty($decryption_key) || empty($file_id) || empty($file_name)){
            $errors["empty_inputs"] = "One or more fields are empty";
        } 

        // Additional validation for decrypted download
        if ($download_action === "decrypted") {
            if (empty($decryption_key)) {
                $errors["empty_key"] = "Decryption key is required for decrypted download";
            } elseif (strlen($decryption_key) != 64 || !ctype_xdigit($decryption_key)) {
                $errors["wrong_key_length"] = "Key must be exactly 64 hex characters (256-bit key)";
            }
        }

        // If no errors, proceed with file download
        if (empty($errors)) {
            // Get file information from database
            $file = get_file_by_id($pdo, $file_id, $_SESSION["user_id"]);
            
            if (!$file) {
                $errors["file_not_found"] = "File not found or access denied";
            } else {
                // Handle download based on the action type
                if ($download_action === "encrypted") {
                    // Download the encrypted file directly
                    $download_success = downloadEncryptedFile($file);
                    if (!$download_success) {
                        $errors["download_failed"] = "Failed to download encrypted file";
                    } else {
                        // Log download and notify
                        logFileDownload($pdo, $file_id, $_SESSION["user_id"], "encrypted");
                        sendDownloadNotificationEmail($file["file_name"]);
                        exit(); // File has been served
                    }
                } else {
                    // Decrypt and download the file
                    $temp_dir = sys_get_temp_dir() . '/share_temp_files'; // Location for decrypted temporary files
                    if (!is_dir($temp_dir)) {
                        mkdir($temp_dir, 0755, true);
                    }
                    
                    // Create a unique filename for the decrypted file
                    $temp_file = $temp_dir . '/download_' . $_SESSION["user_id"] . '_' . $file_id . '_' . md5(basename($file["original_filename"]));
                    
                    // Initialize encryption service
                    $encryption_service = new FileEncryptionService();

                    // Convert hex key to binary
                    $binary_key = hex2bin($decryption_key);

                    // Check if decryption was successful (handle both return formats)
                    if (is_array($decryption_result)) {
                        if (!$decryption_result['success']) {
                            $error_message = $decryption_result['message'] ?? "Failed to decrypt file. Check your decryption key.";
                            $errors["decryption_failed"] = $error_message;
                        }
                    } else if (!$decryption_result) {
                        $errors["decryption_failed"] = "Failed to decrypt the file. Please check your key and try again.";
                    } else {
                        // Prepare headers for file download
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . $file["original_filename"] . '"');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($temp_file));
                        
                        // Clean output buffer
                        if (ob_get_level()) {
                            ob_end_clean();
                        }
                        flush();
                        
                        // Output file
                        readfile($temp_file);
                        
                        // Log download
                        logFileDownload($pdo, $file_id, $_SESSION["user_id"], "decrypted");
                        
                        // Send email notification
                        sendDownloadNotificationEmail($file["file_name"]);
                        
                        // Clean up temporary file
                        @unlink($temp_file);
                        
                        exit(); // File has been served
                    }
                }
            }
        }
    } catch (Exception $e) {
        $errors["system_error"] = "An error occurred: " . $e->getMessage();
        error_log("Exception in file_download.inc.php: " . $e->getMessage());
    }

    // Redirect back to dashboard if errors
    if (!empty($errors)) {
        $_SESSION["errors_file_download"] = $errors;
        header("Location: /dashboard.php");
        exit();
    }
} else {
    header("Location: /dashboard.php");
    exit();
}

// Function to download the encrypted file directly
function downloadEncryptedFile($file) {
    if (!file_exists($file["file_path"])) {
        return false;
    }
    
    // Prepare headers for file download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file["original_filename"] . '.enc"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file["file_path"]));
    
    // Clean output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    flush();
    
    // Output file
    readfile($file["file_path"]);
    
    return true;
}

// Function to send download notification email
function sendDownloadNotificationEmail($file_name) {
    try {
        // Get user's email address
        $user_email = $_SESSION["email"] ?? get_user_email_by_id($GLOBALS['pdo'], $_SESSION["user_id"]);
        
        if (!$user_email) {
            return false;
        }
        
        // Load email template
        $template_path = __DIR__ . '/../templates/file-download-template.html';
        if (!file_exists($template_path)) {
            error_log("Download email template not found: " . $template_path);
            return false;
        }
        
        $template = file_get_contents($template_path);
        $template = str_replace('{{file_name}}', $file_name, $template);
        $template = str_replace('{{download_date}}', date('Y-m-d H:i:s'), $template);
        $template = str_replace('{{year}}', date('Y'), $template);
        
        // Send email asynchronously
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 465;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->SMTPAuth = true;
        $mail->Username = 'share.e2eeplatform@gmail.com';
        $mail->Password = 'vczc yawy voeo nkbk';
        $mail->setFrom('share.e2eeplatform@gmail.com', 'Share E2EE Platform');
        $mail->addReplyTo('no-reply@share.com', 'Share E2EE Platform');
        $mail->addAddress($user_email, $_SESSION["user_data"]["first_name"] . " " . $_SESSION["user_data"]["last_name"]);
        $mail->isHTML(true);
        $mail->Subject = 'File Download Confirmation: ' . $file_name;
        $mail->Body = $template;
        $mail->AltBody = 'Your file was downloaded successfully: ' . $file_name;
        
        // Send email
        $mail->send();
        
        return true;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}
?>