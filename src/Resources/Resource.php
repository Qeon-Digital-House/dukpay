<?php

namespace Rrq\Dukpay\Resources;

use Rrq\Dukpay\DukPayClient;
use Rrq\Dukpay\DukPayResponse;

abstract class Resource
{
    /** @param array{callback_url?: ?string, redirect_url?: ?string} $defaults */
    public function __construct(
        protected readonly DukPayClient $client,
        protected readonly array $defaults = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $options
     */
    protected function call(string $api, array $payload, array $options = []): DukPayResponse
    {
        return $this->client->call($api, $payload, $options);
    }

    /** Fill in the default callbackUrl when the API accepts one and none was provided. */
    protected function withCallbackUrl(array $payload): array
    {
        if (empty($payload['callbackUrl']) && ! empty($this->defaults['callback_url'])) {
            $payload['callbackUrl'] = $this->defaults['callback_url'];
        }

        return $payload;
    }

    /** Fill in the default redirectUrl when the API accepts one and none was provided. */
    protected function withRedirectUrl(array $payload): array
    {
        if (empty($payload['redirectUrl']) && ! empty($this->defaults['redirect_url'])) {
            $payload['redirectUrl'] = $this->defaults['redirect_url'];
        }

        return $payload;
    }

    /** Fill in requestTime (milliseconds) when not provided. */
    protected function withRequestTime(array $payload, bool $asString = false): array
    {
        if (! isset($payload['requestTime'])) {
            $ms = (int) floor(microtime(true) * 1000);
            $payload['requestTime'] = $asString ? (string) $ms : $ms;
        }

        return $payload;
    }

    /** Format a numeric amount with two decimals as DukPay expects ("100.00"). */
    public static function amount(int|float|string $amount, int $decimals = 2): string
    {
        return number_format((float) $amount, $decimals, '.', '');
    }

    /** Remove null values so optional fields are not sent. */
    protected function filter(array $payload): array
    {
        return array_filter($payload, static fn ($value) => $value !== null);
    }
}
