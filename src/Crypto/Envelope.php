<?php

namespace Rrq\Dukpay\Crypto;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Rrq\Dukpay\Exceptions\DukPayException;
use Rrq\Dukpay\Exceptions\DukPayDecryptionException;
use Throwable;

/**
 * Hybrid encryption used by DukPay V2:
 *  - AES-256-GCM (12-byte nonce, 128-bit tag) over the business JSON, AAD = secretId|requestKey|api
 *  - RSA-OAEP (SHA-256, MGF1 SHA-256) over the 32-byte AES key
 *
 * Wire format: {"cipherText": Base64(nonce + ciphertext + tag), "encryptedSymmetricKey": Base64(rsa)}
 *
 * phpseclib is used for RSA because ext-openssl only supports OAEP with SHA-1.
 */
final class Envelope
{
    private const NONCE_LENGTH = 12;

    private const TAG_LENGTH = 16;

    private const KEY_LENGTH = 32;

    public function __construct(
        private readonly string $recipientPublicKeyPem,
        private readonly string $ownPrivateKeyPem,
    ) {
    }

    public static function aad(string $secretId, string $requestKey, string $api): string
    {
        return $secretId.'|'.$requestKey.'|'.$api;
    }

    /**
     * Encrypt plaintext JSON for DukPay and return the envelope JSON string (the HTTP body).
     */
    public function seal(string $plainJson, string $aad): string
    {
        if ($plainJson === '') {
            throw new DukPayException('Cannot encrypt an empty request body.');
        }

        $aesKey = random_bytes(self::KEY_LENGTH);
        $nonce = random_bytes(self::NONCE_LENGTH);
        $tag = '';

        $cipher = openssl_encrypt($plainJson, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $nonce, $tag, $aad, self::TAG_LENGTH);

        if ($cipher === false) {
            throw new DukPayException('AES-GCM encryption failed: '.(openssl_error_string() ?: 'unknown error'));
        }

        try {
            $encryptedKey = $this->rsaPublic()->encrypt($aesKey);
        } catch (Throwable $e) {
            throw new DukPayException('RSA-OAEP encryption failed: '.$e->getMessage(), 0, $e);
        }

        return json_encode([
            'cipherText' => base64_encode($nonce.$cipher.$tag),
            'encryptedSymmetricKey' => base64_encode($encryptedKey),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Decrypt an envelope (array or JSON string) addressed to the merchant and return the plaintext JSON.
     */
    public function open(array|string $envelope, string $aad): string
    {
        if (is_string($envelope)) {
            $envelope = json_decode($envelope, true);
        }

        if (! self::isEnvelope($envelope)) {
            throw new DukPayDecryptionException('Payload is not a DukPay encrypted envelope.');
        }

        $encryptedKey = base64_decode((string) $envelope['encryptedSymmetricKey'], true);
        $raw = base64_decode((string) $envelope['cipherText'], true);

        if ($encryptedKey === false || $raw === false || strlen($raw) < self::NONCE_LENGTH + self::TAG_LENGTH) {
            throw new DukPayDecryptionException('Envelope contains invalid Base64 data.');
        }

        try {
            $aesKey = $this->rsaPrivate()->decrypt($encryptedKey);
        } catch (Throwable $e) {
            throw new DukPayDecryptionException('RSA-OAEP decryption failed: '.$e->getMessage(), 0, $e);
        }

        if (! is_string($aesKey) || strlen($aesKey) !== self::KEY_LENGTH) {
            throw new DukPayDecryptionException('RSA-OAEP decryption did not yield a 32-byte AES key.');
        }

        $nonce = substr($raw, 0, self::NONCE_LENGTH);
        $tag = substr($raw, -self::TAG_LENGTH);
        $cipher = substr($raw, self::NONCE_LENGTH, -self::TAG_LENGTH);

        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $nonce, $tag, $aad);

        if ($plain === false) {
            throw new DukPayDecryptionException('AES-GCM decryption failed (bad key, AAD or tampered data).');
        }

        return $plain;
    }

    public static function isEnvelope(mixed $value): bool
    {
        return is_array($value)
            && ! empty($value['cipherText'])
            && ! empty($value['encryptedSymmetricKey'])
            && is_string($value['cipherText'])
            && is_string($value['encryptedSymmetricKey']);
    }

    private function rsaPublic(): RSA\PublicKey
    {
        /** @var RSA\PublicKey $key */
        $key = PublicKeyLoader::loadPublicKey($this->recipientPublicKeyPem);

        return $key->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha256')->withMGFHash('sha256');
    }

    private function rsaPrivate(): RSA\PrivateKey
    {
        /** @var RSA\PrivateKey $key */
        $key = PublicKeyLoader::loadPrivateKey($this->ownPrivateKeyPem);

        return $key->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha256')->withMGFHash('sha256');
    }
}
