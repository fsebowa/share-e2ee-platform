<?php 
require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';
require_once __DIR__ . '/../encryption/file_encryption.inc.php';
require_once __DIR__ . '/file_contr.inc.php';

check_login_otp_status();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // get data from POST
    $file_id = $_POST["file_id"] ?? null;
    $delete_phrase = $_POST["delete_phrase"] ?? null;
    $csrf_token = $_POST["csrf_token"] ?? null;
    $csrf_token_time = $_SESSION["csrf_token_time"] ?? null;
    $recaptcha_response = $_POST["g-recaptcha-response"] ?? null;
    $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';

    if (csrf_token_expired($csrf_token_time)) {
        $errors["csrf_token_expired"] = "Session token expired. Try again!";
    } elseif (csrf_token_invalid($csrf_token)) {
        $errors["csrf_token_invalid"] = "Invalid CSRF token";
    } elseif(is_recaptcha_invalid($secretKey, $recaptcha_response)) {
        $errors["invalid_recaptcha"] = "The reCAPTCHA verification failed. Please try again!";
    }



} else {
    header("Location: /dashboard.php");
    exit();
}