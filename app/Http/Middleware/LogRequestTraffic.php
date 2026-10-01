<?php

namespace App\Http\Middleware;

use App\Support\RequestLogContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class LogRequestTraffic
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('observability.traffic.enabled') || $this->excluded($request)) {
            return $next($request);
        }

        $startedAt = hrtime(true);
        $request->attributes->set('request_id', (string) Str::uuid());

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->write($request, $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500, $startedAt, true);

            throw $exception;
        }

        $response->headers->set('X-Request-ID', $request->attributes->get('request_id'));
        $this->write($request, $response->getStatusCode(), $startedAt, false);

        return $response;
    }

    private function excluded(Request $request): bool
    {
        foreach (config('observability.traffic.except', []) as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    private function write(Request $request, int $status, int $startedAt, bool $failed): void
    {
        Log::channel(config('observability.traffic.channel'))->info('HTTP request completed', [
            ...RequestLogContext::from($request),
            'status_code' => $status,
            'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
            'failed' => $failed,
        ]);
    }
}
