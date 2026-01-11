<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active A/B Tests
    |--------------------------------------------------------------------------
    |
    | List of currently active A/B tests. Remove a test name from this array
    | to disable it (all users will see the control variant).
    |
    | Leave empty array [] to enable all tests by default.
    |
    */
    'active_tests' => [
        // 'dashboard_cta', // Uncomment to enable the dashboard CTA A/B test
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Configurations
    |--------------------------------------------------------------------------
    |
    | Configure individual A/B tests here including variants and weights.
    |
    */
    'tests' => [
        'dashboard_cta' => [
            'name' => 'Dashboard CTA Test',
            'description' => 'Test whether dual CTAs (Quick + Advanced) perform better than single Quick CTA',
            'variants' => [
                'control' => [
                    'name' => 'Dual CTA (Control)',
                    'description' => 'Shows both Quick Invoice and Advanced Invoice buttons',
                    'weight' => 50,
                ],
                'variant_quick_only' => [
                    'name' => 'Quick Only',
                    'description' => 'Shows only Quick Invoice button',
                    'weight' => 50,
                ],
            ],
            'primary_metric' => 'First invoice created within 24 hours',
            'secondary_metrics' => [
                'CTA click rate',
                'Time to first invoice',
                'Invoice completion rate',
            ],
        ],
    ],
];
