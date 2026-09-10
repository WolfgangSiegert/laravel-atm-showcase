<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashInventory extends Model
{
    protected $fillable = ['atm_id', 'denomination_minor', 'quantity'];

    protected function casts(): array
    {
        return ['denomination_minor' => 'integer', 'quantity' => 'integer'];
    }

    public function atm(): BelongsTo
    {
        return $this->belongsTo(Atm::class);
    }
}
