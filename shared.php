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
                        <button class="action-btn"><i class="fa-solid fa-code-compare"></i>Calculate hashes</button>
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
                    <li class="active-cat">All</li>
                    <li>Active</li>
                    <li>Expired</li>
                    <li>Password Protected</li>
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
                            <div class="main-cont share-item">
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
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
        
        // Setup category filtering
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
                    
                    if (categoryText === 'All') {
                        file.closest('.main-cont').style.display = 'block';
                    } else if (categoryText === 'Active' && shareClass.includes('active')) {
                        file.closest('.main-cont').style.display = 'block';
                    } else if (categoryText === 'Expired' && shareClass.includes('expired')) {
                        file.closest('.main-cont').style.display = 'block';
                    } else if (categoryText === 'Password Protected' && shareClass.includes('password-protected')) {
                        file.closest('.main-cont').style.display = 'block';
                    } else {
                        file.closest('.main-cont').style.display = 'none';
                    }
                });
            });
        });
        
        // Setup search functionality
        const searchInput = document.getElementById('search_files');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                document.querySelectorAll('.file').forEach(function(file) {
                    const fileName = file.querySelector('.file-title').textContent.toLowerCase();
                    const mainCont = file.closest('.main-cont');
                    
                    if (fileName.includes(searchTerm)) {
                        mainCont.style.display = 'block';
                    } else {
                        mainCont.style.display = 'none';
                    }
                });
            });
        }
        
        // If there are revoke errors or success, auto-close after 5 seconds
        setTimeout(function() {
            const errorMessages = document.getElementById('errorMessages');
            const successMessages = document.getElementById('successMessage');
            
            if (errorMessages) {
                errorMessages.style.transition = 'opacity 1s';
                errorMessages.style.opacity = '0';
                setTimeout(() => errorMessages.remove(), 1000);
            }
            
            if (successMessages) {
                successMessages.style.transition = 'opacity 1s';
                successMessages.style.opacity = '0';
                setTimeout(() => successMessages.remove(), 1000);
            }
        }, 5000);
        
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
    });
    
    // Function to copy text to clipboard
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
</body>
</html>