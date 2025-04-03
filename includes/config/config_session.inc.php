<?php

ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 1800); // 30 minutes

// Set a session name to avoid using the default 'PHPSESSID'
session_name('SECURE_SESSION');

session_set_cookie_params([
    'lifetime' => 1800,
    'domain' => 'localhost',
    'path' => '/',
    'secure' => true,
    'httponly' => true
]);


// Check if a session is already active before starting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// generating CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
}

// make token accessible to other pages
$token = $_SESSION['csrf_token'];

// Regenerate session ID periodically for security
$interval = 60 * 30; // 30 minutes
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] >= $interval)) { // 24 hours
    
    // Check if session_manager is already included
    if (!function_exists('regenerate_session_safely')) {
        require_once __DIR__ . '/session_manager.inc.php';
    }
    regenerate_session_safely();
}

// generate a random 256-bit key for AES-256-GCM encryption
function generate_key() {
    $key = random_bytes(32); // equals to 256 bits
    return $key;
}

// Generate a secure random 32-byte key and return a hex representation for display and storage purposes
function generate_key_hex() {
    $key = random_bytes(32); 
    return bin2hex($key);  // Convert to hex 
}

// if request is an AJAX request, generate a key
if (isset($_GET['action']) && $_GET['action'] === 'generate_key') {
    $key_hex = generate_key_hex();
    echo $key_hex;
    exit();
}