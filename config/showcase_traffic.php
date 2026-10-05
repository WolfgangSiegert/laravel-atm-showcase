<?php

return [
    'enabled' => env('SHOWCASE_TRAFFIC_ENABLED', false),

    'joinsplit' => [
        'origin' => env('SHOWCASE_TRAFFIC_JOINSPLIT_ORIGIN', 'https://joinsplit.tiny-bits.org'),
        'path' => env('SHOWCASE_TRAFFIC_JOINSPLIT_PATH', '/app'),
    ],

    // Shared deliberately with the existing portfolio event retention window.
    'retention_days' => (int) env('PORTFOLIO_TRAFFIC_RETENTION_DAYS', 90),
    'rate_limit_per_minute' => (int) env('SHOWCASE_TRAFFIC_RATE_LIMIT', 120),
    'report_timezone' => 'Europe/Berlin',
];
