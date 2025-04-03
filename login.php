<?php
    require_once __DIR__ . '/includes/config/config_session.inc.php';
    require_once __DIR__ . '/includes/auth/login_view.inc.php';

    if (!isset($_SESSION["user_id"])) { ?>
        <!DOCTYPE html>
        <html>
        <head>
            <?php include __DIR__ . "/includes/templates/header.php"; ?>
            <title>Log In</title>
            <!-- JSEncrypt library for RSA encryption -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js"></script>
            <script src="/js/form-encryption.js"></script>
            
            <script src="https://www.google.com/recaptcha/api.js"></script>
            <!-- submitting recapture token -->
            <script>
            function onSubmit(token) {
                // document.getElementById("login_form").submit();
                console.log("reCAPTCHA validation successful");
            }
            </script>
        </head>
        <body class="signin">
            <?php include __DIR__ . "/includes/templates/nav.php"; ?>
            <div class="signin-form">
                <div class="container">
                    <h2>Sign In</h2>
                    <p class="caption-text">
                        <?php if (isset($_SESSION["login_data"]) && 
                                isset($_SESSION["login_data"]["registered"]) && 
                                $_SESSION["login_data"]["registered"] === true && 
                                isset($_SESSION["login_data"]["first_name"])) { ?>
                            <span style="color: green;"><br>Hello <?php echo htmlspecialchars($_SESSION["login_data"]["first_name"]); ?>, your account was created successfully  </span><br><span>Login to continue</span>
                        <?php } else { ?>
                            Welcome back to Share! 
                        <?php } ?>
                    </p>
                    <form action="/includes/auth/login.inc.php" method="post" id="login_form" class="secure-form">
                        <?php
                            check_login_errors();
                        ?>
                        <div class="form-inputs">
                            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                            <?php 
                                login_inputs();
                            ?>
                            <div class="form-box">
                                <input type="password" id="password" name="password" placeholder="Password" data-encrypt="true">
                                <div class="pass-eye">
                                    <i class="fa-regular fa-eye-slash open-eye"></i>
                                    <i class="fa-regular fa-eye close-eye"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Submit button with reCAPTCHA trigger -->
                        <button class="g-recaptcha btn black-btn" 
                                data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                                data-callback='onSubmit' 
                                data-action='submit'>Sign In</button>
                        <!-- <button class="btn black-btn" type="submit">Sign Up</button> -->
                    </form>
                    <a href="/" class="btn white-btn">Back to Home</a>
                </div>
                <p class="caption-text have-account">Don't have an account? <a href="/signup.php" class="active-text">Sign Up</a></p>
            </div>
            <?php include __DIR__ . "/includes/templates/footer.php"; ?>
        </body>
        </html>
    <?php }  else { 
        header("Location: /dashboard.php?you_are_already_logged_in");
    } ?>
