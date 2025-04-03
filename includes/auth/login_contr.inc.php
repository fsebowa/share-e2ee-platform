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
            // $errors[] = 'Error with reCAPTCHA validation. Please try again later.';
            curl_close($ch);
            return true; //error detected
        }
        curl_close($ch);

        //Decode JSON Data
        $responseData = json_decode($response);

        //If reCAPTCHA response is valid
        if($responseData->success){
            //continue form submission
            return false; // no errors
        } else{
            return true; //true that the recaptcha is invalid
            // $errors[] = "The reCAPTCHA verification failed. Please try again!";
        }
    } else{
        return true; // reCAPTCHA response missing or empty
        // $errors[] = "Something went wrong, Please try again!";
    }
}

function is_input_empty(string $email, string $password) {
    if (empty($email) || empty($password)) {
        return true;
    } else {
        return false;
    }
}

function is_email_valid(string $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    } else {
        return false;
    }
}

function is_user_wrong(bool|array $result) {
    if (!$result) {
        return true;
    } else {
        return false;
    }
}

function is_password_wrong(string $password, string $hashedPassword) {
    if (!password_verify($password, $hashedPassword)) {
        return true;
    } else {
        return false;
    }
}

function create_otp_code(object $pdo, string $user_id, string $otp_code, string $otp_expiry) {
    set_otp_code($pdo, $user_id, $otp_code, $otp_expiry);
}