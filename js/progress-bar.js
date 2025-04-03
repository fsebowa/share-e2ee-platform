document.addEventListener("DOMContentLoaded", function() {
    const fileUploadForm = document.getElementById("file_upload_form");
    
    if (fileUploadForm) {        
        fileUploadForm.addEventListener("submit", function(e) {
            // Only show progress if a file is selected
            const fileInput = this.querySelector('input[type="file"]');
            if (fileInput && fileInput.files.length > 0) {
                showProgressBar();
                simulateProgress();
            }
        });
    }
    
    // Setup preview form auto-hide loading
    const previewForm = document.getElementById("open_file_form");
    if (previewForm) {
        previewForm.addEventListener("submit", function() {
            // Ensure loading overlay is hidden after a timeout
            setTimeout(function() {
                hideProgressBar();
            }, 15000); // 15 seconds should be enough for most files to start downloading
        });
    }
});

// Function to show the progress bar
function showProgressBar() {
    const container = document.getElementById("progressBackdrop");
    const bar = document.getElementById("progressBar");
    
    if (container && bar) {
        bar.style.width = "0%";
        bar.classList.remove("indeterminate");
        // Show container
        container.style.display = "block";
    }
}

// Function to update progress percentage
function updateProgress(percentage) {
    const bar = document.getElementById("progressBar");
    
    if (bar) {
        if (percentage < 0) {
            bar.classList.add("indeterminate");
        } else {
            bar.classList.remove("indeterminate");
            bar.style.width = percentage + "%";
        }
    }
}

// Function to hide the progress bar
function hideProgressBar() {
    const container = document.getElementById("progressContainer");
    
    if (container) {
        container.style.display = "none";
        container.classList.remove("active");
    }
    
    // Clear any pending progress bar timeouts
    if (window.progressTimeout) {
        clearTimeout(window.progressTimeout);
    }
    if (window.previewTimeout) {
        clearTimeout(window.previewTimeout);
    }
}

// Function to simulate progress for user experience
function simulateProgress() {
    updateProgress(-1);
    
    // Store reference to timeout for potential cleanup
    window.progressTimeout = setTimeout(() => {
        const bar = document.getElementById("progressBar");
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
            updateProgress(progress);
        }, 300);
    }, 500);
}

// Make functions available globally
window.showProgressBar = showProgressBar;
window.updateProgress = updateProgress;
window.hideProgressBar = hideProgressBar;
window.simulateProgress = simulateProgress;



function openEllipseChild(className, popupName) {
    document.querySelectorAll(className).forEach(popupName => {
        popupName.addEventListener("click", function(e) {
            e.stopPropagation();

            // get file information from parent file element
            const fileElement = this.closest(".file");
            const fileName = fileElement.querySelector(".file-title").textContent;
            const fileId = fileElement.getAttribute('data-file-id');
        })
    })
}