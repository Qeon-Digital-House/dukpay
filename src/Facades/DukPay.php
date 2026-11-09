<?php

namespace Rrq\Dukpay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Rrq\Dukpay\DukPayClient client()
 * @method static \Rrq\Dukpay\Resources\Payins payins()
 * @method static \Rrq\Dukpay\Resources\Refunds refunds()
 * @method static \Rrq\Dukpay\Resources\Accounts accounts()
 * @method static \Rrq\Dukpay\Resources\Subscriptions subscriptions()
 * @method static \Rrq\Dukpay\Resources\Finance finance()
 * @method static \Rrq\Dukpay\DukPayResponse call(string $api, array $payload = [], array $options = [])
 * @method static \Rrq\Dukpay\DukPayCallback parseCallback(\Illuminate\Http\Request $request)
 * @method static string encryptCardInput(array $input)
 *
 * @see \Rrq\Dukpay\DukPay
 */
class DukPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Rrq\Dukpay\DukPay::class;
    }
}
