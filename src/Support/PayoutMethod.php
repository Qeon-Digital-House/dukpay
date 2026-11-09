<?php

namespace Rrq\Dukpay\Support;

/** Payout payoutMethod values (section 4.2 of the DukPay guide). */
final class PayoutMethod
{
    public const BANK_TRANSFER = 'BANK_TRANSFER';
    public const INSTANT_TRANSFER = 'INSTANT_TRANSFER';
    public const BANK_CARD = 'BANK_CARD';
    public const EWALLET = 'EWALLET';
    public const MOBILE_WALLET = 'MOBILE_WALLET';
    public const MOBILE_PHONE = 'MOBILE_PHONE';
    public const DANA = 'DANA';
    public const LINKAJA = 'LINKAJA';
    public const SHOPEEPAY = 'SHOPEEPAY';
    public const OVO = 'OVO';
    public const GCASH = 'GCASH';
    public const GRABPAY = 'GRABPAY';
    public const PAYMAYA = 'PAYMAYA';
    public const ZALO_PAY = 'ZALO_PAY';
    public const PAYBY = 'PAYBY';
    public const PIX = 'PIX';
    public const YOOMONEY = 'YOOMONEY';
    public const SBP = 'SBP';
    public const FAWRYPAY = 'FAWRYPAY';

    /** Payout methods per country as listed in the guide. UZB and MYS are marked "currently unsupported". */
    public const BY_COUNTRY = [
        Country::IDN => [self::DANA, self::LINKAJA, self::SHOPEEPAY, self::OVO, self::BANK_TRANSFER],
        Country::PHL => [self::GCASH, self::GRABPAY, self::PAYMAYA, self::BANK_TRANSFER],
        Country::KOR => [self::BANK_TRANSFER],
        Country::THA => [self::BANK_TRANSFER],
        Country::PAK => [self::BANK_TRANSFER, self::EWALLET],
        Country::VNM => [self::BANK_TRANSFER, self::ZALO_PAY],
        Country::MEX => [self::BANK_TRANSFER],
        Country::ARE => [self::PAYBY],
        Country::BRA => [self::PIX],
        Country::RUS => [self::YOOMONEY, self::MOBILE_PHONE, self::BANK_CARD, self::SBP],
        Country::KAZ => [self::BANK_CARD],
        Country::UZB => [self::BANK_CARD],
        Country::AZE => [self::BANK_CARD],
        Country::MYS => [self::BANK_TRANSFER],
        Country::MNG => [self::BANK_TRANSFER],
        Country::EGY => [self::FAWRYPAY, self::BANK_TRANSFER, self::INSTANT_TRANSFER, self::MOBILE_WALLET],
        Country::NGA => [self::BANK_TRANSFER],
        Country::TJK => [self::BANK_CARD],
        Country::KGZ => [self::BANK_CARD],
        Country::KEN => [self::BANK_TRANSFER],
        Country::UGA => [self::BANK_TRANSFER],
    ];

    /** @return list<string> */
    public static function forCountry(string $country): array
    {
        return self::BY_COUNTRY[strtoupper($country)] ?? [];
    }

    public static function supports(string $country, string $method): bool
    {
        return in_array(strtoupper($method), self::forCountry($country), true);
    }
}
