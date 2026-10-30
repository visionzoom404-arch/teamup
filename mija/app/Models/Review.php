<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['is_published' => 'boolean', 'photos' => 'array', 'recommend' => 'boolean', 'published_at' => 'datetime']; }

    public function scopePublished($q) { return $q->where('status', 'published'); }

    public function photoUrls(): array { return array_map(fn ($p) => asset('storage/'.$p), $this->photos ?? []); }

    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function guest(): BelongsTo { return $this->belongsTo(User::class, 'guest_id'); }
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
}
