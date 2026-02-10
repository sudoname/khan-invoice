<?php

namespace App\Models\WhatsApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'type',
        'enabled',
        'name',
        'trigger',
        'schedule',
        'message_template',
        'constraints',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'trigger' => 'array',
        'schedule' => 'array',
        'constraints' => 'array',
    ];

    protected $attributes = [
        'channel' => 'whatsapp',
        'type' => 'unpaid_invoice_followup',
        'enabled' => true,
    ];

    /**
     * Get the user that owns the rule.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get automation logs for this rule.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class);
    }

    /**
     * Check if rule is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable the rule.
     */
    public function enable(): void
    {
        $this->update(['enabled' => true]);
    }

    /**
     * Disable the rule.
     */
    public function disable(): void
    {
        $this->update(['enabled' => false]);
    }

    /**
     * Get schedule attempts in minutes.
     */
    public function getScheduleAttempts(): array
    {
        return $this->schedule['attempts'] ?? config('whatsapp.followups.default_schedule');
    }

    /**
     * Check if business hours constraint is enabled.
     */
    public function hasBusinessHoursConstraint(): bool
    {
        return $this->constraints['business_hours_only'] ?? false;
    }

    /**
     * Get max messages per day constraint.
     */
    public function getMaxPerDay(): ?int
    {
        return $this->constraints['max_per_day'] ?? null;
    }

    /**
     * Check if should check opt-outs.
     */
    public function shouldCheckOptOut(): bool
    {
        return $this->constraints['check_opt_out'] ?? true;
    }

    /**
     * Replace template variables.
     */
    public function renderMessage(array $variables): string
    {
        $message = $this->message_template;

        foreach ($variables as $key => $value) {
            $message = str_replace("{{" . $key . "}}", $value, $message);
        }

        return $message;
    }
}
