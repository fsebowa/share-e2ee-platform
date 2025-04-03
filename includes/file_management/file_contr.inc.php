<?php

declare(strict_types=1);

//Google reCAPTCHA validation
function is_recaptcha_invalid($secretKey, $recaptcha_response) {
    if(!isset($recaptcha_response) || empty($recaptcha_response)){
        return true; // reCAPTCHA response missing or empty
    }
    
    $api_url = 'https://www.google.com/recaptcha/api/siteverify';
    $resq_data = array(
        'secret' => $secretKey,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    );
    
    // Try using file_get_contents first (simpler)
    try {
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($resq_data)
            ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($api_url, false, $context);
        
        if ($response === FALSE) {
            // Fall back to cURL if file_get_contents fails
            throw new Exception("file_get_contents failed");
        }
        
        $responseData = json_decode($response);
        return !$responseData->success;
    } 
    catch (Exception $e) {
        // Fall back to cURL method if file_get_contents fails
        if (!function_exists('curl_init')) {
            error_log("cURL not available after file_get_contents failed");
            return true; // Error detecting recaptcha
        }
        
        $cURLConfig = array(
            CURLOPT_URL => $api_url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $resq_data
        );
        $ch = curl_init();
        curl_setopt_array($ch, $cURLConfig);
        $response = curl_exec($ch);

        // Check for cURL error
        if (curl_errno($ch)) {
            curl_close($ch);
            error_log("cURL error: " . curl_error($ch));
            return true; //error detected
        }
        curl_close($ch);

        //Decode JSON Data
        $responseData = json_decode($response);

        //If reCAPTCHA response is valid
        return !$responseData->success;
    }
}

// Input validation
function is_input_empty(string $file_name, string $key, array $file) {
    if (empty($file_name) || empty($key) || empty($file) || !isset($file["size"]) || $file["size"] <= 0) {
        return true;
    } else {
        return false;
    }
}

// Key length
function is_key_length_invalid(string $key_hex) {
    if (strlen($key_hex) != 64 || !ctype_xdigit($key_hex)) {
        return true;
    } else {
        return false;
    }
}

// file max size
function is_file_too_large(array $file) {
    if (!isset($file["size"])) {
        return true;
    }
    
    $max_size = 20 * 1024 * 1024; // 20MB in bytes
    if ($file["size"] > $max_size) {
        return true;
    } else {
        return false;
    }
}

// file types
function is_file_type_invalid(array $file) {
    if (!isset($file["tmp_name"]) || !file_exists($file["tmp_name"])) {
        return true;
    }
    
    $permited_file_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'audio/mpeg',
        'audio/wav',
        'video/mp4',
        'video/mpeg',
        'application/zip',
        'application/x-rar-compressed'
    ];
    
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    if (!$file_info) {
        error_log("Failed to open fileinfo");
        return true;
    }
    
    $mime_type = finfo_file($file_info, $file["tmp_name"]);
    finfo_close($file_info);
    
    if (!in_array($mime_type, $permited_file_types)) {
        return true;
    } else {
        return false;
    }
}

// File upload email template
function is_file_email_template_missing(string $file_template) {
    if (!file_exists($file_template)) {
        error_log("Email template not found: $file_template");
        return true;
    } else {
        return false;
    }
}