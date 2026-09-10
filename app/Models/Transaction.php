<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class Transaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['account_id', 'card_id', 'atm_id', 'type', 'purpose', 'amount_minor', 'currency', 'balance_after_minor', 'cash_breakdown', 'idempotency_key'];

    protected $hidden = ['idempotency_key'];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'balance_after_minor' => 'integer',
            'cash_breakdown' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction): void {
            $transaction->receipt_reference ??= 'ATM-'.Str::ulid();
        });
        static::updating(fn () => throw new LogicException('Buchungen dürfen nicht verändert werden.'));
        static::deleting(fn () => throw new LogicException('Buchungen dürfen nicht gelöscht werden.'));
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function atm(): BelongsTo
    {
        return $this->belongsTo(Atm::class);
    }
}
