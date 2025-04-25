<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/../auth/auth_checker.inc.php';

// Ensure user is logged in and verified
if (!check_login_otp_status()) {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrf_token = $_POST["csrf_token"] ?? '';
    $shareId = $_POST["share_id"] ?? 0;
    
    // Validate inputs
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token'] || empty($shareId)) {
        $_SESSION['share_revoke_error'] = "Invalid request. Please try again.";
        header("Location: /shared.php?error=invalid_request");
        exit();
    }
    
    try {
        require_once __DIR__ . '/../config/dbh.inc.php';
        require_once __DIR__ . '/share_model.inc.php';
        
        // Get user ID from session
        $userId = $_SESSION["user_id"];
        
        // Log the request
        error_log("User {$userId} is attempting to delete share {$shareId}");
        
        // Delete the share from the database
        $result = deactivate_file_share($pdo, (int)$shareId, $userId);
        
        if ($result) {
            // Store the deleted share ID in session for the JavaScript to use
            $_SESSION['revoked_share_id'] = $shareId;
            $_SESSION['share_revoke_success'] = "Share link has been permanently deleted.";
            
            // Check if this is an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true]);
                exit;
            }
            
            // Regular form submission - redirect with success message
            header("Location: /shared.php?message=share_deleted&share_id=" . $shareId);
        } else {
            $_SESSION['share_revoke_error'] = "Failed to delete share. Please try again.";
            
            // Check if this is an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => false, 'error' => 'Failed to delete share']);
                exit;
            }
            
            // Regular form submission - redirect with error
            header("Location: /shared.php?error=delete_failed");
        }
    } catch (PDOException $e) {
        error_log("Share deletion failed: " . $e->getMessage());
        $_SESSION['share_revoke_error'] = "An unexpected error occurred. Please try again later.";
        
        // Check if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'error' => 'Database error']);
            exit;
        }
        
        header("Location: /shared.php?error=database_error");
    } catch (Exception $e) {
        error_log("Share deletion exception: " . $e->getMessage());
        $_SESSION['share_revoke_error'] = "An unexpected error occurred. Please try again later.";
        
        // Check if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'error' => 'System error']);
            exit;
        }
        
        header("Location: /shared.php?error=system_error");
    }
} else {
    // Not a POST request, redirect to shared files page
    header("Location: /shared.php");
}
exit();