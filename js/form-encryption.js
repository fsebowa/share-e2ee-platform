// Handles end-to-end encryption for sensitive form data using RSA encryption with JSEncrypt library

document.addEventListener('DOMContentLoaded', async function() {
    // Create a global encryption instance to reuse
    window.encryptionInstance = new JSEncrypt();
    try {
        // Fetch server's public key
        const response = await fetch('/includes/encryption/get_public_key.inc.php');
        const data = await response.json();
        if (data.success && data.publicKey) {
            // Set the key immediately when available
            window.encryptionInstance.setPublicKey(data.publicKey);
            window.SERVER_PUBLIC_KEY = data.publicKey; // Store the public key globally
            setupFormEncryption();  // Initialize encryption handlers
        } else {
            console.error('Failed to load public key');
        }
    } catch (error) {
        console.error('Error loading public key: ', error);
    }
    // Auto-hide loading overlay when page loads
    hideLoadingOverlay();
    sessionStorage.removeItem('fileDownloadStarted');     // Clear any stored file download flags
});

// handle all form encryptin including reCAPTCHA-enabled forms
function setupFormEncryption() {
    const callbackFunctions = ['onSubmit', 'onSubmitPreview', 'onSubmitDownload', 'onSubmitDelete'];
    // store original callbacks and override them with encryption-enbaled versions
    callbackFunctions.forEach(callbackName => {
        if (typeof window[callbackName] === 'function') {
            window[`original${callbackName}`] = window[callbackName];
            window[callbackName] = function(token) {
                handleFormSubmission(callbackName, token);
            };
        }
    });
}
// Process form submission based on which callback was triggered
function handleFormSubmission(callbackName, token) {
    let formId, loadingMessage, useCustomProgress = false;
    // Set appropriate form ID and loading message based on callback
    switch(callbackName) {
        case 'onSubmitPreview':
            formId = 'open_file_form';
            loadingMessage = 'Decrypting file...';
            break;
        case 'onSubmitDownload':
            formId = 'download_file_form';
            loadingMessage = 'Preparing download...';
            break;
        case 'onSubmitDelete':
            formId = 'delete_file_form';
            loadingMessage = 'Verifying deletion...';
            useCustomProgress = true; // Use custom progress for delete
            break;
        default: // Standard onSubmit for login/signup/otp
            // Try to determine form ID based on existing forms
            if (document.getElementById('login_form')) {
                formId = 'login_form';
                loadingMessage = 'Verifying credentials...';
            } else if (document.getElementById('signup_form')) {
                formId = 'signup_form';
                loadingMessage = 'Creating account...';
            } else if (document.getElementById('otp_form')) {
                formId = 'otp_form';
                loadingMessage = 'Verifying OTP...';
            } else if (document.getElementById('file_upload_form')) {
                formId = 'file_upload_form';
                loadingMessage = 'Preparing upload...';
                useCustomProgress = true; // Use custom progress for upload
            }
            break;
    }
    if (!formId) {
        console.error('Could not determine form ID for callback: ' + callbackName);
        return;
    }
    const form = document.getElementById(formId);
    if (!form) {
        console.error('Form not found: ' + formId);
        return;
    }
    // Special handling for file upload form
    if (formId === 'file_upload_form') {
        if (typeof showUploadProgress === 'function') {
            const fileInput = form.querySelector('input[type="file"]');
            if (fileInput && fileInput.files.length > 0) {
                showUploadProgress();
            }
        }
        // For file upload form, only encrypt text fields, not the file
        const fileInput = form.querySelector('input[type="file"]');
        const sensitiveTextFields = Array.from(form.querySelectorAll('[data-encrypt="true"]')).filter(
            field => field.type !== 'file'
        );
        if (sensitiveTextFields.length > 0) {
            try {
                // Create payload from text fields only
                let payload = {};
                sensitiveTextFields.forEach(field => {
                    payload[field.name] = field.value;
                    // Create hidden field with same name and empty value
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = field.name;
                    hiddenField.value = '';
                    form.appendChild(hiddenField);
                    // Disable original field
                    field.disabled = true;
                });
                // Encrypt the text data
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
                // Submit the form with the file and encrypted text fields
                form.submit();
            } catch (error) {
                console.error("Encryption error:", error);
                hideLoadingOverlay();
                alert("Error encrypting form data. Please try again.");
            }
        } else {
            form.submit();
        }
        
        return;
    }
    // Regular form handling for non-file forms
    const sensitiveFields = form.querySelectorAll('[data-encrypt="true"]');
    const hasSensitiveFields = sensitiveFields.length > 0;
    // Show loading overlay if fields are filled and we're not using custom progress
    const shouldShowLoading = Array.from(form.querySelectorAll('input')).some(
        input => input.type !== 'hidden' && input.value.trim() !== ''
    );
    
    // Handle special case for file delete with custom progress
    if (formId === 'delete_file_form' && useCustomProgress) {
        const deleteInput = document.querySelector('#delete_file_form input[name="decryption_key"]');
        if (deleteInput && deleteInput.value.length > 0 && /^[0-9a-fA-F]{64}$/.test(deleteInput.value)) {
            showDeleteProgress();
        }
    } 
    // Use default loading overlay for other operations
    else if (shouldShowLoading && !useCustomProgress) {
        showLoadingOverlay(loadingMessage);
    }
    // If no sensitive fields, just submit the form
    if (!hasSensitiveFields) {
        if (window[`original${callbackName}`]) {
            window[`original${callbackName}`](token);
        } else {
            form.submit();
        }
        return;
    }
    // Handle encrypted submission
    try {
        // Create payload from sensitive fields
        let payload = {};
        sensitiveFields.forEach(field => {
            payload[field.name] = field.value;
            field.disabled = true;
            // Create hidden field with same name and empty value to preserve form structure
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = field.name;
            hiddenField.value = '';
            form.appendChild(hiddenField);
        });
        // Encrypt data using the server's public key
        const encrypt = new JSEncrypt();
        encrypt.setPublicKey(window.SERVER_PUBLIC_KEY);
        const encryptedData = encrypt.encrypt(JSON.stringify(payload));
        // Add encrypted data to hidden field
        let encryptedField = form.querySelector('input[name="encrypted_data"]');
        if (!encryptedField) {
            encryptedField = document.createElement('input');
            encryptedField.type = 'hidden';
            encryptedField.name = 'encrypted_data';
            form.appendChild(encryptedField);
        }
        encryptedField.value = encryptedData;
        form.submit();
        
    } catch (error) {
        console.error("Encryption error:", error);
        hideLoadingOverlay();
        alert("Error encrypting form data. Please try again.");
    }
}

