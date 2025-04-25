// JavaScript for the shared files page
document.addEventListener('DOMContentLoaded', function() {
    console.log('shared.js loaded');
    
    // Initialize popup behaviors first
    setupPopupBehavior();
    
    // Setup menu items and other functionality
    setupEllipseMenus();
    setupLinkButtons();
    setupSearch();
    setupSorting();
    
    // Handle any pending operations (like deleted shares)
    checkRevokedShares();
    
    // Auto-dismiss messages after a delay
    setTimeout(hideMessages, 5000);
});

function setupPopupBehavior() {
    console.log('Setting up popup behavior');
    
    // Close popups when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.file-menu-popup') && 
            !e.target.closest('.elipse-menu') && 
            !e.target.closest('.file-ellipse-popup') &&
            !e.target.closest('.copy-link-btn') &&
            !e.target.closest('.revoke-link-btn')) {
            
            closeAllPopups();
        }
    });
    
    // Prevent propagation when clicking inside popups
    document.querySelectorAll('.file-ellipse-popup').forEach(popup => {
        if (popup) {
            popup.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
    
    // Ensure all menus start hidden
    document.querySelectorAll('.file-menu-popup').forEach(popup => {
        popup.style.display = 'none';
    });
    
    // Make sure all ellipse menu icons are visible
    document.querySelectorAll('.elipse-menu').forEach(menu => {
        menu.style.display = 'inline-block';
        menu.style.visibility = 'visible';
    });
}


function setupEllipseMenus() {
    console.log('Setting up ellipse menus');
    
    document.querySelectorAll('.elipse-menu').forEach(menu => {
        // Remove any existing listeners by cloning the element
        const newMenu = menu.cloneNode(true);
        if (menu.parentNode) {
            menu.parentNode.replaceChild(newMenu, menu);
        }
        
        // Add fresh click handler
        newMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('Ellipse menu clicked');
            
            // Get the menu popup
            const fileContainer = this.closest('.file');
            if (!fileContainer) {
                console.error('No parent file container found');
                return;
            }
            
            const menuPopup = fileContainer.querySelector('.file-menu-popup');
            if (!menuPopup) {
                console.error('No menu popup found');
                return;
            }
            
            // Check if this popup is already visible
            const isAlreadyOpen = (menuPopup.style.display === 'block');
            
            // Close all popups first
            closeAllPopups();
            
            // Only open this one if it wasn't already open
            if (!isAlreadyOpen) {
                menuPopup.style.display = 'block';
                console.log('Menu opened');
            }
        });
    });
}

function setupLinkButtons() {
    console.log('Setting up link buttons');
    
    // Setup copy link buttons
    document.querySelectorAll('.copy-link-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('Copy link clicked');
            
            const fileElement = this.closest('.file');
            if (!fileElement) return;
            
            const shareUrl = fileElement.getAttribute('data-share-url');
            const fileName = fileElement.querySelector('.file-title').textContent.trim();
            
            // Update popup content
            const popup = document.getElementById('copyShareLink');
            if (!popup) {
                console.error('Copy share link popup not found');
                return;
            }
            
            const title = popup.querySelector('h2');
            if (title) title.textContent = 'Copy Link: ' + fileName;
            
            const input = document.getElementById('shareLink');
            if (input) input.value = shareUrl;
            
            // Hide any previous copied message
            const copiedMsg = document.getElementById('shareLink_copied');
            if (copiedMsg) copiedMsg.style.display = 'none';
            
            // Close all popups and show this one
            closeAllPopups();
            popup.style.display = 'block';
        });
    });
    
    // Setup revoke link buttons
    document.querySelectorAll('.revoke-link-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('Revoke link clicked');
            
            const fileElement = this.closest('.file');
            if (!fileElement) return;
            
            const shareId = fileElement.getAttribute('data-share-id');
            const fileName = fileElement.querySelector('.file-title').textContent.trim();
            
            // Update popup content
            const popup = document.getElementById('deleteLink');
            if (!popup) {
                console.error('Delete link popup not found');
                return;
            }
            
            const title = popup.querySelector('h2');
            if (title) title.textContent = 'Revoke Share: ' + fileName;
            
            const input = document.getElementById('revoke_share_id');
            if (input) input.value = shareId;
            
            // Close all popups and show this one
            closeAllPopups();
            popup.style.display = 'block';
        });
    });
    
    // Setup revoke form submission
    const deleteLinkForm = document.getElementById('delete_link_form');
    if (deleteLinkForm) {
        deleteLinkForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const shareIdInput = document.getElementById('revoke_share_id');
            if (!shareIdInput) return;
            
            const shareId = shareIdInput.value;
            
            // Store the share ID in session storage for use after redirect
            sessionStorage.setItem('revokedShareId', shareId);
            
            // Show loading overlay
            const deleteProgressBackdrop = document.getElementById('deleteProgressBackdrop');
            if (deleteProgressBackdrop) {
                deleteProgressBackdrop.style.display = 'block';
            }
            
            // Submit the form
            this.submit();
            
            // Provide visual feedback immediately
            const fileContainer = document.getElementById('share-item-' + shareId);
            if (fileContainer) {
                fileContainer.style.opacity = '0.5';
            }
        });
    }
}

