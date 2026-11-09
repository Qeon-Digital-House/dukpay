<?php

namespace Rrq\Dukpay\Events;

use Rrq\Dukpay\DukPayCallback;

/**
 * Fired after a DukPay notification has been verified and decrypted.
 * Listeners run synchronously; if one throws, DukPay receives a non-success
 * answer and will retry. Make listeners idempotent (DukPay may deliver the
 * same notification more than once).
 */
class DukPayCallbackReceived
{
    public function __construct(public readonly DukPayCallback $callback)
    {
    }
}
