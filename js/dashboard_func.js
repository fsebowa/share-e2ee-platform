let closeAllPopups; // make it available globally

document.addEventListener("DOMContentLoaded", () => {
    // cleanupShareURL();
    // Global variables for popup management
    const uploadForm = document.querySelector(".upload-form");
    const openFilePopup = document.getElementById("openFile");
    const downloadFilePopup = document.getElementById("downloadFile");
    const deleteFilePopup = document.getElementById("deleteFile");
    const addFileButton = document.getElementById("add_file");
    const profilePopup = document.querySelector(".profile-popup");
    const shareFilePopup = document.getElementById('shareFile');

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

        // close download file popup
        if (downloadFilePopup) {
            downloadFilePopup.style.display = 'none';
        }

        // deleteFIle popup
        if (deleteFilePopup) {
            deleteFilePopup.style.display = 'none';
        }
        
        // Close profile popup
        if (profilePopup) {
            profilePopup.style.display = 'none';
        }
        // close share file popup
        if (shareFilePopup) {
            shareFilePopup.style.display = 'none';
        }

        if (typeof cleanupUrlState === 'function') {
            cleanupUrlState();
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

    // Function to open ellipse menu options
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
                
                // Enable any previously disabled inputs before opening popup
                const form = document.getElementById(formId);
                if (form) {
                    Array.from(form.querySelectorAll('input[disabled]')).forEach(input => {
                        input.disabled = false;
                    });

                    // Reset the download action to default for the download form
                    if (formId === 'download_file_form') {
                        const actionInput = form.querySelector('input[name="download_action"]');
                        if (actionInput) {
                            actionInput.value = 'decrypted';
                        }
                    }
                }
                
                // Open popup
                openPopup(popup);
                
                // Update popup title with file name
                const popupTitle = popup.querySelector('h2');
                if (popupTitle) {
                    popupTitle.textContent = `${actionName} ${fileName}`;
                }
                
                // Add file ID to form for server-side processing
                if (form && fileId) {
                    let fileIdInput = form.querySelector('input[name="file_id"]');
                    if (!fileIdInput) {
                        fileIdInput = document.createElement('input');
                        fileIdInput.type = 'hidden';
                        fileIdInput.name = 'file_id';
                        fileIdInput.setAttribute('data-encrypt', 'true');
                        form.appendChild(fileIdInput);
                    }
                    fileIdInput.value = fileId;

                    // Add file name to form for server-side processing
                    if (actionName === 'Delete' || actionName === 'Download') {
                        let fileNameInput = form.querySelector('input[name="file_name"]');
                        if (!fileNameInput) {
                            fileNameInput = document.createElement('input');
                            fileNameInput.type = 'hidden';
                            fileNameInput.name = 'file_name';
                            fileNameInput.setAttribute('data-encrypt', 'true');
                            form.appendChild(fileNameInput);
                        }
                        fileNameInput.value = fileName;
                    }
                    
                    // Clear any previous user input values
                    const userInput = form.querySelector(inputSelector);
                    if (userInput) {
                        userInput.value = '';
                    }
                }
            });
        });
    }

    // Function to show popup and errors
    function showPopupErrors(errorFlag, popupId, formId) {
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
                        fileIdInput.setAttribute('data-encrypt', 'true');
                        form.appendChild(fileIdInput);
                    }
                    fileIdInput.value = fileId;
                    
                    // If this is a delete or download form, also set the file name
                    if (formId === 'delete_file_form' || formId === 'download_file_form') {
                        const fileName = fileTitle.replace(/^(Delete|Download|Preview) /, '');
                        let fileNameInput = form.querySelector('input[name="file_name"]');
                        if (!fileNameInput) {
                            fileNameInput = document.createElement('input');
                            fileNameInput.type = 'hidden';
                            fileNameInput.name = 'file_name';
                            fileNameInput.setAttribute('data-encrypt', 'true');
                            form.appendChild(fileNameInput);
                        }
                        fileNameInput.value = fileName;
                    }
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
    [uploadForm, openFilePopup, downloadFilePopup, deleteFilePopup].forEach(popup => {
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

    // ------- FILE DOWNLOAD POPUP ---------
    if (downloadFilePopup) {
        // Initially hide the popup
        downloadFilePopup.style.display = "none";

        // Handle "Download" menu item click
        openEllipseChild('.file-menu-popup ul li:nth-child(3)', 'downloadFile', 'Download', 'download_file_form', 'input[name="key"]');
        
        // Handle encrypted download button
        const encryptedDownloadBtn = document.getElementById("encrypted_download_btn");
        if (encryptedDownloadBtn) {
            encryptedDownloadBtn.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Get the form and key input
                const form = document.getElementById('download_file_form');
                const keyInput = form.querySelector('input[name="key"]');
                
                // Validate key is provided
                if (!keyInput || keyInput.value.trim() === '') {
                    alert("Please enter your decryption key to verify your ownership of this file.");
                    return;
                }
                
                // Set the action to encrypted
                const actionInput = document.getElementById("download_action");
                if (actionInput) {
                    actionInput.value = "encrypted";
                    actionInput.setAttribute('data-encrypt', 'true');
                }
                
                // Show loading overlay
                if (typeof showLoadingOverlay === 'function') {
                    showLoadingOverlay("Downloading encrypted file...");
                }
                
                // Mark that a download is starting
                sessionStorage.setItem('fileDownloadStarted', Date.now());
                
                // Submit form directly instead of using reCAPTCHA
                try {
                    // Add a bypass token for the server-side check
                    let tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
                    if (!tokenInput) {
                        tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = 'g-recaptcha-response';
                        form.appendChild(tokenInput);
                    }
                    tokenInput.value = 'bypass_token_for_encrypted';
                    
                    // Submit the form through onSubmitDownload to ensure encryption
                    if (typeof window.onSubmitDownload === 'function') {
                        window.onSubmitDownload('bypass_token_for_encrypted');
                    } else {
                        // Fallback - direct submit
                        form.submit();
                    }
                } catch (error) {
                    console.error("Error submitting download form:", error);
                    hideLoadingOverlay();
                    alert("Error processing download. Please try again.");
                }
            });
        }
        
        // Handle decrypted download (via reCAPTCHA button)
        const decryptedDownloadBtn = document.getElementById("decrypted_download_btn");
        if (decryptedDownloadBtn) {
            // Remove any existing event listeners by cloning and replacing
            const newBtn = decryptedDownloadBtn.cloneNode(true);
            decryptedDownloadBtn.parentNode.replaceChild(newBtn, decryptedDownloadBtn);
            
            // Add a single clean event listener
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Get the form and key input
                const form = document.getElementById('download_file_form');
                const keyInput = form.querySelector('input[name="key"]');
                
                // Validate key is provided
                if (!keyInput || keyInput.value.trim() === '') {
                    alert("Please enter a decryption key to download the file.");
                    return;
                }
                
                // Set the action to decrypted
                const actionInput = form.querySelector('input[name="download_action"]');
                if (actionInput) {
                    actionInput.value = 'decrypted';
                }
                
                // Show loading overlay with console log for debugging
                console.log("Showing loading overlay for decrypt download");
                if (typeof showLoadingOverlay === 'function') {
                    showLoadingOverlay("Decrypting and downloading file...");
                }
                
                // Add a download ID to prevent duplicates
                const downloadId = Date.now().toString();
                sessionStorage.setItem('currentDownloadId', downloadId);
                
                // Submit form with bypass token
                let tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
                if (!tokenInput) {
                    tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = 'g-recaptcha-response';
                    form.appendChild(tokenInput);
                }
                tokenInput.value = 'bypass_token_for_decrypted';
                
                // Submit directly - DO NOT use onSubmitDownload to avoid double processing
                // Create a copy of the form to prevent resubmission
                const formClone = form.cloneNode(true);
                formClone.style.display = 'none';
                document.body.appendChild(formClone);
                formClone.submit();
                
                // Remove the clone after submission
                setTimeout(() => {
                    formClone.remove();
                }, 1000);
            });
        }
    }

    // ------- DELETE FILE POPUP ---------
    if (deleteFilePopup) {
        // Initially hide the popup
        deleteFilePopup.style.display = "none";
        
        // Directly target all delete menu items
        document.querySelectorAll('.file-menu-popup ul li').forEach(item => {
            if (item.textContent.trim() === 'Delete') {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    // Get file information from parent file element
                    const fileElement = this.closest(".file");
                    if (!fileElement) return;
                    
                    const fileName = fileElement.querySelector(".file-title").textContent.trim();
                    const fileId = fileElement.getAttribute('data-file-id');
                    
                    // Save file info to session storage
                    sessionStorage.setItem('childFileId', fileId);
                    sessionStorage.setItem('childFileTitle', `Delete ${fileName}`);
                    
                    // Get the delete form and update it
                    const form = document.getElementById('delete_file_form');
                    if (!form) return;
                    
                    // Enable any previously disabled inputs
                    Array.from(form.querySelectorAll('input[disabled]')).forEach(input => {
                        input.disabled = false;
                    });
                    
                    // Set file ID in the form
                    let fileIdInput = form.querySelector('input[name="file_id"]');
                    if (!fileIdInput) {
                        fileIdInput = document.createElement('input');
                        fileIdInput.type = 'hidden';
                        fileIdInput.name = 'file_id';
                        fileIdInput.setAttribute('data-encrypt', 'true');
                        form.appendChild(fileIdInput);
                    }
                    fileIdInput.value = fileId;
                    
                    // Set file name in the form
                    let fileNameInput = form.querySelector('input[name="file_name"]');
                    if (!fileNameInput) {
                        fileNameInput = document.createElement('input');
                        fileNameInput.type = 'hidden';
                        fileNameInput.name = 'file_name';
                        fileNameInput.setAttribute('data-encrypt', 'true');
                        form.appendChild(fileNameInput);
                    }
                    fileNameInput.value = fileName;
                    
                    // Update popup title
                    const popupTitle = deleteFilePopup.querySelector('h2');
                    if (popupTitle) {
                        popupTitle.textContent = `Delete ${fileName}`;
                    }
                    
                    // Clear any previous user input
                    const keyInput = form.querySelector('input[name="decryption_key"]');
                    if (keyInput) {
                        keyInput.value = '';
                    }
                    
                    // Show the delete popup
                    openPopup(deleteFilePopup);
                    
                    console.log("Delete popup opened for file:", fileName, "ID:", fileId);
                });
            }
        });
    }

    // Check if we need to show the upload form due to errors
    if (typeof hasUploadErrors !== 'undefined' && hasUploadErrors === true) {
        openPopup(uploadForm);
    }

    // Check if we need to show the preview popup due to errors
    showPopupErrors(hasPreviewErrors, 'openFile', 'open_file_form');

    // Check if we need to show the download popup due to errors
    showPopupErrors(hasDownloadErrors, 'downloadFile', 'download_file_form');

    // Check if we need to show the delete popup due to errors
    showPopupErrors(hasDeleteErrors, 'deleteFile', 'delete_file_form');

    // Check if we need to show the share popup due to errors
    // showPopupErrors(shareErrors, 'shareFile', 'share_file_form');
    if (typeof shareErrors != 'undefined'  && shareErrors ===  true) {
        const fileId = new URLSearchParams(window.location.search).get('file_id');
        if (fileId) {
            const fileElement = document.querySelector(`file[data-file-id="${fileId}"]`);
            const fileName = fileElement ? fileElement.querySelector(".file-title").textContent.trim() : "File";
            const sharePopup = document.getElementById('shareFile');
            if (sharePopup) {
                openPopup(sharePopup);
                // update title
                const popupTitle = sharePopup.querySelector('h2');
                if (popupTitle) {
                    popupTitle.textContent = `Share ${fileName}`;
                }
                // update hidden file_id
                const fileIdInput = sharePopup.querySelector('input[name="file_id"]');
                if (fileIdInput) {
                    fileIdInput.value = fileId;
                }
                // make form visible
                const formArea = sharePopup.querySelector('form');
                const successArea = sharePopup.querySelector('.share-success');
                if (formArea) {
                    formArea.style.display = 'block';
                }
                if (successArea) {
                    successArea.style.display = 'none';
                }
            }
        }

    }
    
    // Hide any loading overlay that might still be visible
    if (typeof hideLoadingOverlay === 'function') {
        hideLoadingOverlay();
    }
});
