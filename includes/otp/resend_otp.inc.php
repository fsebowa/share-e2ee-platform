<?php

require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../config/dbh.inc.php';
require_once __DIR__ . '/otp_model.inc.php';
require_once __DIR__ . '/otp_contr.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION["user_id"])) {
        $_SESSION["errors_otp"] = ["Invalid user session"];
        header("Location: /login.php?error=invalid_user_session");
        die();
    } 

    // get user details from session
    $user_id = $_SESSION["user_id"];
    $email = $_SESSION["email"];
    $first_name = $_SESSION["first_name"];
    $last_name = $_SESSION["last_name"];
    $full_name = $first_name ." ". $last_name;

    try {
        // Generate OTP code
        $otp_code = rand(100000, 999999);
        $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minute"));
        
        if (file_exists(__DIR__ . '/../templates/otp-mail-template.html')) {
            $template = file_get_contents(__DIR__ . '/../templates/otp-mail-template.html');
        } else {
            $_SESSION["errors_otp"] = ["Email template not found."];
            header("Location: /otp.php?error=otp_fail_send");
            die();
        }

        $template = str_replace('{{otp_code}}', $otp_code, $template);
        $template = str_replace('{{otp_expiry}}', $otp_expiry, $template);
        $template = str_replace('{{year}}', date('Y'), $template);

        // Send OTP to user
        $mail = new PHPMailer(true);
        try {
            // server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Port = 465;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //encryption mechanism
            $mail->SMTPAuth = true;
            $mail->Username = 'share.e2eeplatform@gmail.com';
            $mail->Password = 'vczc yawy voeo nkbk';

            // Recepients
            $mail->setFrom('otp-verification@share.com', 'Share E2EE Platform');
            $mail->addReplyTo('no-reply@share.com', 'Share E2EE Platform');
            $mail->addAddress($email, $full_name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'OTP Verification Code';
            $mail->Body = $template;
            $mail->AltBody = 'Enter the code below to complete the sign in request'. $otp_code;

            //send the otp, check for errors
            if (!$mail->send()) {
                $_SESSION["errors_otp"]["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
                header("Location: /otp.php?error=otp_fail_send");
                die();
            } else { // send the email  
                request_new_otp($pdo, $user_id, $otp_code, $otp_expiry);
                $_SESSION["success_otp"] = ["A new OTP code has been sent"];
                header("Location: /otp.php?message=new_otp_sent_to_email"); // redirect to opt page
                
                $pdo = null; //close the db connection
                $stmt = null;
                die();
            }
        } catch (Exception $e){
            $_SESSION["errors_otp"]["send_otp_error"] = "Error sending OTP.<br>Try again!";
            $_SESSION["errors_otp"]["mailer_error"] = "Mailer Error: ". $mail->ErrorInfo;
            header("Location: /otp.php");
            die();
        }
    } catch (PDOException $e) {
        $_SESSION["errors_otp"]["database_error"] = "Database error: " . $e->getMessage();
        header("Location: /otp.php");
        die();
    }
    
} else {
    header("Location: /login.php?error=invalid_session");
    die();
}