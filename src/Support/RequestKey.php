<?php

namespace Rrq\Dukpay\Support;

/** Generates unique X-DUKPAY-REQ-KEY values (19-digit numeric, time-ordered). */
final class RequestKey
{
    public static function generate(): string
    {
        return (string) (int) floor(microtime(true) * 1000).str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
