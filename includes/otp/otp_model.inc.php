<?php

declare(strict_types=1);

function get_otp_verification(object $pdo, string $otp_code) {
    $query = "SELECT * FROM otp_verification WHERE otp_code = :otp_code;";
    $stmt = $pdo->prepare($query); // prevents SQL injection attacks
    $stmt->bindParam(":otp_code", $otp_code);
    $stmt->execute();

    $opt_result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $opt_result;
} 

function get_user(object $pdo, string $user_id) {
    $query = "SELECT * FROM users WHERE user_id = :user_id;";
    $stmt = $pdo->prepare($query); // prevents SQL injections
    $stmt->bindParam(":email", $user_id);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

function request_new_otp(object $pdo, string $user_id, string $otp_code, string $otp_expiry) {
    $query = "UPDATE otp_verification SET otp_code=:otp_code, otp_expiry=:otp_expiry WHERE user_id = :user_id;";
    $stmt = $pdo->prepare($query); // prevents SQL injections
    
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":otp_code", $otp_code);
    $stmt->bindParam(":otp_expiry", $otp_expiry);
    $stmt->execute();
}

function is_otp_invalid() {
    $query = "SELECT * FROM otp_codes WHERE user_id = 1 AND otp_code = '123456' AND otp_expiry > NOW()";
}

function delete_otp() {
    $query ="DELETE FROM otp_codes WHERE user_id = 1 AND otp_code = '123456';";
}