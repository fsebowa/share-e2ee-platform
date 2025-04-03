let closeAllPopups; // make it available globally

document.addEventListener("DOMContentLoaded", () => {
    // Global variables for popup management
    const uploadForm = document.querySelector(".upload-form");
    const openFilePopup = document.getElementById("openFile");
    const deleteFilePopup = document.getElementById("deleteFile");
    const addFileButton = document.getElementById("add_file");
    const profilePopup = document.querySelector(".profile-popup");

    // ------- POPUP MANAGEMENT ---------
    // Function to close all popups - make it available globally
    closeAllPopups = function() {
        // Close file menu popups
        document.querySelectorAll('.file-menu-popup').forEach(popup => {
            popup.style.display = 'none';
        });
        
        // Close upload form
        if (uploadForm) {
            uploadForm.style.display = 'none';
        }
        
        // Close open file popup
        if (openFilePopup) {
            openFilePopup.style.display = 'none';
        }

        // deleteFIle popup
        if (deleteFilePopup) {
            deleteFilePopup.style.display = 'none';
        }
        
        // Close profile popup
        if (profilePopup) {
            profilePopup.style.display = 'none';
        }
    };
    
    // Function to open a specific popup and close others
    function openPopup(popupElement) {
        if (!popupElement) return;
        
        // Close other popups first
        closeAllPopups();
        
        // Open the requested popup
        popupElement.style.display = 'block';
    }
    
    // Close popups when clicking outside
    document.addEventListener("click", function(e) {
        // Skip if click is inside any popup or on a popup trigger
        const isInsidePopup = e.target.closest('.upload-form') || 
                            e.target.closest('.file-ellipse-popup') || 
                            e.target.closest('.file-menu-popup') ||
                            e.target.closest('.profile-popup') ||
                            e.target.closest('.profile-icon') ||
                            e.target.closest('.elipse-menu') ||
                            e.target === addFileButton;
        if (!isInsidePopup) {
            closeAllPopups();
        }
    });
    
    // Add File Button - Show upload form
    if (addFileButton) {
        addFileButton.addEventListener("click", function(e) {
            e.stopPropagation();
            openPopup(uploadForm);
        });
    }
    
    // Prevent popups from closing when clicked
    [uploadForm, openFilePopup].forEach(popup => {
        if (popup) {
            popup.addEventListener("click", function(e) {
                e.stopPropagation();
            });
        }
    });
    
    // ------- FILE INPUT HANDLING ---------
    const fileInput = document.getElementById('file');
    const fileNameSpan = document.getElementById('file-name');
    
    if (fileInput && fileNameSpan) {
        // Add change event listener to file input
        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                fileNameSpan.textContent = this.files[0].name;
            } else {
                fileNameSpan.textContent = 'No file chosen';
            }
        });
    }
    
    // ------- CATEGORY FILTERING ---------
    document.querySelectorAll('.categories li').forEach(function(category) {
        category.addEventListener('click', function() {
            // Remove active class from all categories
            document.querySelectorAll('.categories li').forEach(function(cat) {
                cat.classList.remove('active-cat');
            });
            
            // Add active class to clicked category
            this.classList.add('active-cat');
            
            const categoryText = this.textContent;
            
            // Show/hide files based on category
            document.querySelectorAll('.file').forEach(function(file) {
                if (categoryText === 'All' || file.getAttribute('data-file-type') === categoryText) {
                    file.style.display = 'flex';
                } else {
                    file.style.display = 'none';
                }
            });
        });
    });
    
    // ------- KEY GENERATION ---------
    // Insert generated key
    const generateKeyButton = document.getElementById("generate_key_button");
    if (generateKeyButton) {
        generateKeyButton.addEventListener("click", function() {
            // ajax request to generate key
            $.ajax({
                url: '/includes/config/config_session.inc.php',
                type: 'GET',
                data: { action: 'generate_key'},
                success: function(response) {
                    $("#key").val(response); // display generated key
                },
                error: function() {
                    alert("An error occurred while generating the key. Please try again!");
                }
            });
        });
    }
    
    // ------- FILE MENU POPUP ---------
    const ellipsisButtons = document.querySelectorAll(".elipse-menu");
    if (ellipsisButtons.length > 0) {
        // Hide all file menu popups initially
        document.querySelectorAll('.file-menu-popup').forEach(popup => {
            popup.style.display = 'none';
        });
        
        ellipsisButtons.forEach((button) => {
            const fileContainer = button.closest('.file');
            const popup = fileContainer ? fileContainer.querySelector('.file-menu-popup') : null;
            
            if (popup) {             
                // Add click handler to toggle popup
                button.addEventListener("click", function(e) {
                    e.stopPropagation();
                    
                    // Check if this popup is already open
                    const isAlreadyOpen = popup.style.display === 'block';
                    
                    // Close all popups
                    closeAllPopups();
                    
                    // Toggle this popup (open if it was closed)
                    if (!isAlreadyOpen) {
                        popup.style.display = 'block';
                    }
                });
                
                // Prevent popup from closing when clicked
                popup.addEventListener("click", function(e) {
                    e.stopPropagation();
                });
            } 
        });
    }
    
    // ------- FILE PREVIEW POPUP ---------
    if (openFilePopup) {
        // Initially hide the popup
        openFilePopup.style.display = "none";
        
        // Find all "Open" options in file menus
        document.querySelectorAll('.file-menu-popup ul li:first-child, .uploaded-files .file .file-title').forEach(openOption => {
            openOption.addEventListener("click", function(e) {
                e.stopPropagation();
                
                // Get file information from the parent file element
                const fileElement = this.closest('.file');
                const fileName = fileElement.querySelector('.file-title').textContent.trim();
                const fileId = fileElement.getAttribute('data-file-id');
                
                // Save the file info to session storage in case of errors
                sessionStorage.setItem('previewFileId', fileId);
                sessionStorage.setItem('previewFileTitle', `Preview ${fileName}`);
                
                // Open the file preview popup
                openPopup(openFilePopup);
                
                // Update popup title with file name
                const popupTitle = openFilePopup.querySelector('h2');
                if (popupTitle) {
                    popupTitle.textContent = `Preview ${fileName}`;
                }
                
                // Add file ID to form for server-side processing
                const openForm = document.getElementById('open_file_form');
                if (openForm && fileId) {
                    let fileIdInput = openForm.querySelector('input[name="file_id"]');
                    if (!fileIdInput) {
                        fileIdInput = document.createElement('input');
                        fileIdInput.type = 'hidden';
                        fileIdInput.name = 'file_id';
                        openForm.appendChild(fileIdInput);
                    }
                    fileIdInput.value = fileId;
                    
                    // Clear any previous decryption key
                    const keyInput = openForm.querySelector('input[name="key"]');
                    if (keyInput) {
                        keyInput.value = '';
                    }
                }
            });
        });
        
        // Handle successful file downloads by detecting form submission
        const openForm = document.getElementById('open_file_form');
        if (openForm) {
            openForm.addEventListener('submit', function() {
                // Store that we're expecting a file download
                sessionStorage.setItem('fileDownloadStarted', 'true');
                
                // Set a timeout to close the popup after the download starts (6 seconds)
                setTimeout(function() {
                    // Close the file preview popup if the file download was initiated
                    if (sessionStorage.getItem('fileDownloadStarted') === 'true') {
                        openFilePopup.style.display = 'none';
                        sessionStorage.removeItem('fileDownloadStarted');
                    }
                }, 6000);
            });
        }
    }

    // Check if we need to show the upload form due to errors
    if (typeof hasUploadErrors !== 'undefined' && hasUploadErrors === true && uploadForm) {
        openPopup(uploadForm);
    }
    
    // Check if we need to show the preview popup due to errors
    if (typeof hasPreviewErrors !== 'undefined' && hasPreviewErrors === true && openFilePopup) {
        // Get stored file info from session storage
        const fileId = sessionStorage.getItem('previewFileId');
        const fileTitle = sessionStorage.getItem('previewFileTitle');
        
        if (fileId && fileTitle) {
            // Update popup title
            const popupTitle = openFilePopup.querySelector('h2');
            if (popupTitle) {
                popupTitle.textContent = fileTitle;
            }
            
            // Set file ID in the form
            const openForm = document.getElementById('open_file_form');
            if (openForm) {
                let fileIdInput = openForm.querySelector('input[name="file_id"]');
                if (!fileIdInput) {
                    fileIdInput = document.createElement('input');
                    fileIdInput.type = 'hidden';
                    fileIdInput.name = 'file_id';
                    openForm.appendChild(fileIdInput);
                }
                fileIdInput.value = fileId;
            }
            
            // Show the popup
            openPopup(openFilePopup);
        }
    }
    
    // Hide any loading overlay that might still be visible
    if (typeof hideLoadingOverlay === 'function') {
        hideLoadingOverlay();
    }


    // Find all "Delete" options in file menus
    document.querySelectorAll('.file-menu-popup ul li:last-child').forEach(openOption => {
        openOption.addEventListener("click", function(e) {
            e.stopPropagation();
            
            // Get file information from the parent file element
            const fileElement = this.closest('.file');
            const fileName = fileElement.querySelector('.file-title').textContent.trim();
            const fileId = fileElement.getAttribute('data-file-id');
            
            // Save the file info to session storage in case of errors
            sessionStorage.setItem('deleteFileId', fileId);
            sessionStorage.setItem('deleteFileTitle', `Delete ${fileName}`);
            
            // Open the file delete popup
            openPopup(deleteFilePopup);
            
            // Update popup title with file name
            const popupTitle = deleteFilePopup.querySelector('h2');
            if (popupTitle) {
                popupTitle.textContent = `Delete ${fileName}`;
            }
            
            // Add file ID to form for server-side processing
            const openForm = document.getElementById('delete_file_form');
            if (openForm && fileId) {
                let fileIdInput = openForm.querySelector('input[name="file_id"]');
                if (!fileIdInput) {
                    fileIdInput = document.createElement('input');
                    fileIdInput.type = 'hidden';
                    fileIdInput.name = 'file_id';
                    openForm.appendChild(fileIdInput);
                }
                fileIdInput.value = fileId;
                
                // Clear any previous decryption key
                const keyInput = openForm.querySelector('input[name="key"]');
                if (keyInput) {
                    keyInput.value = '';
                }
            }
        });
    });

});

function onSubmit(token) {
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput && fileInput.files.length > 0) {
        showProgressBar();
        simulateProgress();
    }
    document.getElementById("file_upload_form").submit();
}

// Function for the preview form submission
function onSubmitPreview(token) {
    if (typeof showLoadingOverlay === 'function') {
        showLoadingOverlay("Decrypting file...");
    }
    // Submit the form
    document.getElementById("open_file_form").submit();
}

function onSubmitDelete(token) {
    if (typeof showLoadingOverlay === 'function') {
        showLoadingOverlay("Deleting file...");
    }
    document.getElementById("delete_file_form").submit();
}