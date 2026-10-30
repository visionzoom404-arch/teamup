<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['due_on' => 'date:Y-m-d', 'paid_at' => 'datetime']; }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }

}
