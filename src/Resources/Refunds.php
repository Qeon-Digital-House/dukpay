<?php

namespace Rrq\Dukpay\Resources;

use InvalidArgumentException;
use Rrq\Dukpay\DukPayResponse;
use Rrq\Dukpay\Support\Api;

/** Refunds (section 6). */
class Refunds extends Resource
{
    /**
     * Create a refund (refund).
     *
     *   businessNo, sourceOrderNo (DukPay orderNo), reason, amount? (omit = full refund),
     *   sourceTransactionType? (PAY_IN | WITHHOLD), callbackUrl?
     */
    public function create(array $payload): DukPayResponse
    {
        return $this->call(Api::REFUND, $this->withCallbackUrl($payload));
    }

    /** Query a refund (refundQuery). */
    public function query(?string $businessNo = null, ?string $orderNo = null): DukPayResponse
    {
        if ($businessNo === null && $orderNo === null) {
            throw new InvalidArgumentException('Provide businessNo or orderNo.');
        }

        return $this->call(Api::REFUND_QUERY, $this->filter(['businessNo' => $businessNo, 'orderNo' => $orderNo]));
    }
}
