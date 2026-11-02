<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['created_at' => 'datetime', 'day' => 'date', 'is_new' => 'boolean', 'is_bot' => 'boolean'];
}
