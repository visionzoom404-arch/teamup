<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['is_published' => 'boolean']; }

    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function guest(): BelongsTo { return $this->belongsTo(User::class, 'guest_id'); }
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
}
