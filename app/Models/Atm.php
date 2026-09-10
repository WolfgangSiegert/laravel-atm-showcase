<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Atm extends Model
{
    protected $fillable = ['code', 'label', 'currency', 'status'];

    public function cashInventories(): HasMany
    {
        return $this->hasMany(CashInventory::class);
    }
}
