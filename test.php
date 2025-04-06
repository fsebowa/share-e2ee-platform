<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Test database connection
require_once 'includes/config/dbh.inc.php';
echo "DB Connection Test: ";
if (isset($pdo) && $pdo instanceof PDO) {
    echo "SUCCESS<br>";
} else {
    echo "FAILED<br>";
}

// Test session
session_start();
$_SESSION['test'] = 'Working';
echo "Session Test: ";
if ($_SESSION['test'] === 'Working') {
    echo "SUCCESS<br>";
} else {
    echo "FAILED<br>";
}

// Test password function
echo "Password Verify Test: ";
$hash = password_hash("test123", PASSWORD_DEFAULT);
if (password_verify("test123", $hash)) {
    echo "SUCCESS<br>";
    echo $hash;
} else {
    echo "FAILED<br>";
}