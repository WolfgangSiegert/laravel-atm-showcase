<?php

use App\Models\Account;
use App\Models\Atm;
use App\Models\AuditEvent;
use App\Models\CashInventory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
    $this->operator = User::where('email', config('atm.demo_operator_email'))->firstOrFail();
    $this->atm = Atm::where('code', config('atm.code'))->firstOrFail();
    $key = 'operator-login:'.hash('sha256', config('atm.demo_operator_email').'|127.0.0.1');
    RateLimiter::clear($key);
});

it('protects the dashboard and rejects a normal user', function () {
    $this->get('/operator')->assertRedirect(route('operator.login'));

    $user = User::create([
        'name' => 'Kein Betreiber',
        'email' => 'customer@example.test',
        'password' => 'secret-password',
    ]);
    $this->actingAs($user)->get('/operator')->assertForbidden();
});

it('authenticates an operator, rotates the session and does not flash the password', function () {
    $this->withSession(['previous' => true]);
    $previousId = session()->getId();

    $this->from('/operator/login')->post('/operator/session', [
        'email' => strtoupper(config('atm.demo_operator_email')),
        'password' => config('atm.demo_operator_password'),
    ])->assertRedirect(route('operator.dashboard'))
        ->assertSessionMissing('_old_input.password');

    expect(session()->getId())->not->toBe($previousId);
    $this->assertAuthenticatedAs($this->operator);
    expect($this->operator->operator_role)->toBe(User::OPERATOR_ROLE_SUPERADMIN)
        ->and($this->operator->canManageAdministration())->toBeTrue();
    $this->assertDatabaseHas('audit_events', [
        'event_type' => 'operator.login',
        'outcome' => 'success',
        'actor_user_id' => $this->operator->id,
    ]);
});

it('offers an explicitly enabled one click guest login with read only dashboard access', function () {
    config(['demo.enabled' => true, 'demo.admin_guest_enabled' => true]);
    $this->artisan('atm:demo-provision')->assertExitCode(0);

    $this->get('/admin/login')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Operator/Login')
        ->where('guestAccessEnabled', true));

    $this->post('/admin/guest-session')->assertRedirect(route('operator.dashboard'));
    $guest = User::where('email', config('demo.admin_guest_email'))->firstOrFail();
    $this->assertAuthenticatedAs($guest);
    $this->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('operatorRole', User::OPERATOR_ROLE_VIEWER)
        ->where('canManage', false));
    $this->assertDatabaseHas('audit_events', [
        'event_type' => 'operator.guest_login',
        'actor_user_id' => $guest->id,
    ]);
});

it('keeps every management route forbidden for the read only guest', function () {
    config(['demo.enabled' => true, 'demo.admin_guest_enabled' => true]);
    $this->artisan('atm:demo-provision')->assertExitCode(0);
    $guest = User::where('email', config('demo.admin_guest_email'))->firstOrFail();
    $account = Account::firstOrFail();
    $card = $account->cards()->firstOrFail();
    $inventory = CashInventory::where('atm_id', $this->atm->id)->firstOrFail();

    $this->actingAs($guest)->patch('/admin/atm/status', ['status' => 'maintenance'])->assertForbidden();
    $this->post("/admin/inventory/{$inventory->id}/adjust", ['adjustment' => 1])->assertForbidden();
    $this->post('/admin/accounts', [])->assertForbidden();
    $this->patch("/admin/accounts/{$account->id}/status", ['status' => 'blocked'])->assertForbidden();
    $this->post("/admin/accounts/{$account->id}/cards", [])->assertForbidden();
    $this->patch("/admin/cards/{$card->id}/status", ['status' => 'blocked'])->assertForbidden();
    $this->post("/admin/cards/{$card->id}/reset-lock")->assertForbidden();

    expect($this->atm->fresh()->status)->toBe('active')
        ->and($inventory->fresh()->quantity)->not->toBe($inventory->quantity + 1)
        ->and($account->fresh()->status)->toBe('active')
        ->and($card->fresh()->status)->toBe('active');
});

it('keeps public guest login disabled by default', function () {
    $this->get('/admin/login')->assertInertia(fn (Assert $page) => $page->where('guestAccessEnabled', false));
    $this->post('/admin/guest-session')->assertNotFound();
});

