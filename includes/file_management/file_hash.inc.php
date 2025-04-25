<?php
require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';

// Ensure user is logged in and verified
check_login_otp_status();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get reCAPTCHA response and validate
    $recaptcha_response = $_POST["g-recaptcha-response"] ?? null;
    $csrf_token = $_POST["csrf_token"] ?? '';
    $csrf_token_time = $_SESSION["csrf_token_time"] ?? 0;
    $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';
    
    // Initialize error and success arrays
    $errors = [];
    
    try {
        require_once __DIR__ . '/file_contr.inc.php';
        
        // Validate security tokens
        if (csrf_token_expired($csrf_token_time)) {
            $errors[] = "Session token expired. Try again!";
        } elseif (csrf_token_invalid($csrf_token)) {
            $errors[] = "Invalid CSRF token";
        } elseif (is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors[] = "The reCAPTCHA verification failed. Please try again!";
        } 
        // Check if files are uploaded
        elseif (!isset($_FILES['hash_file_input_1']) || $_FILES['hash_file_input_1']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "First file upload failed or no file was selected.";
        } elseif (!isset($_FILES['hash_file_input_2']) || $_FILES['hash_file_input_2']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Second file upload failed or no file was selected.";
        } else {
            // Get the encryption key
            $key = $_POST['key'] ?? null;
            
            // Save key to session (for displaying it after redirect)
            $_SESSION['last_key'] = $key;
            
            // Validate key
            if (empty($key)) {
                $errors[] = "A valid HMAC key is required.";
            } 
            elseif (strlen($key) != 64 || !ctype_xdigit($key)) {
                $errors[] = "Key must be exactly 64 hex characters (256-bit key).";
            }
            else {
                // Process files if key is valid
                $file1Path = $_FILES['hash_file_input_1']['tmp_name'];
                $file2Path = $_FILES['hash_file_input_2']['tmp_name'];
                
                // Check file sizes
                $maxFileSize = 20 * 1024 * 1024; // 20 MB
                if ($_FILES['hash_file_input_1']['size'] > $maxFileSize || $_FILES['hash_file_input_2']['size'] > $maxFileSize) {
                    $errors[] = "File size exceeds the maximum allowed (20 MB).";
                }
                else {
                    // Calculate file hashes using HMAC
                    $hash1 = calculateFileHash($file1Path, $key);
                    $hash2 = calculateFileHash($file2Path, $key);
                    
                    if ($hash1 === false) {
                        $errors[] = "Failed to calculate hash for first file.";
                    } 
                    elseif ($hash2 === false) {
                        $errors[] = "Failed to calculate hash for second file.";
                    }
                    else {
                        // Clear any previous results
                        unset($_SESSION['errors_hash']);
                        unset($_SESSION['success_hash']);
                        
                        // Store results in session
                        $_SESSION['hash_results'] = [
                            'hash1' => $hash1,
                            'hash2' => $hash2,
                            'match' => ($hash1 === $hash2),
                            'file1_name' => $_FILES['hash_file_input_1']['name'],
                            'file2_name' => $_FILES['hash_file_input_2']['name']
                        ];
                        
                        // Set success or error message based on match
                        if ($hash1 === $hash2) {
                            $_SESSION['success_hash'] = ["Files match! Both files have identical hashes."];
                        } else {
                            $_SESSION['errors_hash'] = ["Files don't match. The hashes are different."];
                        }
                    }
                }
            }
        }
    } 
    catch (Exception $e) {
        $errors[] = "An error occurred: " . $e->getMessage();
        error_log("Exception in file_hash.inc.php: " . $e->getMessage());
    }
    
    // Store errors in session if any (and there are no other errors/success messages)
    if (!empty($errors) && !isset($_SESSION['errors_hash']) && !isset($_SESSION['success_hash'])) {
        $_SESSION['errors_hash'] = $errors;
    }
    
    // Redirect back to the hashing page
    header('Location: /hashing.php');
    exit;
}
else {
    // Not a POST request, redirect to hashing page
    header('Location: /hashing.php');
    exit;
}

function calculateFileHash($filePath, $key) {
    // Convert hex key to binary
    $binaryKey = @hex2bin($key);
    if (!$binaryKey) {
        return false;
    }
    
    // Read file in chunks to handle large files
    $handle = @fopen($filePath, 'rb');
    if (!$handle) {
        return false;
    }
    
    // Initialize hash context
    $hashContext = hash_init('sha256', HASH_HMAC, $binaryKey);
    
    // Process file in chunks
    $chunkSize = 8192; // 8 KB chunks
    while (!feof($handle)) {
        $data = fread($handle, $chunkSize);
        if ($data === false) {
            fclose($handle);
            return false;
        }
        hash_update($hashContext, $data);
    }
    
    // Finalize hash
    fclose($handle);
    return hash_final($hashContext);
}