function setupRegularFormSubmission() {
    const secureForms = document.querySelectorAll('form.secure-form');
    secureForms.forEach(form => {
        // skip forms that use reCAPTCHA
        if (form.querySelector('.g-recaptcha')) {
            return;
        }
        form.addEventListener('submit', function(e){
            const sensitiveFields = form.querySelectorAll('[data-encrypted="true"]');
            if (sensitiveFields.length === 0) {
                return; // form submits normally
            }
            e.preventDefault();
            try {
                showLoadingOverlay('Processing...');
                // create payload
                let payload = {};
                sensitiveFields.forEach(field => {
                    payload[field.name] = field.value;
                    field.disabled = true;
                    // create hidden field with empty value
                    const hiddenField =  document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = field.name;
                    hiddenField.value = '';
                    form.appendChild(hiddenField);
                });
                // encrypt the data
                const encrypt = new JSEncrypt();
                encrypt.setPublicKey(window.SERVER_PUBLIC_KEY);
                const encryptedData = encrypt.encrypt(JSON.stringify(payload));
                
                // Add to form
                let encryptedField = form.querySelector('input[name="encrypted_data"]');
                if (!encryptedField) {
                    encryptedField = document.createElement('input');
                    encryptedField.type = 'hidden';
                    encryptedField.name = 'encrypted_data';
                    form.appendChild(encryptedField);
                }
                encryptedField.value = encryptedData;
                form.submit();
            } catch (error) {
                console.error("Error encrypting form data:", error);
                hideLoadingOverlay();
            }
        });
    });
}

// define callback functions globally
window.onSubmit = function(token) {
    handleFormSubmission('onSubmit', token);
};

window.onSubmitPreview = function(token) {
    handleFormSubmission('onSubmitPreview', token);
};

window.onSubmitDownload = function(token) {
    document.getElementById("download_action").value = "decrypted";
    const keyInput = document.querySelector('#download_file_form input[name="key"]');
    if (keyInput && keyInput.value.length === 0) {
        return false;
    }
    handleFormSubmission('onSubmitDownload', token);
}

window.onSubmitDelete = function(token) {
    handleFormSubmission('onSubmitDelete', token);
};

// Loading overlay functions
function showLoadingOverlay(message) {
    let overlay = document.getElementById('loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <div id="loading-message">${message}</div>
            </div>
        `;
        document.body.appendChild(overlay);
        
        // Needed CSS
        const style = document.createElement('style');
        style.textContent = `
            #loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.7);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            }
            .loading-content {
                background: white;
                padding: 20px;
                border-radius: 5px;
                text-align: center;
            }
            .loading-spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #000;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                margin: 0 auto 15px;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
    document.getElementById('loading-message').textContent = message;
    overlay.style.display = 'flex';
    // Set a safety timeout to hide the overlay after 6 seconds for file downloads
    if (window.loadingOverlayTimeout) {
        clearTimeout(window.loadingOverlayTimeout);
    }
    // Use different timeouts for different operations
    if (message.includes("Decrypting")) {
        // Mark that a file download is expected
        sessionStorage.setItem('fileDownloadStarted', 'true');
        window.loadingOverlayTimeout = setTimeout(function() {
            hideLoadingOverlay();
            // Also hide the file preview popup after download likely started
            const openFilePopup = document.getElementById("openFile");
            if (openFilePopup && sessionStorage.getItem('fileDownloadStarted') === 'true') {
                openFilePopup.style.display = 'none';
                sessionStorage.removeItem('fileDownloadStarted');
            }
        }, 3000); // Shorter timeout for file operations (3 seconds)
    } else {
        // For other operations like login
        window.loadingOverlayTimeout = setTimeout(hideLoadingOverlay, 10000); // 10 seconds default
    }
}

function hideLoadingOverlay() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
    // Clear any pending timeout
    if (window.loadingOverlayTimeout) {
        clearTimeout(window.loadingOverlayTimeout);
    }
}

// make functions globally available
window.showLoadingOverlay = showLoadingOverlay;
window.hideLoadingOverlay = hideLoadingOverlay;