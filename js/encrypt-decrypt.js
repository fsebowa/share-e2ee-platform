// JavaScript functions for the encrypt-decrypt page
document.addEventListener('DOMContentLoaded', function() {
    // Setup encrypt file input display
    setupFileInput('encrypt_file_input', 'encrypt-file-name');
    // Setup decrypt file input display
    setupFileInput('decrypt_file_input', 'decrypt-file-name');
    // Setup encrypt generate key button
    setupGenerateKeyButton('encrypt_generate_key', 'encrypt_key');
    // Hide any messages after 5 seconds
    setTimeout(hideMessages, 5000);
});

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

function setupGenerateKeyButton(buttonId, inputId) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.addEventListener('click', function() {
            generateKey(inputId);
        });
    }
}

// Generates a key via AJAX and populates the specified input field
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

//Hides error and success messages with a fade effect
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

// Callback for reCAPTCHA on encrypt form
function onSubmitEncrypted(token) {    
    // Get form reference
    const form = document.getElementById('encrypt_file_form');
    if (!form) {
        return;
    }
    // Validate form inputs
    const keyInput = form.querySelector('input[name="key"]');
    const fileInput = form.querySelector('input[type="file"]');
    
    if (!keyInput || keyInput.value.trim() === '') {
        alert("Please enter or generate a key.");
        return;
    }
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert("Please select a file.");
        return;
    }
    
    // Show loading overlay
    if (typeof showLoadingOverlay === 'function') {
        showLoadingOverlay("Encrypting and downloading file...");
    } else {
        console.warn("showLoadingOverlay function not available");
    }
    // Process the form
    submitEncryptedForm(form, token, 'encrypt');
}

//Callback for reCAPTCHA on decrypt form
function onSubmitDecrypted(token) {    
    // Get form reference
    const form = document.getElementById('decrypt_file_form');
    if (!form) {
        return;
    }
    // Validate form inputs
    const keyInput = form.querySelector('input[name="key"]');
    const fileInput = form.querySelector('input[type="file"]');
    
    if (!keyInput || keyInput.value.trim() === '') {
        alert("Please enter a decryption key.");
        return;
    }
    
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert("Please select a file.");
        return;
    }
    // Show loading overlay
    if (typeof showLoadingOverlay === 'function') {
        showLoadingOverlay("Decrypting and downloading file...");
    } else {
        console.warn("showLoadingOverlay function not available");
    }
    // Process the form
    submitEncryptedForm(form, token, 'decrypt');
}

// Submit encrypted form data
function submitEncryptedForm(form, token, operation) {
    console.log(`Processing ${operation} form submission`);
    try {
        // Get the sensitive fields that need encryption
        const sensitiveFields = form.querySelectorAll('[data-encrypt="true"]');        
        // Check for public key
        if (!window.SERVER_PUBLIC_KEY) {
            console.error("Server public key not available");
            alert("Encryption error: Server public key not available. Please refresh the page.");
            if (typeof hideLoadingOverlay === 'function') hideLoadingOverlay();
            return;
        }
        // Create payload from sensitive fields
        let payload = {};
        sensitiveFields.forEach(field => {
            payload[field.name] = field.value;
            // Store original value
            field.dataset.originalValue = field.value;
            // Temporarily disable to prevent plaintext submission
            field.disabled = true;
        });
        
        // Encrypt the data
        const encrypt = new JSEncrypt();
        encrypt.setPublicKey(window.SERVER_PUBLIC_KEY);
        const encryptedData = encrypt.encrypt(JSON.stringify(payload));
        if (!encryptedData) {
            throw new Error("Encryption failed - result is empty");
        }
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
        tokenInput.value = token || 'bypass_token';
        
        // Set a timeout to restore fields and hide overlay
        setTimeout(function() {
            console.log("Restoring form fields and hiding overlay");
            
            // Re-enable fields and restore values
            sensitiveFields.forEach(field => {
                if (field.dataset.originalValue) {
                    field.value = field.dataset.originalValue;
                    field.disabled = false;
                }
            });
            
            // Hide the overlay
            if (typeof hideLoadingOverlay === 'function') {
                hideLoadingOverlay();
            }
        }, 3000);
        
        // Submit the form
        form.submit();
        
    } catch (error) {        
        // Re-enable all fields in case of error
        form.querySelectorAll('[data-encrypt="true"]').forEach(field => {
            field.disabled = false;
        });
        
        // Hide the overlay
        if (typeof hideLoadingOverlay === 'function') {
            hideLoadingOverlay();
        }
        
        alert(`Error processing form: ${error.message}. Please try again.`);
    }
}

// Make sure the reCAPTCHA callbacks are globally accessible
window.onSubmitEncrypted = onSubmitEncrypted;
window.onSubmitDecrypted = onSubmitDecrypted;