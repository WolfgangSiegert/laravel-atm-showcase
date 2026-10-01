<?php

return [
    'enabled' => env('USAGE_METRICS_ENABLED', false),
    'retention_days' => (int) env('USAGE_METRICS_RETENTION_DAYS', 400),
];
