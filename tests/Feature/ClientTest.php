<?php

namespace Rrq\Dukpay\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Rrq\Dukpay\Crypto\MerchantAes;
use Rrq\Dukpay\Exceptions\DukPayApiException;
use Rrq\Dukpay\Exceptions\DukPayHttpException;
use Rrq\Dukpay\Exceptions\InvalidSignatureException;
use Rrq\Dukpay\Facades\DukPay;
use Rrq\Dukpay\Tests\Support\FakeDukPayServer;
use Rrq\Dukpay\Tests\TestCase;

class ClientTest extends TestCase
{
    public function test_create_payin_is_encrypted_signed_and_decrypted(): void
    {
        $server = (new FakeDukPayServer())->fake(fn ($api, $payload) => [0, [
            'orderNo' => 'T20250615103000123456',
            'refNo' => 'R1',
            'currency' => 'IDR',
            'orderAmount' => 10000,
            'status' => 'init',
            'channelAttrs' => ['checkoutUrl' => 'https://pay.example/c/1', 'qrCode' => '000201...'],
        ]]);

        $response = DukPay::payins()->create([
            'businessNo' => 'INV-001',
            'paymentMethod' => 'QRIS',
            'country' => 'IDN',
            'currency' => 'IDR',
            'orderAmount' => '10000',
            'properties' => ['email' => 'buyer@example.com'],
        ]);

        $this->assertTrue($response->successful());
        $this->assertSame('init', $response->data('status'));
        $this->assertSame('https://pay.example/c/1', $response['channelAttrs.checkoutUrl']);

        $sent = $server->received[0];
        $this->assertSame('orderCreateV2', $sent['api']);
        $this->assertSame('INV-001', $sent['payload']['businessNo']);
        $this->assertSame('https://merchant.test/dukpay/callback', $sent['payload']['callbackUrl']);
        $this->assertIsInt($sent['payload']['requestTime']);
        $this->assertSame('V2', $sent['headers']['X-DUKPAY-SIGN-VERSION'][0]);
        $this->assertMatchesRegularExpression('/^\d{10}$/', $sent['headers']['X-DUKPAY-TIMESTAMP'][0]);
        $this->assertArrayNotHasKey('X-PRODUCT-ID', $sent['headers']);

        Http::assertSent(fn ($request) => $request->url() === 'https://pre-payment.dukpay.com/public/merchant'
            && ! str_contains($request->body(), 'INV-001'));
    }

