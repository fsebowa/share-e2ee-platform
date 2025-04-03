<?php
require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';
require_once __DIR__ . '/../encryption/file_encryption.inc.php';
require_once __DIR__ . '/file_contr.inc.php';

check_login_otp_status();

if ($_SERVER["REQUEST_METHOD"] === "POST" || (isset($_GET['stream']) && isset($_GET['file_id']))) {
    
    $user_id = $_SESSION["user_id"] ?? null;
    
    session_write_close();    // Close session write to allow parallel requests (crucial for video streaming)
    
    $is_streaming_request = isset($_GET['stream']) && isset($_GET['file_id']);
    
    if ($is_streaming_request) {
        $file_id = $_GET['file_id']; // For streaming requests, get file_id from GET
        $decryption_key = $_GET['key'] ?? null;
    } else {
        // For initial requests, get data from POST
        $file_id = $_POST["file_id"] ?? null;
        $decryption_key = $_POST["key"] ?? null;
        $recaptcha_response = $_POST["g-recaptcha-response"] ?? null;
        $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';
    }

    $errors = [];

    if (!$is_streaming_request) {
        if(is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["invalid_recaptcha"] = "The reCAPTCHA verification failed. Please try again!";
        } elseif (empty($file_id) || empty($decryption_key)) {
            $errors["empty_inputs"] = "One or more fields are empty";
        } elseif (!empty($decryption_key) && (strlen($decryption_key) != 64 || !ctype_xdigit($decryption_key))) {
            $errors["wrong_key_length"] = "Key must be exactly 64 hex characters (256-bit key)";
        }
    }

    // If no errors, proceed with file handling
    if(empty($errors)) {
        try {
            require_once __DIR__ . '/../config/dbh.inc.php';
            require_once __DIR__ . '/file_model.inc.php';

            $file_info = get_file_by_id($pdo, $file_id, $user_id);
            if (!$file_info) {
                $errors["file_not_found"] = "File not found.";
            } else {
                $temp_dir = sys_get_temp_dir() . '/share_temp_files'; // location for decrypted temporary files
                if (!is_dir($temp_dir)) {
                    mkdir($temp_dir, 0755, true);
                }
                
                // Create a unique but deterministic filename based on file_id and user_id
                $temp_file = $temp_dir . '/decrypted_' . $user_id . '_' . $file_id . '_' . md5(basename($file_info['original_filename']));
                $needs_decryption = !$is_streaming_request || !file_exists($temp_file); // decrypt files for initial request

                if ($needs_decryption) {
                    // Initialize encryption service
                    $encryption_service = new FileEncryptionService();
                    
                    // Convert hex key (64 chars) to binary key (32 bytes)
                    if (!$is_streaming_request) {
                        // Validate the key format first
                        if (!ctype_xdigit($decryption_key) || strlen($decryption_key) !== 64) {
                            $errors["invalid_key"] = "Invalid key format. The key must be 64 hexadecimal characters.";
                        }
                    }
                    
                    if (empty($errors)) {
                        // Convert hex to binary
                        $binary_key = hex2bin($decryption_key);
                        
                        // Debug log key info
                        error_log("Decryption key length (hex): " . strlen($decryption_key));
                        error_log("Decryption key length (binary): " . strlen($binary_key));
                        
                        // Attempt decryption
                        $decryption_result = $encryption_service->decrypt_file(
                            $file_info["file_path"],
                            $binary_key,
                            $temp_file
                        );

                        // Check if decryption was successful
                        if (!$decryption_result['success']) {
                            $error_message = $decryption_result['message'] ?? "Failed to decrypt file. Check your decryption key.";
                            $errors["decryption_error"] = $error_message;
                            error_log("Decryption error: " . $error_message);
                        }
                    }
                }
                
                if (empty($errors) && file_exists($temp_file)) {
                    // Determine MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $temp_file);
                    finfo_close($finfo);
                    
                    // Normalize video MIME types
                    if (strpos($mime_type, 'video/') === 0) {
                        $ext = strtolower(pathinfo($file_info['original_filename'], PATHINFO_EXTENSION));
                        if ($ext === 'mp4') {
                            $mime_type = 'video/mp4';
                        } elseif ($ext === 'webm') {
                            $mime_type = 'video/webm';
                        } elseif ($ext === 'ogg' || $ext === 'ogv') {
                            $mime_type = 'video/ogg';
                        }
                    }
                    $file_size = filesize($temp_file);
                    $is_video = strpos($mime_type, 'video/') === 0;
                    
                    if (!$is_streaming_request && $is_video) {
                        // Generate a URL for video streaming 
                        $stream_url = '/includes/file_management/file_preview.inc.php?' . 
                                    'stream=1&file_id=' . urlencode($file_id) . 
                                    '&key=' . urlencode($decryption_key);
                        
                        // Creating an HTML page with a video player
                        echo '<!DOCTYPE html>
                                <html>
                                <head>
                                    <title>Video Player - ' . htmlspecialchars($file_info['file_name']) . '</title>
                                    <style>
                                        body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 0; background-color: #000; height: 100vh;}
                                        video { max-width: 100%; max-height: 100%; height: 605px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);}
                                        .controls { position: fixed; top: 20px; right: 20px; z-index: 10;}
                                        .controls a {margin-left: 10px; display: inline-block; background: rgba(255, 255, 255); color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px; box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px; }
                                        .controls a:hover {background:  rgba(234, 234, 234); box-shadow: rgba(99, 99, 99, 0.5) 0px 2px 8px 0px;}
                                    </style>
                                </head>
                                <body> 
                                <div class="controls">
                                        <a href="/dashboard.php">Back to Dashboard</a>
                                        <a href="' . $stream_url . '&download=1">Download Video</a>
                                    </div>
                                    <video controls autoplay>
                                        <source src="' . $stream_url . '" type="' . $mime_type . '">
                                        Your browser does not support the video tag.
                                    </video>
                                </body>
                                </html>';
                        exit();
                    }
                    
                    // Handle HTTP Range requests for video streaming
                    $range = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : null;
                    $is_download = isset($_GET['download']) && $_GET['download'] == '1';
                    
                    if ($range && $is_video && !$is_download) {
                        list($unit, $range) = explode('=', $range, 2);
                        
                        if ($unit == 'bytes') {
                            // Parse the actual range values
                            list($range) = explode(',', $range, 2);
                            list($start, $end) = explode('-', $range, 2);
                            
                            $start = empty($start) ? 0 : intval($start);
                            $end = empty($end) ? ($file_size - 1) : intval($end);
                            
                            $end = min($end, $file_size - 1);  // prevents exceeding the file size
                            $length = $end - $start + 1;
                            
                            // setting appropriate headers for partial content
                            header('HTTP/1.1 206 Partial Content');
                            header('Content-Length: ' . $length);
                            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $file_size);
                            header('Accept-Ranges: bytes');
                            header('Content-Type: ' . $mime_type);
                            header('Content-Disposition: inline; filename="' . basename($file_info['original_filename']) . '"');
                            
                            // Add CORS headers for cross-origin requests
                            header('Access-Control-Allow-Origin: *');
                            header('Access-Control-Allow-Methods: GET, OPTIONS');
                            header('Access-Control-Allow-Headers: Range');
                            
                            header('Cache-Control: public, max-age=86400'); // enabling cache control for video segments
                            
                            // Ensure clean output
                            if (ob_get_level()) {
                                ob_end_clean();
                            }
                            flush();
                            
                            // Open the file and seek to the start position
                            $fp = fopen($temp_file, 'rb');
                            fseek($fp, $start);
                            
                            // Output data in chunks
                            $buffer_size = 8192; // 8KB chunks
                            $bytes_to_read = $length;
                            
                            while ($bytes_to_read > 0 && !feof($fp)) {
                                $buffer = fread($fp, min($buffer_size, $bytes_to_read));
                                echo $buffer;
                                flush();
                                $bytes_to_read -= strlen($buffer);
                            }
                            fclose($fp);
                            exit();
                        }
                    } else {
                        // Determine if file should be displayed inline or downloaded
                        $inline_types = ['application/pdf', 'image/', 'text/'];
                        $display_inline = false;
                        
                        // Always download videos in "download" mode
                        if ($is_video && $is_download) {
                            $display_inline = false;
                        } else {
                            foreach ($inline_types as $type) {
                                if (strpos($mime_type, $type) === 0) {
                                    $display_inline = true;
                                    break;
                                }
                            }
                        }
                        
                        // setting appropriate headers 
                        header('Content-Description: File Transfer');
                        header('Content-Type: ' . $mime_type);
                        
                        if ($display_inline && !$is_download) {
                            header('Content-Disposition: inline; filename="' ."decrypted_" . basename($file_info['original_filename']) . '"');
                        } else {
                            header('Content-Disposition: attachment; filename="' ."decrypted_". basename($file_info['original_filename']) . '"');
                        }
                        
                        if ($is_video) {
                            header('Accept-Ranges: bytes');
                        }
                        
                        header('Content-Length: ' . $file_size);
                        header('Cache-Control: public, max-age=86400'); // Allow caching for non-sensitive content
                        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
                        
                        // Ensure clean output
                        if (ob_get_level()) {
                            ob_end_clean();
                        }
                        flush();
                        readfile($temp_file); // Output the file
                        
                        // For non-video files, delete the temp file immediately
                        // For videos, only delete when downloading
                        if (!$is_video || $is_download) {
                            @unlink($temp_file);
                        }
                        exit();
                    }
                }
            }
        } catch (Exception $e) {
            $errors["system_error"] = "An error occurred: " . $e->getMessage();
            error_log("Exception in file_preview.inc.php: " . $e->getMessage());
        }
    }

    // redirect back to dashboard if error messages
    if (!empty($errors)) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION["errors_file_preview"] = $errors;
        header("Location: /dashboard.php");
        exit();
    }
} else {
    header("Location: /dashboard.php");
    exit();
}

// Helper function to clean up old temporary files called via a cron job
function cleanup_temp_files($temp_dir, $max_age_hours = 24) {
    if (!is_dir($temp_dir)) return;
    
    $files = glob($temp_dir . '/decrypted_*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file) > $max_age_hours * 3600)) {
            @unlink($file);
        }
    }
}
?>