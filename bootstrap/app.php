<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireOperator;
use App\Http\Middleware\ResetPublicDemo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [ResetPublicDemo::class, HandleInertiaRequests::class, AddSecurityHeaders::class]);
        $middleware->alias(['operator' => RequireOperator::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['pin', 'password']);
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
