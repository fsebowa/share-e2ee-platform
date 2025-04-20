<?php
declare(strict_types=1);

// // CSRF Token Validation
// function csrf_token_invalid($csrf_token) {
//     if($csrf_token != $_SESSION['csrf_token']){
//         return true;
//     }
//     else{
//         return false;
//     }
// }

// //CSRF Token Time Validation
// function csrf_token_expired($csrf_token_time) {
//     $max_time = 60*60*20;
//     // $max_time = 5;
//     if(($csrf_token_time + $max_time) <= time()){
//         unset($_SESSION['csrf_token']);
//         unset($_SESSION['csrf_token_time']);
//         return true; // token time expired
//     } else{
//         return false; // token time valid
//     }
// }

// // Google reCAPTCHA validation
// function is_recaptcha_invalid($secretKey, $recaptcha_response) {
//     if(isset($recaptcha_response) && !empty($recaptcha_response)){
//         $api_url = 'https://www.google.com/recaptcha/api/siteverify';
//         $resq_data = array(
//             'secret' => $secretKey,
//             'response' => $recaptcha_response,
//             'remoteip' => $_SERVER['REMOTE_ADDR']
//         );
//         $cURLConfig = array(
//             CURLOPT_URL => $api_url,
//             CURLOPT_POST => true,
//             CURLOPT_RETURNTRANSFER => true,
//             CURLOPT_POSTFIELDS => $resq_data
//         );
//         $ch = curl_init();
//         curl_setopt_array($ch, $cURLConfig);
//         $response = curl_exec($ch);

//         // Check for cURL error
//         if (curl_errno($ch)) {
//             curl_close($ch);
//             return true; //error detected
//         }
//         curl_close($ch);

//         //Decode JSON Data
//         $responseData = json_decode($response);

//         //If reCAPTCHA response is valid
//         if($responseData->success){
//             //continue form submission
//             return false; // no errors
//         } else{
//             return true; //true that the recaptcha is invalid
//         }
//     } else{
//         return true; // reCAPTCHA response missing or empty
//     }
// }

function validate_share_inputs(
    ?string $recipient,
    string $expiryDays,
    ?string $maxAccess,
    string $keyDelivery,
    ?string $password
) {
    $errors = [];
    
    // Validate recipient email for non-public links with email delivery
    if ($keyDelivery === 'email' && (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL))) {
        $errors[] = "Valid recipient email is required for email delivery";
    }
    
    // Validate expiry days (must be a positive integer)
    if (!is_numeric($expiryDays) || (int)$expiryDays < 1 || (int)$expiryDays > 30) {
        $errors[] = "Expiry must be between 1 and 30 days";
    }
    
    // Validate max accesses if provided
    if ($maxAccess !== null && (!is_numeric($maxAccess) || (int)$maxAccess < 1)) {
        $errors[] = "Maximum accesses must be a positive number";
    }
    
    // Validate key delivery method
    $validDeliveryMethods = ['email', 'manual', 'sms'];
    if (!in_array($keyDelivery, $validDeliveryMethods)) {
        $errors[] = "Invalid key delivery method";
    }
    
    // Validate password strength if provided
    if ($password !== null && !empty($password) && strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    return $errors;
}

// Generates a secure random token for share URLs
function generate_share_token(int $length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Calculates the expiry date based on days from now
function calculate_expiry_date(int $days) {
    $date = new DateTime();
    $date->add(new DateInterval("P{$days}D"));
    return $date->format('Y-m-d H:i:s');
}

//Creates a password hash for share protection
function hash_share_password(string $password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verifies a share access password
function verify_share_password(string $password, string $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

function generate_share_url(string $token) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return "{$protocol}://{$host}/s.php?token={$token}";
}

// //Generate a full share URL
// function generate_share_url(string $token) {
//     return get_share_base_url() . $token;
// }