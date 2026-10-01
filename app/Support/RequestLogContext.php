<?php

namespace App\Support;

use Illuminate\Http\Request;

class RequestLogContext
{
    /** @return array<string, bool|int|string|array<string, string>|null> */
    public static function from(Request $request): array
    {
        $route = $request->route();

        return array_filter([
            'request_id' => $request->attributes->get('request_id'),
            'http_method' => $request->method(),
            'route_name' => is_object($route) ? $route->getName() : null,
            'route_pattern' => is_object($route) ? $route->uri() : null,
            'authenticated' => $request->user() !== null,
            'headers' => self::headers($request),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /** @return array<string, string> */
    private static function headers(Request $request): array
    {
        $logged = array_map('strtolower', config('observability.traffic.logged_headers', []));
        $sensitive = array_map('strtolower', config('observability.traffic.sensitive_headers', []));
        $headers = [];

        foreach ($logged as $name) {
            if (! $request->headers->has($name)) {
                continue;
            }

            $headers[$name] = in_array($name, $sensitive, true)
                ? '[REDACTED]'
                : (string) $request->headers->get($name);
        }

        return $headers;
    }
}
