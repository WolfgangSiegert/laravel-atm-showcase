<?php

return [
    // Opt-in exclusively for a database containing fictional showcase data.
    'enabled' => (bool) env('PUBLIC_DEMO_ENABLED', false),
    'reset_seconds' => 86400,
    'booking_requests_per_minute' => 30,
    'cards' => [
        'DEMO-001' => '1234',
        'DEMO-002' => '0042',
    ],
];
