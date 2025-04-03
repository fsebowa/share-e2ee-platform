document.addEventListener("DOMContentLoaded", function() {
    // Hide all progress indicators initially
    document.querySelectorAll('.progress-backdrop').forEach(backdrop => {
        backdrop.style.display = 'none';
    });
    
    const fileUploadForm = document.getElementById("file_upload_form");
    if (fileUploadForm) {        
        fileUploadForm.addEventListener("submit", function(e) {
            // Only show progress if a file is selected
            const fileInput = this.querySelector('input[type="file"]');
            if (fileInput && fileInput.files.length > 0) {
                showUploadProgress();
            }
        });
    }
    
    // Setup delete form auto-hide loading
    const deleteForm = document.getElementById("delete_file_form");
    if (deleteForm) {
        deleteForm.addEventListener("submit", function() {
            const deletePhrase = this.querySelector('input[name="delete_phrase"]');
            if (deletePhrase && deletePhrase.value.toUpperCase() === "DELETE") {
                showDeleteProgress();
            }
        });
    }
});

// Function to show the upload progress
function showUploadProgress() {
    const container = document.getElementById("uploadProgressBackdrop");
    const bar = document.getElementById("uploadProgressBar");
    
    if (container && bar) {
        bar.style.width = "0%";
        bar.classList.remove("indeterminate");
        // Show container
        container.style.display = "block";
        
        // Start simulating progress
        simulateProgress(bar);
    }
}

// Function to show the delete progress
function showDeleteProgress() {
    const container = document.getElementById("deleteProgressBackdrop");
    const bar = document.getElementById("deleteProgressBar");
    
    if (container && bar) {
        bar.style.width = "0%";
        bar.classList.remove("indeterminate");
        // Show container
        container.style.display = "block";
        
        // Start simulating progress
        simulateProgress(bar);
    }
}

// Function to update progress percentage
function updateProgress(bar, percentage) {
    if (bar) {
        if (percentage < 0) {
            bar.classList.add("indeterminate");
        } else {
            bar.classList.remove("indeterminate");
            bar.style.width = percentage + "%";
        }
    }
}

// Function to hide all progress bars
function hideAllProgress() {
    document.querySelectorAll('.progress-backdrop').forEach(backdrop => {
        backdrop.style.display = 'none';
    });
    
    // Clear any pending progress bar timeouts
    if (window.progressTimeout) {
        clearTimeout(window.progressTimeout);
    }
}

// Function to simulate progress for user experience
function simulateProgress(bar) {
    updateProgress(bar, -1);
    
    // Store reference to timeout for potential cleanup
    window.progressTimeout = setTimeout(() => {
        if (bar) {
            bar.classList.remove("indeterminate");
        }
        
        // Simulate progress increasing
        let progress = 0;
        const interval = setInterval(() => {
            progress += 5;
            
            // Slow down as we approach 90%
            if (progress > 70) {
                progress += 1;
            }
            if (progress >= 90) {
                clearInterval(interval);
                progress = 90;
            }
            updateProgress(bar, progress);
        }, 300);
    }, 500);
}

// Make functions available globally
window.showUploadProgress = showUploadProgress;
window.showDeleteProgress = showDeleteProgress;
window.hideAllProgress = hideAllProgress;