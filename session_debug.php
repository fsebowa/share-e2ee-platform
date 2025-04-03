<?php
// Save this as session_debug.php in your project root
require_once __DIR__ . '/includes/config/config_session.inc.php';

// Maximum error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Session Debug Information</h1>";

// Display current session ID
echo "<h2>Session ID</h2>";
echo session_id();

// Display all session variables
echo "<h2>All Session Variables</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check for the user_id specifically
echo "<h2>User Authentication Check</h2>";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . " (FOUND)";
} else {
    echo "user_id not found in session!<br>";
    echo "Available keys: " . implode(", ", array_keys($_SESSION));
    
    // Look for other potential user identifiers
    $potential_keys = ['userId', 'uid', 'id', 'userID', 'user', 'userid'];
    $found = false;
    
    foreach ($potential_keys as $key) {
        if (isset($_SESSION[$key])) {
            echo "<br>Found potential user ID in key '$key': " . $_SESSION[$key];
            $found = true;
        }
    }
    
    if (!$found) {
        echo "<br>No obvious user ID keys found in session.";
    }
}

// Option to create a temporary user_id for testing
echo "<h2>Test Options</h2>";
echo "<form method='post' action=''>";
echo "<button type='submit' name='create_test_user' value='1'>Create Temporary User ID for Testing</button>";
echo "</form>";

if (isset($_POST['create_test_user'])) {
    $_SESSION['user_id'] = 999; // Temporary test user ID
    echo "<p>Created temporary user_id = 999. Refresh page to see updated session.</p>";
}

// Show dashboard script output for session handling
echo "<h2>How Dashboard Checks Login</h2>";

$dashboard_file = file_get_contents(__DIR__ . '/dashboard.php');
if ($dashboard_file) {
    // Extract session-related code
    preg_match_all('/require_once ["\'].*?["\'];|check_login.*?;|\$_SESSION\[["\'].*?[\'"]\]/', $dashboard_file, $matches);
    
    if (!empty($matches[0])) {
        echo "<pre>";
        foreach ($matches[0] as $line) {
            echo htmlspecialchars($line) . "\n";
        }
        echo "</pre>";
    } else {
        echo "Could not find session-related code in dashboard.php";
    }
} else {
    echo "Could not read dashboard.php";
}

// Look for auth_checker.inc.php
if (file_exists('includes/auth/auth_checker.inc.php')) {
    echo "<h3>Auth Checker Content</h3>";
    $auth_checker = file_get_contents(__DIR__ . '/includes/auth/auth_checker.inc.php');
    echo "<pre>" . htmlspecialchars($auth_checker) . "</pre>";
} else {
    echo "<p>auth_checker.inc.php not found or not readable</p>";
}