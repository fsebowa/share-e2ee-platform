document.addEventListener("DOMContentLoaded", function() {
    const profileIcon = document.querySelector(".profile-icon");
    const profilePopup = document.querySelector(".profile-popup");
    
    if (profileIcon && profilePopup) {
        profilePopup.style.display = "none";
        
        profileIcon.addEventListener("click", function(e) {
            e.stopPropagation();
            
            // Check if popup is already visible
            const isVisible = profilePopup.style.display === "block";
            
            // Close all popups first (if dashboard_func.js is loaded and has the function)
            if (typeof closeAllPopups === 'function') {
                closeAllPopups();
            } else {
                // Fallback - close just this popup if the global function isn't available
                const otherPopups = document.querySelectorAll('.file-menu-popup, .file-ellipse-popup, .upload-form');
                otherPopups.forEach(popup => {
                    popup.style.display = 'none';
                });
            }
            
            // Toggle the profile popup
            if (!isVisible) {
                profilePopup.style.display = "block";
            }
        });
        
        // Prevent popup from closing when clicked
        profilePopup.addEventListener("click", function(e) {
            e.stopPropagation();
        });
    }
});