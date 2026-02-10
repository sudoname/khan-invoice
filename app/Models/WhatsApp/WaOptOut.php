<?php

namespace App\Models\WhatsApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaOptOut extends Model
{
    const UPDATED_AT = null; // Only track created_at

    protected $fillable = [
        'user_id',
        'phone_e164',
        'reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that owns the opt-out.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a phone number has opted out for a user.
     */
    public static function hasOptedOut(int $userId, string $phoneE164): bool
    {
        return static::where('user_id', $userId)
            ->where('phone_e164', $phoneE164)
            ->exists();
    }

    /**
     * Opt out a phone number.
     */
    public static function optOut(int $userId, string $phoneE164, ?string $reason = null): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'phone_e164' => $phoneE164,
            ],
            [
                'reason' => $reason ?? 'User requested opt-out',
            ]
        );
    }

    /**
     * Opt in a phone number (remove opt-out).
     */
    public static function optIn(int $userId, string $phoneE164): void
    {
        static::where('user_id', $userId)
            ->where('phone_e164', $phoneE164)
            ->delete();
    }
}
