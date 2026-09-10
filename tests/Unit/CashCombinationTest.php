<?php

use App\Support\CashCombination;

it('finds a combination when taking the largest note first would fail', function () {
    $result = (new CashCombination)->find(6000, [4000 => 1, 3000 => 2]);

    expect($result)->toBe([3000 => 2]);
});

it('uses the fewest notes among available combinations', function () {
    $result = (new CashCombination)->find(10000, [5000 => 2, 2000 => 5, 1000 => 10]);

    expect($result)->toBe([5000 => 2]);
});

it('respects limited inventory and rejects impossible amounts', function () {
    $service = new CashCombination;

    expect($service->find(7000, [5000 => 1, 2000 => 1]))->toBe([5000 => 1, 2000 => 1])
        ->and($service->find(8000, [5000 => 1, 2000 => 1]))->toBeNull()
        ->and($service->find(0, [1000 => 1]))->toBeNull();
});
