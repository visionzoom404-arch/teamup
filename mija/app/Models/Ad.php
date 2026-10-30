<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ad extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d']; }

    public function host(): BelongsTo { return $this->belongsTo(User::class, 'host_id'); }
    public function property(): BelongsTo { return $this->belongsTo(Property::class); }

    public function typeLabel(): string { return config('mija.ad_types.'.$this->type.'.0', $this->type); }

    public function scopeLive($q) { return $q->where('status', 'active')->where(fn ($w) => $w->whereNull('ends_on')->orWhere('ends_on', '>=', today()->toDateString())); }

}
