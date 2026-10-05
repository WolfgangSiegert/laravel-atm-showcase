<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShowcaseTrafficDaily extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'showcase_traffic_daily';

    protected $fillable = ['visit_date', 'site', 'path', 'view_count'];

    protected function casts(): array
    {
        return [
            'visit_date' => 'immutable_date',
            'view_count' => 'integer',
        ];
    }
}
