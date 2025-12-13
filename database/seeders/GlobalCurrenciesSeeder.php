<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GlobalCurrenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            // Major Global Currencies
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1.0000, 'is_base' => true, 'is_active' => true],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.9200, 'is_base' => false, 'is_active' => true],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'exchange_rate' => 0.7900, 'is_base' => false, 'is_active' => true],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF', 'exchange_rate' => 0.8800, 'is_base' => false, 'is_active' => true],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'exchange_rate' => 149.5000, 'is_base' => false, 'is_active' => true],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'exchange_rate' => 7.2400, 'is_base' => false, 'is_active' => true],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$', 'exchange_rate' => 1.3600, 'is_base' => false, 'is_active' => true],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'exchange_rate' => 1.5300, 'is_base' => false, 'is_active' => true],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'exchange_rate' => 1.6500, 'is_base' => false, 'is_active' => true],

            // African Currencies
            ['code' => 'NGN', 'name' => 'Nigerian Naira', 'symbol' => '₦', 'exchange_rate' => 1560.0000, 'is_base' => false, 'is_active' => true],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R', 'exchange_rate' => 18.5000, 'is_base' => false, 'is_active' => true],
            ['code' => 'GHS', 'name' => 'Ghanaian Cedi', 'symbol' => 'GH₵', 'exchange_rate' => 12.1000, 'is_base' => false, 'is_active' => true],
            ['code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KSh', 'exchange_rate' => 129.0000, 'is_base' => false, 'is_active' => true],
            ['code' => 'UGX', 'name' => 'Ugandan Shilling', 'symbol' => 'USh', 'exchange_rate' => 3750.0000, 'is_base' => false, 'is_active' => true],
            ['code' => 'TZS', 'name' => 'Tanzanian Shilling', 'symbol' => 'TSh', 'exchange_rate' => 2510.0000, 'is_base' => false, 'is_active' => true],
            ['code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'E£', 'exchange_rate' => 30.9000, 'is_base' => false, 'is_active' => true],
            ['code' => 'MAD', 'name' => 'Moroccan Dirham', 'symbol' => 'MAD', 'exchange_rate' => 10.1000, 'is_base' => false, 'is_active' => true],
            ['code' => 'XOF', 'name' => 'West African CFA Franc', 'symbol' => 'CFA', 'exchange_rate' => 603.5000, 'is_base' => false, 'is_active' => true],
            ['code' => 'XAF', 'name' => 'Central African CFA Franc', 'symbol' => 'FCFA', 'exchange_rate' => 603.5000, 'is_base' => false, 'is_active' => true],

            // Middle East Currencies
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'AED', 'exchange_rate' => 3.6700, 'is_base' => false, 'is_active' => true],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'SAR', 'exchange_rate' => 3.7500, 'is_base' => false, 'is_active' => true],
            ['code' => 'QAR', 'name' => 'Qatari Riyal', 'symbol' => 'QAR', 'exchange_rate' => 3.6400, 'is_base' => false, 'is_active' => true],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'symbol' => 'KWD', 'exchange_rate' => 0.3100, 'is_base' => false, 'is_active' => true],
            ['code' => 'ILS', 'name' => 'Israeli Shekel', 'symbol' => '₪', 'exchange_rate' => 3.6800, 'is_base' => false, 'is_active' => true],

            // Asian Currencies
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'exchange_rate' => 83.2000, 'is_base' => false, 'is_active' => true],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'exchange_rate' => 1.3400, 'is_base' => false, 'is_active' => true],
            ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'exchange_rate' => 7.8100, 'is_base' => false, 'is_active' => true],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'exchange_rate' => 4.6900, 'is_base' => false, 'is_active' => true],
            ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿', 'exchange_rate' => 35.5000, 'is_base' => false, 'is_active' => true],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'exchange_rate' => 15650.0000, 'is_base' => false, 'is_active' => true],
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱', 'exchange_rate' => 56.2000, 'is_base' => false, 'is_active' => true],
            ['code' => 'VND', 'name' => 'Vietnamese Dong', 'symbol' => '₫', 'exchange_rate' => 24350.0000, 'is_base' => false, 'is_active' => true],
            ['code' => 'PKR', 'name' => 'Pakistani Rupee', 'symbol' => 'Rs', 'exchange_rate' => 278.5000, 'is_base' => false, 'is_active' => true],
            ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳', 'exchange_rate' => 110.0000, 'is_base' => false, 'is_active' => true],
            ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => '₩', 'exchange_rate' => 1310.0000, 'is_base' => false, 'is_active' => true],

            // South American Currencies
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$', 'exchange_rate' => 4.9500, 'is_base' => false, 'is_active' => true],
            ['code' => 'ARS', 'name' => 'Argentine Peso', 'symbol' => '$', 'exchange_rate' => 350.0000, 'is_base' => false, 'is_active' => true],
            ['code' => 'CLP', 'name' => 'Chilean Peso', 'symbol' => '$', 'exchange_rate' => 890.0000, 'is_base' => false, 'is_active' => true],
            ['code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => '$', 'exchange_rate' => 3950.0000, 'is_base' => false, 'is_active' => true],
            ['code' => 'PEN', 'name' => 'Peruvian Sol', 'symbol' => 'S/', 'exchange_rate' => 3.7200, 'is_base' => false, 'is_active' => true],

            // European Currencies
            ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'kr', 'exchange_rate' => 10.5000, 'is_base' => false, 'is_active' => true],
            ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'kr', 'exchange_rate' => 10.8000, 'is_base' => false, 'is_active' => true],
            ['code' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'kr', 'exchange_rate' => 6.8600, 'is_base' => false, 'is_active' => true],
            ['code' => 'PLN', 'name' => 'Polish Zloty', 'symbol' => 'zł', 'exchange_rate' => 4.0300, 'is_base' => false, 'is_active' => true],
            ['code' => 'CZK', 'name' => 'Czech Koruna', 'symbol' => 'Kč', 'exchange_rate' => 22.8000, 'is_base' => false, 'is_active' => true],
            ['code' => 'HUF', 'name' => 'Hungarian Forint', 'symbol' => 'Ft', 'exchange_rate' => 355.0000, 'is_base' => false, 'is_active' => true],
            ['code' => 'RON', 'name' => 'Romanian Leu', 'symbol' => 'lei', 'exchange_rate' => 4.5700, 'is_base' => false, 'is_active' => true],
            ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => '₺', 'exchange_rate' => 28.5000, 'is_base' => false, 'is_active' => true],
            ['code' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => '₽', 'exchange_rate' => 91.5000, 'is_base' => false, 'is_active' => true],

            // Other Important Currencies
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$', 'exchange_rate' => 17.2000, 'is_base' => false, 'is_active' => true],
        ];

        // Insert or update currencies
        foreach ($currencies as $currency) {
            \App\Models\Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        $this->command->info('Successfully seeded ' . count($currencies) . ' global currencies!');
    }
}
