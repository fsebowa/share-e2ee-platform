/**
 * JavaScript functions for the encrypt-decrypt page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Setup encrypt file input display
    setupFileInput('encrypt_file_input', 'encrypt-file-name');
    
    // Setup decrypt file input display
    setupFileInput('decrypt_file_input', 'decrypt-file-name');

    // Setup encrypt generate key button
    setupGenerateKeyButton('encrypt_generate_key', 'encrypt_key');

    // No generate key button for decrypt - users must know the key to decrypt

    // Hide any messages after 5 seconds
    setTimeout(hideMessages, 5000);
    
    // Listen for beforeunload event which might indicate a download 
    window.addEventListener('beforeunload', function(e) {
        if (window.processingCrypto) {
            hideLoadingOverlay();
            window.processingCrypto = false;
        }
    });
});

/**
 * Sets up a file input field to display the selected filename
 */
function setupFileInput(inputId, displayId) {
    const fileInput = document.getElementById(inputId);
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const fileNameSpan = document.getElementById(displayId);
            if (fileNameSpan) {
                fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'Select file';
            }
        });
    }
}

/**
 * Sets up a generate key button to fetch and populate a key
 */
function setupGenerateKeyButton(buttonId, inputId) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.addEventListener('click', function() {
            generateKey(inputId);
        });
    }
}

/**
 * Generates a key via AJAX and populates the specified input field
 */
function generateKey(inputId) {
    $.ajax({
        url: '/includes/config/config_session.inc.php',
        type: 'GET',
        data: { action: 'generate_key'},
        success: function(response) {
            $("#" + inputId).val(response);
        },
        error: function() {
            alert("An error occurred while generating the key. Please try again!");
        }
    });
}

/**
 * Hides error and success messages with a fade effect
 */
function hideMessages() {
    const errorMessages = document.getElementById('errorMessages');
    const successMessages = document.getElementById('successMessage');
    
    if (errorMessages) {
        errorMessages.style.transition = 'opacity 1s';
        errorMessages.style.opacity = '0';
        setTimeout(() => errorMessages.remove(), 1000);
    }
    
    if (successMessages) {
        successMessages.style.transition = 'opacity 1s';
        successMessages.style.opacity = '0';
        setTimeout(() => successMessages.remove(), 1000);
    }
}

/**
 * Callback for reCAPTCHA on encrypt form
 */
window.onSubmitEncrypted = function(token) {
    handleFormSubmission('encrypt_file_form', token, "Encrypting your file...");
};

/**
 * Callback for reCAPTCHA on decrypt form
 */
window.onSubmitDecrypted = function(token) {
    handleFormSubmission('decrypt_file_form', token, "Decrypting your file...");
};

/**
 * Generic form submission handler for both forms
 */
function handleFormSubmission(formId, token, loadingMessage) {
    // Get the form
    const form = document.getElementById(formId);
    if (!form) {
        console.error(`Form ${formId} not found`);
        return;
    }
    
    // Show loading overlay
    if (typeof showLoadingOverlay === 'function') {
        showLoadingOverlay(loadingMessage);
    }
    
    // Validate inputs
    const keyInput = form.querySelector('input[name="key"]');
    const fileInput = form.querySelector('input[type="file"]');
    
    if (!keyInput || keyInput.value.trim() === '') {
        if (typeof hideLoadingOverlay === 'function') {
            hideLoadingOverlay();
        }
        alert("Please enter or generate a key.");
        return;
    }
    
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        if (typeof hideLoadingOverlay === 'function') {
            hideLoadingOverlay();
        }
        alert("Please select a file.");
        return;
    }
    
    // Process form with encryption
    const sensitiveFields = form.querySelectorAll('[data-encrypt="true"]');
    if (sensitiveFields.length > 0 && window.SERVER_PUBLIC_KEY) {
        try {
            // Create payload from sensitive fields
            let payload = {};
            sensitiveFields.forEach(field => {
                payload[field.name] = field.value;
                // Disable original field to prevent plaintext transmission
                field.disabled = true;
            });
            
            // Encrypt the data
            const encrypt = new JSEncrypt();
            encrypt.setPublicKey(window.SERVER_PUBLIC_KEY);
            const encryptedData = encrypt.encrypt(JSON.stringify(payload));
            
            // Add encrypted data to form
            let encryptedField = form.querySelector('input[name="encrypted_data"]');
            if (!encryptedField) {
                encryptedField = document.createElement('input');
                encryptedField.type = 'hidden';
                encryptedField.name = 'encrypted_data';
                form.appendChild(encryptedField);
            }
            encryptedField.value = encryptedData;
            
            // Add reCAPTCHA token
            let tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
            if (!tokenInput) {
                tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'g-recaptcha-response';
                form.appendChild(tokenInput);
            }
            tokenInput.value = token;
            
            // Track download status
            window.downloadStarting = true;
            
            // Set a shorter timeout to hide the loading overlay
            window.processingCrypto = true;
            
            // Hide the loading overlay after a shorter time
            setTimeout(function() {
                if (typeof hideLoadingOverlay === 'function' && window.processingCrypto) {
                    hideLoadingOverlay();
                    window.processingCrypto = false;
                }
            }, 5000); // Reduced to 5 seconds
            
            // Submit form
            form.submit();
        } catch (error) {
            console.error("Encryption error:", error);
            if (typeof hideLoadingOverlay === 'function') {
                hideLoadingOverlay();
            }
            alert("Error encrypting form data. Please try again.");
        }
    } else {
        // No encryption needed or missing public key, just submit
        console.warn("Client-side encryption skipped - missing public key or no sensitive fields");
        form.submit();
    }
}