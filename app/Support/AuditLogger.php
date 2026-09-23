<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Atm;
use App\Models\AuditEvent;
use App\Models\Card;
use App\Models\User;

class AuditLogger
{
    /** @param array<string, int|string|bool|null> $context */
    public function record(
        string $eventType,
        string $outcome,
        ?Card $card = null,
        ?Account $account = null,
        ?Atm $atm = null,
        ?User $actor = null,
        ?string $reasonCode = null,
        array $context = [],
    ): AuditEvent {
        return AuditEvent::create([
            'event_type' => $eventType,
            'outcome' => $outcome,
            'actor_user_id' => $actor?->id,
            'account_id' => $account?->id ?? $card?->account_id,
            'card_id' => $card?->id,
            'atm_id' => $atm?->id,
            'reason_code' => $reasonCode,
            'context' => $context === [] ? null : $context,
        ]);
    }
}
