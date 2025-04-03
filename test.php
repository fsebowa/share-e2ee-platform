<?php
// $key = openssl_random_pseudo_bytes(16); // 16 bytes for AES-128-GCM

// function encrypt_data($plaintext, $key) {
//     $cipher = "aes-128-gcm";
//     $iv_length = openssl_cipher_iv_length($cipher);
//     if (in_array($cipher, openssl_get_cipher_methods())) {
//         $options = 0;
//         $encryption_iv = openssl_random_pseudo_bytes($iv_length);
//         $encrypted_data = openssl_encrypt($plaintext, $cipher, $key, $options, $encryption_iv, $tag);
//         // store encryption data for decryption later
//         return [
//             'cipher_text' => $encrypted_data,
//             'encryption_iv' => base64_encode($encryption_iv),
//             'tag' => base64_encode($tag) // store authentication tag safely
//         ];
//     } else {
//         return false; // encryption failed; cipher method doesn't exist
//     }
    
// }

// function decrypt_data($encrypted_text, $key, $iv, $tag) {
//     $cipher = "aes-128-gcm";

//     // decode IV and tag from base64
//     $iv = base64_decode($iv);
//     $tag = base64_decode($tag);

//     if (in_array($cipher, openssl_get_cipher_methods())) {
//         $options = 0;
//         $decrypted_data = openssl_decrypt($encrypted_text, $cipher, $key, $options, $iv, $tag);
//         return $decrypted_data;
//     } else {
//         return false; // encryption failed; cipher method doesn't exist
//     }
    
// }

// $text = "Hey man, let's encrypt";
// $encryption_test = encrypt_data($text, $key);
// $decryption_test = decrypt_data($encryption_test['cipher_text'], $key, $encryption_test['encryption_iv'], $encryption_test['tag']);
// echo 'Encrypted message: '. $encryption_test['cipher_text'];
// echo '<br>Decrypted message: '. $decryption_test;
// echo '<br>IV: '. $encryption_test['encryption_iv'];
// echo '<br>Tag: '. $encryption_test['tag'];


echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Video Player - ' . htmlspecialchars($file_info['file_name']) . '</title>
        <style>
            body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 0; background-color: #000; height: 100vh; display: flex; justify-content: center; align-items: center; }
            video { max-width: 100%; max-height: 100%; }
            .controls { position: fixed; top: 20px; right: 20px; z-index: 10;}
            .controls a {margin-left: 10px; display: inline-block; background: rgba(255, 255, 255); color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px; box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px; }
            .controls a:hover {background:  rgba(234, 234, 234); box-shadow: rgba(99, 99, 99, 0.5) 0px 2px 8px 0px;}
        </style>
    </head>
    <body> 
    <div class="controls">
            <a href="/dashboard.php">Back to Dashboard</a>
            <a href="' . $stream_url . '&download=1">Download Video</a>
        </div>
        <video controls autoplay>
            <source src="' . $stream_url . '" type="' . $mime_type . '">
            Your browser does not support the video tag.
        </video>
    </body>
    </html>';

?>