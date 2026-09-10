<?php

use App\Models\AuditEvent;
use App\Models\Card;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
    $this->card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
});

it('records ATM login outcomes without storing submitted secrets', function () {
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '9999'])->assertSessionHasErrors('pin');
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '8888'])->assertSessionHasErrors('pin');
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '1234'])->assertRedirect(route('atm.session'));

    expect(AuditEvent::where('event_type', 'atm_session.login')->where('outcome', 'rejected')->count())->toBe(2)
        ->and(AuditEvent::where('event_type', 'atm_session.login')->where('outcome', 'success')->count())->toBe(1);
    $serialized = AuditEvent::all()->toJson();
    expect($serialized)->not->toContain('9999')->not->toContain('8888')->not->toContain('1234')->not->toContain('127.0.0.1');
});

it('records successful and rejected money movements with minimal context', function () {
    $this->card->account->update(['balance_minor' => 2000]);
    $this->withSession(['atm_card_id' => $this->card->id, 'atm_last_activity' => now()->timestamp]);

    $this->post('/atm/deposits', [
        'amount' => '2,50',
        'purpose' => 'Privater Testzweck',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertSessionHasNoErrors();
    $this->post('/atm/withdrawals', [
        'withdrawal_amount' => '30',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertSessionHasErrors('withdrawal_amount');

    $deposit = AuditEvent::where('event_type', 'transaction.deposit')->firstOrFail();
    $withdrawal = AuditEvent::where('event_type', 'transaction.withdrawal')->firstOrFail();
    expect($deposit->outcome)->toBe('success')
        ->and($deposit->context)->toBe(['amount_minor' => 250])
        ->and($withdrawal->outcome)->toBe('rejected')
        ->and($withdrawal->reason_code)->toBe('business_rule')
        ->and(AuditEvent::all()->toJson())->not->toContain('Privater Testzweck');
});

it('records timeout and explicit logout outcomes', function () {
    $this->withSession([
        'atm_card_id' => $this->card->id,
        'atm_last_activity' => now()->timestamp - config('atm.idle_seconds'),
    ]);
    $this->get('/atm/session')->assertRedirect(route('atm.cards'));

    $this->withSession(['atm_card_id' => $this->card->id, 'atm_last_activity' => now()->timestamp]);
    $this->delete('/atm/session')->assertRedirect(route('atm.cards'));

    $this->assertDatabaseHas('audit_events', ['event_type' => 'atm_session.invalidated', 'reason_code' => 'idle_timeout']);
    $this->assertDatabaseHas('audit_events', ['event_type' => 'atm_session.logout', 'outcome' => 'success']);
});

it('does not permit changing or deleting an audit event through the model', function () {
    $event = AuditEvent::create(['event_type' => 'test.event', 'outcome' => 'success']);

    expect(fn () => $event->update(['outcome' => 'rejected']))->toThrow(LogicException::class)
        ->and(fn () => $event->delete())->toThrow(LogicException::class)
        ->and($event->fresh()->outcome)->toBe('success');
});
