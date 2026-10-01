<?php

namespace App\Providers;

use App\Support\PortfolioTraffic;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('demo-bookings', fn (Request $request) => config('demo.enabled')
            ? Limit::perMinute(config('demo.booking_requests_per_minute'))->by($request->ip())
            : Limit::none());

        RateLimiter::for('portfolio-traffic', function (Request $request): Limit {
            $limit = max(1, (int) config('portfolio_traffic.rate_limit_per_minute'));
            $key = app(PortfolioTraffic::class)->rateLimitKey($request);

            return Limit::perMinute($limit)->by($key);
        });
    }
}
