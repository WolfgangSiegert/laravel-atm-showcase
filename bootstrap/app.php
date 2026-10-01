<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogRequestTraffic;
use App\Http\Middleware\RequireAdminWriteAccess;
use App\Http\Middleware\RequireOperator;
use App\Http\Middleware\ResetPublicDemo;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackUsageMetrics;
use App\Support\RequestLogContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(LogRequestTraffic::class);
        $middleware->web(append: [SetLocale::class, ResetPublicDemo::class, TrackUsageMetrics::class, HandleInertiaRequests::class, AddSecurityHeaders::class]);
        $middleware->alias([
            'operator' => RequireOperator::class,
            'admin.write' => RequireAdminWriteAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['pin', 'password']);
        $exceptions->context(fn () => app()->runningInConsole() ? [] : RequestLogContext::from(request()));
        $exceptions->report(function (Throwable $exception) {
            $configuredChannel = config('observability.errors.channel');
            $channel = is_string($configuredChannel) && $configuredChannel !== ''
                ? $configuredChannel
                : config('logging.default');

            Log::channel($channel)->error('Unhandled exception', [
                ...RequestLogContext::from(request()),
                'exception' => $exception,
            ]);

            return false;
        });
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();
            if (config('app.debug') || $request->expectsJson() || ! in_array($status, [403, 404, 419, 429, 500, 503], true)) {
                return $response;
            }

            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
