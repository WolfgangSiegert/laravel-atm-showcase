<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('uses German by default and shares supported locales', function () {
    $this->get('/atm')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'de')
            ->where('supportedLocales', ['de', 'en'])
        );
});

it('persists an allowed locale in the session', function () {
    $this->from('/atm')->post('/locale', ['locale' => 'en'])
        ->assertRedirect('/atm')
        ->assertSessionHas('locale', 'en');

    $this->get('/atm')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

it('rejects unsupported locales', function () {
    $this->from('/atm')->post('/locale', ['locale' => 'fr'])
        ->assertRedirect('/atm')
        ->assertSessionHasErrors('locale')
        ->assertSessionMissing('locale');
});

it('localizes server-side validation messages', function () {
    $this->withSession(['locale' => 'en'])
        ->from('/atm/cards')
        ->post('/atm/session', [])
        ->assertRedirect('/atm/cards')
        ->assertSessionHasErrors([
            'card_id' => 'Please select a demo card.',
            'pin' => 'Please enter your PIN.',
        ]);
});
