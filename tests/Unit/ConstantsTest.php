<?php

namespace Rrq\Dukpay\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rrq\Dukpay\Support\Country;
use Rrq\Dukpay\Support\PaymentMethod;
use Rrq\Dukpay\Support\PayoutMethod;

class ConstantsTest extends TestCase
{
    public function test_lookups(): void
    {
        $this->assertSame('IDR', Country::currency('idn'));
        $this->assertNull(Country::currency('XXX'));
        $this->assertTrue(PaymentMethod::supports(Country::IDN, 'qris'));
        $this->assertFalse(PaymentMethod::supports(Country::IDN, PaymentMethod::PIX));
        $this->assertContains(PayoutMethod::DANA, PayoutMethod::forCountry(Country::IDN));
    }

    public function test_every_mapped_country_has_a_currency(): void
    {
        foreach (array_keys(PaymentMethod::BY_COUNTRY + PayoutMethod::BY_COUNTRY) as $country) {
            $this->assertNotNull(Country::currency($country), $country);
        }
    }
}
