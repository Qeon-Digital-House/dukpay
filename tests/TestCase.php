<?php

namespace Rrq\Dukpay\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rrq\Dukpay\DukPayServiceProvider;
use Rrq\Dukpay\Tests\Support\Keys;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [DukPayServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('dukpay.base_url', 'https://pre-payment.dukpay.com');
        $app['config']->set('dukpay.secret_id', 'test');
        $app['config']->set('dukpay.secret_key', 'merchant-secret-key');
        $app['config']->set('dukpay.merchant_private_key', Keys::pair('merchant')['private']);
        $app['config']->set('dukpay.dukpay_public_key', Keys::pair('dukpay')['public']);
        $app['config']->set('dukpay.callback_url', 'https://merchant.test/dukpay/callback');
    }
}
