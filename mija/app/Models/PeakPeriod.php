<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeakPeriod extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d'];
    }
}
