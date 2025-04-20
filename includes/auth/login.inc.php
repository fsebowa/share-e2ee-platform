<?php
require_once __DIR__ .  '/../config/config_session.inc.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/../../vendor/autoload.php';
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
        require_once __DIR__ . '/../config/dbh.inc.php';
        require_once __DIR__ . '/login_model.inc.php';
        require_once __DIR__ . '/login_contr.inc.php';

        // Error handling
        $errors = [];

        // Check CSRF token and recaptcha
        if (csrf_token_expired($csrf_token_time)) {
            $errors["csrf_token_expired"] = "Session token expired. Try again!";
        } elseif (csrf_token_invalid($csrf_token)) {
            $errors["csrf_token_invalid"] = "Invalid CSRF token";
        } elseif (is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["recaptch_invalid"] = "The reCAPTCHA verification failed. Please try again!";
        } elseif (!$encryptedData) {
            $errors["encryption_error"] = "Failed to process encrypted data. Please try again!";
        } else {
            // Decrypt the data
            $decryptedData = $encryptionService->decryptFormData($encryptedData);

            if (!$decryptedData) {
                $errors["decryption_error"] = "Error decrypting form data. Please try again!";
            } else {
                $email = $decryptedData["email"] ?? '';
                $password = $decryptedData["password"] ?? '';
                
                if (is_input_empty($email, $password)) {
                    $errors["empty_input"] = "One or more fields are empty";
                } elseif (is_email_valid($email)) { 
                    $errors["invalid_email"] = "Invalid email";
                } else {
                    $result = get_user($pdo, $email);
                    $user_id = $result["id"] ?? null;
                    
                    if (!$user_id) {
                        $errors["email_not_registered"] = "Wrong email or password!"; // User not found
                    } else {
                        $first_name = $result["first_name"];
                        $last_name = $result["last_name"];
                        $full_name = $first_name . " " . $last_name;
                        
                        if (is_user_wrong($result)) {
                            $errors["email_not_registered"] = "Wrong email or password!";
                        }
                        if (!is_user_wrong($result) && is_password_wrong($password, $result["password"])) {
                            $errors["incorrect_login"] = "Wrong email or password!";
                        }
                    }
                }
            }
        }
        
        if (empty($errors)) {
            // Loading email template
            if (file_exists(__DIR__ . '/../templates/otp-mail-template.html')) {
                $template = file_get_contents(__DIR__ . '/../templates/otp-mail-template.html');
            } else {
                $errors["email_template_not_found"] = "Email template not found.";
            }
            
            // Generate OTP code
            $otp_code = rand(100000, 999999);
            $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minute"));
            $template = str_replace('{{otp_code}}', $otp_code, $template);
            $template = str_replace('{{otp_expiry}}', $otp_expiry, $template);
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
                $mail->addAddress($email, $full_name);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'OTP Verification Code';
                $mail->Body = $template;
                $mail->AltBody = 'Enter the code below to complete the sign in request' . $otp_code;
                
                // Send the OTP, check for errors
                if (!$mail->send()) {
                    $errors["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
                    exit();
                } else { // Send the email
                    require_once __DIR__ . '/../config/session_manager.inc.php';
                    $userData = [
                        "email" => $result["email"],
                        "first_name" => htmlspecialchars($result["first_name"]),
                        "last_name" => htmlspecialchars($result["last_name"])
                    ];
                    initialise_user_session($result["id"], $userData);

                    // Insert OTP code in database
                    create_otp_code($pdo, $user_id, $otp_code, $otp_expiry);
                    header("Location: /otp.php?message=otp_sent_to_email"); // Redirect to OTP page
                    
                    $pdo = null; // Close the db connection
                    $stmt = null;
                    die();
                }
            } catch (Exception $e) {
                $errors["send_otp_error"] = "Error sending OTP.<br>Try again!";
                $errors["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
            }
        }

        if ($errors) {
            $_SESSION["errors_login"] = $errors;

            $loginData = [
                "email" => $email ?? ''
            ];            
            $_SESSION["login_data"] = $loginData;

            header("Location: /login.php");
            die();
        }

    } catch (PDOException $e) {
        die("Submission Query failed: ". $e->getMessage());
    }
} else {
    header("Location: /login.php");
    die();
}