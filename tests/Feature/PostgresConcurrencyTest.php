<?php

use App\Models\Account;
use App\Models\Atm;
use App\Models\Card;
use App\Models\CashInventory;
use App\Models\Transaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $url = getenv('ATM_POSTGRES_TEST_URL');
    if (! is_string($url) || $url === '') {
        $this->markTestSkipped('Set ATM_POSTGRES_TEST_URL to run PostgreSQL concurrency tests.');
    }
    $databaseName = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
    if (! str_ends_with($databaseName, '_test')) {
        throw new RuntimeException('The PostgreSQL concurrency database name must end with _test.');
    }

    config([
        'database.default' => 'pgsql',
        'database.connections.pgsql.url' => $url,
        'cache.default' => 'array',
        'session.driver' => 'array',
    ]);
    DB::purge();
    DB::setDefaultConnection('pgsql');
    Artisan::call('migrate:fresh', ['--force' => true]);
    $this->seed(DatabaseSeeder::class);
});

/**
 * @param  list<array{card_id: int, amount: int, key: string, operation?: string}>  $jobs
 * @return list<array{status: string, transaction_id?: int}>
 */
function runConcurrentWithdrawals(array $jobs): array
{
    $directory = sys_get_temp_dir().'/atm-concurrency-'.Str::uuid();
    mkdir($directory, 0700);
    $startFile = $directory.'/start';
    $processes = [];
    $environment = [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'pgsql',
        'DB_URL' => getenv('ATM_POSTGRES_TEST_URL'),
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'array',
        'BCRYPT_ROUNDS' => '4',
        'PUBLIC_DEMO_ENABLED' => 'true',
    ];

    foreach ($jobs as $index => $job) {
        $readyFile = $directory.'/ready-'.$index;
        $process = new Process([
            PHP_BINARY,
            base_path('tests/Support/ConcurrentWithdrawalWorker.php'),
            (string) $job['card_id'],
            (string) $job['amount'],
            $job['key'],
            $readyFile,
            $startFile,
            $job['operation'] ?? 'withdrawal',
        ], base_path(), $environment);
        $process->setTimeout(20);
        $process->start();
        $processes[] = [$process, $readyFile];
    }

    try {
        $deadline = microtime(true) + 10;
        while (collect($processes)->contains(fn ($item) => ! file_exists($item[1]))) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('Workers did not reach the concurrency barrier.');
            }
            usleep(10_000);
        }
        touch($startFile);

        $results = [];
        foreach ($processes as [$process]) {
            $process->wait();
            if (! $process->isSuccessful()) {
                throw new RuntimeException($process->getErrorOutput() ?: $process->getOutput());
            }
            $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        }

        return $results;
    } finally {
        foreach ($processes as [$process, $readyFile]) {
            if ($process->isRunning()) {
                $process->stop();
            }
            if (file_exists($readyFile)) {
                unlink($readyFile);
            }
        }
        if (file_exists($startFile)) {
            unlink($startFile);
        }
        if (is_dir($directory)) {
            rmdir($directory);
        }
    }
}

it('serializes two withdrawals that exceed one account balance', function () {
    $card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
    $card->account->update(['balance_minor' => 15000]);

    $results = runConcurrentWithdrawals([
        ['card_id' => $card->id, 'amount' => 10000, 'key' => (string) Str::uuid()],
        ['card_id' => $card->id, 'amount' => 10000, 'key' => (string) Str::uuid()],
    ]);

    expect(collect($results)->pluck('status')->sort()->values()->all())->toBe(['rejected', 'success'])
        ->and($card->account->fresh()->balance_minor)->toBe(5000)
        ->and(Transaction::where('type', 'withdrawal')->count())->toBe(1);
});

it('serializes a demo reset against an already prepared withdrawal', function () {
    $card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
    $card->account->update(['balance_minor' => 20000]);
    $results = runConcurrentWithdrawals([
        ['card_id' => $card->id, 'amount' => 10000, 'key' => (string) Str::uuid()],
        ['card_id' => $card->id, 'amount' => 0, 'key' => (string) Str::uuid(), 'operation' => 'reset'],
    ]);
    expect($results[1]['status'])->toBe('reset')
        ->and($results[0]['status'])->toBeIn(['success', 'rejected'])
        ->and($card->account->fresh()->balance_minor)->toBe(0)
        ->and($card->fresh()->session_version)->toBe(1)
        ->and(Transaction::count())->toBe(0)
        ->and(CashInventory::where('denomination_minor', 10000)->value('quantity'))->toBe(10);
});

it('performs an overdue demo reset exactly once under concurrent traffic', function () {
    DB::table('demo_reset_state')->insert(['id' => 1, 'last_reset_at' => now()->subDays(2)]);
    $card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
    $job = ['card_id' => $card->id, 'amount' => 0, 'key' => (string) Str::uuid(), 'operation' => 'reset_due'];
    $results = runConcurrentWithdrawals([$job, $job]);
    expect(collect($results)->pluck('status')->sort()->values()->all())->toBe(['reset', 'skipped'])
        ->and($card->fresh()->session_version)->toBe(1);
});

it('serializes two accounts competing for the final matching note', function () {
    $cards = Card::orderBy('id')->get();
    $cards->each(fn (Card $card) => $card->account->update(['balance_minor' => 10000]));
    $atm = Atm::where('code', config('atm.code'))->firstOrFail();
    CashInventory::where('atm_id', $atm->id)->update(['quantity' => 0]);
    CashInventory::where('atm_id', $atm->id)->where('denomination_minor', 10000)->update(['quantity' => 1]);

    $results = runConcurrentWithdrawals($cards->map(fn (Card $card) => [
        'card_id' => $card->id,
        'amount' => 10000,
        'key' => (string) Str::uuid(),
    ])->all());

    expect(collect($results)->pluck('status')->sort()->values()->all())->toBe(['rejected', 'success'])
        ->and((int) Account::sum('balance_minor'))->toBe(10000)
        ->and(Transaction::where('type', 'withdrawal')->count())->toBe(1)
        ->and(CashInventory::where('atm_id', $atm->id)->where('denomination_minor', 10000)->value('quantity'))->toBe(0);
});

it('returns one booking for the same concurrent idempotency key', function () {
    $card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
    $card->account->update(['balance_minor' => 20000]);
    $atm = Atm::where('code', config('atm.code'))->firstOrFail();
    $before = CashInventory::where('atm_id', $atm->id)->where('denomination_minor', 10000)->value('quantity');
    $key = (string) Str::uuid();

    $results = runConcurrentWithdrawals([
        ['card_id' => $card->id, 'amount' => 10000, 'key' => $key],
        ['card_id' => $card->id, 'amount' => 10000, 'key' => $key],
    ]);

    expect(collect($results)->pluck('status')->all())->toBe(['success', 'success'])
        ->and(collect($results)->pluck('transaction_id')->unique()->count())->toBe(1)
        ->and($card->account->fresh()->balance_minor)->toBe(10000)
        ->and(Transaction::where('type', 'withdrawal')->count())->toBe(1)
        ->and(CashInventory::where('atm_id', $atm->id)->where('denomination_minor', 10000)->value('quantity'))->toBe($before - 1);
});
