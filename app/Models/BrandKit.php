<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrandKit extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'logo_url',
        'primary_color',
        'secondary_color',
        'accent_color',
        'font_heading',
        'font_body',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // When a brand kit is marked as default, unset other defaults
        static::saving(function ($brandKit) {
            if ($brandKit->is_default && $brandKit->isDirty('is_default')) {
                static::where('user_id', $brandKit->user_id)
                    ->where('id', '!=', $brandKit->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the user that owns the brand kit
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get marketing designs using this brand kit
     */
    public function marketingDesigns(): HasMany
    {
        return $this->hasMany(MarketingDesign::class, 'brand_kit_id');
    }

    /**
     * Check if this is the default brand kit
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Mark this brand kit as default
     */
    public function markAsDefault(): void
    {
        $this->update(['is_default' => true]);
    }

    /**
     * Get all colors as array
     */
    public function getColors(): array
    {
        return [
            'primary' => $this->primary_color,
            'secondary' => $this->secondary_color,
            'accent' => $this->accent_color,
        ];
    }

    /**
     * Get all fonts as array
     */
    public function getFonts(): array
    {
        return [
            'heading' => $this->font_heading,
            'body' => $this->font_body,
        ];
    }

    /**
     * Check if brand kit has logo
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo_url);
    }

    /**
     * Scope for default brand kit
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for user's brand kits
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
