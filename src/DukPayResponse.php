<?php

namespace Rrq\Dukpay;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use JsonSerializable;
use LogicException;
use Rrq\Dukpay\Exceptions\DukPayApiException;

/**
 * A verified and decrypted DukPay synchronous response.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements Arrayable<string, mixed>
 */
final class DukPayResponse implements ArrayAccess, Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $api,
        public readonly string $requestKey,
        public readonly int $code,
        public readonly ?string $message,
        public readonly mixed $data,
        public readonly string $rawBody,
        public readonly int $httpStatus = 200,
    ) {
    }

    public function successful(): bool
    {
        return $this->code === 0;
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    /** Decrypted business payload, or a value from it using "dot" notation. */
    public function data(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        return is_array($this->data) ? Arr::get($this->data, $key, $default) : $default;
    }

    /** @throws DukPayApiException */
    public function throw(): self
    {
        if ($this->failed()) {
            throw new DukPayApiException($this);
        }

        return $this;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_array($this->data) && Arr::has($this->data, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('DukPay responses are immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('DukPay responses are immutable.');
    }
}
