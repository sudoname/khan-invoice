<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeatureFlag extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'enabled',
        'environments',
        'rules',
        'enabled_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'environments' => 'array',
        'rules' => 'array',
        'enabled_at' => 'datetime',
    ];

    /**
     * Check if a feature is enabled globally
     */
    public static function isEnabled(string $key): bool
    {
        return Cache::remember(
            "feature_flag:{$key}",
            now()->addMinutes(5),
            fn() => static::where('key', $key)->value('enabled') ?? false
        );
    }

    /**
     * Check if feature is enabled for current environment
     */
    public static function isEnabledForEnvironment(string $key): bool
    {
        $flag = Cache::remember(
            "feature_flag_full:{$key}",
            now()->addMinutes(5),
            fn() => static::where('key', $key)->first()
        );

        if (!$flag || !$flag->enabled) {
            return false;
        }

        if ($flag->environments && is_array($flag->environments)) {
            return in_array(app()->environment(), $flag->environments);
        }

        return true;
    }

    /**
     * Enable a feature flag
     */
    public function enable(): void
    {
        $this->update([
            'enabled' => true,
            'enabled_at' => now(),
        ]);

        Cache::forget("feature_flag:{$this->key}");
        Cache::forget("feature_flag_full:{$this->key}");
    }

    /**
     * Disable a feature flag
     */
    public function disable(): void
    {
        $this->update([
            'enabled' => false,
            'enabled_at' => null,
        ]);

        Cache::forget("feature_flag:{$this->key}");
        Cache::forget("feature_flag_full:{$this->key}");
    }
}
