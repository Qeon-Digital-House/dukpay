<?php

namespace Rrq\Dukpay\Tests\Feature;

use Rrq\Dukpay\Facades\DukPay;
use Rrq\Dukpay\Tests\Support\FakeDukPayServer;
use Rrq\Dukpay\Tests\TestCase;

class DefaultCountryTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('dukpay.country', 'idn');
    }

    public function test_country_and_currency_are_filled_from_config(): void
    {
        $server = (new FakeDukPayServer())->fake();

        DukPay::payins()->create(['businessNo' => 'A', 'paymentMethod' => 'QRIS', 'orderAmount' => '10000']);
        DukPay::payins()->hostedCheckout(['businessNo' => 'C', 'orderAmount' => '1.00']);
        DukPay::accounts()->create(['businessNo' => 'D']);
        DukPay::accounts()->charge(['credential' => 'c', 'businessNo' => 'E', 'orderAmount' => 1]);

        foreach ($server->received as $request) {
            $this->assertSame('IDN', $request['payload']['country'], $request['api']);
            $this->assertSame('IDR', $request['payload']['currency'], $request['api']);
        }
    }

    public function test_payload_values_win_and_currency_follows_explicit_country(): void
    {
        config(['dukpay.currency' => 'USD']);
        $this->app->forgetInstance(\Rrq\Dukpay\DukPay::class);
        DukPay::clearResolvedInstances();

        $server = (new FakeDukPayServer())->fake();

        DukPay::payins()->create(['businessNo' => 'A', 'orderAmount' => '1']);                         // IDN + configured USD
        DukPay::payins()->create(['businessNo' => 'B', 'country' => 'KOR', 'orderAmount' => '1']);     // KOR -> KRW, not USD
        DukPay::payins()->create(['businessNo' => 'C', 'country' => 'RUS', 'currency' => 'USD', 'orderAmount' => '1']);
        DukPay::payins()->query('A');                                                                    // untouched

        $this->assertSame(['IDN', 'USD'], [$server->received[0]['payload']['country'], $server->received[0]['payload']['currency']]);
        $this->assertSame(['KOR', 'KRW'], [$server->received[1]['payload']['country'], $server->received[1]['payload']['currency']]);
        $this->assertSame(['RUS', 'USD'], [$server->received[2]['payload']['country'], $server->received[2]['payload']['currency']]);
        $this->assertSame(['businessNo' => 'A'], $server->received[3]['payload']);
    }
}
