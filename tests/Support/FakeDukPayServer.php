<?php

namespace Rrq\Dukpay\Tests\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Rrq\Dukpay\Crypto\Envelope;
use Rrq\Dukpay\Crypto\KeyLoader;
use Rrq\Dukpay\Crypto\Signer;

/**
 * Plays the DukPay side of the protocol: verifies the merchant signature with the merchant
 * public key, decrypts with the DukPay private key, and answers signed + encrypted.
 */
final class FakeDukPayServer
{
    /** @var array<int, array{api: string, requestKey: string, payload: array, headers: array}> */
    public array $received = [];

    private Envelope $envelope;

    private Signer $signer;

    public function __construct(private readonly string $secretId = 'test')
    {
        $merchant = Keys::pair('merchant');
        $dukpay = Keys::pair('dukpay');

        // DukPay encrypts for the merchant and decrypts with its own private key.
        $this->envelope = new Envelope(KeyLoader::publicPem($merchant['public']), KeyLoader::privatePem($dukpay['private']));
        // DukPay signs with its private key and verifies with the merchant public key.
        $this->signer = new Signer(KeyLoader::privatePem($dukpay['private']), KeyLoader::publicPem($merchant['public']));
    }

    /** @param callable(string $api, array $payload): array{0?: int, 1?: mixed, 2?: ?string}|null $responder */
    public function fake(?callable $responder = null, bool $signResponse = true, bool $tamper = false): self
    {
        $responder ??= fn () => [0, ['ok' => true]];

        Http::fake(function (Request $request) use ($responder, $signResponse, $tamper) {
            $api = $request->header('X-DUKPAY-API')[0];
            $requestKey = $request->header('X-DUKPAY-REQ-KEY')[0];
            $timestamp = $request->header('X-DUKPAY-TIMESTAMP')[0];

            preg_match('/^SecretId=([^,]+),Signature=(.+)$/', $request->header('Authorization')[0], $m);

            if (($m[1] ?? null) !== $this->secretId
                || ! $this->signer->verify(Signer::content($this->secretId, $requestKey, $timestamp, $api, $request->body()), $m[2] ?? '')) {
                return Http::response(json_encode(['code' => 1001, 'message' => 'signature error', 'data' => null]), 200);
            }

            $payload = json_decode($this->envelope->open($request->body(), Envelope::aad($this->secretId, $requestKey, $api)), true);

            $this->received[] = [
                'api' => $api,
                'requestKey' => $requestKey,
                'payload' => $payload,
                'headers' => $request->headers(),
            ];

            [$code, $data, $message] = $responder($api, $payload) + [0, null, null];

            $wireData = $data === null ? null : json_decode(
                $this->envelope->seal(json_encode($data), Envelope::aad($this->secretId, $requestKey, $api)),
                true,
            );

            $body = json_encode(['code' => $code, 'message' => $message, 'data' => $wireData], JSON_UNESCAPED_SLASHES);
            $headers = ['Content-Type' => 'application/json', 'X-DUKPAY-SIGN-VERSION' => 'V2'];

            if ($signResponse) {
                $headers['X-DUKPAY-RESP-SIGN'] = $this->sign($requestKey, $timestamp, $api, $body);
            }

            if ($tamper) {
                $body = str_replace('"code":0', '"code":0 ', $body);
            }

            return Http::response($body, 200, $headers);
        });

        return $this;
    }

    /** Build a callback request (body + headers) as DukPay would send it. */
    public function callback(array $data, string $type = 'payment_order', ?string $requestKey = null): array
    {
        $requestKey ??= '2063888785439178752';
        $timestamp = (string) time();
        $body = $this->envelope->seal(json_encode($data), Envelope::aad($this->secretId, $requestKey, 'dukpay-callback'));

        return [$body, [
            'Content-Type' => 'application/json',
            'X-DUKPAY-REQ-KEY' => $requestKey,
            'X-DUKPAY-TIMESTAMP' => $timestamp,
            'X-DUKPAY-API' => 'dukpay-callback',
            'X-DUKPAY-SIGN-VERSION' => 'V2',
            'X-DUKPAY-RESP-SIGN' => $this->sign($requestKey, $timestamp, 'dukpay-callback', $body),
            'callback-type' => $type,
        ]];
    }

    private function sign(string $requestKey, string $timestamp, string $api, string $body): string
    {
        return $this->signer->sign(Signer::content($this->secretId, $requestKey, $timestamp, $api, $body));
    }
}
