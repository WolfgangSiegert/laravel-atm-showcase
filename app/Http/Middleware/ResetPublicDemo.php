<?php

namespace App\Http\Middleware;

use App\Actions\ResetDemoData;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResetPublicDemo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('demo.enabled') && $request->is('atm', 'atm/*')) {
            app(ResetDemoData::class)->execute(onlyIfDue: true);
        }

        return $next($request);
    }
}
