<?php

namespace Rrq\Dukpay;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Rrq\Dukpay\Support\CallbackType;

/**
 * A verified and decrypted DukPay asynchronous notification.
 *
 * @implements Arrayable<string, mixed>
 */
final class DukPayCallback implements Arrayable
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public readonly ?string $type,
        public readonly array $data,
        public readonly string $requestKey,
        public readonly ?string $timestamp,
        public readonly string $rawBody,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }

    public function status(): ?string
    {
        return $this->get('status');
    }

    public function businessNo(): ?string
    {
        return $this->get('businessNo');
    }

    public function orderNo(): ?string
    {
        return $this->get('orderNo');
    }

    public function is(string $type): bool
    {
        return $this->type === $type;
    }

    public function isPayin(): bool
    {
        return $this->is(CallbackType::PAYMENT_ORDER);
    }

    public function isPayout(): bool
    {
        return $this->is(CallbackType::PAYOUT_ORDER);
    }

    public function isRefund(): bool
    {
        return $this->is(CallbackType::REFUND_ORDER);
    }

    public function isSuccess(): bool
    {
        return $this->status() === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status() === 'failed';
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'requestKey' => $this->requestKey,
            'timestamp' => $this->timestamp,
            'data' => $this->data,
        ];
    }
}
