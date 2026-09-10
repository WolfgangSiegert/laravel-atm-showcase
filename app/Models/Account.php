<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = ['customer_id', 'reference', 'currency', 'balance_minor', 'status'];

    protected function casts(): array
    {
        return ['balance_minor' => 'integer'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }
}