function setupSearch() {
    console.log('Setting up search');
    
    const searchInput = document.getElementById('search_files');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            document.querySelectorAll('.main-cont.share-item').forEach(function(container) {
                const fileTitle = container.querySelector('.file-title');
                if (!fileTitle) return;
                
                const fileName = fileTitle.textContent.toLowerCase();
                
                if (fileName.includes(searchTerm)) {
                    container.style.display = 'block';
                } else {
                    container.style.display = 'none';
                }
            });
        });
    }
}

function setupSorting() {
    console.log('Setting up sorting');
    
    const sortDropdown = document.getElementById('sort_files');
    if (sortDropdown) {
        sortDropdown.addEventListener('change', function() {
            sortSharedFiles(this.value);
        });
        
        // Initialize with default sort
        sortSharedFiles(sortDropdown.value);
    }
}

function sortSharedFiles(sortBy) {
    console.log('Sorting files by:', sortBy);
    
    const filesContainer = document.querySelector('.uploaded-files');
    if (!filesContainer) return;
    
    // Get all file containers
    const fileContainers = Array.from(filesContainer.querySelectorAll('.main-cont.share-item'));
    if (fileContainers.length === 0) return;
    
    // Sort the containers
    fileContainers.sort((a, b) => {
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
    
    // Re-append all containers in the new order
    fileContainers.forEach(container => container.remove());
    fileContainers.forEach(container => filesContainer.appendChild(container));
}


function checkRevokedShares() {
    console.log('Checking for revoked shares');
    
    // Check for shares revoked via form submission
    const revokedShareId = sessionStorage.getItem('revokedShareId');
    if (revokedShareId) {
        console.log('Found revoked share ID in session storage:', revokedShareId);
        removeShareFromUI('share-item-' + revokedShareId);
        sessionStorage.removeItem('revokedShareId');
    }
    
    // Check for shares revoked via PHP redirect
    const urlParams = new URLSearchParams(window.location.search);
    const shareIdParam = urlParams.get('share_id');
    if (shareIdParam && urlParams.get('message') === 'share_revoked') {
        console.log('Found revoked share ID in URL:', shareIdParam);
        removeShareFromUI('share-item-' + shareIdParam);
        
        // Clean up URL to prevent repeated removal on refresh
        if (history.replaceState) {
            const newUrl = window.location.pathname;
            history.replaceState(null, document.title, newUrl);
        }
    }
}

function removeShareFromUI(shareContainerId) {
    console.log('Removing share from UI:', shareContainerId);
    
    const shareContainer = document.getElementById(shareContainerId);
    if (!shareContainer) {
        console.error('Share container not found:', shareContainerId);
        return;
    }
    
    // Animate removal
    shareContainer.style.transition = 'opacity 0.5s, height 0.5s, margin 0.5s';
    shareContainer.style.opacity = '0';
    shareContainer.style.height = '0';
    shareContainer.style.margin = '0';
    shareContainer.style.overflow = 'hidden';
    
    // Remove after animation
    setTimeout(() => {
        shareContainer.remove();
        
        // Check if there are no more shares
        const remainingShares = document.querySelectorAll('.main-cont.share-item');
        if (remainingShares.length === 0) {
            const filesContainer = document.querySelector('.dash-files');
            if (filesContainer) {
                filesContainer.innerHTML = '<p class="empty-dash">You haven\'t shared any files yet</p>';
            }
        }
    }, 500);
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

function closeAllPopups() {
    console.log('Closing all popups');
    
    // Close file menus
    document.querySelectorAll('.file-menu-popup').forEach(popup => {
        popup.style.display = 'none';
    });
    
    // Close action popups
    document.querySelectorAll('.file-ellipse-popup').forEach(popup => {
        popup.style.display = 'none';
    });
}

function copyToClipboard(elementId) {
    console.log('Copying to clipboard:', elementId);
    
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.select();
    document.execCommand('copy');
    
    // Show success message
    const messageId = elementId + '_copied';
    const message = document.getElementById(messageId);
    if (message) {
        message.style.display = 'inline';
        setTimeout(() => {
            message.style.display = 'none';
        }, 2000);
    }
}

// Make functions globally available
window.copyToClipboard = copyToClipboard;
window.closeAllPopups = closeAllPopups;
window.sortSharedFiles = sortSharedFiles;