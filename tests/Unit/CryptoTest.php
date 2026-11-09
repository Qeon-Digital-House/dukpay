<?php

namespace Rrq\Dukpay\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rrq\Dukpay\Crypto\Envelope;
use Rrq\Dukpay\Crypto\KeyLoader;
use Rrq\Dukpay\Crypto\MerchantAes;
use Rrq\Dukpay\Crypto\Signer;
use Rrq\Dukpay\Exceptions\DukPayDecryptionException;
use Rrq\Dukpay\Tests\Support\Keys;

class CryptoTest extends TestCase
{
    public function test_sign_content_and_aad_follow_the_spec(): void
    {
        $this->assertSame(
            'test2066417280937607168'.'1781507246'.'orderQuery'.'{"a":1}',
            Signer::content('test', '2066417280937607168', ' 1781507246 ', 'orderQuery', " {\"a\":1}\n"),
        );
        $this->assertSame('test|2066417280937607168|orderQuery', Envelope::aad('test', '2066417280937607168', 'orderQuery'));
    }

    public function test_envelope_round_trip(): void
    {
        $dukpay = Keys::pair('dukpay');
        $sender = new Envelope(KeyLoader::publicPem($dukpay['public']), KeyLoader::privatePem(Keys::pair('merchant')['private']));
        $receiver = new Envelope(KeyLoader::publicPem(Keys::pair('merchant')['public']), KeyLoader::privatePem($dukpay['private']));

        $wire = $sender->seal('{"businessNo":"MER202506150001"}', 'test|1|orderQuery');
        $envelope = json_decode($wire, true);

        $this->assertTrue(Envelope::isEnvelope($envelope));
        $this->assertSame(12 + strlen('{"businessNo":"MER202506150001"}') + 16, strlen(base64_decode($envelope['cipherText'])));
        $this->assertSame(256, strlen(base64_decode($envelope['encryptedSymmetricKey'])));
        $this->assertSame('{"businessNo":"MER202506150001"}', $receiver->open($wire, 'test|1|orderQuery'));
    }

    public function test_envelope_rejects_wrong_aad(): void
    {
        $dukpay = Keys::pair('dukpay');
        $sender = new Envelope(KeyLoader::publicPem($dukpay['public']), KeyLoader::privatePem(Keys::pair('merchant')['private']));
        $receiver = new Envelope(KeyLoader::publicPem(Keys::pair('merchant')['public']), KeyLoader::privatePem($dukpay['private']));

        $this->expectException(DukPayDecryptionException::class);

        $receiver->open($sender->seal('{"a":1}', 'test|1|orderQuery'), 'test|2|orderQuery');
    }

    public function test_signature_round_trip(): void
    {
        $merchant = Keys::pair('merchant');
        $merchantSigner = new Signer(KeyLoader::privatePem($merchant['private']), KeyLoader::publicPem(Keys::pair('dukpay')['public']));
        $dukpayVerifier = new Signer(KeyLoader::privatePem(Keys::pair('dukpay')['private']), KeyLoader::publicPem($merchant['public']));

        $signature = $merchantSigner->sign('hello');

        $this->assertTrue($dukpayVerifier->verify('hello', $signature));
        $this->assertFalse($dukpayVerifier->verify('hello!', $signature));
        $this->assertFalse($dukpayVerifier->verify('hello', 'not base64 !!'));
    }

    public function test_key_loader_accepts_base64_pem_and_files(): void
    {
        $pair = Keys::pair('merchant');
        $fromBase64 = KeyLoader::privatePem($pair['private']);

        $this->assertNotFalse(openssl_pkey_get_private($fromBase64));
        $this->assertNotFalse(openssl_pkey_get_private(KeyLoader::privatePem($pair['private_pem'])));
        $this->assertNotFalse(openssl_pkey_get_private(KeyLoader::privatePem(str_replace("\n", '\n', $pair['private_pem']))));

        $file = tempnam(sys_get_temp_dir(), 'dukpay');
        file_put_contents($file, $pair['public_pem']);
        $this->assertNotFalse(openssl_pkey_get_public(KeyLoader::publicPem($file)));
        unlink($file);
    }

    public function test_merchant_aes_matches_spec(): void
    {
        $aes = new MerchantAes('merchant-secret-key');
        $input = $aes->encryptInput(['otp' => '123456']);

        $this->assertStringStartsWith('MERCHANT_AES:', $input);
        $this->assertMatchesRegularExpression('/^MERCHANT_AES:[A-Za-z0-9_-]+$/', $input, 'URL-safe, no padding');

        // Independent decrypt following the documented algorithm.
        $raw = base64_decode(strtr(substr($input, 13), '-_', '+/'));
        $plain = openssl_decrypt(substr($raw, 12, -16), 'aes-256-gcm', hash('sha256', 'merchant-secret-key', true), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, -16));

        $this->assertSame('{"otp":"123456"}', $plain);
        $this->assertSame('{"otp":"123456"}', $aes->decryptInput($input));
    }
}
