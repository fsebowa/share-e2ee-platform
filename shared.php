<?php
    require_once __DIR__ . '/includes/config/config_session.inc.php';
    require_once __DIR__ . '/includes/auth/auth_checker.inc.php';
    require_once __DIR__ . '/includes/config/dbh.inc.php';
    require_once __DIR__ . '/includes/file_management/file_model.inc.php';
    require_once __DIR__ . '/includes/file_management/file_view.inc.php';
    require_once __DIR__ . '/includes/share/share_model.inc.php';
    require_once __DIR__ . '/includes/share/share_contr.inc.php';

    check_login_otp_status(); 

    // Get user's shared files
    $user_shared_files = get_user_shared_files_with_details($pdo, $_SESSION["user_id"] ?? 0);
    
    // Define error/success flags for JavaScript
    $hasRevokeErrors = isset($_SESSION['share_revoke_error']) && !empty($_SESSION['share_revoke_error']);
    $hasRevokeSuccess = isset($_SESSION['share_revoke_success']) && !empty($_SESSION['share_revoke_success']);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Shared Files</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- JSEncrypt library for RSA encryption -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script src="/js/profile-popup.js"></script>
    <script src="/js/progress-bar.js"></script>
    <script src="/js/form-encryption.js"></script>
    <script src="/js/dashboard_func.js"></script>
    <script src="/js/share.js"></script>
    <script src="/js/shared.js"></script>
    <script>
        // Make error/success flags available to JavaScript
        window.hasRevokeErrors = <?php echo $hasRevokeErrors ? 'true' : 'false'; ?>;
        window.hasRevokeSuccess = <?php echo $hasRevokeSuccess ? 'true' : 'false'; ?>;
    </script>
