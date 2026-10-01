<?php

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Monolog\Handler\TestHandler;

beforeEach(function () {
    $this->withoutVite();
    Log::forgetChannel('traffic');
});

it('keeps the production traffic channel at info level', function () {
    expect(config('logging.channels.traffic_stderr.level'))->toBe('info')
        ->and(config('logging.channels.traffic_stderr.handler_with.stream'))->toBe('php://stderr');
});

it('logs minimal structured traffic data and returns a correlation id', function () {
    Event::fake([MessageLogged::class]);
    config([
        'observability.traffic.enabled' => true,
        'observability.traffic.logged_headers' => ['accept', 'authorization'],
    ]);

    $response = $this->withHeaders([
        'Accept' => 'text/html',
        'Authorization' => 'Bearer secret-token',
    ])->get('/atm?account=private-value');

    $response->assertOk();
    expect(Str::isUuid($response->headers->get('X-Request-ID')))->toBeTrue();

    Event::assertDispatched(MessageLogged::class, function (MessageLogged $event) use ($response): bool {
        return $event->message === 'HTTP request completed'
            && $event->context['request_id'] === $response->headers->get('X-Request-ID')
            && $event->context['http_method'] === 'GET'
            && $event->context['route_name'] === 'atm.index'
            && $event->context['route_pattern'] === 'atm'
            && $event->context['status_code'] === 200
            && $event->context['authenticated'] === false
            && $event->context['headers']['accept'] === 'text/html'
            && $event->context['headers']['authorization'] === '[REDACTED]'
            && ! str_contains(json_encode($event->context), 'private-value')
            && ! str_contains(json_encode($event->context), 'secret-token')
            && ! array_key_exists('user_id', $event->context)
            && ! array_key_exists('ip', $event->context);
    });
});

it('does not log excluded health traffic', function () {
    Event::fake([MessageLogged::class]);
    config([
        'observability.traffic.enabled' => true,
        'observability.traffic.except' => ['up'],
    ]);

    $this->get('/up')->assertOk()->assertHeaderMissing('X-Request-ID');

    Event::assertNotDispatched(MessageLogged::class, fn (MessageLogged $event): bool => $event->message === 'HTTP request completed');
});

it('can route exception reports to a configured channel with safe request context', function () {
    config([
        'app.debug' => false,
        'observability.traffic.enabled' => false,
        'observability.errors.channel' => 'error_test',
        'logging.channels.error_test' => [
            'driver' => 'monolog',
            'handler' => TestHandler::class,
            'level' => 'debug',
        ],
    ]);
    Log::forgetChannel('error_test');
    Route::get('/test-error-report', fn () => throw new RuntimeException('diagnostic failure'))
        ->name('test.error-report');

    $this->get('/test-error-report?pin=1234')->assertStatus(500);

    /** @var TestHandler $handler */
    $handler = Log::channel('error_test')->getLogger()->getHandlers()[0];
    $record = $handler->getRecords()[0];

    expect($record->message)->toBe('Unhandled exception')
        ->and($record->context['route_name'])->toBe('test.error-report')
        ->and($record->context['route_pattern'])->toBe('test-error-report')
        ->and(json_encode($record->context))->not->toContain('1234');
});
