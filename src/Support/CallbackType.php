<?php

namespace Rrq\Dukpay\Support;

/** Values of the "callback-type" header on DukPay notifications. */
final class CallbackType
{
    public const PAYMENT_ORDER = 'payment_order';
    public const PAYOUT_ORDER = 'payout_order';
    public const REFUND_ORDER = 'refund_order';
    public const SUBSCRIPTION_ORDER = 'subscription_order';
    public const WITHHOLD_ORDER = 'withhold_order';
    public const AUTHORIZATION_ACCOUNT = 'authorization_account';
    public const CHARGEBACK_ORDER = 'chargeback_order';
    public const PAYMENT_FEE = 'payment_fee';
    public const PAYOUT_FEE = 'payout_fee';
    public const REFUND_FEE = 'refund_fee';
    public const AUTHORIZE_FEE = 'authorize_fee';
}
