<?php

declare(strict_types=1);

function get_masked_email() {
    if (isset($_SESSION["user_id"])) {
        list($user, $domain) = explode('@', $_SESSION["email"]);
    
        // Mask part of the username and domain
        $user_masked = substr($user, 0, 3) . str_repeat('.', 3);
        $domain_parts = explode('.', $domain);
        $domain_masked = substr($domain_parts[0], 0, 3) . str_repeat('.', 2);
        
        // Reconstruct the masked email
        return $user_masked . '@' . $domain_masked . '.' . end($domain_parts);
    }
}

function check_otp_errors() {
    if (isset($_SESSION["errors_otp"])) {
        $errors = $_SESSION["errors_otp"];
        echo "<br>";

        // Handle both string and array formats
        if (is_array($errors)) {
            foreach ($errors as $error) {
                echo '<br> <p class="error-danger">' . $error . '</p>';
            }
        } else {
            echo '<br> <p class="error-danger">' . $errors . '</p>';
        }

        unset($_SESSION['errors_otp']);
    }
}


function check_success_messages() {
    if (isset($_SESSION["success_otp"])) {
        $success = $_SESSION["success_otp"];
        echo "<br>";

        // Handle both string and array formats
        if (is_array($success)) {
            foreach ($success as $suc) {
                echo '<br> <p class="success-message">' . $suc . '</p>';
            }
        } else {
            echo '<br> <p class="success-message">' . $success . '</p>';
        }

        unset($_SESSION['success_otp']);
    }
}