<?php

require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';
require_once __DIR__ . '/file_contr.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require __DIR__ . '/../../vendor/autoload.php';

check_login_otp_status();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // get data from POST
    $file_id = $_POST["file_id"] ?? null;
    $file_name = $_POST["file_name"] ?? null;
    $delete_phrase = $_POST["delete_phrase"] ?? null;
    $csrf_token = $_POST["csrf_token"] ?? null;
    $csrf_token_time = $_SESSION["csrf_token_time"] ?? null;
    $recaptcha_response = $_POST["g-recaptcha-response"] ?? null;
    $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';
    
    try {
        require_once __DIR__ . '/../config/dbh.inc.php';
        require_once __DIR__ . '/file_model.inc.php';

        $errors = [];
        $success = [];

        // validate inputs and error handling
        if (csrf_token_expired($csrf_token_time)) {
            $errors["csrf_token_expired"] = "Session token expired. Try again!";
        } elseif (csrf_token_invalid($csrf_token)) {
            $errors["csrf_token_invalid"] = "Invalid CSRF token";
        } elseif(is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["invalid_recaptcha"] = "The reCAPTCHA verification failed. Please try again!";
        } elseif (empty($delete_phrase) || empty($file_id)) {
            $errors["empty_inputs"] = "One or more fields are empty";
        } elseif (!empty($delete_phrase) && strtoupper($delete_phrase )!== "DELETE") {
            $errors["wrong_phrase"] = "Wrong phrase! Only pharase allowed is 'DELETE'";
        }

        // if no errors, proceed with file deletion 
        if (empty($errors)) {
            $deleteFile  = delete_file($pdo, $file_id, $_SESSION["user_id"]);
            if (!$deleteFile) {
                $errors["delete_failed"] = "An error occurred, please try again!";
            } else {
                $delete_date = date("Y-m-d H:i:s");
                
                // load email template
                $file_email_delete_template = __DIR__ . '/../templates/file-delete-template.html';
                if (is_file_email_template_missing($file_email_delete_template)) {
                    $errors["file_template_not_found"] = "Email template not found.";
                } else {
                    $file_template = file_get_contents($file_email_delete_template);
                }

                // create email template to for delete confirmation
                $file_template = str_replace('{{file_name}}', $file_name, $file_template);
                $file_template = str_replace('{{delete_date}}', $delete_date, $file_template);
                $file_template = str_replace('{{year}}', date('Y'), $file_template);

                // send delete confirmation to user
                $mail = new PHPMailer(true);
                try {
                    // server settings
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
                    $mail->addAddress($_SESSION["email"], $_SESSION["user_data"]["first_name"] . " " . $_SESSION["user_data"]["last_name"]);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'File Deletion Confirmation: ' . $file_name;
                    $mail->Body = $file_template;
                    $mail->AltBody = 'Your file was deleted successfully' . $file_name;
                    if (!$mail->send()) {
                        $errors["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
                        die();
                    } else { //send email
                        $success["deleted_successfully"] = "File Deleted Successfully";
                        $_SESSION["success_file_delete"] = $success;
                        header("Location: /dashboard.php?message=file_delete_success");
                        die();
                    }
                } catch (Exception $e) {
                    $errors["send_otp_error"] = "Error sending delete confirmation email.<br>Try again!";
                    $errors["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
                }
            }
        } 
    } catch (Exception $e) {
        $errors["system_error"] = "An error occurred: " . $e->getMessage();
        error_log("Exception in file_deletee.inc.php: " . $e->getMessage());
    }

    // redirect back to dashboard if error or success messages
    if (!empty($errors)) {
        $_SESSION["errors_file_delete"] = $errors;
        header("Location: /dashboard.php");
        exit();
    }
    if (!empty($success)) {
        $_SESSION["success_file_delete"] = $success;
        header("Location: /dashboard.php");
        exit();
    }
} else {
    header("Location: /dashboard.php");
    exit();
}