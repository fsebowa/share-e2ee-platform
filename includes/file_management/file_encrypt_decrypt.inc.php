<?php
require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';
require_once __DIR__ . '/../encryption/file_encryption.inc.php';
require_once __DIR__ . '/../encryption/encryption_service.inc.php';
require_once __DIR__ . '/file_contr.inc.php';

// Check if user is logged in and OTP verified
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
        } elseif (!isset($_FILES['file']) || !isset($_FILES['file']['tmp_name']) || empty($_FILES['file']['tmp_name'])) {
            $errors["no_file"] = "No file was uploaded. Please select a file.";
        } else {
            // Decrypt the form data
            $decryptedData = $encryptionService->decryptFormData($encryptedData);
            
            if (!$decryptedData) {
                $errors["decryption_error"] = "Error decrypting form data. Please try again!";
            } else {
                // Extract data from decrypted payload
                $key = $decryptedData["key"] ?? null;
                $operation = $decryptedData["operation"] ?? "encrypt"; // Default to encrypt if not specified
                error_log("Operation set to: " . $operation);
                
                // Validate key length
                if (empty($key) || strlen($key) != 64 || !ctype_xdigit($key)) {
                    $errors["invalid_key"] = "Key must be exactly 64 hex characters (256-bit key)";
                }
                
                // Validate file size
                if (is_file_too_large($_FILES['file'])) {
                    $errors["file_too_large"] = "File is too large. Maximum allowed size is 20MB.";
                }
            }
        }
        
        // If no errors, proceed with file encryption/decryption
        if (empty($errors)) {
            // Initialize encryption service
            $encryption_service = new FileEncryptionService();
            
            // Create temporary directories for processing
            $temp_dir = sys_get_temp_dir() . '/share_temp_files';
            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0755, true);
            }
            
            // Convert hex key to binary
            $binary_key = hex2bin($key);
            
            // Generate a unique temporary filename
            $temp_output_file = $temp_dir . '/' . uniqid() . '_' . basename($_FILES['file']['name']);
            
            if ($operation === "encrypt") {
                // Encrypt the file
                $result = $encryption_service->encrypt_file(
                    $_FILES['file']['tmp_name'],
                    $binary_key,
                    $temp_dir
                );
                
                if (!$result || !isset($result['success']) || $result['success'] !== true) {
                    $errors["encryption_failed"] = "Failed to encrypt file: " . ($result['message'] ?? "Unknown error");
                } else {
                    $download_file = $result['path'];
                    $download_filename = "encrypted_" . basename($_FILES['file']['name']) . ".enc";
                    $mime_type = "application/octet-stream";
                    $success["encryption_success"] = "File encrypted successfully";
                }
            } else {
                // Decrypt the file
                $result = $encryption_service->decrypt_file(
                    $_FILES['file']['tmp_name'],
                    $binary_key,
                    $temp_output_file
                );
                
                if (!$result || !isset($result['success']) || $result['success'] !== true) {
                    $errors["decryption_failed"] = "Failed to decrypt file: " . ($result['message'] ?? "Unknown error");
                } else {
                    // If we get here, decryption was successful
                    $download_file = $result['path'];
                    
                    // Clean up the filename - remove .enc extension if present
                    $original_filename = basename($_FILES['file']['name']);
                    if (substr($original_filename, -4) === ".enc") {
                        $original_filename = substr($original_filename, 0, strlen($original_filename) - 4);
                    }
                    
                    // Remove any "encrypted_" prefix if present
                    if (substr($original_filename, 0, 10) === "encrypted_") {
                        $original_filename = substr($original_filename, 10);
                    }
                    
                    $download_filename = $original_filename;
                    
                    // Try to determine the actual mime type of the decrypted file
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $download_file);
                    finfo_close($finfo);
                    
                    $success["decryption_success"] = "File decrypted successfully";
                }
            }
            
            // If we have a file to download, send it to the user
            if (isset($download_file) && file_exists($download_file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: ' . $mime_type);
                header('Content-Disposition: attachment; filename="' . $download_filename . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($download_file));
                
                // Clear any existing output buffers
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Output the file
                readfile($download_file);
                
                // Clean up - delete the temporary file
                @unlink($download_file);
                
                exit;
            }
        }
    } catch (Exception $e) {
        $errors["system_error"] = "An error occurred: " . $e->getMessage();
        error_log("Exception in file_encrypt_decrypt.inc.php: " . $e->getMessage());
    }

    // If we reach here, there were errors or no file to download
    if (!empty($errors)) {
        $_SESSION["errors_encrypt_decrypt"] = $errors;
        // Only redirect if there were errors - success case downloads the file
        header("Location: /encrypt-decrypt.php");
        exit();
    }
    
    // This code should only be reached if something went wrong but no error was set
    $_SESSION["errors_encrypt_decrypt"] = ["An unknown error occurred while processing your file"];
    header("Location: /encrypt-decrypt.php");
    exit();
} else {
    header("Location: /encrypt-decrypt.php");
    exit();
}