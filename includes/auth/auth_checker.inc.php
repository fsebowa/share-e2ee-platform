<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session_manager.inc.php';

function check_login_otp_status() {
    // check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        header("Location: /login.php?error=not_logged_in");
        exit();
    }

    // Check if OTP is verified
    if (!is_session_verified()) {
        header("Location: /otp.php?error=complete_otp_verification_to_continue");
        exit();
    }

    // Check for session timeout
    if (!check_session_timeout(1800)) { // 30 minutes
        $_SESSION["errors_login"] = ["Your session timedout"];
        header("Location: /login.php?message=session_timedout_you_have_been_logged_out");
        exit();
    }
    
    // User is logged in and OTP verified - continue
    return true;
}

function check_logged_in_redirect_from_otp() {
    // If not logged in, redirect to login page
    if (!isset($_SESSION["user_id"])) {
        header("Location: /login.php?error=you_are_not_logged_in");
        exit();
    }
    
    // If already verified, redirect to dashboard
    if (is_session_verified()) {
        header("Location: /dashboard.php?message=you_are_already_logged_in_with_otp_verification");
        exit();
    }
    
    // User is logged in but not verified - stay on OTP page
    return true;
}

?>