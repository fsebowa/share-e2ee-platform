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

    // function to open ellipse menu options
    function openEllipseChild(selector, popupId, actionName, formId, inputSelector) {
        document.querySelectorAll(selector).forEach(element => {
            element.addEventListener("click", function(e) {
                e.stopPropagation();
                
                // Get file information from parent file element
                const fileElement = this.closest(".file");
                const fileName = fileElement.querySelector(".file-title").textContent.trim();
                const fileId = fileElement.getAttribute('data-file-id');
                
                // Save file info to session storage
                sessionStorage.setItem('childFileId', fileId);
                sessionStorage.setItem('childFileTitle', `${actionName} ${fileName}`);
                
                // Get popup element by ID
                const popup = document.getElementById(popupId);
                
                if (!popup) return;
                
                // Open popup
                openPopup(popup);
                
                // Update popup title with file name
                const popupTitle = popup.querySelector('h2');
                if (popupTitle) {
                    popupTitle.textContent = `${actionName} ${fileName}`;
                }
                
                // Add file ID to form for server-side processing
                const form = document.getElementById(formId);
                if (form && fileId) {
                    let fileIdInput = form.querySelector('input[name="file_id"]');
                    if (!fileIdInput) {
                        fileIdInput = document.createElement('input');
                        fileIdInput.type = 'hidden';
                        fileIdInput.name = 'file_id';
                        form.appendChild(fileIdInput);
                    }
                    fileIdInput.value = fileId;
                    
                    // Clear any previous user input values
                    const userInput = form.querySelector(inputSelector);
                    if (userInput) {
                        userInput.value = '';
                    }
                }
            });
        });
    }

    // function to show popup and errors
    function showPopupErrors(errorFlag, popupId, formId) {
        // Only proceed if there are errors and a popup ID is provided
        if (typeof errorFlag !== 'undefined' && errorFlag === true && popupId) {
            // Get the popup element by ID
            const popupElement = document.getElementById(popupId);
            if (!popupElement) return;
            
            // Get stored file info from session storage
            const fileId = sessionStorage.getItem('childFileId');
            const fileTitle = sessionStorage.getItem('childFileTitle');
    
            if (fileId && fileTitle) {
                // Update popup title
                const popupTitle = popupElement.querySelector('h2');
                if (popupTitle) {
                    popupTitle.textContent = fileTitle;
                }
    
                // Set file ID in the form
                const form = document.getElementById(formId);
                if (form) {
                    let fileIdInput = form.querySelector('input[name="file_id"]');
                    if (!fileIdInput) {
                        fileIdInput = document.createElement('input');
                        fileIdInput.type = 'hidden';
                        fileIdInput.name = 'file_id';
                        form.appendChild(fileIdInput);
                    }
                    fileIdInput.value = fileId;
                }
                
                // Show the popup
                openPopup(popupElement);
            }
        }
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

        openEllipseChild('.file-menu-popup ul li:first-child, .uploaded-files .file .file-title', 'openFile', 'Preview', 'open_file_form', 'input[name="key"]');
        
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
    showPopupErrors(hasPreviewErrors, 'openFile', 'open_file_form');

    // ------- DELETE FILE POPUP ---------
    if (deleteFilePopup) {
        // Initially hide the popup
        deleteFilePopup.style.display = "none";

        // Find all "Delete" options in file menus
        openEllipseChild('.file-menu-popup ul li:last-child', 'deleteFile', 'Delete', 'delete_file_form', 'input[name="delete_phrase"]');
    }

    // Check if we need to show the delete popup due to errors
    const hasDeleteErrors = typeof deletePopupErrors !== 'undefined' && deletePopupErrors;
    showPopupErrors(hasDeleteErrors, 'deleteFile', 'delete_file_form');
    
    // Hide any loading overlay that might still be visible
    if (typeof hideLoadingOverlay === 'function') {
        hideLoadingOverlay();
    }
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