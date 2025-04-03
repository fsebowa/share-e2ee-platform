<?php

// Endpoint that returns the server's public key for client-side encryption

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config_session.inc.php';
require_once __DIR__ . '/encryption_service.inc.php';

try {
    $encryptionService = new EncryptionService();
    $publicKey = $encryptionService->getPublicKey();

    echo json_encode([
        'success' => true,
        'publicKey' => $publicKey
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve public key'
    ]);
}