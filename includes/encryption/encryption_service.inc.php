<?php
declare(strict_types=1);

// this file handles encyption/ decryption operations for the platform 

class EncryptionService {
    private $privateKey;
    private $publicKey;
    private $keysDirectory;

    // Cache for loaded keys
    private static $instanceCache = null;

    // loading keys or contructor generates them if they don't exist
    public function __construct() {
        // Use a previously initialized instance if available
        if (self::$instanceCache !== null) {
            $this->privateKey = self::$instanceCache['privateKey'];
            $this->publicKey = self::$instanceCache['publicKey'];
            return;
        }

        $this->keysDirectory = dirname(dirname(__DIR__)) . '/config/keys';

        // creating keys directory if it doesn't exist
        if (!file_exists($this->keysDirectory)) {
            mkdir($this->keysDirectory, 0700, true);
        }

        $privateKeyFile = $this->keysDirectory . '/private_key.pem';
        $publicKeyFile = $this->keysDirectory . '/public_key.pem';

        // generating keys if they don't exist
        if (!file_exists($privateKeyFile) || !file_exists($publicKeyFile)) {
            $this->generateKeys();
        } else {
            // load existing keys
            $this->privateKey = file_get_contents($privateKeyFile);
            $this->publicKey = file_get_contents($publicKeyFile);

            // verfiy keys validity
            if (!$this->privateKey || !$this->publicKey) {
                $this->generateKeys();   // keys exist but couldn't be read properly - regenerate new ones
            }
        }

        // Cache the keys for future instances
        self::$instanceCache = [
            'privateKey' => $this->privateKey,
            'publicKey' => $this->publicKey
        ];
    }

    // Generate new RSA key pair
    private function generateKeys() {
        // configure key generation
        $config = array(
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );

        // create key pair
        $res = openssl_pkey_new($config);
        if (!$res) {
            throw new Exception("Failed to generate key pair: " . openssl_error_string());
        }

        // extract private key
        openssl_pkey_export($res, $privateKey);
        $this->privateKey = $privateKey;

        // extract public key
        $keyDetails = openssl_pkey_get_details($res);
        $this->publicKey = $keyDetails["key"];

        // save keys to files
        file_put_contents($this->keysDirectory . '/private_key.pem', $this->privateKey);
        chmod($this->keysDirectory . '/private_key.pem', 0600); // secure permissions

        file_put_contents($this->keysDirectory . '/public_key.pem', $this->publicKey);

        // save public key as JS file
        $jsPublicKey = "const SERVER_PUBLIC_KEY = `" . trim($this->publicKey) . "`;";
        file_put_contents(dirname(__DIR__) . '/js/public-key.js', $jsPublicKey);
    }

    public function getPublicKey() {
        return $this->publicKey;
    }

    // decrypt data encrypted with the public key
    public function decrypt($encryptedData) {
        if (empty($encryptedData)) {
            return null;
        }

        static $openssl_padding = OPENSSL_PKCS1_PADDING;


        // decode encrytped data using base64
        $encryptedBinary = base64_decode($encryptedData);
        if ($encryptedBinary === false) {
            return null; // invalid base64 data
        }

        $decrypted = '';
        // decrypt using private key
        if (openssl_private_decrypt($encryptedBinary, $decrypted, $this->privateKey, $openssl_padding)) {
            return $decrypted; // Return raw decrypted data for flexibility
        }
        return null; 
    }

    public function decryptFormData($encryptedData) {
        if (empty($encryptedData)) {
            return null;
        }

        static $openssl_padding = OPENSSL_PKCS1_PADDING;

        // Decode encrypted data using base64
        $encryptedBinary = base64_decode($encryptedData);
        if ($encryptedBinary === false) {
            return null; // Invalid base64 data
        }

        $decrypted = '';
        // Decrypt using private key with error suppression
        if (@openssl_private_decrypt($encryptedBinary, $decrypted, $this->privateKey, $openssl_padding)) {
            return json_decode($decrypted, true, 512, JSON_BIGINT_AS_STRING);
        }
        return null;
    }
}
