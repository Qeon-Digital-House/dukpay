<?php

namespace Rrq\Dukpay\Support;

/** DukPay response codes (section 9 of the DukPay guide). */
final class ResponseCode
{
    public const SUCCESS = 0;

    // Common errors
    public const PARAM_ERROR = 1000;
    public const SIGNATURE_ERROR = 1001;
    public const REQUEST_EXPIRED = 1002;
    public const SYSTEM_ERROR = 1003;
    public const AUTH_FAILED = 1004;
    public const NO_PERMISSION = 1005;
    public const API_NOT_FOUND = 1006;
    public const CHANNEL_SERVICE_ERROR = 1010;
    public const CONFIG_OR_PARAM_ERROR = 1011;

    // Business errors
    public const NO_AVAILABLE_ACCOUNT = 2001;
    public const UNSUPPORTED_PAYMENT_METHOD = 2003;
    /** The guide uses 2004 for both "Order not found" and "Order creation failed". */
    public const ORDER_NOT_FOUND = 2004;
    public const ORDER_CREATION_FAILED = 2004;
    public const AMOUNT_OUT_OF_RANGE = 2012;
    public const UNSUPPORTED_PAYMENT_METHOD_ALT = 2013;
    public const PAYOUT_ORDER_EXISTS = 2014;
    public const BALANCE_NOT_ENOUGH = 2015;

    /** Messages as written in the guide. */
    public const MESSAGES = [
        self::SUCCESS => 'success',
        self::PARAM_ERROR => 'param error',
        self::SIGNATURE_ERROR => 'signature error',
        self::REQUEST_EXPIRED => 'request expired',
        self::SYSTEM_ERROR => 'system error',
        self::AUTH_FAILED => 'auth failed',
        self::NO_PERMISSION => 'no permission',
        self::API_NOT_FOUND => 'api not found',
        self::CHANNEL_SERVICE_ERROR => 'channel service error',
        self::CONFIG_OR_PARAM_ERROR => 'config or param error',
        self::NO_AVAILABLE_ACCOUNT => 'No available account',
        self::UNSUPPORTED_PAYMENT_METHOD => 'Unsupported payment method',
        self::ORDER_NOT_FOUND => 'Order not found / Order creation failed',
        self::AMOUNT_OUT_OF_RANGE => 'AMOUNT_OUT_RANGE',
        self::UNSUPPORTED_PAYMENT_METHOD_ALT => 'UNSUPPORTED_PAYMENT_METHOD',
        self::PAYOUT_ORDER_EXISTS => 'payout order already exists',
        self::BALANCE_NOT_ENOUGH => 'balance not enough',
    ];

    public static function message(int $code): ?string
    {
        return self::MESSAGES[$code] ?? null;
    }

    public static function isSuccess(int $code): bool
    {
        return $code === self::SUCCESS;
    }

    /** True for 2003 and 2013, which both mean the payment method is not supported. */
    public static function isUnsupportedPaymentMethod(int $code): bool
    {
        return $code === self::UNSUPPORTED_PAYMENT_METHOD || $code === self::UNSUPPORTED_PAYMENT_METHOD_ALT;
    }
}
