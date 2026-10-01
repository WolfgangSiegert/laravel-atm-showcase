<?php

namespace App\Http\Middleware;

use App\Support\UsageMetrics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUsageMetrics
{
    public function __construct(private UsageMetrics $metrics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldCount($request, $response)) {
            $this->metrics->increment(UsageMetrics::PUBLIC_PAGE_VIEW);

            if ($request->routeIs('atm.index')) {
                $this->metrics->increment(UsageMetrics::PUBLIC_LANDING_VIEW);
            }
        }

        return $response;
    }

    private function shouldCount(Request $request, Response $response): bool
    {
        if (! $this->metrics->enabled() || ! $request->isMethod('GET') || ! $request->routeIs('atm.*')) {
            return false;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return false;
        }

        return ! $request->headers->has('X-Inertia-Prefetch')
            && strtolower((string) $request->header('Purpose')) !== 'prefetch'
            && ! str_contains(strtolower((string) $request->header('Sec-Purpose')), 'prefetch');
    }
}
