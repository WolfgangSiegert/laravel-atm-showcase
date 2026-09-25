<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'portfolioUrl' => config('app.portfolio_url'),
            'locale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales'),
            'notice' => fn () => $request->session()->get('notice'),
        ];
    }
}
