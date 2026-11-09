<?php

namespace Rrq\Dukpay\Support;

/** ISO 3166-1 alpha-3 country codes used by DukPay, with their default currency. */
final class Country
{
    public const IDN = 'IDN'; // Indonesia
    public const BRA = 'BRA'; // Brazil
    public const PHL = 'PHL'; // Philippines
    public const MEX = 'MEX'; // Mexico
    public const RUS = 'RUS'; // Russia
    public const BLR = 'BLR'; // Belarus
    public const VNM = 'VNM'; // Vietnam
    public const PAK = 'PAK'; // Pakistan
    public const KOR = 'KOR'; // South Korea
    public const COL = 'COL'; // Colombia
    public const THA = 'THA'; // Thailand
    public const SAU = 'SAU'; // Saudi Arabia
    public const CHL = 'CHL'; // Chile
    public const SGP = 'SGP'; // Singapore
    public const TZA = 'TZA'; // Tanzania
    public const KEN = 'KEN'; // Kenya
    public const UGA = 'UGA'; // Uganda
    public const EGY = 'EGY'; // Egypt
    public const KAZ = 'KAZ'; // Kazakhstan
    public const NGA = 'NGA'; // Nigeria
    public const GHA = 'GHA'; // Ghana
    public const ZAF = 'ZAF'; // South Africa
    public const ARG = 'ARG'; // Argentina
    public const PER = 'PER'; // Peru
    public const UZB = 'UZB'; // Uzbekistan
    public const MYS = 'MYS'; // Malaysia
    public const ARE = 'ARE'; // United Arab Emirates
    public const TUR = 'TUR'; // Turkey
    public const MNG = 'MNG'; // Mongolia
    public const BGD = 'BGD'; // Bangladesh
    public const NPL = 'NPL'; // Nepal
    public const JPN = 'JPN'; // Japan
    public const ARM = 'ARM'; // Armenia
    public const AZE = 'AZE'; // Azerbaijan
    public const TJK = 'TJK'; // Tajikistan
    public const KGZ = 'KGZ'; // Kyrgyzstan

    /** Default currency per country (Russia/Belarus/card direct also accept USD pricing). */
    public const CURRENCIES = [
        self::IDN => 'IDR', self::BRA => 'BRL', self::PHL => 'PHP', self::MEX => 'MXN',
        self::RUS => 'RUB', self::BLR => 'RUB', self::VNM => 'VND', self::PAK => 'PKR',
        self::KOR => 'KRW', self::COL => 'COP', self::THA => 'THB', self::SAU => 'SAR',
        self::CHL => 'CLP', self::SGP => 'SGD', self::TZA => 'TZS', self::KEN => 'KES',
        self::UGA => 'UGX', self::EGY => 'EGP', self::KAZ => 'KZT', self::NGA => 'NGN',
        self::GHA => 'GHS', self::ZAF => 'ZAR', self::ARG => 'ARS', self::PER => 'PEN',
        self::UZB => 'UZS', self::MYS => 'MYR', self::ARE => 'AED', self::TUR => 'TRY',
        self::MNG => 'MNT', self::BGD => 'BDT', self::NPL => 'NPR', self::JPN => 'JPY',
        self::ARM => 'AMD', self::AZE => 'AZN', self::TJK => 'TJS', self::KGZ => 'KGS',
    ];

    public static function currency(string $country): ?string
    {
        return self::CURRENCIES[strtoupper($country)] ?? null;
    }
}
