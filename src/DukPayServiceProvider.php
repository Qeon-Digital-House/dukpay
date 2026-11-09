<?php

namespace Rrq\Dukpay;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Rrq\Dukpay\Crypto\Envelope;
use Rrq\Dukpay\Crypto\KeyLoader;
use Rrq\Dukpay\Crypto\MerchantAes;
use Rrq\Dukpay\Crypto\Signer;

class DukPayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dukpay.php', 'dukpay');

        $this->app->singleton(DukPayClient::class, function (Application $app) {
            $config = $app['config']->get('dukpay');

            $merchantPrivate = KeyLoader::privatePem($config['merchant_private_key'] ?? null, 'merchant_private_key');
            $dukpayPublic = KeyLoader::publicPem($config['dukpay_public_key'] ?? null, 'dukpay_public_key');

            $logger = ! empty($config['log_channel']) ? $app['log']->channel($config['log_channel']) : null;

            return new DukPayClient(
                config: $config,
                http: $app->make(HttpFactory::class),
                envelope: new Envelope($dukpayPublic, $merchantPrivate),
                signer: new Signer($merchantPrivate, $dukpayPublic),
                logger: $logger,
            );
        });

        $this->app->singleton(DukPay::class, function (Application $app) {
            $config = $app['config']->get('dukpay');

            return new DukPay(
                $app->make(DukPayClient::class),
                new MerchantAes($config['secret_key'] ?? null),
                [
                    'callback_url' => $config['callback_url'] ?? null,
                    'redirect_url' => $config['redirect_url'] ?? null,
                    'country' => $config['country'] ?? null,
                    'currency' => $config['currency'] ?? null,
                ],
            );
        });

        $this->app->alias(DukPay::class, 'dukpay');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/dukpay.php' => config_path('dukpay.php'),
            ], 'dukpay-config');
        }

        if ($this->app['config']->get('dukpay.webhook.enabled', true) && ! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/dukpay.php');
        }
    }
}
