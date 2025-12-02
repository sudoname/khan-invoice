<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected $attributes = [
        'discount' => 0,
        'tax_rate' => 0,
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Ensure discount is never null
            if ($item->discount === null) {
                $item->discount = 0;
            }

            // Ensure tax_rate is never null
            if ($item->tax_rate === null) {
                $item->tax_rate = 0;
            }

            // Calculate line total WITHOUT per-item tax
            // VAT is applied at invoice level, not per-item
            $item->line_total = ($item->quantity * $item->unit_price) - $item->discount;
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
