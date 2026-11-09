<?php

namespace Rrq\Dukpay\Resources;

use Rrq\Dukpay\Crypto\MerchantAes;
use Rrq\Dukpay\DukPayClient;
use Rrq\Dukpay\DukPayResponse;
use Rrq\Dukpay\Support\Api;

/** payin: collect a payment from a user (section 4.1, 4.3). */
class Payins extends Resource
{
    public function __construct(DukPayClient $client, array $defaults, private readonly MerchantAes $merchantAes)
    {
        parent::__construct($client, $defaults);
    }

    /**
     * Create a payin order with a local payment method (orderCreateV2).
     *
     * Required fields vary per country, e.g. Indonesia:
     *   businessNo, paymentMethod (QRIS, DANA, OVO, BANK_TRANSFER...), country=IDN, currency=IDR,
     *   orderAmount, properties[...]
     */
    public function create(array $payload): DukPayResponse
    {
        return $this->call(Api::ORDER_CREATE, $this->withRequestTime($this->withRedirectUrl($this->withCallbackUrl($payload))));
    }

    /**
     * Create a Hosted Checkout order (advanceOrder). The response contains data.channelAttrs.checkoutUrl.
     *
     *   businessNo, country, currency, orderAmount, callbackUrl, redirectUrl, productName?
     */
    public function hostedCheckout(array $payload): DukPayResponse
    {
        return $this->call(Api::HOSTED_CHECKOUT, $this->withRequestTime($this->withRedirectUrl($this->withCallbackUrl($payload))));
    }

    /** Query a payin order by merchant order number (orderQueryV2). */
    public function query(string $businessNo): DukPayResponse
    {
        return $this->call(Api::ORDER_QUERY, ['businessNo' => $businessNo]);
    }

    /**
     * Bank card direct payment, step 1: create the order and challenge session (challengeOrder).
     * Do NOT include card data here. Keep data.token and data.action.nextIndex.
     *
     *   businessNo, country=RUS, paymentMethod=BANK_CARD, currency, orderAmount,
     *   callbackUrl, redirectUrl, properties.clientIp
     */
    public function createCardOrder(array $payload): DukPayResponse
    {
        $payload += ['paymentMethod' => 'BANK_CARD'];

        return $this->call(Api::CHALLENGE_ORDER, $this->withRequestTime($this->withRedirectUrl($this->withCallbackUrl($payload)), true));
    }

    /**
     * Bank card direct payment, step 2..n: advance the challenge (challengeOrder).
     *
     * $input is the plaintext user input, e.g.
     *   ['cardNo' => '4111...', 'expiryMonth' => '12', 'expiryYear' => '2028', 'cvv' => '123', 'cardHolderName' => '...']
     *   ['otp' => '123456']
     * It is encrypted server-side with MERCHANT_AES before being sent.
     *
     * @param array<string, mixed> $input
     */
    public function advanceChallenge(string $token, int|string $currentIndex, array $input): DukPayResponse
    {
        return $this->call(Api::CHALLENGE_ORDER, [
            'token' => $token,
            'currentIndex' => (string) $currentIndex,
            'input' => $this->merchantAes->encryptInput($input),
        ]);
    }
}
