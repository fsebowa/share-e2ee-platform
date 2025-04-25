<?php
    require_once __DIR__ . '/includes/config/config_session.inc.php';
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . "/includes/templates/header.php"; ?>
    <title>Terms of Service - Share</title>
</head>
<body class="policies">
    <?php include __DIR__ . "/includes/templates/nav.php"; ?>
    
    <div class="terms">
        <h1>Terms of Service</h1>
        
        <p>Welcome to Share, an end-to-end encrypted file sharing platform. By accessing or using our service, you agree to be bound by these Terms of Service. Please read them carefully.</p>
        
        <h2>1. Acceptance of Terms</h2>
        <p>By creating an account or using any part of the Share service, you agree to these Terms of Service. If you do not agree to these terms, you may not use our service.</p>
        
        <h2>2. Description of Service</h2>
        <p>Share provides an end-to-end encrypted file storage and sharing service. Our platform allows you to:</p>
        <ul>
            <li>Upload and encrypt files</li>
            <li>Store encrypted files securely</li>
            <li>Share encrypted files with others</li>
            <li>Control access to shared files with time limits and access restrictions</li>
            <li>Verify file integrity through HMAC</li>
        </ul>
        
        <h2>3. Account Registration and Security</h2>
        <p>To use our service, you must create an account with a valid email address. You are responsible for:</p>
        <ul>
            <li>Providing accurate information during registration</li>
            <li>Maintaining the security of your account credentials</li>
            <li>All activities that occur under your account</li>
            <li>Safekeeping your encryption keys</li>
        </ul>
        <p><strong>Important:</strong> We cannot recover your encryption keys or decrypt your files if you lose your keys. You are solely responsible for maintaining your encryption keys.</p>
        
        <h2>4. User Conduct</h2>
        <p>You agree not to use our service to:</p>
        <ul>
            <li>Upload, share, or store content that violates laws or regulations</li>
            <li>Upload, share, or store content that infringes on intellectual property rights</li>
            <li>Distribute malware, viruses, or other harmful code</li>
            <li>Attempt to gain unauthorized access to our systems or other users' accounts</li>
            <li>Use our service for any illegal activities</li>
            <li>Interfere with or disrupt the service or servers</li>
        </ul>
        
        <h2>5. Content Responsibility</h2>
        <p>Due to our end-to-end encryption system, we cannot view the content of your files. However, you remain fully responsible for all content you upload, store, or share. We reserve the right to terminate accounts that violate our terms.</p>
        
        <h2>6. Privacy and Data Security</h2>
        <p>Your privacy is important to us. Our <a href="/privacy-policy.php">Privacy Policy</a> describes how we collect, use, and protect your information. By using our service, you agree to our Privacy Policy.</p>
        
        <h2>7. Service Limitations</h2>
        <p>Our service has the following limitations:</p>
        <ul>
            <li>Maximum file size: 20MB per file</li>
            <li>Share links may expire after a set period (1-30 days)</li>
            <li>We may implement reasonable usage limits to ensure service quality</li>
        </ul>
        
        <h2>8. Disclaimer of Warranties</h2>
        <p>Our service is provided "as is" and "as available" without any warranties of any kind, either express or implied. We do not warrant that the service will be uninterrupted, secure, or error-free.</p>
        
        <h2>9. Limitation of Liability</h2>
        <p>To the maximum extent permitted by law, we shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including loss of profits, data, or goodwill, resulting from your use of or inability to use the service.</p>
        
        <h2>10. Changes to Terms</h2>
        <p>We reserve the right to modify these Terms of Service at any time. We will provide notice of significant changes by posting an updated version on our website. Your continued use of the service after such modifications constitutes your acceptance of the revised terms.</p>
        
        <h2>11. Academic Project Disclaimer</h2>
        <p>Share is developed as an academic project. While we strive to implement strong security measures, the service may not meet all requirements for production use in sensitive environments. Users should evaluate the suitability of the service for their specific needs.</p>
        
        <h2>12. Governing Law</h2>
        <p>These Terms shall be governed by and construed in accordance with the laws of the jurisdiction in which Share operates, without regard to its conflict of law provisions.</p>
        
        <h2>13. Contact Us</h2>
        <p>If you have any questions about these Terms, please contact us at terms@share.com.</p>
        
        <p class="last-updated">Last Updated: April 25, 2025</p>
    </div>
    
    <?php include __DIR__ . "/includes/templates/footer.php"; ?>
</body>
</html>