</head>
<body class="dashboard">
    <?php include __DIR__ . "/includes/templates/dashboard_nav.php"; ?>
    <div class="container dash-flex">
        <div class="left-menu" style="justify-content: flex-start;">
            <button id="add_file"><span style="font-size: 18px;">+</span> New</button>
            <div class="left-menu-top"  style="top: 55px;">
                <div class="action-buttons">
                    <div class="btn-container">
                        <button class="action-btn"><a href="/dashboard.php"><i class="fa-regular fa-folder-open"></i> Files</a></button>
                        <button class="action-btn active-btn"><i class="fa-solid fa-share-nodes"></i>Shared</button>
                    </div>
                </div>
                <div class="action-buttons">
                    <div class="btn-container">   
                        <button class="action-btn"><a href="/encrypt-decrypt.php"><i class="fa-solid fa-user-lock"></i>Encrypt/ Decrypt</a></button>
                        <button class="action-btn"><i class="fa-solid fa-code-compare"></i><a href="/hashing.php">Calculate hashes</a></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-dashboard">
            <div class="search-sort-container dash-box">
                <div class="search-bar">
                    <form action="">
                        <input type="search" name="search_files" id="search_files" placeholder="Search your shared files">
                        <button><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>
                <div class="sort dash-box">
                    <p>Sort by:</p>
                    <form action="">
                        <select class="btn-form btn1" name="sort_files" id="sort_files">
                            <option value="Date Added">Date Shared</option>
                            <option value="Name">Name</option>
                            <option value="Expiry">Expiration Date</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="categories dash-box">
                <h4>Categories:</h4>
                <ul class="dash-box">
                    <li class="active-cat" data-category="All">All</li>
                    <li data-category="Active">Active</li>
                    <li data-category="Expired">Expired</li>
                    <li data-category="Password Protected">Password Protected</li>
                </ul>
            </div>
            
            <div class="dash-files">
                <?php 
                // Check for error or success messages
                if (isset($_SESSION['share_revoke_error'])) {
                    echo '<div class="error-messages" id="errorMessages">';
                    echo '<p class="error-danger">' . htmlspecialchars($_SESSION['share_revoke_error']) . '</p>';
                    echo '</div>';
                    unset($_SESSION['share_revoke_error']);
                }
                
                if (isset($_SESSION['share_revoke_success'])) {
                    echo '<div class="success-messages" id="successMessage">';
                    echo '<p class="success-message">' . htmlspecialchars($_SESSION['share_revoke_success']) . '</p>';
                    echo '</div>';
                    unset($_SESSION['share_revoke_success']);
                }
                ?>
                
                <!-- Display shared files -->
                <?php if (empty($user_shared_files)) { ?>
                    <p class="empty-dash">You haven't shared any files yet</p>
                <?php } else { ?>
                    <div class="uploaded-files">
                        <?php foreach ($user_shared_files as $share) { 
                            // Calculate days until expiry
                            $expiry = new DateTime($share['expiry_date']);
                            $now = new DateTime();
                            $interval = $now->diff($expiry);
                            $is_expired = $expiry < $now;
                            $days_left = $interval->days;
                            
                            // Generate share URL
                            $share_url = generate_share_url($share['share_token']);
                            
                            // Determine share category class
                            $share_class = $is_expired ? 'expired' : 'active';
                            $share_class .= !empty($share['access_password']) ? ' password-protected' : '';
                        ?>
                            <div class="main-cont share-item" id="share-item-<?php echo htmlspecialchars($share['id']); ?>">
                                <div class="file col" 
                                    data-file-type="<?php echo htmlspecialchars($share['file_type'] ?? 'Document'); ?>" 
                                    data-file-id="<?php echo htmlspecialchars($share['file_id']); ?>"
                                    data-share-id="<?php echo htmlspecialchars($share['id']); ?>"
                                    data-share-url="<?php echo htmlspecialchars($share_url); ?>"
                                    data-share-class="<?php echo $share_class; ?>">
                                    <div class="top row">
                                        <p class="file-title truncate"><?php echo htmlspecialchars($share['original_name'] ?? $share['file_name'] ?? 'Shared File'); ?></p>
                                        <p class="caption-text" style="display: flex; align-items: center; gap: 2px;"> 
                                            <?php if (!empty($share['shared_with'])): ?>
                                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90px; display: inline-block;">
                                                <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($share['shared_with']); ?>
                                            </span> |
                                            <?php endif; ?>
                                            <span><?php echo isset($share['file_size']) ? number_format($share['file_size'] / 1024 /1024, 2) . ' MB' : 'Unknown size'; ?></span>
                                        </p>
                                    </div>
                                    <div class="bottom row">
                                        <p class="caption-text">
                                            Created: <?php echo isset($share['created_at']) ? date('M j, Y', strtotime($share['created_at'])) : 'Unknown date'; ?>
                                        </p>
                                        <span class="elipse-menu">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </span>
                                    </div>
                                    <div class="file-menu-popup">
                                        <ul>
                                            <li><a href="<?php echo htmlspecialchars($share_url); ?>" target="_blank" rel="noopener noreferrer">Open link</a></li>
                                            <li class="copy-link-btn">Copy link</li>
                                            <li class="revoke-link-btn">Revoke link</li>
                                        </ul>
                                    </div>
                                </div>
                                <?php if ($is_expired): ?>
                                    <p class="error-danger">Expired</p>
                                <?php elseif ($days_left == 0): ?>
                                    <p class="error-danger">Expires today</p>
                                <?php elseif ($days_left == 1): ?>
                                    <p class="error-danger">Expires tomorrow</p>
                                <?php else: ?>
                                    <p class="error-danger">Expires in <?php echo $days_left; ?> days</p>
                                <?php endif; ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
            
            <!-- Delete progress -->
            <div class="progress-backdrop delete-progress" id="deleteProgressBackdrop">
                <div id="deleteProgressContainer">
                    <div class="progress-container">
                        <i class="fa-solid fa-trash-can"></i>
                        <div class="progress-bar" id="deleteProgressBar"></div>
                        <p>Revoking link...</p>
                    </div>
                </div>
            </div>
            
            <!-- Copy link popup -->
            <div class="file-ellipse-popup" id="copyShareLink">
                <form id="copy_link_form">
                    <h2>Copy Share Link</h2>
                    <p class="caption-text">Share this link to provide access to your file</p>
                    <div class="form-inputs">
                        <input type="url" id="shareLink" readonly>
                    </div>
                    <button type="button" class="btn black-btn" onclick="copyToClipboard('shareLink')">Copy Link</button>
                    <span id="shareLink_copied" class="copied-message">Copied!</span>
                </form>
            </div>

            <!-- Revoke link popup -->
            <div class="file-ellipse-popup" id="deleteLink">
                <form action="/includes/share/revoke_share.inc.php" method="post" id="delete_link_form" class="secure-form">
                    <h2>Revoke Share Link</h2>
                    <p class="error-danger">This will permanently remove access and cannot be undone.</p>
                    <p class="caption-text">Anyone with this link will lose access immediately!</p>
                    <div class="form-inputs">
                        <input type="hidden" name="share_id" id="revoke_share_id">
                        <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                    </div>
                    <button class="btn red-btn">Revoke Link</button>
                </form>
            </div>
            
            <!-- Upload form (same as dashboard for consistency) -->
            <div class="upload-form" id="uploadForm">
                <form action="/includes/file_management/file_upload.inc.php" method="post" id="file_upload_form" enctype="multipart/form-data" class="secure-form">
                    <h2>Upload new File</h2>
                    <p class="caption-text">All files uploaded are encrypted</p>
                    <div class="form-inputs">
                        <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>">
                        <input type="text" id="file_name" name="file_name" placeholder="File name" data-encrypt="true">
                        <div class="form-box">
                            <input type="text" id="key" name="key" placeholder="Enter a 256-bit key (64 hex characters)" data-encrypt="true"> <span>or</span>
                            <span class="btn gray-btn" id="generate_key_button">Generate Key</span>
                        </div>
                        <span class="caption-text">You will receive an email with the key</span>
                        <div class="custom-input">
                            <span id="file-name" class="file-name">Select file</span>
                            <label for="file" class="custom-file-label">Choose File</label>
                            <input type="file" name="file" id="file" class="custom-file-input">
                        </div>
                    </div>
                    <!-- Submit button with reCAPTCHA trigger -->
                    <button class="g-recaptcha btn white-btn" 
                        data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                        data-callback='onSubmit' 
                        data-action='submit'><i class="fa-solid fa-upload"></i> Upload</button><br>
                </form>
                <span class="caption-text" style="font-size: 14px;">Your files are end-to-end encrypted</span>
            </div>
        </div>
    </div>
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
    <!-- Direct ellipse menu fix script -->
    <script>
    // Ensure this runs after all other scripts
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Running ellipse menu fix');
        
        // Function to close all popups
        function closeAllPopups() {
            document.querySelectorAll('.file-menu-popup, .file-ellipse-popup').forEach(function(popup) {
                popup.style.display = 'none';
            });
        }
        
        // Close popups when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.file-menu-popup') && 
                !e.target.closest('.elipse-menu') && 
                !e.target.closest('.file-ellipse-popup')) {
                closeAllPopups();
            }
        });
        
        // Add fresh click handlers to ellipse menu icons
        document.querySelectorAll('.elipse-menu').forEach(function(menu) {
            // Remove existing handlers by cloning
            const newMenu = menu.cloneNode(true);
            menu.parentNode.replaceChild(newMenu, menu);
            
            // Add fresh handler
            newMenu.addEventListener('click', function(e) {
                e.stopPropagation();
                console.log('Ellipse menu clicked');
                
                // Get the menu popup
                const fileContainer = this.closest('.file');
                const menuPopup = fileContainer.querySelector('.file-menu-popup');
                
                // Toggle visibility (close if already open)
                const isAlreadyOpen = (menuPopup.style.display === 'block');
                
                // Close all menus first
                document.querySelectorAll('.file-menu-popup').forEach(function(popup) {
                    popup.style.display = 'none';
                });
                
                // Toggle this menu
                if (!isAlreadyOpen) {
                    menuPopup.style.display = 'block';
                }
            });
        });
        
        // Add fresh click handlers to menu items
        document.querySelectorAll('.copy-link-btn').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                
                const fileElement = this.closest('.file');
                const shareUrl = fileElement.getAttribute('data-share-url');
                const fileName = fileElement.querySelector('.file-title').textContent.trim();
                
                // Update popup content
                const popup = document.getElementById('copyShareLink');
                popup.querySelector('h2').textContent = 'Copy Link: ' + fileName;
                document.getElementById('shareLink').value = shareUrl;
                
                // Hide any previous copied message
                const copiedMsg = document.getElementById('shareLink_copied');
                if (copiedMsg) copiedMsg.style.display = 'none';
                
                // Close all other popups and show this one
                closeAllPopups();
                popup.style.display = 'block';
            });
        });
        
        document.querySelectorAll('.revoke-link-btn').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                
                const fileElement = this.closest('.file');
                const shareId = fileElement.getAttribute('data-share-id');
                const fileName = fileElement.querySelector('.file-title').textContent.trim();
                
                // Update popup content
                const popup = document.getElementById('deleteLink');
                popup.querySelector('h2').textContent = 'Revoke Share: ' + fileName;
                document.getElementById('revoke_share_id').value = shareId;
                
                // Close all other popups and show this one
                closeAllPopups();
                popup.style.display = 'block';
            });
        });
        
        // Make sure all file elements are displayed correctly initially
        document.querySelectorAll('.file').forEach(function(file) {
            file.style.display = 'flex';
        });
        
        // Make sure all ellipse menu icons are visible
        document.querySelectorAll('.elipse-menu').forEach(function(icon) {
            icon.style.display = 'inline-block';
            icon.style.visibility = 'visible';
        });
    });
    
    // Global copy to clipboard function
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
    </script>
    <script>
