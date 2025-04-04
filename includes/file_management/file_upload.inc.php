<?php 
require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require __DIR__ . '/../../vendor/autoload.php';

check_login_otp_status(); // check if user is logged in and OTP is verified

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $file_name = $_POST["file_name"] ?? null;
    $encryption_key = $_POST["key"] ?? null;
    $file = $_FILES["file"] ?? null;
    $recaptcha_response = $_POST["g-recaptcha-response"] ?? null;
    $secretKey = '6LfncLgqAAAAAKefUSncQyC01BjUaUTclJ5dXEqb';

    try {
        require_once __DIR__ . '/../config/dbh.inc.php';
        require_once __DIR__ . '/file_model.inc.php';
        require_once __DIR__ . '/file_contr.inc.php';
        require_once __DIR__ . '/../encryption/file_encryption.inc.php';

        // error handling
        $errors = [];
        $success = [];
        $hasUploadErrors = false;

        $stored_file_details = get_user_files($pdo, $_SESSION["user_id"]);
        $get_stored_file_name = array_map('strtolower', array_column($stored_file_details, 'file_name'));

        if (is_recaptcha_invalid($secretKey, $recaptcha_response)) {
            $errors["recaptcha_invalid"] = "The reCAPTCHA verification failed. Please try again!";
        } elseif (is_input_empty($file_name, $encryption_key, $file)) {
            $errors["empty_input"] = "One or more fields are empty";
        } else {
            if (isset($file["name"]) && file_exists_in_db($pdo, $_SESSION["user_id"], $file["name"])) {
                $errors["file_exist"] = "File already exists, upload a different file";
            }
            if (in_array(strtolower($file_name), $get_stored_file_name)) {
                $errors["filename_in_use"] = "File name already in use. Try a different name!";
            }
            if (is_key_length_invalid($encryption_key)) {
                $errors["invalid_key"] = "Encryption key must be exactly 64 hex characters long";
            }
            if (is_file_too_large($file)) {
                $errors["file_too_large"] = "File is too large. Maximum allowed size is 20MB.";
            }
            if (is_file_type_invalid($file)) {
                $errors["file_type_invalid"] = "This file type is not supported.";
            }
        }
        
        if (empty($errors)) {
            // intialise encryption service
            $encryption_service = new FileEncryptionService();

            // load email template
            $file_email_upload_template = __DIR__ . '/../templates/file-upload-template.html';
            if (is_file_email_template_missing($file_email_upload_template)) {
                $errors["file_template_not_found"] = "Email template not found.";
            } else {
                $file_template = file_get_contents($file_email_upload_template);
            }

            // create uploads directory if doesn't exist
            $upload_directory = __DIR__ . '/../../uploads/' . $_SESSION["user_id"] . '/';
            if (!is_dir($upload_directory)) {
                mkdir($upload_directory, 0755, true);
            }

            // get original filename and determine file type
            $original_filename = $file["name"];
            $file_type = $encryption_service->get_file_type($file["tmp_name"]);
            $file_size = $file["size"];

            // Convert the hex key to binary for encryption
            $binary_key = hex2bin($encryption_key);

            // encrypt the file
            $encrypted_file = $encryption_service->encrypt_file(
                $file["tmp_name"],
                $binary_key,
                $upload_directory
            );

            if (!$encrypted_file) {
                $errors["encryption_failed"] = "Failed to encrypt file. Please try again!";
            } else {
                // save file metadata to database
                $file_id = save_file_metadata(
                    $pdo,
                    $_SESSION["user_id"],
                    $file_name,
                    $encrypted_file["path"],
                    $original_filename,
                    $file_size,
                    $file_type
                );

                if (!$file_id) {
                    $errors["databse_error"] = "Failed to save file metadata. Please try again.";
                } else {
                    $upload_date = date("Y-m-d H:i:s");
                    // Create email template with encryption key
                    $file_template = str_replace('{{file_name}}', $file_name, $file_template);
                    $file_template = str_replace('{{original_filename}}', $original_filename, $file_template);
                    $file_template = str_replace('{{file_size}}', number_format($file_size / 1024 /1024, 2), $file_template);
                    $file_template = str_replace('{{upload_date}}', $upload_date, $file_template);
                    $file_template = str_replace('{{encryption_key}}', $encryption_key, $file_template);
                    $file_template = str_replace('{{year}}', date('Y'), $file_template);

                    // get user email
                    $user_email = get_user_email_by_id($pdo, $_SESSION["user_id"]);

                    // send file upload confirmation to user with encryption key
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
                        $mail->addAddress($user_email, $_SESSION["user_data"]["first_name"] . " " . $_SESSION["user_data"]["last_name"]);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'File Upload Confirmation: ' . $file_name;
                        $mail->Body = $file_template;
                        $mail->AltBody = 'Here is your encryption key for the file uploaded' . $encryption_key;
                        if (!$mail->send()) {
                            $errors["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
                            die();
                        } else { //send email
                            $success["upload_success"] = "File Uploaded Successfully. Check your email for the file's encryption key";
                            $_SESSION["success_file"] = $success;
                            header("Location: /dashboard.php?message=file_upload_success");
                            die();
                        }
                    } catch (Exception $e) {
                        $errors["upload_file_error"] = "Error sending upload confirmation email.<br>Try again!";
                        $errors["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
                    }
                }
            }
        }
        
        if ($errors) {
            $_SESSION["errors_file_upload"] = $errors;
            $_SESSION["upload_data"] = [
                "file_name" => $file_name
            ];
            $hasUploadErrors = true;
            header("Location: /dashboard.php");
            die();
        }
        if ($success) {
            $_SESSION["success_file"] = $success;
            header("Location: /dashboard.php");
            die();
        }

    } catch (Exception $e) {
        $errors["email_error"] = "Error sending email: " . $mail->ErrorInfo;
    }
} else {
    header("Location: /dashboard.php");
    die();
}