    public function test_every_resource_uses_the_documented_api_name(): void
    {
        $server = (new FakeDukPayServer())->fake();

        DukPay::payins()->hostedCheckout(['businessNo' => 'A', 'country' => 'IDN', 'currency' => 'IDR', 'orderAmount' => '1.00']);
        DukPay::payins()->query('A');
        DukPay::payins()->createCardOrder(['businessNo' => 'B', 'country' => 'RUS', 'currency' => 'RUB', 'orderAmount' => '1.00', 'properties' => ['clientIp' => '1.1.1.1']]);
        DukPay::payins()->advanceChallenge('tok', 1, ['otp' => '123456']);
        DukPay::refunds()->create(['businessNo' => 'R', 'sourceOrderNo' => 'T1', 'reason' => 'x']);
        DukPay::refunds()->query(orderNo: 'RF1');
        DukPay::accounts()->create(['businessNo' => 'AC', 'country' => 'RUS', 'currency' => 'RUB', 'purpose' => 'OnInitiate']);
        DukPay::accounts()->createV2(['businessNo' => 'AC2', 'country' => 'KOR', 'currency' => 'KRW', 'purpose' => 'OnInitiate']);
        DukPay::accounts()->query('acc1');
        DukPay::accounts()->charge(['credential' => 'c', 'businessNo' => 'W', 'country' => 'KOR', 'currency' => 'KRW', 'orderAmount' => 100]);
        DukPay::accounts()->chargeQuery('W');
        DukPay::accounts()->revokeCredential('acc1');
        DukPay::subscriptions()->create(['businessNo' => 'S', 'accountId' => 'acc1', 'amountType' => 'fixed', 'amount' => '150', 'interval' => 'month']);
        DukPay::subscriptions()->cancel('sub1');
        DukPay::subscriptions()->query('sub1');
        DukPay::subscriptions()->refund(['businessNo' => 'SR', 'subscriptionId' => 'sub1', 'termNo' => 1, 'amount' => '150']);
        DukPay::subscriptions()->switchPlan(['businessNo' => 'SW', 'sourceSubscriptionId' => 'sub1', 'balanceUsage' => 'FOR_CONVERT']);
        DukPay::finance()->balance();
        DukPay::finance()->currencyExchange('USD', 'IDR', 10);
        DukPay::finance()->reconciliation(['date' => '2026-11-01', 'reconciliationType' => 'BALANCE', 'accountNo' => 'A1', 'currency' => 'IDR']);
        DukPay::finance()->profitSharing('T1');

        $this->assertSame([
            'advanceOrder', 'orderQueryV2', 'challengeOrder', 'challengeOrder',
            'refund', 'refundQuery', 'accountCreate', 'accountCreateV2', 'accountQuery', 'credentialWithhold',
            'credentialWithholdQuery', 'credentialManagement', 'subscriptionCreate', 'subscriptionCancel',
            'subscriptionQuery', 'subscriptionRefund', 'switchSubscriptionPlans', 'accountBalance',
            'currencyExchange', 'financial', 'profitSharingQuery',
        ], array_column($server->received, 'api'));

        // V1 account create must not send purpose; V2 keeps it.
        $this->assertArrayNotHasKey('purpose', $server->received[6]['payload']);
        $this->assertSame('OnInitiate', $server->received[7]['payload']['purpose']);

        // Card order: requestTime as string, BANK_CARD default.
        $this->assertIsString($server->received[2]['payload']['requestTime']);
        $this->assertSame('BANK_CARD', $server->received[2]['payload']['paymentMethod']);

        // Challenge input is MERCHANT_AES encrypted with the secret key.
        $advance = $server->received[3]['payload'];
        $this->assertSame('1', $advance['currentIndex']);
        $this->assertSame('{"otp":"123456"}', (new MerchantAes('merchant-secret-key'))->decryptInput($advance['input']));

        // Empty payload is sent as a JSON object.
        $this->assertSame([], $server->received[17]['payload']);

        // Request keys are unique.
        $keys = array_column($server->received, 'requestKey');
        $this->assertCount(count($keys), array_unique($keys));
    }

    public function test_non_zero_code_throws_api_exception(): void
    {
        (new FakeDukPayServer())->fake(fn () => [2012, null, 'AMOUNT_OUT_RANGE']);

        try {
            DukPay::payins()->create(['businessNo' => 'BIG', 'orderAmount' => '99999999']);
            $this->fail('Expected exception');
        } catch (DukPayApiException $e) {
            $this->assertSame(2012, $e->getCode());
            $this->assertSame('AMOUNT_OUT_RANGE', $e->response->message);
        }
    }

    public function test_throwing_can_be_disabled_per_call(): void
    {
        (new FakeDukPayServer())->fake(fn () => [1000, null, 'param error']);

        $response = DukPay::call('orderQueryV2', ['businessNo' => 'X'], ['throw' => false]);

        $this->assertTrue($response->failed());
        $this->assertSame(1000, $response->code);
    }

    public function test_invalid_response_signature_is_rejected(): void
    {
        (new FakeDukPayServer())->fake(tamper: true);

        $this->expectException(InvalidSignatureException::class);

        DukPay::payins()->query('X');
    }

    public function test_missing_response_signature_on_encrypted_data_is_rejected(): void
    {
        (new FakeDukPayServer())->fake(signResponse: false);

        $this->expectException(InvalidSignatureException::class);

        DukPay::payins()->query('X');
    }

    public function test_http_errors_are_wrapped(): void
    {
        Http::fake(fn () => Http::response('Bad gateway', 502));

        $this->expectException(DukPayHttpException::class);

        DukPay::payins()->query('X');
    }

    public function test_product_id_header_is_sent_when_configured(): void
    {
        config(['dukpay.product_id' => 'PRD-1']);
        $this->app->forgetInstance(\Rrq\Dukpay\DukPayClient::class);
        $this->app->forgetInstance(\Rrq\Dukpay\DukPay::class);
        DukPay::clearResolvedInstances();

        $server = (new FakeDukPayServer())->fake();
        DukPay::finance()->balance('IDR');

        $this->assertSame('PRD-1', $server->received[0]['headers']['X-PRODUCT-ID'][0]);
        $this->assertSame(['currency' => 'IDR'], $server->received[0]['payload']);
    }
}
