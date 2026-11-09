<?php

namespace Rrq\Dukpay;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;
use Rrq\Dukpay\Crypto\Envelope;
use Rrq\Dukpay\Crypto\Signer;
use Rrq\Dukpay\Exceptions\DukPayApiException;
use Rrq\Dukpay\Exceptions\DukPayConfigurationException;
use Rrq\Dukpay\Exceptions\DukPayDecryptionException;
use Rrq\Dukpay\Exceptions\DukPayHttpException;
use Rrq\Dukpay\Exceptions\InvalidSignatureException;
use Rrq\Dukpay\Support\Api;
use Rrq\Dukpay\Support\RequestKey;

/**
 * Low-level transport: encrypts + signs requests, verifies + decrypts responses and callbacks.
 */
class DukPayClient
{
    /**
     * @param array{
     *     base_url: string,
     *     endpoint?: string,
     *     secret_id: string,
     *     product_id?: ?string,
     *     verify_response_signature?: bool,
     *     throw_on_error?: bool,
     *     http?: array{timeout?: int, connect_timeout?: int},
     * } $config
     */
    public function __construct(
        private readonly array $config,
        private readonly HttpFactory $http,
        private readonly Envelope $envelope,
        private readonly Signer $signer,
        private readonly ?LoggerInterface $logger = null,
    ) {
        if (empty($config['secret_id'])) {
            throw new DukPayConfigurationException('DukPay secret_id is not configured.');
        }
    }

