<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

fwrite(STDOUT, 'security-check php='.PHP_VERSION.' sapi='.PHP_SAPI.PHP_EOL);
$targets = [
    'control' => 'https://render.com/',
    'emudhra' => 'https://dv-g3b-emudhra.we-have-a-problem.com/',
    'emudhra-aaa' => 'https://dv-g3b-aaa-emudhra.we-have-a-problem.com/',
];
$passed = true;
foreach ($targets as $name => $url) {
    try {
        Http::withOptions(['verify' => true, 'allow_redirects' => false])->connectTimeout(10)->timeout(20)->get($url);
        $result = $name === 'control' ? 'tls-ok' : 'UNSAFE-accepted';
        $passed = $passed && $name === 'control';
    } catch (Throwable $exception) {
        $cause = $exception;
        $errno = null;
        do {
            if ($cause instanceof RequestException) {
                $errno = $cause->getHandlerContext()['errno'] ?? $errno;
            } elseif ($cause instanceof ConnectException) {
                $errno = $cause->getHandlerContext()['errno'] ?? $errno;
            }
        } while ($cause = $cause->getPrevious());
        $rejected = $errno === 60 && $name !== 'control';
        $result = $rejected ? 'certificate-rejected' : 'INCONCLUSIVE';
        $passed = $passed && $rejected;
    }
    fwrite(STDOUT, 'security-check '.$name.'='.$result.PHP_EOL);
}
exit($passed ? 0 : 1);
