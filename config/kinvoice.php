<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Khan Invoice AI Features
    |--------------------------------------------------------------------------
    |
    | This configuration controls AI-powered features in Khan Invoice.
    | All features are deterministic and work without external AI calls.
    |
    */

    'ai' => [
        /*
        |--------------------------------------------------------------------------
        | Master AI Toggle
        |--------------------------------------------------------------------------
        |
        | When disabled, all AI features are turned off system-wide.
        |
        */
        'enabled' => env('KINVOICE_AI_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Smart Suggestions
        |--------------------------------------------------------------------------
        |
        | History-based suggestions for customers, line items, and due dates.
        | Uses user's own data to provide intelligent autofill recommendations.
        |
        */
        'suggestions' => [
            'enabled' => env('KINVOICE_AI_SUGGESTIONS_ENABLED', true),

            // Maximum number of suggestions to return
            'max_results' => 10,

            // Minimum query length for customer search
            'min_query_length' => 2,

            // Cache duration for suggestions (in seconds)
            'cache_ttl' => 300, // 5 minutes

            // Consider customers/items from last N days
            'lookback_days' => 365,

            // Weight factor for recency (higher = more weight to recent)
            'recency_weight' => 0.7,

            // Weight factor for frequency (higher = more weight to frequent)
            'frequency_weight' => 0.3,
        ],

        /*
        |--------------------------------------------------------------------------
        | Payment Reminders
        |--------------------------------------------------------------------------
        |
        | Automated payment reminder scheduling and delivery.
        | IMPORTANT: Sending is disabled by default for safety.
        |
        */
        'reminders' => [
            'enabled' => env('KINVOICE_AI_REMINDERS_ENABLED', false),

            // Reminder schedule (days relative to due date)
            'schedule' => [
                'before_due' => [-3, -1], // 3 days before, 1 day before
                'on_due' => [0], // On due date
                'after_due' => [3, 7, 14], // 3, 7, 14 days after
            ],

            // Default reminder channel
            'default_channel' => 'email',

            // Supported channels
            'channels' => ['email', 'whatsapp', 'sms'],

            // Auto-send reminders (DANGEROUS - keep false by default)
            'auto_send' => env('KINVOICE_AI_REMINDERS_AUTO_SEND', false),

            // Business hours for sending (24-hour format)
            'business_hours' => [
                'start' => 9, // 9 AM
                'end' => 17, // 5 PM
                'timezone' => 'Africa/Lagos',
            ],

            // Skip weekends
            'skip_weekends' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Insights & Analytics
        |--------------------------------------------------------------------------
        |
        | Intelligent analytics and insights about payment patterns.
        |
        */
        'insights' => [
            'enabled' => env('KINVOICE_AI_INSIGHTS_ENABLED', true),

            // Cache duration for insights (in seconds)
            'cache_ttl' => 3600, // 1 hour

            // Minimum invoices needed for insights
            'min_invoices_for_insights' => 5,

            // Percentile thresholds
            'late_payment_threshold_days' => 7,
            'early_payment_threshold_days' => -3,

            // Top customer/items limits
            'top_customers_limit' => 10,
            'top_late_payers_limit' => 5,
        ],

        /*
        |--------------------------------------------------------------------------
        | Logging & Privacy
        |--------------------------------------------------------------------------
        |
        | Control what gets logged for AI features.
        | NEVER log sensitive data like invoice content or customer PII.
        |
        */
        'logging' => [
            'enabled' => true,

            // Log only metadata (user_id, endpoint, duration, counts)
            'log_metadata_only' => true,

            // Channel for AI-specific logs
            'channel' => env('LOG_CHANNEL', 'stack'),

            // Log level
            'level' => 'info',
        ],

        /*
        |--------------------------------------------------------------------------
        | Rate Limiting
        |--------------------------------------------------------------------------
        |
        | Rate limits for AI endpoints to prevent abuse.
        |
        */
        'rate_limits' => [
            'suggestions' => [
                'max_attempts' => 60, // per minute
                'decay_minutes' => 1,
            ],
            'reminders' => [
                'max_attempts' => 10, // per minute
                'decay_minutes' => 1,
            ],
            'insights' => [
                'max_attempts' => 30, // per minute
                'decay_minutes' => 1,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags Integration
    |--------------------------------------------------------------------------
    |
    | Enable database-driven feature flags for gradual rollouts.
    |
    */
    'use_database_flags' => env('KINVOICE_USE_DATABASE_FLAGS', true),
];
