<?php
// Start session if not already started
require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../encryption/file_encryption.inc.php';
require_once __DIR__ . '/../encryption/encryption_service.inc.php';
require_once __DIR__ . '/../config/dbh.inc.php';
require_once __DIR__ .'/../file_management/file_model.inc.php';
require_once __DIR__ . '/share_model.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get share token from POST
    $shareToken = $_POST['share_token'] ?? '';
    if (!$shareToken) {
        $_SESSION["share_error_messages"] = ["Invalid session. Please restart the sharing process."];
        header("Location: /s.php");
        exit();
    }

    // Store token in session for later redirects
    $_SESSION['active_share_token'] = $shareToken;

    // Get share data
    $shareData = get_file_share_by_token($pdo, $shareToken);
    if (!$shareData || !is_share_valid($shareData)) {
        $_SESSION["share_error_messages"] = ["This share link is no longer valid."];
        header("Location: /s.php");
        exit();
    }

    // Add password verification check
    if (!empty($shareData['access_password']) && 
        !isset($_SESSION['password_verified_'.$shareData['id']])) {
        $_SESSION["share_error_messages"] = ["Password verification required."];
        header("Location: /s.php?token=".$shareToken);
        exit();
    }

    // Initialize encryption service for form data
    $encryptionService = new EncryptionService();
    $encryptedData = $_POST["encrypted_data"] ?? null;
    // Get other POST data
    $csrf_token = $_POST["csrf_token"];
    $csrf_token_time = $_SESSION["csrf_token_time"];
    $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';
    $recaptcha_response = $_POST["g-recaptcha-response"];

    try {
        require_once __DIR__ . '/../file_management/file_contr.inc.php';

        $errors = [];
        $success = [];

        // Validate inputs and security checks
        if (csrf_token_expired($csrf_token_time)) {
            $errors["csrf_token_expired"] = "Session token expired. Try again!";
        } elseif (csrf_token_invalid($csrf_token)) {
            $errors["csrf_token_invalid"] = "Invalid CSRF token";
        } elseif (is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["recaptch_invalid"] = "The reCAPTCHA verification failed. Please try again!";
        } elseif (!$encryptedData) {
            $errors["encryption_error"] = "Failed to process encrypted data. Please try again!";
        } else {
            // Decrypt the data
            $decryptedData = $encryptionService->decryptFormData($encryptedData);

            if (!$decryptedData) {
                $errors["decryption_error"] = "Error decrypting form data. Please try again!";
            } else {
                $decryption_key = $decryptedData["decryption_key"] ?? null;
                if (empty($decryption_key)) {
                    $errors["key_missing"] = "Decryption key is required";
                } elseif (strlen($decryption_key) != 64 || !ctype_xdigit($decryption_key)) {
                    $errors["key_format"] = "Key must be exactly 64 hex characters (256-bit key)";
                }
                // Get file ID and share data
                $file_id = get_file_id_by_share_token($pdo, $shareToken);
                // Validate required fields
                if (empty($file_id)) {
                    $errors["file_id_missing"] = "File ID is required";
                }
            }    
        }
        
        // If no errors, proceed with file download
        if (empty($errors)) {            
            // Get file information from database
            $file_info = get_file_by_id($pdo, $file_id, $shareData["shared_by"]);
            
            if (!$file_info) {
                $errors["file_not_found"] = "File not found.";
            } else {                
                // Convert hex key to binary for decryption
                $binary_key = @hex2bin($decryption_key);
                if (!$binary_key) {
                    $errors["key_conversion"] = "Failed to convert hex key to binary format";
                } else {
                    // Initialize encryption service
                    $encryption_service = new FileEncryptionService();
                    
                    // Prepare temp directory
                    $temp_dir = sys_get_temp_dir() . '/share_temp_files';
                    if (!is_dir($temp_dir)) {
                        if (!mkdir($temp_dir, 0755, true)) {
                            $errors["temp_dir"] = "Failed to create temp directory";
                        }
                    }
                    
                    if (empty($errors)) {
                        $decrypted_file = $temp_dir . '/' . $file_info["original_filename"];
                        // Decrypt the file
                        try {
                            $decryption_result = $encryption_service->decrypt_file(
                                $file_info["file_path"],
                                $binary_key,
                                $decrypted_file
                            );
                            
                            if (!isset($decryption_result['success']) || $decryption_result['success'] !== true) {
                                // If decryption failed, clean up and return error
                                if (file_exists($decrypted_file)) {
                                    @unlink($decrypted_file);
                                }
                                $error_message = $decryption_result['message'] ?? "Invalid decryption key for this file.";
                                $errors["decryption_failed"] = $error_message;
                            } else {
                                // Log the download - safely handle missing user_id for non-logged-in users
                                logFileDownload($pdo, $file_id, $_SESSION["user_id"] ?? 0, 'decrypted');
                                
                                // Increment share access count
                                increment_share_access_count($pdo, $shareData['id']);
                                
                                // Record success (for display if download fails)
                                $_SESSION["share_success_messages"] = ["File decrypted successfully."];
                                
                                // Send email confirmation asynchronously if possible, don't block download
                                // This is a simplification - ideally use a queue system for emails
                                $sender_email = get_user_email_by_id($pdo, $shareData["shared_by"]);
                                
                                // Verify the file exists and is readable
                                if (!file_exists($decrypted_file) || !is_readable($decrypted_file)) {
                                    $errors["decrypted_file_missing"] = "The decrypted file could not be read.";
                                } else {
                                    // Close database connection before sending file
                                    $pdo = null;
                                    
                                    // Clear output buffers
                                    while (ob_get_level()) {
                                        ob_end_clean();
                                    }
                                    
                                    // Send the decrypted file
                                    header('Content-Description: File Transfer');
                                    header('Content-Type: application/octet-stream');
                                    $filename_parts = pathinfo($file_info["original_filename"]);
                                    $decrypted_filename = $filename_parts['filename'];
                                    if (isset($filename_parts['extension'])) {
                                        $decrypted_filename .= '.' . $filename_parts['extension'];
                                    }
                                    header('Content-Disposition: attachment; filename="decrypted_'. $decrypted_filename . '"');
                                    header('Expires: 0');
                                    header('Cache-Control: must-revalidate');
                                    header('Pragma: public');
                                    header('Content-Length: ' . filesize($decrypted_file));
                                    
                                    // Read file and output in chunks
                                    $fp = fopen($decrypted_file, 'rb');
                                    while (!feof($fp)) {
                                        echo fread($fp, 4096);
                                        flush();
                                    }
                                    fclose($fp);
                                    
                                    // Clean up the decrypted file
                                    @unlink($decrypted_file);
                                    
                                    // Email confirmation after file is sent
                                    try {
                                        sendDownloadConfirmationEmail(
                                            $_SESSION["email"] ?? $sender_email,
                                            $_SESSION["first_name"] ?? '' . " " . $_SESSION["last_name"] ?? '',
                                            $file_info,
                                            'decrypted'
                                        );
                                    } catch (Exception $e) {
                                        // Ignore email errors - don't affect download
                                        error_log("Share download email error: " . $e->getMessage());
                                    }
                                    
                                    exit;
                                }
                            }
                        } catch (Exception $e) {
                            $errors["decryption_exception"] = "Error during decryption: " . $e->getMessage();
                            error_log("Share download decryption error: " . $e->getMessage());
                            
                            // Clean up any partial files
                            if (file_exists($decrypted_file)) {
                                @unlink($decrypted_file);
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        $errors["system_error"] = "An error occurred: " . $e->getMessage();
        error_log("Share download system error: " . $e->getMessage());
    }

    // Handle errors and success messages
    if (!empty($errors)) {
        $_SESSION["share_error_messages"] = $errors;
        header("Location: /s.php?token=" . $shareToken);
        exit();
    }
    if (!empty($success)) {
        $_SESSION["share_success_messages"] = $success;
        header("Location: /s.php?token=" . $shareToken);
        exit();
    }
} else {
    // Not a POST request, redirect to share page
    $token = $_GET['token'] ?? $_SESSION['active_share_token'] ?? '';
    header("Location: /s.php" . ($token ? "?token=" . $token : ""));
    exit();
}

function sendDownloadConfirmationEmail($email, $name, $file_info, $download_type) {
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
        $mail->Subject = 'Your file has been downloaded: ' . $file_info["file_name"];
        $mail->Body = $template;
        $mail->AltBody = 'Your file was downloaded (' . $download_type . '): ' . $file_info["file_name"];
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Error sending download confirmation email: " . $e->getMessage());
        return false;
    }
}