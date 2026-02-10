<?php

namespace App\Models\WhatsApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'phone_number',
        'phone_number_id',
        'waba_id',
        'access_token',
        'verify_token',
        'status',
        'last_error',
        'settings',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'verify_token' => 'encrypted',
        'settings' => 'array',
    ];

    protected $attributes = [
        'provider' => 'meta',
        'status' => 'disconnected',
    ];

    /**
     * Get the user that owns the WhatsApp account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get conversations for this account.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(WaConversation::class, 'user_id', 'user_id');
    }

    /**
     * Check if account is connected.
     */
    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    /**
     * Mark account as connected.
     */
    public function markConnected(): void
    {
        $this->update([
            'status' => 'connected',
            'last_error' => null,
        ]);
    }

    /**
     * Mark account as errored.
     */
    public function markError(string $error): void
    {
        $this->update([
            'status' => 'error',
            'last_error' => $error,
        ]);
    }

    /**
     * Get the access token (decrypt if needed).
     */
    public function getAccessTokenAttribute($value)
    {
        return $value;
    }
}