// Enhanced category filtering function
function enhancedCategoryFiltering() {    
    document.querySelectorAll('.categories li').forEach(function(category) {
        // First, clone the element to remove any existing handlers
        const newCategory = category.cloneNode(true);
        if (category.parentNode) {
            category.parentNode.replaceChild(newCategory, category);
        }
        
        // Add fresh click handler
        newCategory.addEventListener('click', function(e) {
            console.log('Category clicked:', this.textContent);
            
            // Remove active class from all categories
            document.querySelectorAll('.categories li').forEach(function(cat) {
                cat.classList.remove('active-cat');
            });
            
            // Add active class to clicked category
            this.classList.add('active-cat');
            
            // Get category from data attribute or text content
            const categoryText = this.getAttribute('data-category') || this.textContent.trim();
            console.log('Filtering by category:', categoryText);
            
            // Count visible items for debug
            let visibleCount = 0;
            
            // Show/hide files based on category
            document.querySelectorAll('.main-cont.share-item').forEach(function(container) {
                const fileElement = container.querySelector('.file');
                if (!fileElement) return;
                
                // Get share class from data attribute
                const shareClass = fileElement.getAttribute('data-share-class') || '';
                
                // Determine if this file should be shown for this category
                let shouldShow = false;
                
                if (categoryText === 'All') {
                    shouldShow = true;
                } else if (categoryText === 'Active' && shareClass.includes('active')) {
                    shouldShow = true;
                } else if (categoryText === 'Expired' && shareClass.includes('expired')) {
                    shouldShow = true;
                } else if (categoryText === 'Password Protected' && shareClass.includes('password-protected')) {
                    shouldShow = true;
                }
                
                // Apply display styling to the main container
                container.style.display = shouldShow ? 'block' : 'none';
                
                // Make sure the file element itself is visible within the container
                if (shouldShow) {
                    fileElement.style.display = 'flex';
                    visibleCount++;
                }
            });
            
            console.log('Visible items after filtering:', visibleCount);
            
            // Show "No files" message if no items visible
            const filesContainer = document.querySelector('.dash-files');
            const existingEmptyMessage = document.querySelector('.empty-dash');
            
            if (visibleCount === 0 && !existingEmptyMessage) {
                // Create and add empty message if none exists
                if (filesContainer) {
                    const uploadedFiles = filesContainer.querySelector('.uploaded-files');
                    if (uploadedFiles) {
                        // Hide the files container but don't remove it
                        uploadedFiles.style.display = 'none';
                    }
                    
                    const message = document.createElement('p');
                    message.className = 'empty-dash';
                    message.textContent = 'No files in this category';
                    filesContainer.appendChild(message);
                }
            } else if (visibleCount > 0 && existingEmptyMessage) {
                // Remove empty message if files are visible
                existingEmptyMessage.remove();
                
                // Make sure uploaded files container is visible
                const uploadedFiles = filesContainer.querySelector('.uploaded-files');
                if (uploadedFiles) {
                    uploadedFiles.style.display = 'block';
                }
            }
        });
    });
    
    // Trigger "All" category on initial load
    const allCategory = document.querySelector('.categories li[data-category="All"]') || 
                        document.querySelector('.categories li:first-child');
    if (allCategory) {
        allCategory.click();
    }
}

// Initialize on document ready
document.addEventListener('DOMContentLoaded', function() {
    // Setup enhanced category filtering
    enhancedCategoryFiltering();
});
</script>
</body>
</html>