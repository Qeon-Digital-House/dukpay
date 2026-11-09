<?php

namespace Rrq\Dukpay\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rrq\Dukpay\Support\Country;
use Rrq\Dukpay\Support\PaymentMethod;
use Rrq\Dukpay\Support\ResponseCode;

class ConstantsTest extends TestCase
{
    public function test_lookups(): void
    {
        $this->assertSame('IDR', Country::currency('idn'));
        $this->assertNull(Country::currency('XXX'));
        $this->assertTrue(PaymentMethod::supports(Country::IDN, 'qris'));
        $this->assertFalse(PaymentMethod::supports(Country::IDN, PaymentMethod::PIX));
    }

    public function test_every_mapped_country_has_a_currency(): void
    {
        foreach (array_keys(PaymentMethod::BY_COUNTRY) as $country) {
            $this->assertNotNull(Country::currency($country), $country);
        }
    }

    public function test_response_codes(): void
    {
        $this->assertSame(2015, ResponseCode::BALANCE_NOT_ENOUGH);
        $this->assertSame('payout order already exists', ResponseCode::message(ResponseCode::PAYOUT_ORDER_EXISTS));
        $this->assertNull(ResponseCode::message(9999));
        $this->assertTrue(ResponseCode::isUnsupportedPaymentMethod(2013));
        $this->assertTrue(ResponseCode::isUnsupportedPaymentMethod(2003));
        $this->assertTrue(ResponseCode::isSuccess(0));
    }
}
