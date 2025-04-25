// JavaScript for the hash calculation page
document.addEventListener('DOMContentLoaded', function() {
    // Setup file input displays
    setupFileInput('hash_file_input_1', 'hash-file-name-1');
    setupFileInput('hash_file_input_2', 'hash-file-name-2');
    
    // Setup generate key button
    const generateKeyBtn = document.getElementById('generate_key');
    if (generateKeyBtn) {
        generateKeyBtn.addEventListener('click', function() {
            generateKey('key');
        });
    }
    
    // Hide messages after delay
    setTimeout(hideMessages, 5000);
});

// Handle file input displays
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

// Generate key via AJAX
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

// Hide messages with fade effect
function hideMessages() {
    const errorMessages = document.getElementById('errorMessages');
    const successMessages = document.getElementById('successMessage');
    
    if (errorMessages) {
        errorMessages.style.transition = 'opacity 1s';
        errorMessages.style.opacity = '0';
        setTimeout(() => {
            if (errorMessages.parentNode) {
                errorMessages.remove();
            }
        }, 1000);
    }
    
    if (successMessages) {
        successMessages.style.transition = 'opacity 1s';
        successMessages.style.opacity = '0';
        setTimeout(() => {
            if (successMessages.parentNode) {
                successMessages.remove();
            }
        }, 1000);
    }
}

// This is the function called by reCAPTCHA
function onSubmitHash(token) {
    // Show loading overlay
    showLoadingOverlay("Calculating file hashes...");
    
    // Validate form
    const form = document.getElementById('hash_file_form');
    const keyInput = form.querySelector('input[name="key"]');
    const fileInput1 = document.getElementById('hash_file_input_1');
    const fileInput2 = document.getElementById('hash_file_input_2');
    
    // Validate inputs
    if (!keyInput || keyInput.value.trim() === '') {
        hideLoadingOverlay();
        alert("Please enter or generate a key.");
        return;
    }
    
    if (!fileInput1 || !fileInput1.files || fileInput1.files.length === 0) {
        hideLoadingOverlay();
        alert("Please select the first file.");
        return;
    }
    
    if (!fileInput2 || !fileInput2.files || fileInput2.files.length === 0) {
        hideLoadingOverlay();
        alert("Please select the second file.");
        return;
    }
    
    // Add reCAPTCHA token
    let tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
    if (!tokenInput) {
        tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'g-recaptcha-response';
        form.appendChild(tokenInput);
    }
    tokenInput.value = token;
    
    // Submit the form directly (not using AJAX)
    form.submit();
}

// Show loading overlay function compatible with dashboard's style
function showLoadingOverlay(message) {
    const overlay = document.getElementById('loadingOverlay');
    const messageEl = document.getElementById('loading-message');
    
    if (messageEl) {
        messageEl.textContent = message || 'Processing...';
    }
    
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

// Hide loading overlay
function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Make functions globally available
window.onSubmitHash = onSubmitHash;
window.showLoadingOverlay = showLoadingOverlay;
window.hideLoadingOverlay = hideLoadingOverlay;