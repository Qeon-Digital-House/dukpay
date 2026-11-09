<?php

namespace Rrq\Dukpay\Tests\Support;

/** Generates throwaway RSA-2048 key pairs once per test run, in the Base64 DER format DukPay uses. */
final class Keys
{
    private static array $pairs = [];

    /** @return array{private: string, public: string, private_pem: string, public_pem: string} */
    public static function pair(string $name): array
    {
        if (! isset(self::$pairs[$name])) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $privatePem);
            $publicPem = openssl_pkey_get_details($key)['key'];

            self::$pairs[$name] = [
                'private' => self::stripPem($privatePem),
                'public' => self::stripPem($publicPem),
                'private_pem' => $privatePem,
                'public_pem' => $publicPem,
            ];
        }

        return self::$pairs[$name];
    }

    private static function stripPem(string $pem): string
    {
        return preg_replace('/-----[^-]+-----|\s+/', '', $pem);
    }
}
