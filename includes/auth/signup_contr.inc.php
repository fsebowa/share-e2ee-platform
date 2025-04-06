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
        }
    } else{
        return true; // reCAPTCHA response missing or empty
    }
}

function is_input_empty(string $first_name, string $last_name, string $email, string $password, string $confirm_password) {
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
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

function is_email_registered(object $pdo, string $email) {
    if (get_email($pdo, $email)) {
        return true;
    } else {
        return false;
    }
}

function password_length(string $password) {
    if (strlen($password) < 6) {
        return true;
    } else {
        return false;
    }
}

function password_match(string $password, string $confirm_password) {
    if ($password != $confirm_password) {
        return true;
    } else {
        return false;
    }
}

function create_user(object $pdo, string $first_name, string $last_name, string $email, string $password) {
    set_user($pdo, $first_name, $last_name, $email, $password);
}