    /**
     * Call any DukPay API by its X-DUKPAY-API name.
     *
     * @param array<string, mixed> $payload Plain business payload.
     * @param array{request_key?: string, product_id?: ?string, throw?: bool} $options
     *
     * @throws DukPayApiException when the response code is not 0 and throwing is enabled
     * @throws DukPayHttpException|InvalidSignatureException|DukPayDecryptionException
     */
    public function call(string $api, array $payload = [], array $options = []): DukPayResponse
    {
        $secretId = (string) $this->config['secret_id'];
        $requestKey = (string) ($options['request_key'] ?? RequestKey::generate());
        $timestamp = (string) time();

        $plain = json_encode(
            $payload === [] ? new \stdClass() : $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        $body = $this->envelope->seal($plain, Envelope::aad($secretId, $requestKey, $api));
        $signature = $this->signer->sign(Signer::content($secretId, $requestKey, $timestamp, $api, $body));

        $headers = [
            'Authorization' => "SecretId={$secretId},Signature={$signature}",
            'X-DUKPAY-SIGN-VERSION' => 'V2',
            'X-DUKPAY-API' => $api,
            'X-DUKPAY-REQ-KEY' => $requestKey,
            'X-DUKPAY-TIMESTAMP' => $timestamp,
            'Accept' => 'application/json',
        ];

        $productId = $options['product_id'] ?? ($this->config['product_id'] ?? null);

        if ($productId !== null && $productId !== '') {
            $headers['X-PRODUCT-ID'] = (string) $productId;
        }

        $this->log('debug', 'DukPay request', ['api' => $api, 'requestKey' => $requestKey]);

        try {
            $httpResponse = $this->http
                ->withHeaders($headers)
                ->timeout((int) ($this->config['http']['timeout'] ?? 30))
                ->connectTimeout((int) ($this->config['http']['connect_timeout'] ?? 10))
                ->withBody($body, 'application/json')
                ->post($this->url());
        } catch (ConnectionException $e) {
            $this->log('error', 'DukPay connection failed', ['api' => $api, 'requestKey' => $requestKey, 'error' => $e->getMessage()]);

            throw new DukPayHttpException("Unable to reach DukPay [{$api}]: ".$e->getMessage(), null, null, $e);
        }

        $raw = $httpResponse->body();
        $decoded = json_decode($raw, true);

        if (! $httpResponse->successful() || ! is_array($decoded) || ! array_key_exists('code', $decoded)) {
            $this->log('error', 'DukPay unexpected HTTP response', ['api' => $api, 'requestKey' => $requestKey, 'status' => $httpResponse->status()]);

            throw new DukPayHttpException(
                "Unexpected DukPay HTTP response [{$api}] (status {$httpResponse->status()}).",
                $httpResponse->status(),
                $raw,
            );
        }

        $data = $decoded['data'] ?? null;

        if (is_string($data) && Envelope::isEnvelope($maybe = json_decode($data, true))) {
            $data = $maybe;
        }

        $encrypted = Envelope::isEnvelope($data);
        $responseSignature = $httpResponse->header('X-DUKPAY-RESP-SIGN');

        if ($this->config['verify_response_signature'] ?? true) {
            if ($responseSignature !== '') {
                $content = Signer::content($secretId, $requestKey, $timestamp, $api, $raw);

                if (! $this->signer->verify($content, $responseSignature)) {
                    $this->log('warning', 'DukPay response signature invalid', ['api' => $api, 'requestKey' => $requestKey]);

                    throw new InvalidSignatureException("DukPay response signature verification failed [{$api}].");
                }
            } elseif ($encrypted) {
                throw new InvalidSignatureException("DukPay response is missing X-DUKPAY-RESP-SIGN [{$api}].");
            }
        }

        if ($encrypted) {
            $data = $this->decodeJson(
                $this->envelope->open($data, Envelope::aad($secretId, $requestKey, $api)),
            );
        }

        $response = new DukPayResponse(
            api: $api,
            requestKey: $requestKey,
            code: (int) $decoded['code'],
            message: isset($decoded['message']) && is_scalar($decoded['message']) ? (string) $decoded['message'] : null,
            data: $data,
            rawBody: $raw,
            httpStatus: $httpResponse->status(),
        );

        $this->log($response->successful() ? 'debug' : 'warning', 'DukPay response', [
            'api' => $api,
            'requestKey' => $requestKey,
            'code' => $response->code,
            'message' => $response->message,
        ]);

        if (($options['throw'] ?? $this->config['throw_on_error'] ?? true) && $response->failed()) {
            throw new DukPayApiException($response);
        }

        return $response;
    }

    /**
     * Verify and decrypt an asynchronous notification.
     *
     * @param string $rawBody The raw HTTP body exactly as received.
     * @param string $requestKey X-DUKPAY-REQ-KEY header.
     * @param string|null $timestamp X-DUKPAY-TIMESTAMP header.
     * @param string|null $signature X-DUKPAY-RESP-SIGN header.
     * @return array<string, mixed>
     *
     * @throws InvalidSignatureException|DukPayDecryptionException
     */
    public function openCallback(string $rawBody, string $requestKey, ?string $timestamp, ?string $signature): array
    {
        $secretId = (string) $this->config['secret_id'];

        if ($requestKey === '') {
            throw new InvalidSignatureException('DukPay callback is missing X-DUKPAY-REQ-KEY.');
        }

        $content = Signer::content($secretId, $requestKey, $timestamp, Api::CALLBACK, $rawBody);

        if (! $this->signer->verify($content, $signature)) {
            throw new InvalidSignatureException('DukPay callback signature verification failed.');
        }

        $decoded = json_decode($rawBody, true);

        if (Envelope::isEnvelope($decoded)) {
            return $this->decodeJson($this->envelope->open($decoded, Envelope::aad($secretId, $requestKey, Api::CALLBACK)));
        }

        if (! is_array($decoded)) {
            throw new DukPayDecryptionException('DukPay callback body is not valid JSON.');
        }

        return $decoded;
    }

    public function url(): string
    {
        return rtrim((string) $this->config['base_url'], '/').'/'.ltrim((string) ($this->config['endpoint'] ?? '/public/merchant'), '/');
    }

    private function decodeJson(string $json): mixed
    {
        $value = json_decode($json, true);

        if ($value === null && trim($json) !== 'null') {
            throw new DukPayDecryptionException('Decrypted DukPay payload is not valid JSON.');
        }

        return $value;
    }

    private function log(string $level, string $message, array $context): void
    {
        try {
            $this->logger?->log($level, $message, $context);
        } catch (\Throwable) {
            // Logging must never break a payment flow.
        }
    }
}
