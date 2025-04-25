<?php
declare(strict_types=1);

function create_file_share(
    object $pdo,
    int $fileId,
    int $sharedBy,
    ?string $sharedWith,
    string $shareToken,
    ?string $accessPassword,
    string $expiryDate,
    ?int $maxAccessCount,
    string $keyDeliveryMethod
) {
    $query = "INSERT INTO file_shares (
                file_id,
                shared_by,
                shared_with,
                share_token,
                access_password,
                expiry_date,
                max_access_count,
                key_delivery_method
            ) VALUES (
                :file_id,
                :shared_by,
                :shared_with,
                :share_token,
                :access_password,
                :expiry_date,
                :max_access_count,
                :key_delivery_method
            )";
    
    $stmt = $pdo->prepare($query);
    
    $stmt->bindParam(":file_id", $fileId, PDO::PARAM_INT);
    $stmt->bindParam(":shared_by", $sharedBy, PDO::PARAM_INT);
    $stmt->bindParam(":shared_with", $sharedWith, PDO::PARAM_STR);
    $stmt->bindParam(":share_token", $shareToken, PDO::PARAM_STR);
    $stmt->bindParam(":access_password", $accessPassword, PDO::PARAM_STR);
    $stmt->bindParam(":expiry_date", $expiryDate, PDO::PARAM_STR);
    $stmt->bindParam(":max_access_count", $maxAccessCount, PDO::PARAM_INT);
    $stmt->bindParam(":key_delivery_method", $keyDeliveryMethod, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        return (int)$pdo->lastInsertId();
    }
    
    return false;
}

function get_file_share_by_token(object $pdo, string $shareToken) {
    $query = "SELECT s.*, f.file_name as original_name, f.original_filename, f.file_type, f.file_size, f.date_uploaded as upload_date, u.first_name, u.last_name, u.email as owner_email
                FROM file_shares s
                JOIN file_uploads f ON s.file_id = f.id
                JOIN users u ON s.shared_by = u.id
                WHERE s.share_token = :share_token AND s.is_active = TRUE";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":share_token", $shareToken, PDO::PARAM_STR);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function increment_share_access_count(object $pdo, int $shareId) {
    $query = "UPDATE file_shares 
                SET current_access_count = current_access_count + 1 
                WHERE id = :share_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":share_id", $shareId, PDO::PARAM_INT);
    
    return $stmt->execute();
}

function get_file_shares(object $pdo, int $fileId, int $userId) {
    $query = "SELECT * FROM file_shares 
                WHERE file_id = :file_id AND shared_by = :user_id
                ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":file_id", $fileId, PDO::PARAM_INT);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// function get_all_user_shared_files (object $pdo, int $user_id) {
//     $query = "SELECT * FROM file_shares WHERE shared_by = :user_id ORDER BY created_at DESC";
//     $stmt = $pdo->prepare($query);
//     $stmt->bindParam(":shared_by", $user_id, PDO::PARAM_INT);
//     $stmt->execute();
//     return $stmt->fetchAll(PDO::FETCH_ASSOC);
// }

function get_user_shared_files_with_details(object $pdo, int $user_id) {
    $query = "SELECT s.*, f.file_name, f.original_filename, f.file_type, f.file_size, f.date_uploaded,
                u.first_name, u.last_name
            FROM file_shares s
            JOIN file_uploads f ON s.file_id = f.id
            LEFT JOIN users u ON s.shared_by = u.id
            WHERE s.shared_by = :user_id 
            ORDER BY s.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deactivate_file_share(object $pdo, int $shareId, int $userId) {
    // Delete the share record completely instead of just marking as inactive
    $query = "DELETE FROM file_shares 
            WHERE id = :share_id AND shared_by = :user_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":share_id", $shareId, PDO::PARAM_INT);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    
    return $stmt->execute();
}

// Check if a share has expired or reached its access limit
function is_share_valid(array $shareData) {
    // Check if share is active
    if (!$shareData['is_active']) {
        return false;
    }
    
    // Check expiry date
    $currentTime = new DateTime();
    $expiryDate = new DateTime($shareData['expiry_date']);
    
    if ($currentTime > $expiryDate) {
        return false;
    }
    
    // Check access limit if one is set
    if ($shareData['max_access_count'] !== null && 
        $shareData['current_access_count'] >= $shareData['max_access_count']) {
        return false;
    }
    
    return true;
}

// Function to mark all expired shares as inactive (for cron job)
function mark_all_expired_shares(object $pdo) {
    $query = "UPDATE file_shares 
                SET is_active = FALSE, 
                    expiry_status = 'expired' 
                WHERE expiry_date < NOW() AND is_active = TRUE";
    
    $stmt = $pdo->prepare($query);
    return $stmt->execute();
}

function get_file_id_by_share_token(object $pdo, string $shareToken) {
    $query = "SELECT file_id FROM file_shares 
                WHERE share_token = :share_token AND is_active = TRUE";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":share_token", $shareToken, PDO::PARAM_STR);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? (int)$result['file_id'] : false;
}