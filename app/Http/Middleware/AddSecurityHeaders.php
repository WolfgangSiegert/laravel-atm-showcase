<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security.headers_enabled')) {
            return $response;
        }

        foreach (config('security.headers') as $name => $value) {
            $response->headers->set($name, $value);
        }

        if (! config('app.debug')) {
            $response->headers->set('Content-Security-Policy', config('security.content_security_policy'));
        }

        if (! config('app.debug') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', config('security.strict_transport_security'));
        }

        return $response;
    }
}