it('creates private operators exclusively as superadmins', function () {
    $this->artisan('atm:operator-create', ['email' => 'owner@example.test'])
        ->expectsQuestion('Neues Betreiberpasswort (mindestens 16 Zeichen)', 'a-unique-password-123')
        ->expectsOutput('Superadmin angelegt.')
        ->assertExitCode(0);

    $created = User::where('email', 'owner@example.test')->firstOrFail();
    expect($created->operator_role)->toBe(User::OPERATOR_ROLE_SUPERADMIN)
        ->and($created->canManageAdministration())->toBeTrue();
});

it('rejects invalid credentials and rate limits repeated attempts', function () {
    config(['atm.operator_requests_per_minute' => 1]);

    $this->post('/operator/session', [
        'email' => config('atm.demo_operator_email'),
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');
    $this->post('/operator/session', [
        'email' => config('atm.demo_operator_email'),
        'password' => config('atm.demo_operator_password'),
    ])->assertSessionHasErrors(['email' => 'Zu viele Anmeldeversuche. Bitte warte eine Minute.']);

    expect(AuditEvent::where('event_type', 'operator.login')->where('outcome', 'rejected')->count())->toBe(2);
    $this->assertGuest();
});

it('shows the configured ATM and recent audit events', function () {
    AuditEvent::create(['event_type' => 'test.event', 'outcome' => 'success']);

    $this->actingAs($this->operator)->get('/operator')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Operator/Dashboard')
        ->where('operatorName', 'Lokaler Demo-Betrieb')
        ->where('atm.code', config('atm.code'))
        ->where('atm.label', 'LERN-Bank Mein Geldautomat')
        ->has('atm.inventory', 4)
        ->where('metrics.accounts', 2)
        ->where('metrics.activeCards', 2)
        ->has('activity', 7)
        ->has('accounts', 2)
        ->has('transactions')
        ->where('auditEvents.0.event_type', 'test.event'));
});

it('serves the new admin routes and keeps the operator dashboard compatible', function () {
    $this->get('/admin')->assertRedirect(route('operator.login'));
    $this->actingAs($this->operator)->get('/admin')->assertOk();
    $this->get('/operator')->assertOk();
});

it('changes ATM status and records the operator and before-after values', function () {
    $this->actingAs($this->operator)->patch('/operator/atm/status', ['status' => 'maintenance'])
        ->assertRedirect(route('operator.dashboard'));

    expect($this->atm->fresh()->status)->toBe('maintenance');
    $event = AuditEvent::where('event_type', 'atm.status_changed')->firstOrFail();
    expect($event->actor_user_id)->toBe($this->operator->id)
        ->and($event->atm_id)->toBe($this->atm->id)
        ->and($event->context)->toBe(['before' => 'active', 'after' => 'maintenance']);
});

it('adjusts inventory atomically and rejects a negative resulting stock', function () {
    $inventory = CashInventory::where('atm_id', $this->atm->id)->firstOrFail();
    $before = $inventory->quantity;

    $this->actingAs($this->operator)->post("/operator/inventory/{$inventory->id}/adjust", ['adjustment' => 3])
        ->assertRedirect(route('operator.dashboard'));
    expect($inventory->fresh()->quantity)->toBe($before + 3);
    $event = AuditEvent::where('event_type', 'atm.inventory_adjusted')->firstOrFail();
    expect($event->context['adjustment'])->toBe(3)
        ->and($event->context['before'])->toBe($before)
        ->and($event->context['after'])->toBe($before + 3);

    $this->post("/operator/inventory/{$inventory->id}/adjust", ['adjustment' => -100])
        ->assertSessionHasErrors('adjustment');
    expect($inventory->fresh()->quantity)->toBe($before + 3)
        ->and(AuditEvent::where('event_type', 'atm.inventory_adjusted')->count())->toBe(1);
});

it('cannot adjust inventory that belongs to another ATM', function () {
    $otherAtm = Atm::create(['code' => 'ATM-OTHER', 'label' => 'Anderer Automat', 'status' => 'active', 'currency' => 'EUR']);
    $inventory = CashInventory::create(['atm_id' => $otherAtm->id, 'denomination_minor' => 1000, 'quantity' => 5]);

    $this->actingAs($this->operator)->post("/operator/inventory/{$inventory->id}/adjust", ['adjustment' => 1])
        ->assertNotFound();
    expect($inventory->fresh()->quantity)->toBe(5);
});

it('logs the operator out and records the event', function () {
    $this->actingAs($this->operator)->delete('/operator/session')->assertRedirect(route('operator.login'));

    $this->assertGuest();
    $this->assertDatabaseHas('audit_events', [
        'event_type' => 'operator.logout',
        'outcome' => 'success',
        'actor_user_id' => $this->operator->id,
    ]);
});
