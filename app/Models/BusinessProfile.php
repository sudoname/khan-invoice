<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessProfile extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'cac_number',
        'tin',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'phone',
        'email',
        'logo_url',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_account_type',
        'default_currency',
        'default_vat_rate',
    ];

    protected $casts = [
        'default_vat_rate' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
