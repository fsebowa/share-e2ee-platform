/**
 * JavaScript for the shared files page
 * Manages UI interactions for shared files, including copying links and revoking shares
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize shared files functionality
    initSharedFilesPage();
    
    // Hide messages after 5 seconds
    setTimeout(hideMessages, 5000);
});

/**
 * Initialize all functionality for the shared files page
 */
function initSharedFilesPage() {
    // Setup menu item actions
    setupMenuItemActions();
    
    // Setup category filtering
    setupCategoryFiltering();
    
    // Setup search functionality
    setupSearch();
    
    // Setup sorting
    setupSorting();
}

/**
 * Set up handlers for ellipsis menu items
 */
function setupMenuItemActions() {
    // Setup handlers for copy link buttons
    document.querySelectorAll('.copy-link-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const fileElement = this.closest('.file');
            const shareUrl = fileElement.getAttribute('data-share-url');
            const fileName = fileElement.querySelector('.file-title').textContent.trim();
            
            // Open copy link popup
            const popup = document.getElementById('copyShareLink');
            popup.querySelector('h2').textContent = 'Copy Link: ' + fileName;
            document.getElementById('shareLink').value = shareUrl;
            
            // Hide any existing copied message
            document.getElementById('shareLink_copied').style.display = 'none';
            
            // Show the popup
            if (typeof closeAllPopups === 'function') {
                closeAllPopups();
            }
            popup.style.display = 'block';
        });
    });
    
    // Setup handlers for revoke link buttons
    document.querySelectorAll('.revoke-link-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const fileElement = this.closest('.file');
            const shareId = fileElement.getAttribute('data-share-id');
            const fileName = fileElement.querySelector('.file-title').textContent.trim();
            
            // Open revoke link popup
            const popup = document.getElementById('deleteLink');
            popup.querySelector('h2').textContent = 'Revoke Share: ' + fileName;
            document.getElementById('revoke_share_id').value = shareId;
            
            // Show the popup
            if (typeof closeAllPopups === 'function') {
                closeAllPopups();
            }
            popup.style.display = 'block';
        });
    });
    
    // Setup form submission for revoke link
    const deleteLinkForm = document.getElementById('delete_link_form');
    if (deleteLinkForm) {
        deleteLinkForm.addEventListener('submit', function() {
            // Show loading overlay
            const deleteProgressBackdrop = document.getElementById('deleteProgressBackdrop');
            if (deleteProgressBackdrop) {
                deleteProgressBackdrop.style.display = 'block';
            }
        });
    }
}

/**
 * Set up category filtering for the files display
 */
function setupCategoryFiltering() {
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
                const shareClass = file.getAttribute('data-share-class') || '';
                const mainCont = file.closest('.main-cont');
                
                if (!mainCont) return;
                
                if (categoryText === 'All') {
                    mainCont.style.display = 'block';
                } else if (categoryText === 'Active' && shareClass.includes('active')) {
                    mainCont.style.display = 'block';
                } else if (categoryText === 'Expired' && shareClass.includes('expired')) {
                    mainCont.style.display = 'block';
                } else if (categoryText === 'Password Protected' && shareClass.includes('password-protected')) {
                    mainCont.style.display = 'block';
                } else {
                    mainCont.style.display = 'none';
                }
            });
        });
    });
}

/**
 * Set up search functionality for files
 */
function setupSearch() {
    const searchInput = document.getElementById('search_files');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            document.querySelectorAll('.file').forEach(function(file) {
                const fileName = file.querySelector('.file-title').textContent.toLowerCase();
                const mainCont = file.closest('.main-cont');
                
                if (!mainCont) return;
                
                if (fileName.includes(searchTerm)) {
                    mainCont.style.display = 'block';
                } else {
                    mainCont.style.display = 'none';
                }
            });
        });
    }
}

/**
 * Set up sorting functionality for files
 */
function setupSorting() {
    const sortDropdown = document.getElementById('sort_files');
    if (sortDropdown) {
        sortDropdown.addEventListener('change', function() {
            sortSharedFiles(this.value);
        });
        
        // Initialize with default sort
        if (sortDropdown.value) {
            sortSharedFiles(sortDropdown.value);
        }
    }
}

/**
 * Sort shared files based on the selected criteria
 * @param {string} sortBy - The sorting criteria
 */
function sortSharedFiles(sortBy) {
    const filesContainer = document.querySelector('.uploaded-files');
    if (!filesContainer) return;
    
    // Get all visible file elements 
    const files = Array.from(filesContainer.querySelectorAll('.main-cont')).filter(file => 
        window.getComputedStyle(file).display !== 'none'
    );
    
    if (files.length === 0) return;
    
    // Sort the files
    files.sort((a, b) => {
        const fileA = a.querySelector('.file');
        const fileB = b.querySelector('.file');
        
        if (!fileA || !fileB) return 0;
        
        switch(sortBy) {
            case 'Name':
                const nameA = fileA.querySelector('.file-title').textContent.trim().toLowerCase();
                const nameB = fileB.querySelector('.file-title').textContent.trim().toLowerCase();
                return nameA.localeCompare(nameB);
                
            case 'Expiry':
                // Extract expiry text
                const expiryTextA = a.querySelector('.error-danger').textContent;
                const expiryTextB = b.querySelector('.error-danger').textContent;
                
                // Handle "Expired" case first
                if (expiryTextA === 'Expired' && expiryTextB !== 'Expired') return 1;
                if (expiryTextA !== 'Expired' && expiryTextB === 'Expired') return -1;
                if (expiryTextA === 'Expired' && expiryTextB === 'Expired') return 0;
                
                // Handle "Today" and "Tomorrow" cases
                if (expiryTextA === 'Expires today' && expiryTextB !== 'Expires today') return -1;
                if (expiryTextA !== 'Expires today' && expiryTextB === 'Expires today') return 1;
                if (expiryTextA === 'Expires tomorrow' && expiryTextB !== 'Expires tomorrow' && expiryTextB !== 'Expires today') return -1;
                if (expiryTextA !== 'Expires tomorrow' && expiryTextA !== 'Expires today' && expiryTextB === 'Expires tomorrow') return 1;
                
                // Extract days for other cases
                const daysA = parseInt(expiryTextA.match(/\d+/) || [0]);
                const daysB = parseInt(expiryTextB.match(/\d+/) || [0]);
                return daysA - daysB;
                
            case 'Date Added':
            default:
                const dateA = fileA.querySelector('.bottom .caption-text').textContent.replace('Created: ', '').trim();
                const dateB = fileB.querySelector('.bottom .caption-text').textContent.replace('Created: ', '').trim();
                return new Date(dateB) - new Date(dateA); // Sort from newest to oldest
        }
    });
    
    // Remove all sorted files from the container and re-add them in the new order
    files.forEach(file => file.remove());
    files.forEach(file => filesContainer.appendChild(file));
}

/**
 * Copy the contents of an input element to clipboard
 * @param {string} elementId - The ID of the input element
 */
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.select();
    document.execCommand('copy');
    
    // Show copied message
    const messageId = elementId + '_copied';
    const message = document.getElementById(messageId);
    if (message) {
        message.style.display = 'inline';
        setTimeout(() => {
            message.style.display = 'none';
        }, 2000);
    }
}

/**
 * Hide error and success messages with a fade effect
 */
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

// Make functions globally available
window.copyToClipboard = copyToClipboard;
window.sortSharedFiles = sortSharedFiles;
window.hideMessages = hideMessages;