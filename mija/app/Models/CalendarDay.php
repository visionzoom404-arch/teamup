<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarDay extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'is_blocked' => 'boolean'];
    }
}
