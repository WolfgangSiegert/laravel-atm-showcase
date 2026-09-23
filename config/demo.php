<?php

return [
    // Opt-in exclusively for a database containing fictional showcase data.
    'enabled' => (bool) env('PUBLIC_DEMO_ENABLED', false),
    'reset_seconds' => 86400,
    'booking_requests_per_minute' => 30,
    'admin_guest_enabled' => (bool) env('PUBLIC_ADMIN_GUEST_ENABLED', false),
    'admin_guest_email' => 'showcase-guest@lern-bank.invalid',
    'admin_guest_name' => 'Öffentlicher Showcase-Gast',
    'cards' => [
        'DEMO-001' => '1234',
        'DEMO-002' => '0042',
    ],
];
