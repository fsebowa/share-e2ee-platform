<?php

declare(strict_types=1);

function check_file_upload_errors() {
    if (isset($_SESSION['errors_file_upload'])) {
        $errors = $_SESSION['errors_file_upload'];
        echo '<div class="error-messages" id="errorMessages">';
        foreach ($errors as $error) {
            echo '<p class="error-danger">' . $error . '</p>';
        }
        echo '</div>';
        unset($_SESSION['errors_file_upload']);
    }
}

function check_file_preview_errors() {
    if (isset($_SESSION['errors_file_preview'])) {
        $errors = $_SESSION['errors_file_preview'];
        echo '<div class="error-messages" id="errorMessages">';
        foreach ($errors as $error) {
            echo '<p class="error-danger">' . $error . '</p>';
        }
        echo '</div>';
        unset($_SESSION['errors_file_preview']);
    }
}

function check_upload_success_messages() {
    if (isset($_SESSION["success_file"])) {
        $success = $_SESSION["success_file"];
        echo '<div class="success-messages" id="successMessage">';
        foreach ($success as $suc) {
            echo '<p class="success-message">' . $suc . '</p>';
        }
        echo '</div>';
        unset($_SESSION['success_file']);
    }
}