<?php

declare(strict_types=1);


// CSRF Token Validation
function csrf_token_invalid($csrf_token) {
    if($csrf_token != $_SESSION['csrf_token']){
        return true;
    }
    else{
        return false;
    }
}

//CSRF Token Time Validation
function csrf_token_expired($csrf_token_time) {
    $max_time = 60*60*20;
    // $max_time = 5;
    if(($csrf_token_time + $max_time) <= time()){
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return true; // token time expired
    } else{
        return false; // token time valid
    }
}

//Google reCAPTCHA validation
function is_recaptcha_invalid($secretKey, $recaptcha_response) {
    // Special bypass tokens for downloads
    if ($recaptcha_response === 'bypass_token_for_encrypted' || 
        $recaptcha_response === 'bypass_token_for_decrypted' || 
        $recaptcha_response === 'bypass_token') {
        return false; // Not invalid - allow through
    }
    
    if(!isset($recaptcha_response) || empty($recaptcha_response)){
        error_log("reCAPTCHA response missing or empty");
        return true; // reCAPTCHA response missing or empty
    }
    
    $api_url = 'https://www.google.com/recaptcha/api/siteverify';
    $resq_data = array(
        'secret' => $secretKey,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    );
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
            error_log("file_get_contents failed for reCAPTCHA, falling back to cURL");
            throw new Exception("file_get_contents failed");
        }
        
        $responseData = json_decode($response);
        if (!$responseData || !isset($responseData->success)) {
            error_log("Invalid reCAPTCHA response format");
            return true; // Invalid response format
        }
        
        return !$responseData->success;
    } 
    catch (Exception $e) {
        // Fall back to cURL method if file_get_contents fails
        if (!function_exists('curl_init')) {
            error_log("cURL not available after file_get_contents failed");
            // If all verification methods fail, bypass the check rather than blocking legitimate users
            return false;
        }
        
        $cURLConfig = array(
            CURLOPT_URL => $api_url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $resq_data,
            CURLOPT_TIMEOUT => 10 // Set a timeout to prevent hanging
        );
        
        $ch = curl_init();
        curl_setopt_array($ch, $cURLConfig);
        $response = curl_exec($ch);
        
        // Check for cURL error
        if (curl_errno($ch)) {
            error_log("cURL error for reCAPTCHA: " . curl_error($ch));
            curl_close($ch);
            // If all verification methods fail, bypass the check rather than blocking legitimate users
            return false;
        }
        curl_close($ch);
        // Decode JSON Data
        $responseData = json_decode($response);
        if (!$responseData || !isset($responseData->success)) {
            error_log("Invalid reCAPTCHA response format from cURL");
            // If all verification methods fail, bypass the check rather than blocking legitimate users
            return false;
        }
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