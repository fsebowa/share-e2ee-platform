<?php
require_once __DIR__ . '/../../includes/config/config_session.inc.php';

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_SERVER['HTTP_X_CSRF_TOKEN']) && 
    $_SERVER['HTTP_X_CSRF_TOKEN'] === $_SESSION['csrf_token']) {
    
    // Clean up share session variables
    unset($_SESSION['share_success']);
    unset($_SESSION['share_url']);
    unset($_SESSION['share_decryption_key']);
    unset($_SESSION['share_expiry']);
    unset($_SESSION['share_recipient']);
    unset($_SESSION['share_file_name']);
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>