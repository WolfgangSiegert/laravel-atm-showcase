<?php

use App\Actions\ResetDemoData;
use App\Actions\WithdrawMoney;
use App\Models\Card;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $cardId, $amount, $key, $readyFile, $startFile] = $argv;

DB::purge();
DB::reconnect();
$card = Card::findOrFail((int) $cardId);
$operation = $argv[6] ?? 'withdrawal';
file_put_contents($readyFile, 'ready');

$deadline = microtime(true) + 10;
while (! file_exists($startFile)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, 'Timed out waiting for the concurrency barrier.');
        exit(2);
    }
    usleep(10_000);
}

try {
    if (str_starts_with($operation, 'reset')) {
        $changed = app(ResetDemoData::class)->execute(onlyIfDue: $operation === 'reset_due');
        echo json_encode(['status' => $changed ? 'reset' : 'skipped'], JSON_THROW_ON_ERROR);
    } else {
        $transaction = app(WithdrawMoney::class)->execute($card, (int) $amount, null, $key);
        echo json_encode(['status' => 'success', 'transaction_id' => $transaction->id], JSON_THROW_ON_ERROR);
    }
} catch (ValidationException $exception) {
    echo json_encode(['status' => 'rejected'], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage());
    exit(2);
}
