<?php

require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../encryption/encryption_service.inc.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Initialize encryption service
    $encryptionService = new EncryptionService();
    $encryptedData = $_POST["encrypted_data"] ?? null;
    $csrf_token = $_POST["csrf_token"];
    $csrf_token_time = $_SESSION["csrf_token_time"];
    $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';
    $recaptcha_response = $_POST["g-recaptcha-response"];

    try {
        require_once __DIR__. '/../config/dbh.inc.php';
        require_once __DIR__. '/otp_model.inc.php';
        require_once __DIR__. '/otp_contr.inc.php';

        // Error handling
        $errors = [];
        $success = [];

        if (csrf_token_expired($csrf_token_time)) {
            $errors["csrf_token_expired"] = "Session token expired. Try again!";
        } elseif (csrf_token_invalid($csrf_token)) {
            $errors["csrf_token_invalid"] = "Invalid CSRF token";
        } elseif (is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["recaptcha_invalid"] = "The reCAPTCHA verification failed. Please try again!";
        } else {
            // Decrypt the data and validate otp input
            $decryptedData = $encryptionService->decryptFormData($encryptedData);
            if (!$decryptedData) {
                $errors["decryption_error"] = "Error decrypting form data. Please try again!";
            } else {
                $otp_code = $decryptedData["otp_code"] ?? '';
                if (is_input_empty($otp_code)) {
                    $errors["empty_input"] = "Enter an OTP to continue";
                }
            }           
        }

        if(empty($errors)) {
            $otp_result = get_otp_verification($pdo, $otp_code);
            $otp_expiry = $otp_result["otp_expiry"];
            $current_time = date("Y-m-d H:i:s", time());

            if (is_otp_wrong($otp_result)) {
                $errors["wrong_otp"] = "Invalid OTP Code";
            }
            if (!is_otp_wrong($otp_result) && has_otp_expired($current_time, $otp_expiry)) {
                $errors["expired_code"] = "Your OTP code has expired <br> Click Resend Code to generate a new one";
            }
        }

        if ($errors) {
            $_SESSION["errors_otp"] = $errors;
            header("Location: /otp.php");
            die();
        }
        if ($success) {
            $_SESSION["success_otp"] = $success;
            header("Location: /otp.php");
            die();
        }

        $userId = $otp_result["user_id"];
        require_once __DIR__ . '/../config/session_manager.inc.php';
        mark_session_verified($userId); // marks session as verified
        
        // Login the user and send to Dashboard
        header("Location: /dashboard.php");

        $pdo = null;
        $stmt = null;
        die();

    } catch (PDOException $e) {
        die("Submission Query failed: ". $e->getMessage());
    }

} else {
    header("Location: /login.php");
}