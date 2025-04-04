<?php

declare(strict_types=1);

// this file queries the database with file related matters

function save_file_metadata(object $pdo, int $user_id, string $file_name, string $file_path, string $original_filename, int $file_size, string $file_type) {
    $query = "INSERT INTO file_uploads (user_id, file_name, file_path, original_filename, file_size, file_type) 
                VALUES (:user_id, :file_name, :file_path, :original_filename, :file_size, :file_type)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":file_name", $file_name);
    $stmt->bindParam(":file_path", $file_path);
    $stmt->bindParam(":original_filename", $original_filename);
    $stmt->bindParam(":file_size", $file_size);
    $stmt->bindParam(":file_type", $file_type);

    $stmt->execute();

    return $pdo->lastInsertId();
}

function get_user_email_by_id(object $pdo, int $user_id) {
    $query = "SELECT email FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result["email"] ?? null;
}

function get_file_id(object $pdo, int $user_id) {
    $query = "SELECT id FROM file_uploads WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

function get_user_files(object $pdo, int $user_id) {
    $query = "SELECT id, file_name, original_filename, file_path, file_type, file_size, date_uploaded 
                FROM file_uploads
                WHERE user_id = :user_id
                ORDER BY date_uploaded DESC";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function delete_file(object $pdo, int $file_id, int $user_id) {
    $query = "SELECT file_path FROM file_uploads WHERE id = :file_id AND user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":file_id", $file_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file && file_exists(($file["file_path"]))) {
        unlink($file["file_path"]); // delete actual file

        // delete database record
        $query = "DELETE FROM file_uploads WHERE id = :file_id AND user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":file_id", $file_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return true;
    }
    return false;
}

function file_exists_in_db(object $pdo, int $user_id, string $original_filename): bool {
    $query = "SELECT COUNT(*) FROM file_uploads WHERE user_id = :user_id AND original_filename = :original_filename";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":original_filename", $original_filename);
    $stmt->execute();

    return $stmt->fetchColumn() > 0;
}

function get_file_by_id(object $pdo, int $file_id, int $user_id) {
    $query = "SELECT id, file_name, original_filename, file_path, file_type, file_size, date_uploaded
                FROM file_uploads
                WHERE id = :file_id AND user_id = :user_id
                LIMIT 1";
    $stmt= $pdo->prepare($query);
    $stmt->bindParam(":file_id", $file_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function logFileDownload(object $pdo, int $file_id, int $user_id, string $download_type) {
    $query = "INSERT INTO file_downloads (file_id, user_id, download_type, download_date) 
            VALUES (:file_id, :user_id, :download_type, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":file_id", $file_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":download_type", $download_type);
    $stmt->execute();
}