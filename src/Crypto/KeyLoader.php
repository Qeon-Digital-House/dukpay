<?php

namespace Rrq\Dukpay\Crypto;

use Rrq\Dukpay\Exceptions\DukPayConfigurationException;

/**
 * Normalises keys given as single-line Base64 DER (the format DukPay uses),
 * PEM strings, or file paths into PEM strings usable by ext-openssl and phpseclib.
 */
final class KeyLoader
{
    public static function privatePem(?string $value, string $name = 'merchant_private_key'): string
    {
        return self::toPem($value, 'PRIVATE KEY', $name);
    }

    public static function publicPem(?string $value, string $name = 'dukpay_public_key'): string
    {
        return self::toPem($value, 'PUBLIC KEY', $name);
    }

    private static function toPem(?string $value, string $label, string $name): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new DukPayConfigurationException("DukPay {$name} is not configured.");
        }

        if (! str_contains($value, '-----BEGIN') && strlen($value) < 4096 && @is_file($value)) {
            $contents = file_get_contents($value);

            if ($contents === false) {
                throw new DukPayConfigurationException("Unable to read DukPay {$name} file [{$value}].");
            }

            $value = str_contains($contents, '-----BEGIN')
                ? trim($contents)
                : (self::looksLikeBase64(trim($contents)) ? trim($contents) : base64_encode($contents));
        }

        if (str_contains($value, '-----BEGIN')) {
            // Support PEM values stored in .env with literal "\n".
            return str_replace('\n', "\n", $value);
        }

        $base64 = preg_replace('/\s+/', '', $value);

        if (! self::looksLikeBase64($base64)) {
            throw new DukPayConfigurationException("DukPay {$name} is not a valid Base64 / PEM key.");
        }

        return "-----BEGIN {$label}-----\n".chunk_split($base64, 64, "\n")."-----END {$label}-----\n";
    }

    private static function looksLikeBase64(string $value): bool
    {
        return $value !== '' && preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', preg_replace('/\s+/', '', $value)) === 1;
    }
}
