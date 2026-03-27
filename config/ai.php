<?php

return [
    'features' => [
        'smart_search' => [
            'enabled' => env('AI_SMART_SEARCH_ENABLED', true),
            'temperature' => (float) env('AI_SMART_SEARCH_TEMPERATURE', 0.2),
        ],
        'booking_helper' => [
            'enabled' => env('AI_BOOKING_HELPER_ENABLED', true),
            'temperature' => (float) env('AI_BOOKING_HELPER_TEMPERATURE', 0.3),
        ],
        'provider_recommendations' => [
            'enabled' => env('AI_PROVIDER_RECOMMENDATIONS_ENABLED', true),
            'temperature' => (float) env('AI_PROVIDER_RECOMMENDATIONS_TEMPERATURE', 0.2),
            'max_candidates' => (int) env('AI_PROVIDER_RECOMMENDATIONS_MAX_CANDIDATES', 6),
        ],
    ],
];
