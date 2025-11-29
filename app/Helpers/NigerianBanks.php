<?php

namespace App\Helpers;

class NigerianBanks
{
    public static function all(): array
    {
        return [
            'Access Bank',
            'Citibank Nigeria',
            'Ecobank Nigeria',
            'Fidelity Bank',
            'First Bank of Nigeria',
            'First City Monument Bank (FCMB)',
            'Globus Bank',
            'Guaranty Trust Bank (GTBank)',
            'Heritage Bank',
            'Keystone Bank',
            'Polaris Bank',
            'Providus Bank',
            'Stanbic IBTC Bank',
            'Standard Chartered Bank',
            'Sterling Bank',
            'SunTrust Bank',
            'Titan Trust Bank',
            'Union Bank of Nigeria',
            'United Bank for Africa (UBA)',
            'Unity Bank',
            'Wema Bank',
            'Zenith Bank',
        ];
    }

    public static function options(): array
    {
        return array_combine(self::all(), self::all());
    }
}
