<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestReview extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['tags' => 'array']; }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function host(): BelongsTo { return $this->belongsTo(User::class, 'host_id'); }
    public function guest(): BelongsTo { return $this->belongsTo(User::class, 'guest_id'); }

}
