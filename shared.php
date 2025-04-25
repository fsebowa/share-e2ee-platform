<?php
    require_once __DIR__ . '/includes/config/config_session.inc.php';
    require_once __DIR__ . '/includes/auth/auth_checker.inc.php';
    require_once __DIR__ . '/includes/config/dbh.inc.php';
    require_once __DIR__ . '/includes/file_management/file_model.inc.php';
    require_once __DIR__ . '/includes/file_management/file_view.inc.php';

    // check_login_otp_status(); 

    // get user's shared files
    $user_files = get_user_files($pdo, $_SESSION["user_id"] ?? 0);

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
                        <button class="action-btn active-btn"><i class="fa-solid fa-share-nodes"></i><a href="/dashboard/shared.php" target="_blank" rel="noopener noreferrer">Shared</a></button>
                    </div>
                </div>
                <div class="action-buttons">
                    <div class="btn-container">   
                        <button class="action-btn"><i class="fa-solid fa-user-lock"></i><a href="/encrypt-decrypt.php">Encrypt/ Decrypt</a></button>
                        <button class="action-btn"><i class="fa-solid fa-code-compare"></i>Calculate hashes</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-dashboard">
            <div class="search-sort-container dash-box">
                <div class="search-bar">
                    <form action="">
                        <input type="search" name="search_files" id="search_files" placeholder="Search your files">
                        <button><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>
                <div class="sort dash-box">
                    <p>Sort by:</p>
                    <form action="">
                        <select class="btn-form btn1" name="sort_files" id="sort_files">
                            <option value="Date Added">Date Added</option>
                            <option value="Name">Name</option>
                            <option value="Type">Type</option>
                            <option value="Size">Size</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="categories dash-box">
                <h4>Categories:</h4>
                <ul class="dash-box">
                    <li class="active-cat">All</li>
                    <li>Videos</li>
                    <li>Images</li>
                    <li>Audio</li>
                    <li>PDF</li>
                    <li>Documents</li>
                    <li>Spreadsheets</li>
                    <li>Presentations</li>
                    <li>Other</li>
                </ul>
            </div>
            
            <div class="dash-files">
                <!-- Query user files -->
                <?php if (empty($user_files)) {?>
                    <p class="empty-dash">Your shared files will appear here</p>
                <?php } else { ?>
                    <div class="uploaded-files">
                        <?php foreach ($user_files as $file) { ?>
                            <div class="main-cont">
                                <div class="file col" data-file-type="<?php echo htmlspecialchars($file['file_type']); ?>" data-file-id="<?php echo htmlspecialchars($file['id']); ?>">
                                    <div class="top row">
                                        <p class="file-title truncate"><?php echo htmlspecialchars($file['file_name']); ?></p>
                                        <p class="caption-text" style="display: flex; align-items: center; gap: 2px;"> 
                                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 70px; display: inline-block;"><?php echo htmlspecialchars($file['file_type']);?> </span>|
                                            <span><?php echo number_format($file['file_size'] / 1024 /1024, 2); ?> MB</span>
                                        </p>
                                    </div>
                                    <div class="bottom row">
                                        <p class="caption-text"><?php echo date('M j, Y', strtotime($file['date_uploaded'])); ?></p>
                                        <span class="elipse-menu">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </span>
                                    </div>
                                    <div class="file-menu-popup">
                                        <ul>
                                            <li><a href="#link-to-shared-file-goes-here" target="_blank" rel="noopener noreferrer">Open link</a></li>
                                            <li>Copy link</li>
                                            <li>Revoke link (delete)</li>
                                        </ul>
                                    </div>
                                </div>
                                <p class="error-danger">Expires in 3 days</p><!--dynamically inserted depending on amount of days left in database. If expiered, simply show 'Expired'-->
                            </div>
                        <?php } ?>
                    </div>
                <?php  } ?>
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
                <form action="/includes/share/" method="post" id="copy_link_form">
                    <h2></h2> <!-- File name will be inserted here dynamically using JavaScript-->
                    <p class="caption-text">Copy shared link for this file</p>
                    <div class="form-inputs">
                        <input type="url" name="sharelink" id="shareLink" readonly value="">
                    </div>
                    <!-- Submit button with reCAPTCHA trigger -->
                    <button class="success-messages" style="color: white;">Copy Link</button><br>
                </form>
            </div>

            <!-- Delete link popup -->
            <div class="file-ellipse-popup" id="deleteLink">
                <form action="/includes/share/" method="post" id="delete_link_form" class="secure-form">
                    <h2></h2> <!-- File name will be inserted here dynamically using JavaScript-->
                    <p class="error-danger">This will permanently delete the link and cannot be undone.</p> <br>
                    <p class="caption-text">Users with this link will lose access immediately!</p>
                    <div class="form-inputs">
                        <input type="hidden" name="csrf_token" value="<?php echo $token ?? ''; ?>"> 
                    </div>
                    <!-- Submit button with reCAPTCHA trigger -->
                    <button class="g-recaptcha btn red-btn" 
                        data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                        data-callback='onSubmitDeleteLink' 
                        data-action='submit'>Revoke Link</button><br>
                </form>
            </div>
        </div>
    </div>
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
</body>
</html>