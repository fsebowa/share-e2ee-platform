document.addEventListener('DOMContentLoaded', function() {
    // Initialize popup behaviors first
    setupPopupBehavior();
    
    // Setup ellipsis menu handlers with proper popup closing
    setupEllipsisMenuHandlers();
        
    // Then setup other functionality
    setupCategoryFiltering();
    setupSearch();
    setupFileSorting();
    
    // Handle any pending operations (like deleted shares)
    checkRevokedShares();
    
    // Auto-dismiss messages after a delay
    setTimeout(hideMessages, 5000);
});

/**
 * Initialize all functionality for the shared files page
 */
function initSharedFilesPage() {
    // Add ID to all share items
    addShareItemIds();
    
    // Setup menu item actions
    setupMenuItemActions();
    
    // Setup category filtering
    setupCategoryFiltering();
    
    // Setup search functionality
    setupSearch();
    
    // Setup sorting
    setupSorting();
    
    // Apply the initial category filter (All by default)
    applyInitialCategory();
}

/**
 * Add IDs to all share items to support removal and tracking
 */
function addShareItemIds() {
    document.querySelectorAll('.main-cont.share-item').forEach(function(item, index) {
        const fileElement = item.querySelector('.file');
        if (fileElement && !item.id) {
            const shareId = fileElement.getAttribute('data-share-id');
            if (shareId) {
                item.id = 'share-item-' + shareId;
            } else {
                item.id = 'share-item-unknown-' + index;
            }
        }
    });
}

/**
 * Apply the initial category filter when the page loads
 */
