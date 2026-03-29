<?php

return [
    'fallback_enabled' => env('AI_FALLBACK_ENABLED', env('APP_ENV') !== 'production'),

    'features' => [
        'booking_helper' => [
            'enabled' => env('AI_BOOKING_HELPER_ENABLED', true),
            'temperature' => (float) env('AI_BOOKING_HELPER_TEMPERATURE', 0.3),
        ],
        'provider_service_builder' => [
            'enabled' => env('AI_PROVIDER_SERVICE_BUILDER_ENABLED', true),
            'temperature' => (float) env('AI_PROVIDER_SERVICE_BUILDER_TEMPERATURE', 0.4),
        ],
        'provider_recommendations' => [
            'enabled' => env('AI_PROVIDER_RECOMMENDATIONS_ENABLED', true),
            'temperature' => (float) env('AI_PROVIDER_RECOMMENDATIONS_TEMPERATURE', 0.2),
            'max_candidates' => (int) env('AI_PROVIDER_RECOMMENDATIONS_MAX_CANDIDATES', 6),
        ],
    ],
];
