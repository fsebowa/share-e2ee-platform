<?php
    require_once __DIR__ . '/includes/config/config_session.inc.php';
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Privacy Policy - Share</title>
</head>
<body class="policies">
    <?php include __DIR__ . "/includes/templates/nav.php"; ?>
    
    <div class="privacy-policy">
        <h1>Privacy Policy</h1>
        
        <p>At Share, we're committed to protecting your privacy and ensuring the security of your data. This Privacy Policy explains how we collect, use, and safeguard your information when you use our end-to-end encrypted file sharing platform.</p>
        
        <h2>Information We Collect</h2>
        <p>We collect the following types of information:</p>
        <ul>
            <li><strong>Account Information:</strong> When you create an account, we collect your first name, last name, and email address.</li>
            <li><strong>File Metadata:</strong> We store metadata about your uploaded files such as file names, sizes, and upload dates. However, we do not have access to the actual content of your files as they are end-to-end encrypted.</li>
            <li><strong>Usage Information:</strong> We collect information about how you use our service, including login times, file sharing activities, and feature usage.</li>
        </ul>
        
        <h2>How We Use Your Information</h2>
        <p>We use your information for the following purposes:</p>
        <ul>
            <li>To notify you about changes and activity on your files.</li>
            <li>To detect, prevent and address technical issues</li>
        </ul>
        
        <h2>End-to-End Encryption</h2>
        <p>Our platform uses end-to-end encryption for all file storage and sharing. This means:</p>
        <ul>
            <li>Files are encrypted on your device before they are uploaded to our servers</li>
            <li>We never receive or store your encryption keys</li>
            <li>We cannot access the contents of your encrypted files</li>
            <li>Files remain encrypted during transit and while stored on our servers</li>
            <li>Only recipients with the correct decryption key can access the files you share</li>
        </ul>
        
        <h2>Email Communications</h2>
        <p>We send emails for account verification, notifications, and to deliver encryption keys when you choose that delivery method. We will never ask for your encryption keys via email.</p>
        
        <h2>Data Security</h2>
        <p>We implement strong security measures to protect your personal information, including:</p>
        <ul>
            <li>Using industry-standard encryption for all data transmission</li>
            <li>Employing secure session management with CSRF protection</li>
            <li>Requiring two-factor authentication via one-time passwords (OTP)</li>
            <li>Automatically terminating inactive sessions</li>
        </ul>
        
        <h2>Third-Party Services</h2>
        <p>We use the following third-party services:</p>
        <ul>
            <li>Google reCAPTCHA for form security</li>
            <li>SMTP services for email delivery</li>
        </ul>
        <p>These services may collect additional information as described in their respective privacy policies.</p>
        
        <h2>Data Retention</h2>
        <p>We retain your personal information only for as long as necessary to provide you with our services. You can delete your files at any time, and shared links can be configured with expiration dates.</p>
        
        <h2>Changes to This Privacy Policy</h2>
        <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date.</p>
        
        <h2>Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact us at privacy@share.com.</p>
        
        <p class="last-updated">Last Updated: April 25, 2025</p>
    </div>
    
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
</body>
</html>