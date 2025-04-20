document.addEventListener('DOMContentLoaded', function() {
    // Set up share button handlers for all share menu items
    document.querySelectorAll('.file-menu-popup ul li:nth-child(2)').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            // Get file information from parent file element
            const fileElement = this.closest(".file");
            if (!fileElement) return;
            const fileId = fileElement.getAttribute('data-file-id');
            const fileName = fileElement.querySelector(".file-title").textContent.trim();
            // Call open share popup with file ID
            openSharePopup(fileId, fileName);
        });
    });
    
    // Creating short delay to ensure scripts are loaded
    setTimeout(function() {
        // Auto-reopen share popup if needed
        const urlParams = new URLSearchParams(window.location.search);
        const fileId = urlParams.get('file_id');
        
        // Check if we have share success data
        const shareSuccess = typeof window.shareCreated !== 'undefined' && window.shareCreated === true;
        const shareErrors = typeof window.shareErrors !== 'undefined' && window.shareErrors === true;        
        
        if (fileId && (shareSuccess || shareErrors)) {
            // Find file name for this file ID
            const fileElement = document.querySelector(`.file[data-file-id="${fileId}"]`);
            const fileName = fileElement ? fileElement.querySelector(".file-title").textContent.trim() : "File";
            
            // Open share popup with file ID and name
            openSharePopup(fileId, fileName);
            // If share was created successfully, show success view
            if (shareSuccess) {
                showShareSuccess();
            } else {
                // Make sure form area is visible when there are errors
                const formArea = document.querySelector('#shareFile form');
                if (formArea) {
                    formArea.style.display = 'block';
                }
                
                const successArea = document.querySelector('#shareFile .share-success');
                if (successArea) {
                    successArea.style.display = 'none';
                }
            }
        }
    }, 100);

    // Function to clean up URL parameters
    function cleanupUrlState() {
        if (window.location.pathname.includes('/dashboard') && window.location.search) {
            console.log('Cleaning up URL state');
            history.replaceState({}, document.title, window.location.pathname);
        }
    }

    // Close share popup when clicking outside
    document.addEventListener('click', function(e) {
        const sharePopup = document.getElementById('shareFile');
        if (sharePopup && 
            sharePopup.style.display === 'block' && 
            !sharePopup.contains(e.target) && 
            !e.target.closest('.file-menu-popup') &&
            !e.target.classList.contains('share-button')) {
            
            sharePopup.style.display = 'none';
            cleanupUrlState();
        }
    });
    
    // Prevent closing when clicking inside the popup
    const sharePopup = document.getElementById('shareFile');
    if (sharePopup) {
        sharePopup.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Set up key delivery method toggle
    const keyDeliverySelect = document.getElementById('key_delivery');
    const recipientField = document.getElementById('recipient_field');
    
    if (keyDeliverySelect && recipientField) {
        // Initial setup based on current value
        updateRecipientVisibility();
        // Add change listener
        keyDeliverySelect.addEventListener('change', updateRecipientVisibility);
    }
    
    function updateRecipientVisibility() {
        if (keyDeliverySelect.value === 'email') {
            recipientField.style.display = 'block';
            const recipientInput = document.getElementById('recipient');
            if (recipientInput) recipientInput.required = true;
        } else {
            recipientField.style.display = 'none';
            const recipientInput = document.getElementById('recipient');
            if (recipientInput) recipientInput.required = false;
        }
    }

    // setup password option
    const passCheckBox = document.getElementById('password_protect');
    const passContainer = document.getElementById('password_field');
    const passInput = document.getElementById('share_password');
    if (passCheckBox && passContainer) {
        passCheckBox.addEventListener('change', function () {
            passContainer.style.display = this.checked ? 'block' : 'none';
            passInput.value = '';  // clear password input when click changes
            // make password required based on checkbox state
            if (passInput) {
                if (this.checked) {
                    passInput.setAttribute('required', 'required');
                } else {
                    passInput.removeAttribute('required');
                }
            }
        });
    }
});

