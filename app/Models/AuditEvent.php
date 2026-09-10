<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['event_type', 'outcome', 'actor_user_id', 'account_id', 'card_id', 'atm_id', 'reason_code', 'context'];

    protected function casts(): array
    {
        return ['context' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit-Ereignisse dürfen nicht verändert werden.'));
        static::deleting(fn () => throw new LogicException('Audit-Ereignisse dürfen nicht gelöscht werden.'));
    }
}
