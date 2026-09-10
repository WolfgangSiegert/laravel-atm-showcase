<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('runs the infrastructure migrations against isolated SQLite', function () {
    expect(DB::connection()->getDriverName())->toBe('sqlite')
        ->and(DB::connection()->getDatabaseName())->toBe(':memory:')
        ->and(Schema::hasTable('sessions'))->toBeTrue()
        ->and(Schema::hasTable('cache'))->toBeTrue()
        ->and(Schema::hasTable('jobs'))->toBeTrue()
        ->and(Schema::hasTable('accounts'))->toBeTrue()
        ->and(Schema::hasTable('cards'))->toBeTrue()
        ->and(Schema::hasTable('customers'))->toBeTrue()
        ->and(Schema::hasTable('transactions'))->toBeTrue()
        ->and(Schema::hasColumn('transactions', 'receipt_reference'))->toBeTrue()
        ->and(Schema::hasColumn('transactions', 'purpose'))->toBeTrue()
        ->and(Schema::hasTable('atms'))->toBeTrue()
        ->and(Schema::hasTable('cash_inventories'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'is_operator'))->toBeTrue()
        ->and(Schema::hasTable('audit_events'))->toBeTrue();
});
