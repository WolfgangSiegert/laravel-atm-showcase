<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('adds baseline security headers to web responses', function () {
    $this->get('/atm')->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
});

it('adds CSP and HSTS to non-debug HTTPS responses', function () {
    config(['app.debug' => false]);

    $this->get('https://localhost/atm')->assertOk()
        ->assertHeader('Content-Security-Policy', config('security.content_security_policy'))
        ->assertHeader('Strict-Transport-Security', config('security.strict_transport_security'));
});

it('renders a useful production error page without replacing the status', function () {
    config(['app.debug' => false]);

    $this->get('/nicht-vorhanden')->assertNotFound()->assertInertia(fn (Assert $page) => $page
        ->component('Error')
        ->where('status', 404));
});
