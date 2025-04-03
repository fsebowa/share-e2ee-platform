<?php
require_once __DIR__ .  '/../config/session_manager.inc.php';

if (!is_session_verified()) {
    header("Location: /otp.php?error=you_are_not_yet_verified");
    exit();
}
session_unset();
session_destroy();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(
        session_name(),
        '',
        time() - 3600,
        '/',
        'localhost',
        true,
        true
    );
}

header("Location: /login.php?message=user_logged_out_successfully");
die();

?>