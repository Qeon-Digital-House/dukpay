<?php

namespace Rrq\Dukpay\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Rrq\Dukpay\Events\DukPayCallbackReceived;
use Rrq\Dukpay\Tests\Support\FakeDukPayServer;
use Rrq\Dukpay\Tests\TestCase;

class CallbackTest extends TestCase
{
    private function postCallback(string $body, array $headers)
    {
        $server = [];
        foreach ($headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));
            $server[$key === 'CONTENT_TYPE' ? $key : 'HTTP_'.$key] = $value;
        }

        return $this->call('POST', '/dukpay/callback', [], [], [], $server, $body);
    }

    public function test_valid_callback_is_decrypted_and_dispatched(): void
    {
        Event::fake([DukPayCallbackReceived::class]);

        [$body, $headers] = (new FakeDukPayServer())->callback([
            'orderNo' => 'T20250615103000123456',
            'businessNo' => 'INV-001',
            'status' => 'success',
            'currency' => 'IDR',
            'orderAmount' => 10000,
        ]);

        $this->postCallback($body, $headers)->assertOk()->assertExactJson(['code' => 0]);

        Event::assertDispatched(DukPayCallbackReceived::class, function (DukPayCallbackReceived $event) {
            return $event->callback->isPayin()
                && $event->callback->isSuccess()
                && $event->callback->businessNo() === 'INV-001'
                && $event->callback->requestKey === '2063888785439178752';
        });
    }

    public function test_tampered_callback_is_rejected(): void
    {
        Event::fake([DukPayCallbackReceived::class]);

        [$body, $headers] = (new FakeDukPayServer())->callback(['status' => 'success']);
        $headers['X-DUKPAY-REQ-KEY'] = '999';

        $this->postCallback($body, $headers)->assertStatus(401)->assertJson(['code' => 1001]);

        Event::assertNotDispatched(DukPayCallbackReceived::class);
    }

    public function test_listener_failure_returns_error_so_dukpay_retries(): void
    {
        Event::listen(DukPayCallbackReceived::class, fn () => throw new \RuntimeException('db down'));

        [$body, $headers] = (new FakeDukPayServer())->callback(['status' => 'success'], 'payment_order');

        $this->postCallback($body, $headers)->assertStatus(500);
    }
}
