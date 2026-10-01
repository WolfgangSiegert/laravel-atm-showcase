<?php

use App\Support\PortfolioTraffic;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $readyFile, $startFile] = $argv;

DB::purge();
DB::reconnect();
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
    $request = Request::create('/api/portfolio-traffic', 'POST', server: [
        'REMOTE_ADDR' => '203.0.113.20',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) Firefox/142.0',
    ]);
    app(PortfolioTraffic::class)->record($request, 'portfolio', '/');
    echo json_encode(['status' => 'success'], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage());
    exit(2);
}
