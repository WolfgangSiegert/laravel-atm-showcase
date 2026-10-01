<?php

return [
    'traffic' => [
        'enabled' => env('TRAFFIC_LOG_ENABLED', true),
        'channel' => env('TRAFFIC_LOG_CHANNEL', 'traffic'),
        'except' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TRAFFIC_LOG_EXCEPT', 'up')),
        ))),
        'logged_headers' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TRAFFIC_LOG_HEADERS', 'accept,content-type')),
        ))),
        'sensitive_headers' => [
            'authorization',
            'cookie',
            'proxy-authorization',
            'x-api-key',
            'x-csrf-token',
            'x-xsrf-token',
        ],
    ],

    /* An empty value falls back to Laravel's configured default log channel. */
    'errors' => [
        'channel' => env('ERROR_REPORT_CHANNEL'),
    ],
];
