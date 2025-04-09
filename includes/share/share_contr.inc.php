<?php
declare(strict_types=1);

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