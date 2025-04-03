<?php

declare(strict_types=1);

function get_user(object $pdo, string $email) {
    // $query = "SELECT * FROM users WHERE email = :email;";
    $query = "SELECT id, email, password, first_name, last_name FROM users WHERE email = :email LIMIT 1;";
    $stmt = $pdo->prepare($query); // prevents SQL injections
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

function set_otp_code(object $pdo, string $user_id, string $otp_code, string $otp_expiry) {
    $query = "INSERT INTO otp_verification (user_id, otp_code, otp_expiry) VALUES (:user_id, :otp_code, :otp_expiry) ON DUPLICATE KEY UPDATE otp_code = :otp_code, otp_expiry = :otp_expiry";
    $stmt = $pdo->prepare($query);
    
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":otp_code", $otp_code);
    $stmt->bindParam(":otp_expiry", $otp_expiry);
    $stmt->execute();
}