// Function to open share popup
function openSharePopup(fileId, fileName) {
    // Close all other popups first
    if (typeof closeAllPopups === 'function') {
        closeAllPopups();
    }
    
    // Get the share popup element
    const sharePopup = document.getElementById('shareFile');
    if (!sharePopup){
        return;
    } 
    
    // Hide success area initially, but SHOW the form
    const successArea = sharePopup.querySelector('.share-success');
    const formArea = sharePopup.querySelector('form#share_file_form');
    
    if (successArea) {
        successArea.style.display = 'none';
    } 
    if (formArea) {
        formArea.style.display = 'block'; // Make sure form is visible
        sharePopup.style.width = "500px";
    } 
    
    // Update popup title with file name
    const popupTitle = sharePopup.querySelector('h2');
    if (popupTitle) {
        popupTitle.textContent = `Share ${fileName}`;
    }
    
    // Update hidden file_id input
    const fileIdInput = sharePopup.querySelector('form input[name="file_id"]');
    if (fileIdInput) {
        fileIdInput.value = fileId;
    }
    // Update URL without reloading (for bookmarking or refreshing)
    history.pushState({}, '', `/dashboard.php?file_id=${fileId}`);
    // Show the popup
    sharePopup.style.display = 'block';
}

// Function to display share success state
function showShareSuccess() {
    const sharePopup = document.getElementById('shareFile');
    if (!sharePopup) {
        return;
    }
    
    const formArea = sharePopup.querySelector('form#share_file_form');
    const successArea = sharePopup.querySelector('.share-success');
    
    if (!formArea || !successArea) {
        return;
    }
    
    // Hide form, show success
    formArea.style.display = 'none';
    successArea.style.display = 'block';
    if (successArea.style.display = 'block') {
        sharePopup.style.width = "640px";
    }

    // check delivery method
    const deliveryMethod = window.shareDelivery || 'manual';
    if (deliveryMethod === 'email') {
        sharePopup.style.display = 'none'; // hide the entire popup 
    } else {
        // For manual delivery, show the full details with copy buttons
        // Fill in success data from session
        if (typeof shareUrl !== 'undefined' && shareUrl) {
            const urlInput = document.getElementById('share_url');
            if (urlInput) {
                urlInput.value = shareUrl;
            } 
        }
        
        if (typeof shareKey !== 'undefined' && shareKey) {
            const keyInput = document.getElementById('share_key');
            if (keyInput) {
                keyInput.value = shareKey;
            }
        }
        
        if (typeof shareExpiry !== 'undefined' && shareExpiry) {
            const expirySpan = document.getElementById('share_expiry');
            if (expirySpan) {
                expirySpan.textContent = shareExpiry;
            }
        }
    }
}

// Function to copy text to clipboard
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    // Select the text
    element.select();
    
    try {
        // Copy to clipboard
        document.execCommand('copy');
        
        // Show copied message
        const messageId = `${elementId}_copied`;
        const message = document.getElementById(messageId);
        if (message) {
            message.style.display = 'inline';
            setTimeout(() => {
                message.style.display = 'none';
            }, 2000);
        }
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
}

// Function to handle reCAPTCHA callback for share form
function onSubmitShare(token) {
    // Show loading overlay
    if (typeof showLoadingOverlay === 'function') {
        showLoadingOverlay("Creating share link...");
    }
    
    // Get the form
    const form = document.getElementById('share_file_form');
    if (!form) {
        console.error('Share form not found');
        return;
    }
    
    // Process form with encryption if needed
    const sensitiveFields = form.querySelectorAll('[data-encrypt="true"]');
    if (sensitiveFields.length > 0 && window.SERVER_PUBLIC_KEY) {
        try {
            // Create payload from sensitive fields
            let payload = {};
            sensitiveFields.forEach(field => {
                payload[field.name] = field.value;
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
            
            // Submit form
            form.submit();
        } catch (error) {
            console.error("Encryption error:", error);
            if (typeof hideLoadingOverlay === 'function') {
                hideLoadingOverlay();
            }
            alert("Error encrypting share data. Please try again.");
        }
    } else {
        // No encryption needed, just submit
        form.submit();
    }
}

// Make functions globally available
window.openSharePopup = openSharePopup;
window.showShareSuccess = showShareSuccess;
window.copyToClipboard = copyToClipboard;
window.onSubmitShare = onSubmitShare;