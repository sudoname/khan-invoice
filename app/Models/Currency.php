<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_base',
        'is_active',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Convert amount from base currency to this currency
     */
    public function convertFrom(float $amount): float
    {
        return $amount * $this->exchange_rate;
    }

    /**
     * Convert amount from this currency to base currency
     */
    public function convertTo(float $amount): float
    {
        return $amount / $this->exchange_rate;
    }
}
