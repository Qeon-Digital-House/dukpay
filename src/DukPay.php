<?php

namespace Rrq\Dukpay;

use Illuminate\Http\Request;
use Rrq\Dukpay\Crypto\MerchantAes;
use Rrq\Dukpay\Resources\Accounts;
use Rrq\Dukpay\Resources\Finance;
use Rrq\Dukpay\Resources\Payins;
use Rrq\Dukpay\Resources\Payouts;
use Rrq\Dukpay\Resources\Refunds;
use Rrq\Dukpay\Resources\Subscriptions;

/**
 * Entry point: DukPay::payins()->create([...]), DukPay::payouts()->query(...), etc.
 */
class DukPay
{
    /** @param array{callback_url?: ?string, redirect_url?: ?string} $defaults */
    public function __construct(
        private readonly DukPayClient $client,
        private readonly MerchantAes $merchantAes,
        private readonly array $defaults = [],
    ) {
    }

    public function client(): DukPayClient
    {
        return $this->client;
    }

    public function payins(): Payins
    {
        return new Payins($this->client, $this->defaults, $this->merchantAes);
    }

    public function payouts(): Payouts
    {
        return new Payouts($this->client, $this->defaults);
    }

    public function refunds(): Refunds
    {
        return new Refunds($this->client, $this->defaults);
    }

    public function accounts(): Accounts
    {
        return new Accounts($this->client, $this->defaults);
    }

    public function subscriptions(): Subscriptions
    {
        return new Subscriptions($this->client, $this->defaults);
    }

    public function finance(): Finance
    {
        return new Finance($this->client, $this->defaults);
    }

    /**
     * Call any API by its X-DUKPAY-API name (for endpoints not wrapped by a resource).
     *
     * @param array<string, mixed> $payload
     * @param array{request_key?: string, product_id?: ?string, throw?: bool} $options
     */
    public function call(string $api, array $payload = [], array $options = []): DukPayResponse
    {
        return $this->client->call($api, $payload, $options);
    }

    /**
     * Verify + decrypt an incoming DukPay notification.
     *
     * @throws Exceptions\InvalidSignatureException|Exceptions\DukPayDecryptionException
     */
    public function parseCallback(Request $request): DukPayCallback
    {
        $raw = $request->getContent();
        $requestKey = (string) $request->header('X-DUKPAY-REQ-KEY', '');
        $timestamp = $request->header('X-DUKPAY-TIMESTAMP');

        $data = $this->client->openCallback($raw, $requestKey, $timestamp, $request->header('X-DUKPAY-RESP-SIGN'));

        return new DukPayCallback(
            type: $request->header('callback-type'),
            data: is_array($data) ? $data : ['value' => $data],
            requestKey: $requestKey,
            timestamp: $timestamp,
            rawBody: $raw,
        );
    }

    /** Encrypt bank card / challenge input into a "MERCHANT_AES:..." string. */
    public function encryptCardInput(array $input): string
    {
        return $this->merchantAes->encryptInput($input);
    }
}
