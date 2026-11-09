<?php

namespace Rrq\Dukpay\Crypto;

use Rrq\Dukpay\Exceptions\DukPayConfigurationException;
use Rrq\Dukpay\Exceptions\DukPayException;

/**
 * Sensitive input encryption for the bank card direct flow (challengeOrder).
 *
 *   key   = SHA256(UTF8(secretKey))
 *   raw   = iv(12) + AES-256-GCM(plainJson) + tag(16)
 *   input = "MERCHANT_AES:" + Base64UrlNoPadding(raw)
 *
 * Must only ever run server-side. Never log the plaintext.
 */
final class MerchantAes
{
    public const PREFIX = 'MERCHANT_AES:';

    public function __construct(private readonly ?string $secretKey)
    {
    }

    /** @param array<string, mixed>|string $input */
    public function encryptInput(array|string $input): string
    {
        $secretKey = (string) $this->secretKey;

        if ($secretKey === '') {
            throw new DukPayConfigurationException('DukPay secret_key is required to encrypt bank card input.');
        }

        $plain = is_array($input)
            ? json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            : $input;

        $key = hash('sha256', $secretKey, true);
        $iv = random_bytes(12);
        $tag = '';

        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        if ($cipher === false) {
            throw new DukPayException('MERCHANT_AES encryption failed.');
        }

        return self::PREFIX.rtrim(strtr(base64_encode($iv.$cipher.$tag), '+/', '-_'), '=');
    }

    /** Decrypt a MERCHANT_AES value (mainly useful for tests / debugging). */
    public function decryptInput(string $input): string
    {
        $encoded = str_starts_with($input, self::PREFIX) ? substr($input, strlen(self::PREFIX)) : $input;
        $raw = base64_decode(strtr($encoded, '-_', '+/'), true);

        if ($raw === false || strlen($raw) < 28) {
            throw new DukPayException('Invalid MERCHANT_AES payload.');
        }

        $plain = openssl_decrypt(
            substr($raw, 12, -16),
            'aes-256-gcm',
            hash('sha256', (string) $this->secretKey, true),
            OPENSSL_RAW_DATA,
            substr($raw, 0, 12),
            substr($raw, -16),
        );

        if ($plain === false) {
            throw new DukPayException('MERCHANT_AES decryption failed.');
        }

        return $plain;
    }
}
