<?php

namespace Rrq\Dukpay\Support;

/** Values for the X-DUKPAY-API header. */
final class Api
{
    // Payin
    public const ORDER_CREATE = 'orderCreateV2';
    public const HOSTED_CHECKOUT = 'advanceOrder';
    public const CHALLENGE_ORDER = 'challengeOrder';
    public const ORDER_QUERY = 'orderQueryV2';

    // Refund
    public const REFUND = 'refund';
    public const REFUND_QUERY = 'refundQuery';

    // Authorized account & credential-based charge
    public const ACCOUNT_CREATE = 'accountCreate';
    public const ACCOUNT_CREATE_V2 = 'accountCreateV2';
    public const ACCOUNT_QUERY = 'accountQuery';
    public const CREDENTIAL_WITHHOLD = 'credentialWithhold';
    public const CREDENTIAL_WITHHOLD_QUERY = 'credentialWithholdQuery';
    public const CREDENTIAL_MANAGEMENT = 'credentialManagement';

    // Subscription
    public const SUBSCRIPTION_CREATE = 'subscriptionCreate';
    public const SUBSCRIPTION_CANCEL = 'subscriptionCancel';
    public const SUBSCRIPTION_QUERY = 'subscriptionQuery';
    public const SUBSCRIPTION_REFUND = 'subscriptionRefund';
    public const SUBSCRIPTION_SWITCH = 'switchSubscriptionPlans';

    // Accounts & reconciliation
    public const ACCOUNT_BALANCE = 'accountBalance';
    public const CURRENCY_EXCHANGE = 'currencyExchange';
    public const FINANCIAL = 'financial';
    public const PROFIT_SHARING_QUERY = 'profitSharingQuery';

    // Asynchronous notifications
    public const CALLBACK = 'dukpay-callback';
}
