<?php
    require_once __DIR__ . "/includes/auth/auth_checker.inc.php";
    require_once __DIR__ . "/includes/otp/otp_view.inc.php";
    check_logged_in_redirect_from_otp();
?>

<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>OTP Validation</title>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js"></script>
    <script src="/js/form-encryption.js"></script>
    <script>
    function onSubmit(token) {
        // const otpInput = document.querySelector('form #otp_code');
        // if (otpInput.value.length > 0) {
        //     showLoadingOverlay("Verifying OTP...");
        // }
        // document.getElementById("otp_form").submit();
        console.log("reCAPTCHA validation successful");
    }
    </script>
</head>
<body class="otp">
    <?php include __DIR__ . "/includes/templates/nav.php" ?>
    <div class="otp-form">
        <div class="container">
            <form action="/includes/otp/otp.inc.php" method="post" id="otp_form" class="secure-form">
                <h2>OTP Verification</h2>
                <p class="caption-text">
                    Enter code sent to <br>
                    <?php 
                        echo get_masked_email(); 
                        check_otp_errors();
                        check_success_messages();
                    ?>
                </p>
                <div class="form-inputs">
                    <input type="hidden" name="csrf_token" value="<?php echo $token ?>">
                    <input type="text" id="otp_code" name="otp_code" placeholder="OTP Code" data-encrypt="true">
                </div>
                <!-- Submit button with reCAPTCHA trigger -->
                <button class="g-recaptcha btn black-btn" 
                        data-sitekey="6LfncLgqAAAAABiQR-6AYNqjYPE2wFS5WsrPBAEj" 
                        data-callback='onSubmit' 
                        data-action='submit'>Verify and Continue</button>
                <!-- <button class="btn black-btn" type="submit">Sign Up</button> -->
            </form>
            <p class="caption-text">Didn't receive code?</p>
            <form action="/includes/otp/resend_otp.inc.php" method="post" id="new_otp">
                <button class="active-text" id="resendOtp" type="button">Resend Code</button>
            </form>
            <a href="/" class="btn white-btn">Back to Home</a>
        </div>
    </div>
    <?php include __DIR__ . "/includes/templates/footer.php" ?>
</body>
</html>