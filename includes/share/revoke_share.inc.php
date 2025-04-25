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
        
        // Get share info before deactivation for logging purposes
        $query = "SELECT s.*, f.file_name 
                  FROM file_shares s
                  JOIN file_uploads f ON s.file_id = f.id
                  WHERE s.id = :share_id AND s.shared_by = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":share_id", $shareId, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $stmt->execute();
        $shareInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shareInfo) {
            $_SESSION['share_revoke_error'] = "Share not found or you don't have permission to revoke it.";
            header("Location: /shared.php?error=share_not_found");
            exit();
        }
        
        // Log the revocation
        error_log("User {$userId} is revoking share {$shareId} for file: {$shareInfo['file_name']}");
        
        // Deactivate the share
        $result = deactivate_file_share($pdo, (int)$shareId, $userId);
        
        if ($result) {
            // Store the revoked share ID in session for the JavaScript to use
            $_SESSION['revoked_share_id'] = $shareId;
            $_SESSION['share_revoke_success'] = "Share link has been successfully revoked.";
            header("Location: /shared.php?message=share_revoked&share_id=" . $shareId);
        } else {
            $_SESSION['share_revoke_error'] = "Failed to revoke share. Please try again.";
            header("Location: /shared.php?error=revoke_failed");
        }
    } catch (PDOException $e) {
        error_log("Share revocation failed: " . $e->getMessage());
        $_SESSION['share_revoke_error'] = "An unexpected error occurred. Please try again later.";
        header("Location: /shared.php?error=database_error");
    } catch (Exception $e) {
        error_log("Share revocation exception: " . $e->getMessage());
        $_SESSION['share_revoke_error'] = "An unexpected error occurred. Please try again later.";
        header("Location: /shared.php?error=system_error");
    }
} else {
    // Not a POST request, redirect to shared files page
    header("Location: /shared.php");
}
exit();