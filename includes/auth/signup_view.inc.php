<?php

declare(strict_types=1);

function signup_inputs() {
    echo '
    <div class="form-inputs">
        <div class="form-box">
            <input type="text" id="first_name" name="first-name" placeholder="First Name" value="'. $_SESSION["signup_data"]["first_name"].'">
            <input type="text" id="last_name" name="last-name" placeholder="Last Name" value="'. $_SESSION["signup_data"]["last_name"].'">
        </div>';
    
    if (isset($_SESSION["signup_data"]["email"]) && !isset($_SESSION["errors_signup"]["email_used"]) && !isset($_SESSION["errors_signup"]["invalid_email"])) {
        echo '<input type="text" id="email" name="email" placeholder="Email" data-encrypt="true" value="'. $_SESSION["signup_data"]["email"].'">';
    } else {
        echo '<input type="email" id="email" name="email" placeholder="Email" data-encrypt="true">';
    }
    echo '  
        <div class="form-box">
            <input type="password" id="password" name="password" placeholder="Password" data-encrypt="true">
            <div class="pass-eye">
                <i class="fa-regular fa-eye-slash open-eye"></i>
                <i class="fa-regular fa-eye close-eye"></i>
            </div>
        </div>
    
        <div class="form-box">
            <input type="password" id="confirm_password" name="confirm-password" placeholder="Confirm Password" data-encrypt="true">
            <div class="pass-eye">
                <i class="fa-regular fa-eye-slash open-eye"></i>
                <i class="fa-regular fa-eye close-eye"></i>
            </div>
        </div>';
    echo '</div>';
}

function check_signup_errors() {
    if (isset($_SESSION['errors_signup'])) {
        $errors = $_SESSION['errors_signup'];

        echo "<br>";

        foreach ($errors as $error) {
            echo '<p class="error-danger">' . $error . '</p>';
        }

        unset($_SESSION['errors_signup']);
    }
}