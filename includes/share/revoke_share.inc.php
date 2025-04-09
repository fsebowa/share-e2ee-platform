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
        header("Location: /dashboard.php?error=invalid_request");
        exit();
    }
    try {
        require_once __DIR__ . '/../config/dbh.inc.php';
        require_once __DIR__ . '/share_model.inc.php';
        // Get user ID from session
        $userId = $_SESSION["user_id"];
        // Deactivate the share
        $result = deactivate_file_share($pdo, (int)$shareId, $userId);
        if ($result) {
            $_SESSION['share_revoke_success'] = "Share has been successfully revoked.";
            header("Location: /dashboard.php?message=share_revoked");
        } else {
            $_SESSION['share_revoke_error'] = "Failed to revoke share. Please try again.";
            header("Location: /dashboard.php?error=revoke_failed");
        }
    } catch (PDOException $e) {
        error_log("Share revocation failed: " . $e->getMessage());
        $_SESSION['share_revoke_error'] = "An unexpected error occurred. Please try again later.";
        header("Location: /dashboard.php?error=database_error");
    }
} else {
    header("Location: /dashboard.php");
}
exit();