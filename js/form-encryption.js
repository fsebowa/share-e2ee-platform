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
    // Clear any stored file download flags
    sessionStorage.removeItem('fileDownloadStarted');
});

// Handle all form encryption including reCAPTCHA-enabled forms
function setupFormEncryption() {
    const callbackFunctions = ['onSubmit', 'onSubmitPreview', 'onSubmitDownload', 'onSubmitDelete'];
    // Store original callbacks and override them with encryption-enabled versions
    callbackFunctions.forEach(callbackName => {
        if (typeof window[callbackName] === 'function') {
            window[`original${callbackName}`] = window[callbackName];
            window[callbackName] = function(token) {
                handleFormSubmission(callbackName, token);
            };
        }
    });
    
    // Setup regular form submission for non-reCAPTCHA forms
    setupRegularFormSubmission();
}

// Process form submission based on which callback was triggered
function handleFormSubmission(callbackName, token) {
    let formId, loadingMessage, useCustomProgress = false;
    
    // Set appropriate form ID and loading message based on callback
    switch(callbackName) {
        case 'onSubmitPreview':
            formId = 'open_file_form';
            loadingMessage = 'Securely opening file...';
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
    
    // Special handling for the download form
    if (formId === 'download_file_form') {
        return processDownloadForm(token);
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
            
            // Disable original field to prevent it from being submitted in plain text
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
        
        // Add the reCAPTCHA token if provided
        if (token) {
            let tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
            if (!tokenInput) {
                tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'g-recaptcha-response';
                form.appendChild(tokenInput);
            }
            tokenInput.value = token;
        }
        
        // Submit the form
        form.submit();
        
    } catch (error) {
        console.error("Encryption error:", error);
        hideLoadingOverlay();
        alert("Error encrypting form data. Please try again.");
    }
}

// Function to handle regular (non-reCAPTCHA) form submissions
function setupRegularFormSubmission() {
    const secureForms = document.querySelectorAll('form.secure-form');
    secureForms.forEach(form => {
        // Skip forms that use reCAPTCHA
        if (form.querySelector('.g-recaptcha')) {
            return;
        }
        
        form.addEventListener('submit', function(e){
            const sensitiveFields = form.querySelectorAll('[data-encrypt="true"]');
            if (sensitiveFields.length === 0) {
                return; // Form submits normally
            }
            
            e.preventDefault();
            
            try {
                showLoadingOverlay('Processing...');
                
                // Create payload
                let payload = {};
                sensitiveFields.forEach(field => {
                    payload[field.name] = field.value;
                    field.disabled = true;
                    
                    // Create hidden field with empty value
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = field.name;
                    hiddenField.value = '';
                    form.appendChild(hiddenField);
                });
                
                // Encrypt the data
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

// Special function for handling download form submissions
function processDownloadForm(token) {
    // Get the form
    const form = document.getElementById('download_file_form');
    if (!form) {
        console.error('Download form not found');
        return;
    }
    
    // Get download action and key input
    const downloadAction = document.getElementById("download_action").value;
    const keyInput = form.querySelector('input[name="key"]');
    
    // Validate key is provided (required for both download types)
    if (!keyInput || keyInput.value.trim() === '') {
        alert("Please enter a decryption key. The key is required for both download types.");
        if (typeof hideLoadingOverlay === 'function') {
            hideLoadingOverlay();
        }
        return false;
    }
    
    // Show appropriate loading message based on download type
    const loadingMessage = downloadAction === "encrypted" 
        ? "Downloading encrypted file..." 
        : "Decrypting and downloading file...";
    
    if (typeof showLoadingOverlay === 'function') {
        showLoadingOverlay(loadingMessage);
    }
    
    // Record that a download is starting
    sessionStorage.setItem('fileDownloadStarted', 'true');
    
    // Get all sensitive fields that need encryption
    const sensitiveFields = form.querySelectorAll('[data-encrypt="true"]');
    if (sensitiveFields.length === 0) {
        console.log("No sensitive fields found, submitting form directly");
        
        // Add the token to a hidden input for the server-side verification
        let tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
        if (!tokenInput) {
            tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'g-recaptcha-response';
            form.appendChild(tokenInput);
        }
        tokenInput.value = token;
        
        form.submit();
        return;
    }
    
    // Handle encrypted submission
    try {
        // Create payload from sensitive fields
        let payload = {};
        sensitiveFields.forEach(field => {
            payload[field.name] = field.value;
            
            // Disable original field to prevent it from being submitted in plain text
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
        
        // Add the token to a hidden input for the server-side verification
        let tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
        if (!tokenInput) {
            tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'g-recaptcha-response';
            form.appendChild(tokenInput);
        }
        tokenInput.value = token;
        
        // Set a timeout to close popups after download likely started
        setTimeout(() => {
            if (sessionStorage.getItem('fileDownloadStarted') === 'true') {
                // Hide the download popup
                const downloadPopup = document.getElementById('downloadFile');
                if (downloadPopup) {
                    downloadPopup.style.display = 'none';
                }
                // Hide loading overlay
                hideLoadingOverlay();
                // Clear the flag after handling
                sessionStorage.removeItem('fileDownloadStarted');
            }
        }, 5000); // 5 seconds should be enough for most downloads to start
        
        // Submit the form
        form.submit();
        
    } catch (error) {
        console.error("Encryption error:", error);
        hideLoadingOverlay();
        alert("Error encrypting download request. Please try again.");
    }
}

// Define callback functions globally
window.onSubmit = function(token) {
    handleFormSubmission('onSubmit', token);
};

window.onSubmitPreview = function(token) {
    handleFormSubmission('onSubmitPreview', token);
};

window.onSubmitDownload = function(token) {
    processDownloadForm(token);
};

window.onSubmitDelete = function(token) {
    handleFormSubmission('onSubmitDelete', token);
};

// Loading overlay functions
function showLoadingOverlay(message) {
    console.log("ShowLoadingOverlay called with message:", message);
    
    // Create or get the overlay element
    let overlay = document.getElementById('loading-overlay');
    if (!overlay) {
        console.log("Creating new overlay element");
        overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <div id="loading-message">${message || 'Processing...'}</div>
            </div>
        `;
        document.body.appendChild(overlay);
        
        // Add needed CSS directly
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
    } else {
        console.log("Updating existing overlay element");
        const messageEl = overlay.querySelector('#loading-message');
        if (messageEl) messageEl.textContent = message || 'Processing...';
    }
    
    // Force display style
    overlay.style.display = 'flex';
    console.log("Overlay display set to:", overlay.style.display);
    
    // Cancel any existing timeout
    if (window.loadingOverlayTimeout) {
        clearTimeout(window.loadingOverlayTimeout);
    }
    
    // For downloads, use special handling
    if (message && (message.includes("Decrypting") || message.includes("Downloading"))) {
        console.log("Download operation detected, using extended timeout");
        sessionStorage.setItem('fileDownloadStarted', 'true');
        
        // Only set a very long timeout as fallback
        window.loadingOverlayTimeout = setTimeout(function() {
            console.log("Fallback timeout triggered after 60 seconds");
            hideLoadingOverlay();
            if (typeof closeAllPopups === 'function') closeAllPopups();
        }, 60000); // 60 seconds
    } else {
        // Standard timeout for non-download operations
        window.loadingOverlayTimeout = setTimeout(function() {
            console.log("Standard timeout triggered");
            hideLoadingOverlay();
        }, 30000); // 30 seconds
    }
    // Return the overlay for testing
    return overlay;
}

function setupDownloadDetection() {
    const beforeUnloadHandler = function(e) {
        // When beforeunload fires during a download, hide the overlay
        if (sessionStorage.getItem('fileDownloadStarted') === 'true') {
            hideLoadingOverlay();
            // Remove this handler after it fires once
            window.removeEventListener('beforeunload', beforeUnloadHandler);
        }
    };
    
    window.addEventListener('beforeunload', beforeUnloadHandler);
    
    // As a fallback safety measure, hide after 45 seconds regardless
    window.loadingOverlayTimeout = setTimeout(function() {
        hideLoadingOverlay();
        
        // Also hide popups after download likely started
        if (sessionStorage.getItem('fileDownloadStarted') === 'true') {
            // Hide file popups
            if (typeof closeAllPopups === 'function') {
                closeAllPopups();
            } else {
                const popups = [
                    document.getElementById("openFile"),
                    document.getElementById("downloadFile")
                ];
                popups.forEach(popup => {
                    if (popup) popup.style.display = 'none';
                });
            }
            sessionStorage.removeItem('fileDownloadStarted');
        }
    }, 45000); // 45 seconds - much longer than before
}

function hideLoadingOverlay() {
    console.log("hideLoadingOverlay called");
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
        console.log("Overlay hidden");
    }
    
    // Clear any pending timeout
    if (window.loadingOverlayTimeout) {
        clearTimeout(window.loadingOverlayTimeout);
        window.loadingOverlayTimeout = null;
    }
}
// Helper function for download form submission to prevent duplicate emails
function processDownloadForm(token) {
    // Get the form
    const form = document.getElementById('download_file_form');
    if (!form) {
        console.error('Download form not found');
        return;
    }
    
    // Get download action and key input
    const downloadAction = document.getElementById("download_action").value;
    const keyInput = form.querySelector('input[name="key"]');
    
    // Validate key is provided (required for both download types)
    if (!keyInput || keyInput.value.trim() === '') {
        alert("Please enter a decryption key. The key is required for both download types.");
        if (typeof hideLoadingOverlay === 'function') {
            hideLoadingOverlay();
        }
        return false;
    }
    
    // Show appropriate loading message based on download type
    const loadingMessage = downloadAction === "encrypted" 
        ? "Downloading encrypted file..." 
        : "Decrypting and downloading file...";
    
    if (typeof showLoadingOverlay === 'function') {
        showLoadingOverlay(loadingMessage);
    }
    
    sessionStorage.setItem('fileDownloadStarted', 'true');
    
    // Get all sensitive fields that need encryption
    const sensitiveFields = form.querySelectorAll('[data-encrypt="true"]');
    if (sensitiveFields.length === 0) {
        console.log("No sensitive fields found, submitting form directly");
        
        // Add the token to a hidden input for the server-side verification
        let tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
        if (!tokenInput) {
            tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'g-recaptcha-response';
            form.appendChild(tokenInput);
        }
        tokenInput.value = token || 'bypass_token';
        
        form.submit();
        return;
    }
    
    // Handle encrypted submission
    try {
        // Create payload from sensitive fields
        let payload = {};
        sensitiveFields.forEach(field => {
            payload[field.name] = field.value;
            
            // Disable original field to prevent it from being submitted in plain text
            field.disabled = true;
            
            // Create hidden field with same name and empty value to preserve form structure
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = field.name;
            hiddenField.value = '';
            form.appendChild(hiddenField);
        });
        
        // Add a field to prevent duplicate emails
        payload['prevent_duplicate_email'] = 'true';
        
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
        
        // Add the token to a hidden input for the server-side verification
        let tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
        if (!tokenInput) {
            tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'g-recaptcha-response';
            form.appendChild(tokenInput);
        }
        tokenInput.value = token || 'bypass_token';
        
        // Submit the form
        form.submit();
        
    } catch (error) {
        console.error("Encryption error:", error);
        hideLoadingOverlay();
        alert("Error encrypting download request. Please try again.");
    }
}

// Make functions globally available
window.showLoadingOverlay = showLoadingOverlay;
window.hideLoadingOverlay = hideLoadingOverlay;