<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Provider
    |--------------------------------------------------------------------------
    |
    | The default provider for WhatsApp messaging. Currently supports 'meta'
    | (Meta WhatsApp Cloud API). Can be extended for 'termii', 'twilio', etc.
    |
    */
    'provider' => env('WHATSAPP_PROVIDER', 'meta'),

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API Configuration
    |--------------------------------------------------------------------------
    */
    'meta' => [
        'base_url' => env('WHATSAPP_META_BASE_URL', 'https://graph.facebook.com/v19.0'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'), // Fallback token
        'default_phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'enabled' => env('WA_AI_ENABLED', true),
        'provider' => env('WA_AI_PROVIDER', 'openai'), // openai, anthropic, etc.
        'model' => env('WA_AI_MODEL', 'gpt-4-turbo-preview'),
        'max_tokens' => env('WA_AI_MAX_TOKENS', 1000),
        'temperature' => env('WA_AI_TEMPERATURE', 0.7),
        'system_prompt' => 'You are Kinvoice WhatsApp sales assistant. Help businesses create invoices, track payments, and manage customer interactions. Output ONLY valid JSON matching the action-contract schema. Never make up prices or mark invoices as paid.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Follow-up Automation
    |--------------------------------------------------------------------------
    */
    'followups' => [
        'enabled' => env('WA_FOLLOWUPS_ENABLED', true),
        'default_schedule' => [60, 1440, 4320], // 1hr, 24hr, 3days in minutes
        'max_attempts' => 3,
        'business_hours_only' => false,
        'timezone' => 'Africa/Lagos',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'outbound_per_minute' => 60,
        'webhook_per_minute' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'invoice_created' => "Hi {{customer_name}}! 👋\n\nYour invoice #{{invoice_number}} for {{currency}}{{amount}} has been generated.\n\n📄 View: {{invoice_link}}\n💳 Pay Now: {{payment_link}}\n\nDue: {{due_date}}",

        'payment_received' => "Payment received! ✅\n\nThank you {{customer_name}}. Your payment of {{currency}}{{amount}} for invoice #{{invoice_number}} has been confirmed.\n\n📄 Receipt: {{receipt_link}}",

        'followup_reminder' => "Hi {{customer_name}},\n\nFriendly reminder: Invoice #{{invoice_number}} for {{currency}}{{amount}} is {{status}}.\n\n💳 Pay Now: {{payment_link}}\n\nNeed help? Just reply to this message.",
    ],
];
