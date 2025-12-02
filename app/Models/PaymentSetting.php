<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PaymentSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("payment_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        Cache::forget("payment_setting_{$key}");
    }

    /**
     * Calculate Paystack fee based on settings
     */
    public static function calculatePaystackFee(float $amount): float
    {
        $percentage = (float) self::get('paystack_fee_percentage', 1.5);
        $minimum = (float) self::get('paystack_fee_minimum', 100);
        $cap = (float) self::get('paystack_fee_cap', 3000);

        $calculatedFee = $amount * ($percentage / 100);
        $fee = max($calculatedFee, $minimum);

        return min($fee, $cap);
    }

    /**
     * Calculate service charge based on settings
     */
    public static function calculateServiceCharge(float $amount): float
    {
        $percentage = (float) self::get('service_charge_percentage', 2);
        $minimum = (float) self::get('service_charge_minimum', 150);
        $cap = (float) self::get('service_charge_cap', 3000);

        $calculatedCharge = $amount * ($percentage / 100);
        $charge = max($calculatedCharge, $minimum);

        return min($charge, $cap);
    }

    /**
     * Calculate total fees (Paystack + Service Charge)
     */
    public static function calculateTotalFees(float $amount): array
    {
        $paystackFee = self::calculatePaystackFee($amount);
        $serviceCharge = self::calculateServiceCharge($amount);

        return [
            'paystack_fee' => $paystackFee,
            'service_charge' => $serviceCharge,
            'total_fees' => $paystackFee + $serviceCharge,
            'total_with_fees' => $amount + $paystackFee + $serviceCharge,
        ];
    }
}