function applyInitialCategory() {
    // Get the "All" category element
    const allCategory = document.querySelector('.categories li[data-category="All"]');
    if (!allCategory) return;
    
    // Ensure it has the active-cat class
    document.querySelectorAll('.categories li').forEach(li => {
        li.classList.remove('active-cat');
    });
    allCategory.classList.add('active-cat');
    
    // Apply the filter to show all files initially
    document.querySelectorAll('.file').forEach(file => {
        const mainCont = file.closest('.main-cont');
        if (mainCont) {
            mainCont.style.display = 'block';
        }
    });
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
        deleteLinkForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get the share ID and file container reference
            const shareIdInput = document.getElementById('revoke_share_id');
            const shareId = shareIdInput.value;
            const fileContainerId = shareIdInput.dataset.fileContainer;
            
            // Store the share ID in session storage for use after redirect
            sessionStorage.setItem('revokedShareId', shareId);
            
            // Show loading overlay
            const deleteProgressBackdrop = document.getElementById('deleteProgressBackdrop');
            if (deleteProgressBackdrop) {
                deleteProgressBackdrop.style.display = 'block';
            }
            
            // Submit the form normally
            this.submit();
            
            // Remove the share element from DOM if immediate feedback is preferred
            if (fileContainerId) {
                const fileContainer = document.getElementById(fileContainerId);
                if (fileContainer) {
                    // Use setTimeout to give visual feedback before removing
                    setTimeout(() => {
                        fileContainer.style.opacity = '0.5';
                    }, 500);
                }
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
            
            const categoryText = this.getAttribute('data-category') || this.textContent;
            
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

function setupPopupBehavior() {
    // Get popup elements
    const copyLinkPopup = document.getElementById('copyShareLink');
    const revokeLinkPopup = document.getElementById('deleteLink');
    const allPopups = [copyLinkPopup, revokeLinkPopup];
    
    // Close popups when clicking outside
    document.addEventListener('click', function(e) {
        // If clicking on ellipsis menu, close all popups
        if (e.target.closest('.elipse-menu')) {
            allPopups.forEach(popup => {
                if (popup) popup.style.display = 'none';
            });
            return;
        }
        
        // Don't close if clicking inside popup or on specific buttons
        if (e.target.closest('.file-ellipse-popup') || 
            e.target.closest('.copy-link-btn') || 
            e.target.closest('.revoke-link-btn')) {
            return;
        }
        
        // Close popups otherwise
        allPopups.forEach(popup => {
            if (popup && popup.style.display === 'block') {
                popup.style.display = 'none';
            }
        });
    });
    
    // Prevent propagation when clicking inside popups
    allPopups.forEach(popup => {
        if (popup) {
            popup.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
    
    // Setup ellipsis menu to close all popups when clicked
    document.querySelectorAll('.elipse-menu').forEach(menu => {
        menu.addEventListener('click', function(e) {
            // Close all popups when ellipsis menu is clicked
            allPopups.forEach(popup => {
                if (popup) popup.style.display = 'none';
            });
        });
    });
}

function checkRevokedShares() {
    // First check sessionStorage for shares revoked via form submission
    const revokedShareId = sessionStorage.getItem('revokedShareId');
    if (revokedShareId) {
        removeShareFromUI('share-item-' + revokedShareId);
        sessionStorage.removeItem('revokedShareId');
    }
    
    // Also check URL parameters for shares revoked via PHP redirect
    const urlParams = new URLSearchParams(window.location.search);
    const shareIdParam = urlParams.get('share_id');
    if (shareIdParam && urlParams.get('message') === 'share_revoked') {
        removeShareFromUI('share-item-' + shareIdParam);
        
        // Clean up URL to prevent repeated removal on refresh
        if (history.replaceState) {
            const newUrl = window.location.pathname;
            history.replaceState(null, document.title, newUrl);
        }
    }
}

function removeShareFromUI(shareContainerId) {
    const shareContainer = document.getElementById(shareContainerId);
    if (!shareContainer) return;
    
    // Animate removal for better UX
    shareContainer.style.transition = 'opacity 0.5s, height 0.5s, margin 0.5s';
    shareContainer.style.opacity = '0';
    shareContainer.style.height = '0';
    shareContainer.style.margin = '0';
    shareContainer.style.overflow = 'hidden';
    
    // Remove from DOM after animation completes
    setTimeout(() => {
        shareContainer.remove();
        
        // Check if there are no more shares and show empty message if needed
        const remainingShares = document.querySelectorAll('.main-cont.share-item');
        if (remainingShares.length === 0) {
            const filesContainer = document.querySelector('.dash-files');
            if (filesContainer) {
                filesContainer.innerHTML = '<p class="empty-dash">You haven\'t shared any files yet</p>';
            }
        }
    }, 500);
}

function applyDefaultCategoryFilter() {
    // Ensure the 'All' category has the active-cat class
    const categories = document.querySelectorAll('.categories li');
    categories.forEach(cat => {
        cat.classList.remove('active-cat');
    });
    
    const allCategory = document.querySelector('.categories li:first-child');
    if (allCategory) {
        allCategory.classList.add('active-cat');
        
        // Apply the filter to show all files
        const files = document.querySelectorAll('.file');
        files.forEach(file => {
            const mainCont = file.closest('.main-cont');
            if (mainCont) {
                mainCont.style.display = 'block';
            }
        });
    }
}

function setupEllipsisMenuHandlers() {
    document.querySelectorAll('.elipse-menu').forEach(menu => {
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // First, close any open file-ellipse-popup elements
            closeAllPopups();
            
            // Then find the menu popup associated with this ellipsis
            const fileContainer = this.closest('.file');
            const menuPopup = fileContainer.querySelector('.file-menu-popup');
            
            // Check if this menu is already open
            const isMenuOpen = (menuPopup.style.display === 'block');
            
            // Close all menu popups first
            document.querySelectorAll('.file-menu-popup').forEach(popup => {
                popup.style.display = 'none';
            });
            
            // Toggle this menu (only open if it was closed before)
            if (!isMenuOpen) {
                menuPopup.style.display = 'block';
            }
        });
    });
}

function setupLinkActionHandlers() {
    // Copy link button handlers
    document.querySelectorAll('.copy-link-btn').forEach(button => {
        button.addEventListener('click', handleCopyLinkClick);
    });
    
    // Revoke link button handlers
    document.querySelectorAll('.revoke-link-btn').forEach(button => {
        button.addEventListener('click', handleRevokeLinkClick);
    });
}

function handleCopyLinkClick(e) {
    e.stopPropagation();
    
    // First, close all popups to prevent overlap
    closeAllPopups();
    
    const fileElement = this.closest('.file');
    const shareUrl = fileElement.getAttribute('data-share-url');
    const fileName = fileElement.querySelector('.file-title').textContent.trim();
    
    // Open copy link popup
    const popup = document.getElementById('copyShareLink');
    if (!popup) return;
    
    popup.querySelector('h2').textContent = 'Copy Link: ' + fileName;
    document.getElementById('shareLink').value = shareUrl;
    
    // Hide any existing copied message
    const copiedMsg = document.getElementById('shareLink_copied');
    if (copiedMsg) copiedMsg.style.display = 'none';
    
    // Display the popup
    popup.style.display = 'block';
}

function handleRevokeLinkClick(e) {
    e.stopPropagation();
    
    // First, close all popups to prevent overlap
    closeAllPopups();
    
    const fileElement = this.closest('.file');
    const shareId = fileElement.getAttribute('data-share-id');
    const fileName = fileElement.querySelector('.file-title').textContent.trim();
    
    // Store reference to file container for removal after revocation
    const fileContainer = fileElement.closest('.main-cont');
    
    // Open revoke link popup
    const popup = document.getElementById('deleteLink');
    if (!popup) return;
    
    popup.querySelector('h2').textContent = 'Revoke Share: ' + fileName;
    
    // Set the share ID and store file container reference
    const revokeShareIdInput = document.getElementById('revoke_share_id');
    revokeShareIdInput.value = shareId;
    
    // Add ID to container if needed for easy removal later
    if (fileContainer && !fileContainer.id) {
        fileContainer.id = 'share-item-' + shareId;
    }
    
    // Display the popup
    popup.style.display = 'block';
}

function closeAllPopups() {
    // Close file operation popups (copy link, revoke link)
    const filePopups = document.querySelectorAll('#copyShareLink, #deleteLink');
    filePopups.forEach(popup => {
        if (popup) popup.style.display = 'none';
    });
    
    // Close all file menu popups
    document.querySelectorAll('.file-menu-popup').forEach(popup => {
        popup.style.display = 'none';
    });
}


// Make functions globally available
window.copyToClipboard = copyToClipboard;
window.sortSharedFiles = sortSharedFiles;
window.hideMessages = hideMessages;
window.setupPopupBehavior = setupPopupBehavior;
window.checkRevokedShares = checkRevokedShares;