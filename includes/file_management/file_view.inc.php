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

function check_file_download_errors() {
    if (isset($_SESSION['errors_file_download'])) {
        $errors = $_SESSION['errors_file_download'];
        echo '<div class="error-messages" id="errorMessages">';
        foreach ($errors as $error) {
            echo '<p class="error-danger">' . $error . '</p>';
        }
        echo '</div>';
        unset($_SESSION['errors_file_download']);
    }
}

function check_file_delete_errors() {
    if (isset($_SESSION['errors_file_delete'])) {
        $errors = $_SESSION['errors_file_delete'];
        echo '<div class="error-messages" id="errorMessages">';
        foreach ($errors as $error) {
            echo '<p class="error-danger">' . $error . '</p>';
        }
        echo '</div>';
        unset($_SESSION['errors_file_delete']);
    }
}

function load_success_messages($success_session_name) {
    if (isset($success_session_name)) {
        $success = $success_session_name;
        echo '<div class="success-messages" id="successMessage">';
        foreach ($success as $suc) {
            echo '<p class="success-message">' . $suc . '</p>';
        }
        echo '</div>';
        unset($success_session_name);
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

function check_download_success_messages() {
    // load_success_messages($_SESSION["success_file_download"]);
    if (isset($_SESSION["success_file_download"])) {
        $success = $_SESSION["success_file_download"];
        echo '<div class="success-messages" id="successMessage">';
        foreach ($success as $suc) {
            echo '<p class="success-message">' . $suc . '</p>';
        }
        echo '</div>';
        unset($_SESSION['success_file_download']);
    }
}

function check_delete_success_messages() {
    if (isset($_SESSION["success_file_delete"])) {
        $success = $_SESSION["success_file_delete"];
        echo '<div class="success-messages" id="successMessage">';
        foreach ($success as $suc) {
            echo '<p class="success-message">' . $suc . '</p>';
        }
        echo '</div>';
        unset($_SESSION['success_file_delete']);
    }
}