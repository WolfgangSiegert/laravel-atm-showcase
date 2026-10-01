<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioTrafficEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['occurred_at', 'site', 'path', 'visitor_hash'];

    protected function casts(): array
    {
        return ['occurred_at' => 'immutable_datetime'];
    }
}
