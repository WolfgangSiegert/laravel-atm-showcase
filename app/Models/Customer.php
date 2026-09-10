<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['display_name'];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
