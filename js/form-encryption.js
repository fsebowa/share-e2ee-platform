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
            setupReCaptchaEncryption();  // Initialize encryption handlers
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

// encryption for reCAPTCHA-enabled forms
function setupReCaptchaEncryption() {
    // Store original onSubmit function
    if (typeof window.onSubmit === 'function') {
        window.originalOnSubmit = window.onSubmit;
        
        // Replace with our encryption-aware version
        window.onSubmit = function(token) {
            
            // Show loading overlay
            showLoadingOverlay("Verifying credentials...");
    
            const form = document.querySelector('form.secure-form');
            if (!form) {
                return;
            }
            
            // Get sensitive fields
            const sensitiveFields = form.querySelectorAll('[data-encrypt="true"]');
            
            if (sensitiveFields.length === 0) {
                // No sensitive fields, submit normally
                if (window.originalOnSubmit) {
                    window.originalOnSubmit(token);
                } else {
                    form.submit();
                }
                return;
            }
            
            try {
                // Create payload from sensitive fields
                let payload = {};
                sensitiveFields.forEach(field => {
                    payload[field.name] = field.value;
                    
                    // Instead of removing the field, just disable it
                    field.disabled = true;
                    
                    // Create hidden field with same name and empty value
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = field.name;
                    hiddenField.value = '';
                    
                    // Add the hidden field but keep the original visible for appearance
                    form.appendChild(hiddenField);
                });
                
                // Initialize encryption
                const encrypt = new JSEncrypt();
                encrypt.setPublicKey(window.SERVER_PUBLIC_KEY);
                
                // Encrypt data
                const encryptedData = encrypt.encrypt(JSON.stringify(payload));
                
                // Add to hidden field
                let encryptedField = form.querySelector('input[name="encrypted_data"]');
                if (!encryptedField) {
                    encryptedField = document.createElement('input');
                    encryptedField.type = 'hidden';
                    encryptedField.name = 'encrypted_data';
                    form.appendChild(encryptedField);
                }
                encryptedField.value = encryptedData;
                
                // Submit the form
                form.submit();
                
            } catch (error) {
                console.error("Encryption error:", error);
                alert("Error encrypting form data. Please try again.");
            }
        };
    } else {
        // Fallback for non-reCAPTCHA forms
        const secureForms = document.querySelectorAll('form.secure-form');
        secureForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Similar encryption logic as above
                // Get sensitive fields
                const sensitiveFields = form.querySelectorAll('[data-encrypt="true"]');
                
                if (sensitiveFields.length === 0) {
                    form.submit();
                    return;
                }
                
                try {
                    // Create payload
                    let payload = {};
                    sensitiveFields.forEach(field => {
                        payload[field.name] = field.value;
                        
                        // Create hidden field with empty value
                        const hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.name = field.name;
                        hiddenField.value = '';
                        
                        field.parentNode.insertBefore(hiddenField, field);
                        field.parentNode.removeChild(field);
                    });
                    
                    // Encrypt data
                    const encrypt = new JSEncrypt();
                    encrypt.setPublicKey(window.SERVER_PUBLIC_KEY);
                    const encryptedData = encrypt.encrypt(JSON.stringify(payload));
                    
                    // Add to form
                    const encryptedField = document.createElement('input');
                    encryptedField.type = 'hidden';
                    encryptedField.name = 'encrypted_data';
                    encryptedField.value = encryptedData;
                    form.appendChild(encryptedField);
                    
                    // Submit form
                    form.submit();
                } catch (error) {
                    console.error("Error encrypting form data:", error);
                    alert("Error encrypting form data. Please try again.");
                }
            });
        });
    }
}

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
    // This is shortened from 15 seconds to ensure a better user experience
    if (window.loadingOverlayTimeout) {
        clearTimeout(window.loadingOverlayTimeout);
    }
    
    // Use different timeouts for different operations
    // For decryption operations (detected by message)
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