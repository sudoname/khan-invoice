<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'aspect_ratio',
        'width',
        'height',
        'layout_schema',
        'default_styles',
        'is_active',
        'is_premium',
        'preview_url',
        'usage_count',
    ];

    protected $casts = [
        'layout_schema' => 'array',
        'default_styles' => 'array',
        'is_active' => 'boolean',
        'is_premium' => 'boolean',
        'usage_count' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Get marketing designs using this template
     */
    public function marketingDesigns(): HasMany
    {
        return $this->hasMany(MarketingDesign::class, 'template_id');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Check if template is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if template is premium
     */
    public function isPremium(): bool
    {
        return $this->is_premium;
    }

    /**
     * Get dimensions as array
     */
    public function getDimensions(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'aspect_ratio' => $this->aspect_ratio,
        ];
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for free templates
     */
    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    /**
     * Scope for premium templates
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope by aspect ratio
     */
    public function scopeByAspectRatio($query, string $aspectRatio)
    {
        return $query->where('aspect_ratio', $aspectRatio);
    }
}
