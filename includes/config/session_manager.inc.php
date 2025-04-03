<?php
declare(strict_types=1);
// This file manages sessions

require_once __DIR__ . '/config_session.inc.php';

// OTP verified sessions
function mark_session_verified(int $userId) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['otp_verified'] = true;
    $_SESSION['last_activity'] = time();

    // modify session ID for additional security
    if (!str_contains(session_id(), "_verified")) {
        $sessionData = $_SESSION; // Backup session data
        session_write_close(); // Close current session before setting new ID

        $newSessionId = session_create_id();
        $sessionId = $newSessionId . "_" . $userId . "_verified";
        session_id($sessionId);
        session_start();

        $_SESSION = $sessionData;
        $_SESSION['custom_session_created'] = time();
    }
}

// checks if a session is verified
function is_session_verified() {
    return isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true;
}

// initialize user session after login
function initialise_user_session($userId, $userData) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['otp_verified'] = false;
    $_SESSION['last_activity'] = time();

    // add other user data to session
    foreach ($userData as $key => $value) {
        $_SESSION[$key] = $value;
    }
}

// checking session timeout
function check_session_timeout($timeout_seconds = 1800) {
    if (!isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_seconds)) {
        // session expired
        session_unset();
        session_destroy();
        return false;
    }
    // udpate last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

// Regenerate session ID safely
function regenerate_session_safely() {
    $sessionData = $_SESSION; // backup session data
    session_regenerate_id(true);
    $_SESSION = $sessionData; // restore session data
    $_SESSION['last_regeneration'] = time();
}
?>