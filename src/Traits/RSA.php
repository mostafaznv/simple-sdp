<?php

namespace Mostafaznv\SimpleSDP\Traits;

use Exception;

trait RSA
{
    private $privateKey;
    private $publicKey;
    private $rsaConfig;

    public function __construct()
    {
        $this->rsaConfig = config('simple-sdp.rsa');

        $this->publicKey = openssl_pkey_get_public(file_get_contents($this->rsaConfig['public']));
        $this->privateKey = openssl_pkey_get_private(file_get_contents($this->rsaConfig['private']));

        if (!$this->publicKey) {
            throw new Exception('OpenSSL: Unable to get public key. Is the location correct?');
        }

        if (!$this->privateKey) {
            throw new Exception('OpenSSL: Unable to get private key. Is the location correct?');
        }
    }

    /**
     * Set PublicKey
     *
     * @param $path
     * @throws Exception
     */
    public function setPublicKey($path)
    {
        $this->publicKey = openssl_pkey_get_public(file_get_contents($path));

        if (!$this->publicKey) {
            throw new Exception('OpenSSL: Unable to get public key. Is the location correct?');
        }
    }

    /**
     * Set PrivateKey
     *
     * @param $path
     * @throws Exception
     */
    public function setPrivateKey($path)
    {
        $this->privateKey = openssl_pkey_get_private(file_get_contents($path));

        if (!$this->privateKey) {
            throw new Exception('OpenSSL: Unable to get private key. Is the location correct?');
        }
    }

    /**
     * Sign Given Data.
     *
     * @param $data
     * @param bool $jsonEncode
     * @return string
     * @throws Exception
     */
    public function sign($data, $jsonEncode = true)
    {
        $success = openssl_sign($data, $signature, $this->privateKey, $this->rsaConfig['algo']);
        openssl_free_key($this->privateKey);

        if (!$success) {
            throw new Exception('Sign failed. Ensure you are using a correct private key.');
        }

        return $jsonEncode ? base64_encode($signature) : $signature;
    }

    /**
     * Verify Given Signature.
     *
     * @param $data
     * @param $signature
     * @return bool
     */
    public function verify($data, $signature)
    {
        $signature = base64_decode($signature);

        $success = openssl_verify($data, $signature, $this->publicKey);
        openssl_free_key($this->publicKey);

        if ($success == 1) {
            return true;
        }

        return false;
    }
}