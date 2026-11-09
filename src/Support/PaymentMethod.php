<?php

namespace Rrq\Dukpay\Support;

/** Payin paymentMethod values (section 4.1 of the DukPay guide). */
final class PaymentMethod
{
    // Generic
    public const BANK_CARD = 'BANK_CARD';
    public const BANK_TRANSFER = 'BANK_TRANSFER';
    public const CREDIT_CARD = 'CREDIT_CARD';
    public const CASH = 'CASH';
    public const DCB = 'DCB';
    public const MOBILE_MONEY = 'MOBILE_MONEY';
    public const MOBILE_WALLET = 'MOBILE_WALLET';
    public const SHOPEEPAY = 'SHOPEEPAY';

    // Indonesia
    public const OVO = 'OVO';
    public const LINKAJA = 'LINKAJA';
    public const DANA = 'DANA';
    public const QRIS = 'QRIS';

    // Brazil
    public const PIX = 'PIX';
    public const BOLETO = 'BOLETO';

    // Philippines
    public const GCASH = 'GCASH';
    public const PAYMAYA = 'PAYMAYA';
    public const GRABPAY = 'GRABPAY';
    public const QRPH = 'QRPH';

    // Russia
    public const YOOMONEY = 'YOOMONEY';
    public const SBER_PAY = 'SBER_PAY';
    public const TINKOFF_PAY = 'TINKOFF_PAY';
    public const SBP = 'SBP';

    // Vietnam
    public const ZALO_PAY = 'ZALO_PAY';
    public const VTC_PAY = 'VTC_PAY';
    public const VIET_QR = 'VIET_QR';
    public const VN_PAY = 'VN_PAY';

    // Pakistan
    public const EASYPAISA = 'EASYPAISA';
    public const JAZZ_CASH = 'JAZZ_CASH';

    // South Korea
    public const PAYCO = 'PAYCO';
    public const KAKAO = 'KAKAO';
    public const NAVER_PAY = 'NAVER_PAY';
    public const TMONEY_PAY = 'TMONEY_PAY';
    public const SSPAY = 'SSPAY';

    // Colombia
    public const PSE = 'PSE';
    public const EFECTY = 'EFECTY';

    // Thailand
    public const LINE_PAY = 'LINE_PAY';
    public const TRUE_MONEY = 'TRUE_MONEY';
    public const WECHAT_PAY = 'WECHAT_PAY';
    public const PROMPT_PAY = 'PROMPT_PAY';

    // Saudi Arabia
    public const TABBY = 'TABBY';
    public const APPLE_PAY = 'APPLE_PAY';
    public const STCPAY = 'STCPAY';
    public const TAMARA = 'TAMARA';

    // Chile
    public const WEB_PAY = 'WEB_PAY';
    public const KHIPU = 'KHIPU';
    public const MACH = 'MACH';
    public const HITES = 'HITES';

    // Others
    public const PAY_NOW = 'PAY_NOW';           // Singapore
    public const FAWRYPAY = 'FAWRYPAY';         // Egypt
    public const PAGO_EFECTIVO_QR = 'PAGO_EFECTIVO_QR'; // Peru
    public const UZUM_BANK = 'UZUM_BANK';       // Uzbekistan
    public const DUITNOW_QR = 'DUITNOW_QR';     // Malaysia
    public const PAYBY = 'PAYBY';               // UAE
    public const BOTIM_PAY = 'BOTIM_PAY';       // UAE
    public const QPAY = 'QPAY';                 // Mongolia
    public const NAGAD = 'NAGAD';               // Bangladesh
    public const BKASH = 'BKASH';               // Bangladesh
    public const KHALTI = 'KHALTI';             // Nepal
    public const KONBINI = 'KONBINI';           // Japan
    public const PAYPAY = 'PAYPAY';             // Japan
    public const PAIDY = 'PAIDY';               // Japan
    public const FASTSHIFT = 'FASTSHIFT';       // Armenia

    /** Payin methods per country as listed in the guide. UAE and Turkey are marked "currently unsupported". */
    public const BY_COUNTRY = [
        Country::IDN => [self::OVO, self::LINKAJA, self::SHOPEEPAY, self::DANA, self::BANK_TRANSFER, self::QRIS],
        Country::BRA => [self::PIX, self::BOLETO, self::BANK_TRANSFER, self::CASH],
        Country::PHL => [self::GCASH, self::PAYMAYA, self::GRABPAY, self::BANK_CARD, self::QRPH],
        Country::MEX => [self::BANK_TRANSFER, self::CREDIT_CARD, self::CASH],
        Country::RUS => [self::BANK_CARD, self::YOOMONEY, self::SBER_PAY, self::TINKOFF_PAY, self::SBP, self::DCB],
        Country::BLR => [self::BANK_CARD],
        Country::VNM => [self::ZALO_PAY, self::BANK_CARD, self::BANK_TRANSFER, self::VTC_PAY, self::VIET_QR, self::VN_PAY],
        Country::PAK => [self::EASYPAISA, self::JAZZ_CASH],
        Country::KOR => [self::CREDIT_CARD, self::BANK_TRANSFER, self::DCB, self::PAYCO, self::KAKAO, self::NAVER_PAY, self::TMONEY_PAY, self::SSPAY],
        Country::COL => [self::PSE, self::CREDIT_CARD, self::EFECTY, self::BANK_TRANSFER, self::CASH],
        Country::THA => [self::LINE_PAY, self::TRUE_MONEY, self::SHOPEEPAY, self::WECHAT_PAY, self::PROMPT_PAY],
        Country::SAU => [self::TABBY, self::APPLE_PAY, self::BANK_CARD, self::STCPAY, self::TAMARA],
        Country::CHL => [self::WEB_PAY, self::KHIPU, self::MACH, self::HITES, self::BANK_TRANSFER],
        Country::SGP => [self::PAY_NOW],
        Country::TZA => [self::MOBILE_MONEY],
        Country::KEN => [self::MOBILE_MONEY, self::BANK_CARD],
        Country::UGA => [self::MOBILE_MONEY],
        Country::EGY => [self::FAWRYPAY, self::BANK_CARD, self::MOBILE_WALLET],
        Country::KAZ => [self::BANK_CARD],
        Country::NGA => [self::BANK_CARD],
        Country::GHA => [self::BANK_CARD],
        Country::ZAF => [self::BANK_CARD],
        Country::ARG => [self::BANK_TRANSFER, self::CASH],
        Country::PER => [self::BANK_TRANSFER, self::CASH, self::PAGO_EFECTIVO_QR],
        Country::UZB => [self::BANK_CARD, self::UZUM_BANK],
        Country::MYS => [self::BANK_TRANSFER, self::DUITNOW_QR],
        Country::ARE => [self::BANK_CARD, self::PAYBY, self::BOTIM_PAY],
        Country::TUR => [self::BANK_TRANSFER],
        Country::MNG => [self::BANK_CARD, self::QPAY],
        Country::BGD => [self::NAGAD, self::BKASH],
        Country::NPL => [self::KHALTI],
        Country::JPN => [self::KONBINI, self::PAYPAY, self::PAIDY, self::BANK_CARD, self::BANK_TRANSFER],
        Country::ARM => [self::FASTSHIFT],
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
