<?php

declare(strict_types=1);

function output_firstname() {
    if (isset($_SESSION["user_id"])) {
        echo $_SESSION["first_name"];
    }
}

function login_inputs() {
    if (isset($_SESSION["login_data"]["email"]) && !isset($_SESSION["errors_login"]["email_not_registered"]) && !isset($_SESSION["errors_login"]["invalid_email"])) {
        echo '<input type="email" id="email" name="email" placeholder="Email" data-encrypt="true" value="'. $_SESSION["login_data"]["email"].'">';
    } else {
        echo '<input type="email" id="email" name="email" placeholder="Email" data-encrypt="true">';
    }
}

function check_login_errors() {
    if (isset($_SESSION['errors_login'])) {
        $errors = $_SESSION['errors_login'];

        echo "<br>";

        foreach ($errors as $error) {
            echo '<p class="error-danger">' . $error . '</p>';
        }

        unset($_SESSION['errors_login']);
    }
}