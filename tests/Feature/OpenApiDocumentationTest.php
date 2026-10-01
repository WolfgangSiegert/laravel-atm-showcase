<?php

use Illuminate\Support\Facades\Route;

it('keeps the OpenAPI document aligned with canonical application routes', function () {
    $document = json_decode(
        file_get_contents(base_path('docs/api/openapi.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($document['openapi'])->toBe('3.1.0')
        ->and($document['info']['version'])->toBe(config('app.version'));

    $documentedOperations = collect($document['paths'])
        ->flatMap(fn (array $pathItem, string $path) => collect($pathItem)
            ->filter(fn (mixed $operation, string $method) => in_array($method, ['get', 'post', 'patch', 'delete'], true))
            ->map(fn (array $operation, string $method) => [
                'operationId' => $operation['operationId'],
                'route' => strtoupper($method).' '.$path,
            ])
            ->values())
        ->values();

    expect($documentedOperations->pluck('operationId')->duplicates())->toBeEmpty();

    $canonicalRouteNames = [
        'home',
        'locale.update',
        'atm.index',
        'atm.cards',
        'atm.login',
        'atm.session',
        'atm.logout',
        'atm.deposit',
        'atm.withdrawal',
        'atm.receipt',
        'operator.login',
        'operator.session.store',
        'operator.guest-session.store',
        'operator.dashboard',
        'operator.session.destroy',
        'operator.atm.status',
        'operator.inventory.adjust',
        'admin.accounts.store',
        'admin.accounts.status',
        'admin.cards.store',
        'admin.cards.status',
        'admin.cards.reset-lock',
    ];

    $canonicalRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array($route->getName(), $canonicalRouteNames, true))
        ->flatMap(fn ($route) => collect($route->methods())
            ->reject(fn (string $method) => $method === 'HEAD')
            ->map(fn (string $method) => $method.' /'.ltrim($route->uri(), '/')))
        ->push('GET /up')
        ->sort()
        ->values();

    expect($canonicalRoutes)->toHaveCount(count($canonicalRouteNames) + 1)
        ->and($documentedOperations->pluck('route')->sort()->values()->all())
        ->toBe($canonicalRoutes->all());
});
