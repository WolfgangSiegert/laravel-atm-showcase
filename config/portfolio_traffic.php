<?php

return [
    'enabled' => env('PORTFOLIO_TRAFFIC_ENABLED', false),

    'origins' => [
        'https://tiny-bits.org',
        'https://www.tiny-bits.org',
        'https://wolfgangsiegert.github.io',
    ],

    'paths' => [
        '/',
        '/portfolio/',
        '/portfolio/en/',
        '/portfolio-vue/',
        '/portfolio-vue/en/',
    ],

    'retention_days' => (int) env('PORTFOLIO_TRAFFIC_RETENTION_DAYS', 90),
    'rate_limit_per_minute' => (int) env('PORTFOLIO_TRAFFIC_RATE_LIMIT', 60),
    'report_timezone' => 'Europe/Berlin',
];
