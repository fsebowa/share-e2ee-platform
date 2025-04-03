<?php
    require_once __DIR__ . "/includes/auth/auth_checker.inc.php";
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Share - Secure E2EE Platform</title>
</head>
<body class="home">
    <?php include __DIR__ . "/includes/templates/nav.php"; ?>
    <div class="welcome">
        <div class="conainer">
            <div class="content">
                <img src="/assets/images/lock-on-1.svg" alt="padlock">
                <h1>Bring privacy to your files</h1>
                <p class="caption-text">Securely share files with Share's end-to-end encryption</p>
                <?php 
                    if (!is_session_verified()) { ?>
                        <a href="/signup.php" class="btn black-btn">Start Sharing</a>
                    <?php } else { ?>
                            <a href="/dashboard.php" class="btn black-btn">Go to Dashboard</a>
                    <?php }
                ?>

                <?php ?>
            </div>
        </div>
    </div>
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
</body>
</html>