<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['processed_at' => 'datetime']; }

    public function host(): BelongsTo { return $this->belongsTo(User::class, 'host_id'); }
}
