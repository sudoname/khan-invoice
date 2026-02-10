<?php

namespace App\Models\WhatsApp;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaContact extends Model
{
    protected $fillable = [
        'user_id',
        'phone_e164',
        'name',
        'metadata',
        'last_seen_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Get the user that owns the contact.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get conversations for this contact.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(WaConversation::class);
    }

    /**
     * Get invoices associated with this contact.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get leads associated with this contact.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get the active conversation for this contact.
     */
    public function activeConversation()
    {
        return $this->conversations()
            ->where('status', 'open')
            ->latest('last_message_at')
            ->first();
    }

    /**
     * Update last seen timestamp.
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * Get or create a contact by phone number.
     */
    public static function findOrCreateByPhone(int $userId, string $phoneE164, ?string $name = null): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'phone_e164' => $phoneE164,
            ],
            [
                'name' => $name,
                'last_seen_at' => now(),
            ]
        );
    }
}
