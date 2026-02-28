<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MarketingDesign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'template_id',
        'brand_kit_id',
        'invoice_id',
        'title',
        'prompt',
        'design_json',
        'rendered_url',
        'status',
        'render_attempts',
        'render_error',
        'width',
        'height',
        'file_size',
        'shared_at',
        'download_count',
    ];

    protected $casts = [
        'design_json' => 'array',
        'render_attempts' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'shared_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($design) {
            if (empty($design->uuid)) {
                $design->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the user that owns the design
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the template used for this design
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MarketingTemplate::class, 'template_id');
    }

    /**
     * Get the brand kit used for this design
     */
    public function brandKit(): BelongsTo
    {
        return $this->belongsTo(BrandKit::class, 'brand_kit_id');
    }

    /**
     * Get the invoice this design is based on
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the generation queue entry for this design
     */
    public function generationQueue(): HasOne
    {
        return $this->hasOne(GenerationQueue::class, 'design_id');
    }

    /**
     * Check if design is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if design is rendering
     */
    public function isRendering(): bool
    {
        return $this->status === 'rendering';
    }

    /**
     * Check if design has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if design is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Mark design as rendering
     */
    public function markAsRendering(): void
    {
        $this->update(['status' => 'rendering']);
    }

    /**
     * Mark design as completed
     */
    public function markAsCompleted(string $renderedUrl, int $width, int $height, int $fileSize): void
    {
        $this->update([
            'status' => 'completed',
            'rendered_url' => $renderedUrl,
            'width' => $width,
            'height' => $height,
            'file_size' => $fileSize,
        ]);
    }

    /**
     * Mark design as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'render_error' => $error,
        ]);
    }

    /**
     * Increment render attempts
     */
    public function incrementRenderAttempts(): void
    {
        $this->increment('render_attempts');
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Mark as shared
     */
    public function markAsShared(): void
    {
        if (!$this->shared_at) {
            $this->update(['shared_at' => now()]);
        }
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHuman(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope for completed designs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for rendering designs
     */
    public function scopeRendering($query)
    {
        return $query->where('status', 'rendering');
    }

    /**
     * Scope for failed designs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for user's designs
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for designs by template
     */
    public function scopeByTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    /**
     * Scope for designs by invoice
     */
    public function scopeByInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }
}
