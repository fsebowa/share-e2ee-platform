<?php

declare(strict_types=1);

// require_once __DIR__ . '/../config/config_session.inc.php';

//CSRF Token Time Validation
function csrf_token_expired($csrf_token_time) {
    // $max_time = 20; // test time
    $max_time = 60*60*20;
    if(($csrf_token_time + $max_time) <= time()){
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return true; // token time expired
    } else{
        return false; // token time valid
    }
}

// CSRF Token Validation
function csrf_token_invalid($csrf_token) {
    if($csrf_token != $_SESSION['csrf_token']){
        return true;
    }
    else{
        return false;
    }
}

// reCAPTCHA verification
function is_recaptcha_invalid($secretKey, $recaptcha_response) {
    if(isset($recaptcha_response) && !empty($recaptcha_response)){
        $api_url = 'https://www.google.com/recaptcha/api/siteverify';
        $resq_data = array(
            'secret' => $secretKey,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        );
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
            return true; //error detected
        }
        curl_close($ch);

        //Decode JSON Data
        $responseData = json_decode($response);

        //If reCAPTCHA response is valid
        if($responseData->success){
            return false; // no errors
        } else{
            return true; //true that the recaptcha is invalid
        }
    } else{
        return true; // reCAPTCHA response missing or empty
    }
}

function is_input_empty(string $otp_code) {
    if (empty($otp_code)) {
        return true;
    } else {
        return false;
    }
}

function is_otp_wrong(bool|array $otp_result) {
    if (!$otp_result) {
        return true;
    } else {
        return false;
    }
    
}

function has_otp_expired($current_time, $otp_expiry) {
    if ($current_time > $otp_expiry) {
        return true;
    } else {
        return false;
    }
}