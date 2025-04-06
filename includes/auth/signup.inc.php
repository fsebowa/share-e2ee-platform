<?php
require_once __DIR__ .  '/../config/config_session.inc.php';
require_once __DIR__ . '/../encryption/encryption_service.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // initilising encryption service
    $encryptionService = new EncryptionService();
    $encryptedData = $_POST["encrypted_data"] ?? null;
    $csrf_token = $_POST["csrf_token"];
    $csrf_token_time = $_SESSION['csrf_token_time'];
    $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';
    $recaptcha_response = $_POST["g-recaptcha-response"];

    try {
        require_once __DIR__ . '/../config/dbh.inc.php';
        require_once __DIR__ . '/signup_model.inc.php';
        require_once __DIR__ .'/signup_contr.inc.php';

        // Error handling
        $errors = [];

        if (csrf_token_expired($csrf_token_time)) {
            $errors["csrf_token_expired"] = "Session token expired. Try again!";
        } elseif (csrf_token_invalid($csrf_token)) {
            $errors["csrf_token_invalid"] = "Invalid CSRF token";
        } elseif (is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["recaptcha_invalid"] = "The reCAPTCHA verification failed. Please try again!";
        } elseif (!$encryptedData) {
            $errors["encryption_error"] = "Failed to process encrypted form data. Please try again!";
        } else {
            // decrypt sensitive data
            $decryptedData = $encryptionService->decryptFormData($encryptedData); 

            if (!$decryptedData) {
                $errors["decryption_error"] = "Failed to decrypt form data. Please try again";
            } else { // extract sensitive info from decrypted data
                $first_name = $decryptedData["first-name"] ?? '';
                $last_name =$decryptedData["last-name"] ?? '';
                $email = $decryptedData["email"] ?? '';
                $password = $decryptedData["password"] ?? '';
                $confirm_password = $decryptedData["confirm-password"] ?? '';

                // validate all inputs
                if (is_input_empty($first_name, $last_name, $email, $password, $confirm_password)) {
                    $errors["empty_input"] = "One or more fields are empty";
                } else { // proceed with other checks if inputs are filled
                    if (is_email_valid($email)) {
                        $errors["invalid_email"] = "Invalid email";
                    }
                    if (is_email_registered($pdo, $email)) {
                        $errors["email_used"] = "Email already registered!";
                    }
                    if (password_length($password)) {
                        $errors["password_short"] = "Password should be greater than 6 characters";
                    } elseif (password_match($password, $confirm_password)) {
                        $errors["password_match_failed"] = "Passwords do not match";
                    }
                }
            }
        }

        if (empty($errors)) {

            // Loading email template
            if (file_exists(__DIR__ . '/../templates/registration-confirmation.html')) {
                $template = file_get_contents(__DIR__ . '/../templates/registration-confirmation.html');
            } else {
                $errors["email_template_not_found"] = "Email template not found.";
            }
            
            // Render template
            $creation_date = date("d/m/Y H:i:s");
            $template = str_replace('{{creation_date}}', $creation_date, $template);
            $template = str_replace('{{first_name}}', $first_name, $template);
            $template = str_replace('{{last_name}}', $last_name, $template);
            $template = str_replace('{{email}}', $email, $template);
            $template = str_replace('{{year}}', date('Y'), $template);

            // Send OTP to user
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->Port = 465;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Encryption mechanism
                $mail->SMTPAuth = true;
                $mail->Username = 'share.e2eeplatform@gmail.com';
                $mail->Password = 'vczc yawy voeo nkbk';

                // Recipients
                $mail->setFrom('otp-verification@share.com', 'Share E2EE Platform');
                $mail->addReplyTo('no-reply@share.com', 'Share E2EE Platform');
                $mail->addAddress($email, $first_name . ' ' . $last_name);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Account Registration';
                $mail->Body = $template;
                $mail->AltBody = 'Your account on Share was successfully registered on ' . $creation_date;
                
                // Send the OTP, check for errors
                if (!$mail->send()) {
                    $errors["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
                    exit();
                } else { // Send the email
                    create_user($pdo, $first_name, $last_name, $email, $password);
                    // Set temporary session data for login page
                    $_SESSION["login_data"] = [
                        "first_name" => $first_name,
                        "email" => $email,
                        "registered" => true
                    ];
                    header("Location: /login.php?messege=user_created_successfully_login_to_continue");
                    $pdo = null;
                    $stmt = null;
                    die();
                }
            } catch (Exception $e) {
                $errors["send_otp_error"] = "Error sending OTP.<br>Try again!";
                $errors["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
            }
        }
        
        if ($errors) {
            $_SESSION["errors_signup"] = $errors;

            $signupData = [
                "first_name" => $first_name,
                "last_name" => $last_name,
                "email" => $email ?? '',
            ];            
            $_SESSION["signup_data"] = $signupData;
            header("Location: /signup.php");
            die();
        }
    } catch (PDOException $e) {
        die("Submission Query failed: ". $e->getMessage());
    }
}   else {
    header("Location: /login.php");
    die();
}