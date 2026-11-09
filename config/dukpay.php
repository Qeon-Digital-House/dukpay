<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Test environment: https://pre-payment.dukpay.com
    | Production: request it from DukPay technical support.
    | Every merchant API call is a POST to {base_url}{endpoint}.
    |
    */

    'base_url' => env('DUKPAY_BASE_URL', 'https://pre-payment.dukpay.com'),

    'endpoint' => env('DUKPAY_ENDPOINT', '/public/merchant'),

    /*
    |--------------------------------------------------------------------------
    | Credentials (Security Authentication V2)
    |--------------------------------------------------------------------------
    |
    | secret_id             SecretId issued by DukPay.
    | secret_key            Merchant secretKey. Only needed for the bank card
    |                       direct flow (MERCHANT_AES sensitive input encryption).
    | merchant_private_key  Merchant RSA-2048 private key. Accepts a single-line
    |                       Base64 PKCS#8 DER string, a PEM string, or a path
    |                       to a PEM / DER file.
    | dukpay_public_key     DukPay RSA public key. Accepts a single-line Base64
    |                       X.509 DER string, a PEM string, or a file path.
    | product_id            Optional X-PRODUCT-ID (sub-merchant / product).
    |
    | Test and production keys are different. Never reuse test keys in prod.
    |
    */

    'secret_id' => env('DUKPAY_SECRET_ID'),

    'secret_key' => env('DUKPAY_SECRET_KEY'),

    'merchant_private_key' => env('DUKPAY_MERCHANT_PRIVATE_KEY'),

    'dukpay_public_key' => env('DUKPAY_PUBLIC_KEY'),

    'product_id' => env('DUKPAY_PRODUCT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Default URLs
    |--------------------------------------------------------------------------
    |
    | When set, these are injected into requests that accept callbackUrl /
    | redirectUrl and do not provide one explicitly.
    |
    */

    'callback_url' => env('DUKPAY_CALLBACK_URL'),

    'redirect_url' => env('DUKPAY_REDIRECT_URL'),

    /*
    |--------------------------------------------------------------------------
    | Behaviour
    |--------------------------------------------------------------------------
    |
    | verify_response_signature  Verify X-DUKPAY-RESP-SIGN on synchronous
    |                            responses (strongly recommended).
    | throw_on_error             Throw DukPayApiException when the response
    |                            code is not 0. When false, inspect the
    |                            returned DukPayResponse yourself.
    |
    */

    'verify_response_signature' => env('DUKPAY_VERIFY_RESPONSE_SIGNATURE', true),

    'throw_on_error' => env('DUKPAY_THROW_ON_ERROR', true),

    'http' => [
        'timeout' => (int) env('DUKPAY_HTTP_TIMEOUT', 30),
        'connect_timeout' => (int) env('DUKPAY_HTTP_CONNECT_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Asynchronous notifications (callbacks)
    |--------------------------------------------------------------------------
    |
    | When enabled, the package registers a POST route that verifies the
    | signature, decrypts the body, dispatches the
    | Rrq\Dukpay\Events\DukPayCallbackReceived event and answers {"code":0}.
    | The route does not use the "web" middleware group, so no CSRF token
    | is required.
    |
    */

    'webhook' => [
        'enabled' => env('DUKPAY_WEBHOOK_ENABLED', true),
        'path' => env('DUKPAY_WEBHOOK_PATH', 'dukpay/callback'),
        'name' => 'dukpay.callback',
        'middleware' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log channel used for request/response metadata (api, requestKey, code).
    | Business payloads are never logged. Set to null to disable.
    |
    */

    'log_channel' => env('DUKPAY_LOG_CHANNEL'),

];
