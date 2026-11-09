<?php

namespace Rrq\Dukpay\Crypto;

use Rrq\Dukpay\Exceptions\DukPayException;

/**
 * SHA256withRSA signatures, Base64 encoded.
 *
 * String to sign: secretId + requestKey + timestamp + api + body (no separators,
 * timestamp and body trimmed).
 */
final class Signer
{
    public function __construct(
        private readonly string $merchantPrivateKeyPem,
        private readonly string $dukpayPublicKeyPem,
    ) {
    }

    public static function content(string $secretId, string $requestKey, ?string $timestamp, string $api, ?string $body): string
    {
        return $secretId.$requestKey.trim((string) $timestamp).$api.trim((string) $body);
    }

    /** Sign with the merchant private key. */
    public function sign(string $content): string
    {
        $key = openssl_pkey_get_private($this->merchantPrivateKeyPem);

        if ($key === false) {
            throw new DukPayException('Invalid merchant private key: '.(openssl_error_string() ?: 'unknown error'));
        }

        if (! openssl_sign($content, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new DukPayException('Unable to sign request: '.(openssl_error_string() ?: 'unknown error'));
        }

        return base64_encode($signature);
    }

    /** Verify a DukPay signature with the DukPay public key. */
    public function verify(string $content, ?string $signatureBase64): bool
    {
        $signatureBase64 = trim((string) $signatureBase64);

        if ($signatureBase64 === '') {
            return false;
        }

        $signature = base64_decode($signatureBase64, true);

        if ($signature === false) {
            return false;
        }

        $key = openssl_pkey_get_public($this->dukpayPublicKeyPem);

        if ($key === false) {
            throw new DukPayException('Invalid DukPay public key: '.(openssl_error_string() ?: 'unknown error'));
        }

        return openssl_verify($content, $signature, $key, OPENSSL_ALGO_SHA256) === 1;
    }
}
