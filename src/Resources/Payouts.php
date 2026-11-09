<?php

namespace Rrq\Dukpay\Resources;

use InvalidArgumentException;
use Rrq\Dukpay\DukPayResponse;
use Rrq\Dukpay\Support\Api;

/** payout: send funds to a user (section 4.2, 4.5). */
class Payouts extends Resource
{
    /**
     * Create a payout order (payoutCreate). Code 2014 means the businessNo already exists.
     *
     *   businessNo, country, payoutMethod, currency, amount, customerInfo{...}, description?, callbackUrl?
     */
    public function create(array $payload): DukPayResponse
    {
        return $this->call(Api::PAYOUT_CREATE, $this->withRequestTime($this->withCallbackUrl($this->withCountry($payload))));
    }

    /** Query a payout by DukPay orderNo and/or merchant businessNo (payoutQuery). */
    public function query(?string $businessNo = null, ?string $orderNo = null): DukPayResponse
    {
        if ($businessNo === null && $orderNo === null) {
            throw new InvalidArgumentException('Provide businessNo or orderNo.');
        }

        return $this->call(Api::PAYOUT_QUERY, $this->filter(['businessNo' => $businessNo, 'orderNo' => $orderNo]));
    }
}
