<?php
// This script provides a README file for encrypted file downloads

// Set the content type
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="HOW_TO_OPEN_SHARE_ENCRYPTED_FILE.txt"');

// Output the README content
$readme = <<<'EOT'
HOW TO OPEN SHARE ENCRYPTED FILES
====================================

The file you downloaded is encrypted using strong AES-256 encryption and can only be opened with the correct decryption key.

WHAT IS A .SHARE.ENC FILE?
--------------------------
Files with the .share.enc extension are encrypted files from the Share E2EE Platform. These files contain data that has been encrypted for security and privacy, and can only be accessed with the proper decryption key.

WHY YOUR ANTIVIRUS MIGHT FLAG THIS FILE
--------------------------------------
Some antivirus programs may flag encrypted files because their content cannot be scanned or analyzed. This is a false positive - the file is not harmful, it's simply protected with encryption.

HOW TO OPEN YOUR ENCRYPTED FILE
------------------------------
To access the contents of this file:

1. Log into your Share account 
2. Go to your dashboard
3. Click on the file you want to access
4. Select "Decrypt & Download" 
5. Enter your decryption key when prompted

The decryption key is a 64-character string that was provided to you when you uploaded the file. You should have received this key via email when the file was first uploaded.

SECURITY NOTES
-------------
- Never share your decryption key publicly
- Store your decryption keys in a secure password manager
- If you've lost your decryption key, there is no way to recover the file contents

For assistance, please contact support@shareplatform.com

© Share E2EE Platform
EOT;

echo $readme;
exit;