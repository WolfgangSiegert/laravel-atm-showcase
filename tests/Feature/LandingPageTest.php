<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('redirects the root to the canonical ATM page', function () {
    $this->get(route('home'))->assertRedirect('/atm');
});

it('renders the German application shell with the expected page', function () {
    $this->get(route('atm.index'))
        ->assertOk()
        ->assertSee('lang="de"', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Atm/Welcome')
            ->where('version', '0.6.0')
            ->where('appName', 'Cash Machine'));
});

it('serves the Inertia navigation response', function () {
    $initialPage = $this->get('/atm')->assertOk()->viewData('page');

    $this->get('/atm', [
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Inertia-Version' => $initialPage['version'] ?? '',
    ])
        ->assertOk()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'Atm/Welcome')
        ->assertJsonPath('props.version', '0.6.0');
});

it('provides a health endpoint', function () {
    $this->get('/up')->assertOk();
});

it('returns 404 for unimplemented pages', function (string $path) {
    $this->get($path)->assertNotFound();
})->with(['/login', '/atm/withdraw', '/does-not-exist']);

it('requests a full reload when the browser has outdated assets', function () {
    $this->get('/atm', ['X-Inertia' => 'true', 'X-Inertia-Version' => 'outdated-build'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', url('/atm'));
});
