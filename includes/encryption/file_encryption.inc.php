<?php 

class FileEncryptionService {
    private $cipher = 'aes-256-gcm';
    private $tag_length = 16; // GCM authentication tag length

    public function encrypt_file($source_file, $key, $target_directory) {
        try {
            // Validate key is exactly 32 bytes (256 bits)
            if (strlen($key) != 32) {
                return [
                    'success' => false,
                    'message' => "Encryption key must be exactly 32 bytes (256 bits) long."
                ];
            }
            
            // Generate a unique filename for the encrypted file
            $encrypted_filename = bin2hex(random_bytes(16)) . '.enc';
            $target_file = rtrim($target_directory, '/') . '/' . $encrypted_filename;

            // Check if target directory exists
            if (!is_dir($target_directory)) {
                // Create directory if it doesn't exist
                if (!mkdir($target_directory, 0755, true)) {
                    return [
                        'success' => false,
                        'message' => "Failed to create target directory: $target_directory"
                    ];
                }
            }

            // Generate IV (nonce for GCM)
            $iv_length = openssl_cipher_iv_length($this->cipher);
            $iv = openssl_random_pseudo_bytes($iv_length);
            
            // Open file streams
            $input = fopen($source_file, 'rb');
            $output = fopen($target_file, 'wb');

            if (!$input || !$output) {
                return [
                    'success' => false,
                    'message' => "Failed to open file streams."
                ];
            } 
            
            // Write IV to beginning of output file
            fwrite($output, $iv);

            // For GCM mode, we need to handle the authentication tag
            $tag = null;
            
            // Read the entire file content (we can't encrypt GCM in chunks as easily as CBC)
            $plaintext = stream_get_contents($input);
            
            // Encrypt the file content
            $ciphertext = openssl_encrypt(
                $plaintext,
                $this->cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );
            
            if ($ciphertext === false) {
                fclose($input);
                fclose($output);
                @unlink($target_file);
                return [
                    'success' => false,
                    'message' => "Encryption failed: " . openssl_error_string()
                ];
            }
            
            // Write the authentication tag after the IV
            fwrite($output, $tag);
            
            // Write encrypted data to file
            fwrite($output, $ciphertext);

            // Close file streams
            fclose($input);
            fclose($output);

            return [
                'success' => true,
                'path' => $target_file,
                'filename' => $encrypted_filename
            ];
        } catch (Exception $e) {
            // Log error for debugging
            error_log("File encryption error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Encryption error: " . $e->getMessage()
            ];
        }
    }

    public function decrypt_file($encrypted_file, $key, $output_file) {
        try {
            // Validate key is exactly 32 bytes (256 bits)
            if (strlen($key) != 32) {
                return [
                    'success' => false,
                    'message' => "Decryption key must be exactly 32 bytes (256 bits) long. Got " . strlen($key) . " bytes."
                ];
            }

            // Check if encrypted file exists
            if (!file_exists($encrypted_file)) {
                return [
                    'success' => false,
                    'message' => "Encrypted file not found: $encrypted_file"
                ];
            }
            
            // Open file streams
            $input = fopen($encrypted_file, 'rb');
            if (!$input) {
                return [
                    'success' => false,
                    'message' => "Could not open encrypted file for reading"
                ];
            }

            // Create output directory if it doesn't exist
            $output_dir = dirname($output_file);
            if (!is_dir($output_dir)) {
                if (!mkdir($output_dir, 0755, true)) {
                    fclose($input);
                    return [
                        'success' => false,
                        'message' => "Failed to create output directory"
                    ];
                }
            }

            // Read IV from the beginning of the file
            $iv_length = openssl_cipher_iv_length($this->cipher);
            $iv = fread($input, $iv_length);
            if (strlen($iv) < $iv_length) {
                fclose($input);
                return [
                    'success' => false,
                    'message' => "Invalid file format or file is corrupted (IV too short)"
                ];
            }
            
            // Read the authentication tag
            $tag = fread($input, $this->tag_length);
            if (strlen($tag) < $this->tag_length) {
                fclose($input);
                return [
                    'success' => false,
                    'message' => "Invalid file format or file is corrupted (Authentication tag missing)"
                ];
            }
            
            // Read the rest of the file (ciphertext)
            $ciphertext = stream_get_contents($input);
            fclose($input);
            
            // Decrypt the file
            $plaintext = openssl_decrypt(
                $ciphertext,
                $this->cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );
            
            if ($plaintext === false) {
                return [
                    'success' => false,
                    'message' => "Error Occurred: " . openssl_error_string() . "This may be due to an incorrect key or corrupted file."
                ];
            }
            
            // Write the decrypted data to the output file
            $output = fopen($output_file, 'wb');
            if (!$output) {
                return [
                    'success' => false,
                    'message' => "Could not open output file for writing"
                ];
            }
            
            fwrite($output, $plaintext);
            fclose($output);
            
            // Verify the decrypted file exists and has content
            if (!file_exists($output_file) || filesize($output_file) === 0) {
                @unlink($output_file);
                return [
                    'success' => false,
                    'message' => "Decryption completed but resulted in an empty file. Please check your key."
                ];
            }
            
            return [
                'success' => true,
                'path' => $output_file
            ];
        } catch (Exception $e) {
            if (isset($output) && is_resource($output)) {
                fclose($output);
            }
            if (isset($input) && is_resource($input)) {
                fclose($input);
            }
            if (file_exists($output_file)) {
                @unlink($output_file);
            }
            
            // Log error for debugging
            error_log("File decryption error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Decryption error: " . $e->getMessage()
            ];
        }
    }

    // Determine file type/mime type
    public function get_file_type($file) {
        if (!file_exists($file)) {
            error_log("File doesn't exist: $file");
            return 'Other';
        }
        
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        if (!$file_info) {
            error_log("Failed to open fileinfo");
            return 'Other';
        }
        
        $mime_type = finfo_file($file_info, $file);
        finfo_close($file_info);

        // Mapping MIME types to simplified categories
        $category_map = [
            'image/' => 'Images',
            'video/' => 'Videos',
            'audio/' => 'Audio',
            'application/pdf' => 'PDF',
            'application/msword' => 'Documents',
            'application/vnd.openxmlformats-officedocument.wordprocessingml' => 'Documents',
            'application/vnd.ms-excel' => 'Documents',
            'application/vnd.openxmlformats-officedocument.spreadsheetml' => 'Spreadsheets',
            'application/vnd.ms-powerpoint' => 'Presentations',
            'application/vnd.openxmlformats-officedocument.presentationml' => 'Presentations',
            'text/' => 'Documents'
        ];
        
        foreach ($category_map as $mime_prefix => $category) {
            if (strpos($mime_type, $mime_prefix) === 0) {
                return $category;
            }
        }
        return 'Other';
    }
}