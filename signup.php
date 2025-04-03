<?php
    require_once __DIR__ . '/includes/config/config_session.inc.php';
    require_once __DIR__ . '/includes/auth/signup_view.inc.php';
    
    if (!isset($_SESSION["user_id"])) { ?>
        <!DOCTYPE html>
        <html>
        <head>
            <?php include __DIR__ . "/includes/templates/header.php"; ?>
            <title>Sign Up</title>
            <!-- JSEncrypt library for RSA encryption -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js"></script>
            <script src="/js/form-encryption.js"></script>

            <script src="https://www.google.com/recaptcha/api.js"></script>
            <!-- submitting recapture token -->
            <script>
            function onSubmit(token) {
                // document.getElementById("signup_form").submit();
                console.log("reCAPTCHA validation successful");
            }
            </script>
        </head>
        <body class="signup">
            <?php include __DIR__ . "/includes/templates/nav.php"; ?>
            <div class="signup-form">
                <div class="container">
                    <h2>Sign Up</h2>
                    <p class="caption-text">
                        Welcome to Share! <br>
                        Create an account to start sharing
                    </p>
                    <form action="/includes/auth/signup.inc.php" method="post" id="signup_form" class="secure-form">
                        <?php
                            check_signup_errors();
                        ?>
                        <?php 
                            signup_inputs();
                        ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                        <div class="verify-pass">
                            <div class="level0 strong0"></div>
                            <div class="level1 strong1"></div>
                            <div class="level2 strong2"></div>
                            <div class="level3 strong3"></div>
                            <p class="caption-text" id="pass_check">short</p>
                        </div>
                        <!-- Submit button with reCAPTCHA trigger -->
                        <button class="g-recaptcha btn black-btn" 
                                data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                                data-callback='onSubmit' 
                                data-action='submit'>Sign Up</button>
                        <!-- <button class="btn black-btn" type="submit">Sign Up</button> -->
                    </form>
                    <a href="/" class="btn white-btn">Back to Home</a>
                </div>
                <p class="caption-text have-account">Have an account? <a href="/login.php" class="active-text">Sign In</a></p>
            </div>
            <?php include __DIR__ . "/includes/templates/footer.php"; ?>
        </body>
        </html>
<?php }  else { 
        header("Location: /dashboard.php?you_are_already_logged_in");
    } ?>