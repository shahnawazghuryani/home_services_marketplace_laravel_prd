<?php

return [
    'allow_setup_route' => (bool) env('ALLOW_SETUP_ROUTE', false),

    'support' => [
        'email' => env('SUPPORT_EMAIL', 'help@gharkaam.pk'),
        'phone' => env('SUPPORT_PHONE', '+92 300 0000000'),
        'whatsapp' => env('SUPPORT_WHATSAPP', '923000000000'),
        'hours' => env('SUPPORT_HOURS', 'Mon-Sat, 9 AM - 7 PM'),
    ],

    'moderation' => [
        'blocked_terms' => array_values(array_filter(array_map(
            static fn (string $term): string => strtolower(trim($term)),
            explode(',', (string) env(
                'CONTENT_BLOCKED_TERMS',
                'porn,nude,escort,casino,betting,cocaine,heroin,weapon,hate'
            ))
        ))),
        'block_contact_in_public_fields' => (bool) env('CONTENT_BLOCK_CONTACT', true),
        'image' => [
            'min_width' => (int) env('MODERATION_IMAGE_MIN_WIDTH', 400),
            'min_height' => (int) env('MODERATION_IMAGE_MIN_HEIGHT', 300),
            'max_width' => (int) env('MODERATION_IMAGE_MAX_WIDTH', 6000),
            'max_height' => (int) env('MODERATION_IMAGE_MAX_HEIGHT', 6000),
            'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
        ],
    ],

    'monitoring' => [
        'dashboard_log_lines' => (int) env('DASHBOARD_LOG_LINES', 40),
    ],
];
