<?php

return [
    'code' => 'BER-DEMO-01',
    'withdrawal_denominations_minor' => [10000, 5000, 2000, 1000],
    'initial_cash_quantities' => [
        10000 => 10,
        5000 => 20,
        2000 => 30,
        1000 => 40,
    ],
    'min_withdrawal_minor' => 1000,
    'max_withdrawal_minor' => 100000,
    'max_deposit_minor' => 1000000,
    'max_balance_minor' => 1000000000,
    'pin_length' => 4,
    'max_pin_attempts' => 5,
    'lock_seconds' => 900,
    'idle_seconds' => 300,
    'requests_per_minute' => 10,
];
