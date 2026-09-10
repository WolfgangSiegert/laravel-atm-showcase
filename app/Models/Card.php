<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    protected $fillable = ['account_id', 'demo_reference', 'pin_hash', 'status', 'expires_at'];

    protected $hidden = ['pin_hash', 'failed_attempts', 'locked_until'];

    protected function casts(): array
    {
        return [
            'pin_hash' => 'hashed',
            'failed_attempts' => 'integer',
            'locked_until' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isUsable(): bool
    {
        return $this->status === 'active'
            && (! $this->expires_at || $this->expires_at->isFuture())
            && (! $this->locked_until || ! $this->locked_until->isFuture())
            && $this->account?->status === 'active';
    }
}
